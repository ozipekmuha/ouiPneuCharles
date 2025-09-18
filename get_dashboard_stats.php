<?php
header('Content-Type: application/json');
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

$period = $_GET['period'] ?? 'today';
$response = [];

$date_end = 'NOW()';
$date_start = '';
$group_by_format = '';

switch ($period) {
    case '7days':
        $date_start = 'DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $group_by_format = '%Y-%m-%d';
        break;
    case '30days':
        $date_start = 'DATE_SUB(NOW(), INTERVAL 30 DAY)';
        $group_by_format = '%Y-%m-%d';
        break;
    case 'year':
        $date_start = 'DATE_SUB(NOW(), INTERVAL 1 YEAR)';
        $group_by_format = '%Y-%m';
        break;
    case 'today':
    default:
        $date_start = 'CURDATE()';
        $group_by_format = '%Y-%m-%d %H:00';
        break;
}

try {
    
    $stats = [];
    $stmt_ca = $pdo->prepare("SELECT SUM(montant_total_ttc) FROM Commandes WHERE date_commande BETWEEN $date_start AND $date_end");
    $stmt_ca->execute();
    $stats['chiffre_affaires'] = (float) $stmt_ca->fetchColumn();

    $stmt_orders = $pdo->prepare("SELECT COUNT(*) FROM Commandes WHERE date_commande BETWEEN $date_start AND $date_end");
    $stmt_orders->execute();
    $stats['commandes'] = (int) $stmt_orders->fetchColumn();
    
    $stmt_clients = $pdo->prepare("SELECT COUNT(*) FROM Utilisateurs WHERE date_inscription BETWEEN $date_start AND $date_end");
    $stmt_clients->execute();
    $stats['nouveaux_clients'] = (int) $stmt_clients->fetchColumn();

    $stmt_stock = $pdo->query("SELECT COUNT(*) FROM Pneus WHERE stock_disponible < 5 AND est_actif = 1");
    $stats['stock_faible'] = (int) $stmt_stock->fetchColumn();
    $response['stats'] = $stats;

    // Données du graphique
    $chart_data = [];
    $stmt_chart = $pdo->prepare("SELECT DATE_FORMAT(date_commande, '$group_by_format') as date_group, SUM(montant_total_ttc) as total_sales FROM Commandes WHERE date_commande BETWEEN $date_start AND $date_end GROUP BY date_group ORDER BY date_group ASC");
    $stmt_chart->execute();
    $sales_data = $stmt_chart->fetchAll(PDO::FETCH_KEY_PAIR);

    $response['chart'] = [
        'labels' => array_keys($sales_data),
        'data' => array_values($sales_data)
    ];

    // Activité récente
    $stmt_activity = $pdo->query("
        (SELECT 'commande' as type, id_commande as id, CONCAT('Nouvelle commande #', SUBSTRING(id_commande, 1, 8), '...') as description, date_commande as date FROM Commandes ORDER BY date_commande DESC LIMIT 5)
        UNION
        (SELECT 'client' as type, id_utilisateur as id, CONCAT('Nouveau client: ', email) as description, date_inscription as date FROM Utilisateurs WHERE est_admin = 0 ORDER BY date_inscription DESC LIMIT 5)
        ORDER BY date DESC LIMIT 5
    ");
    $response['recent_activity'] = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);
    
    // Commandes à traiter
    $stmt_orders_to_process = $pdo->query("SELECT c.id_commande, CONCAT(u.prenom, ' ', u.nom) AS nom_client, c.date_commande, c.montant_total_ttc FROM Commandes c JOIN Utilisateurs u ON c.id_utilisateur = u.id_utilisateur WHERE c.statut_commande IN ('en_attente', 'en_cours_de_preparation') ORDER BY c.date_commande ASC");
    $response['orders_to_process'] = $stmt_orders_to_process->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
