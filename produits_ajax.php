<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$where_clauses = ["est_actif = TRUE"];
$bindings = [];

if ($search !== '') {
    $where_clauses[] = "(nom LIKE :search OR taille LIKE :search OR saison LIKE :search OR specifications LIKE :search)";
    $bindings[':search'] = '%' . $search . '%';
}

$sql = "SELECT id, nom, taille, saison, image, decibels, adherenceRouillee, specifications, prix, lienProduit, description, stock_disponible FROM Pneus";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY nom ASC LIMIT 48";

$pneus = [];
try {
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $pneus = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<p>Erreur lors de la recherche.</p>';
    exit;
}

if (empty($pneus)) {
    echo '<p>Aucun pneu trouvé pour la recherche.</p>';
    exit;
}

foreach ($pneus as $pneu) {
    echo '<div class="product-card">';
    echo '<div class="product-card-content">';
    echo '<h3 class="product-name">' . htmlspecialchars($pneu['nom']) . '</h3>';
    echo '<p class="product-specs">' . htmlspecialchars($pneu['taille']) . ' | ' . htmlspecialchars($pneu['saison']) . '</p>';
    echo '<div class="product-price-stock">';
    echo '<p class="product-price">' . htmlspecialchars($pneu['prix']) . '</p>';
    echo '</div>';
    echo '<a href="produit.php?id=' . $pneu['id'] . '" class="cta-button product-cta secondary">Voir Détails</a>';
    echo '</div>';
    echo '</div>';
}
