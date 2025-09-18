<?php
header('Content-Type: text/html; charset=utf-8');


require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
session_start();


$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$isAjax) {
    http_response_code(403);
    exit('Accès non autorisé');
}


$recherche = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);

try {
    if (empty($recherche)) {
        echo '<p class="info-message" style="text-align:center;padding:2em;">Veuillez saisir un terme de recherche.</p>';
        exit;
    }

    
    $sql = "SELECT id, nom, taille, saison, image, specifications, prix, stock_disponible 
            FROM Pneus 
            WHERE est_actif = TRUE 
            AND (nom LIKE :recherche 
                OR taille LIKE :recherche 
                OR saison LIKE :recherche) 
            ORDER BY nom ASC 
            LIMIT 48";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%" . $recherche . "%";
    $stmt->bindParam(':recherche', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo '<p class="no-results" style="text-align:center;padding:2em;">Aucun résultat trouvé pour "' . 
             htmlspecialchars($recherche) . '"</p>';
        exit;
    }

    
    foreach ($results as $pneu) {
        $parsed_specs_for_data = parseProductSpecifications($pneu['specifications']);
        $display_details = getProductDisplayDetails($pneu);
        $marque_nom_pour_data = extractBrandFromName($pneu['nom']);
        ?>
        <div class="product-card" data-aos="fade-up">
            <div class="product-image-placeholder">
                <img loading="lazy" 
                     src="<?php echo htmlspecialchars(!empty($pneu['image']) ? 
                          $pneu['image'] : 
                          'https://placehold.co/400x300/121212/ffdd03?text=Image+Indisponible'); ?>"
                     alt="<?php echo htmlspecialchars($pneu['nom']); ?>" 
                     width="400" 
                     height="300">
                <?php echo $display_details['badge_html']; ?>
            </div>
            <div class="product-card-content">
                <h3 class="product-name"><?php echo htmlspecialchars($pneu['nom']); ?></h3>
                <p class="product-specs">
                    <?php echo htmlspecialchars($pneu['taille']); ?> | 
                    <?php echo htmlspecialchars($pneu['saison']); ?>
                    <?php 
                    if (!empty($pneu['specifications'])) {
                        echo ' | ' . htmlspecialchars($pneu['specifications']);
                    }
                    ?>
                </p>
                <div class="product-price-stock">
                    <p class="product-price"><?php echo htmlspecialchars($pneu['prix']); ?></p>
                    <p class="product-stock <?php echo $display_details['stock_class']; ?>">
                        <?php echo htmlspecialchars($display_details['stock_text']); ?>
                    </p>
                </div>
                <a href="produit.php?id=<?php echo $pneu['id']; ?>" 
                   class="cta-button product-cta secondary">
                    Voir Détails
                </a>
            </div>
        </div>
        <?php
    }

} catch (PDOException $e) {
    error_log("Erreur de recherche : " . $e->getMessage());
    echo '<p class="error-message" style="color:red;text-align:center;padding:2em;">
            Une erreur est survenue lors de la recherche. Veuillez réessayer.
          </p>';
}
