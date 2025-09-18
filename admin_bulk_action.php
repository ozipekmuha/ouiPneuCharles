<?php
header('Content-Type: application/json');
require_once 'includes/db_connect.php';


$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!isset($data['action'], $data['ids']) || !is_array($data['ids'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

$action = $data['action'];
$ids = array_map('intval', $data['ids']);

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Aucun ID fourni']);
    exit;
}


switch ($action) {
    case 'delete':
        $in = implode(',', $ids);
        $stmt = $pdo->prepare("DELETE FROM pneus WHERE id IN ($in)");
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'activate':
        $in = implode(',', $ids);
        $stmt = $pdo->prepare("UPDATE pneus SET statut = 1 WHERE id IN ($in)");
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'deactivate':
        $in = implode(',', $ids);
        $stmt = $pdo->prepare("UPDATE pneus SET statut = 0 WHERE id IN ($in)");
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}