<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';


if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    exit('Requête non autorisée');
}


$query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
$query = trim($query);


function isDimensionSearch($query) {
    return preg_match('/^\d+([\/\s]\d+([\/\s]?R?\d+)?)?$/', $query);
}


function createDimensionSQL($query) {
    $cleanQuery = preg_replace('/[^\d\/\s]/', '', $query);
    $numbers = preg_split('/[\/\s]+/', $cleanQuery);
    $numbers = array_filter($numbers, function($n) { 
        return is_numeric(trim($n)) && trim($n) !== ''; 
    });
    $numbers = array_values($numbers);
    
    $conditions = [];
    $bindings = [];
    
    if (count($numbers) >= 1) {
        $width = $numbers[0];
        
        if (count($numbers) == 1) {
            
            $conditions[] = "taille REGEXP :width_only";
            $bindings[':width_only'] = '^' . $width . '/[0-9]+';
        }
        
        if (count($numbers) == 2) {
            $ratio = $numbers[1];
            
            $conditions[] = "taille REGEXP :width_ratio";
            $bindings[':width_ratio'] = '^' . $width . '/' . $ratio . '(R[0-9]+|/[0-9]+|[^0-9]|$)';
        }
        
        if (count($numbers) >= 3) {
            $ratio = $numbers[1];
            $diameter = $numbers[2];
            
            $conditions[] = "taille REGEXP :full_dimension";
            $bindings[':full_dimension'] = '^' . $width . '/' . $ratio . '(R|/)?' . $diameter . '([^0-9]|$)';
        }
    }
    
    return ['conditions' => $conditions, 'bindings' => $bindings];
}

$bindings = [];


if (empty($query)) {
    $sql = "SELECT id, nom, taille, saison, image, decibels, adherenceRouillee, specifications, prix, lienProduit, description, stock_disponible 
            FROM Pneus 
            WHERE est_actif = TRUE 
            ORDER BY nom ASC 
            LIMIT 50";
    $bindings = [];
    
} else {
    
    $isDimension = isDimensionSearch($query);
    
    if ($isDimension) {
        
        $dimensionSQL = createDimensionSQL($query);
        
        $whereConditions = ["est_actif = TRUE"];
        
        if (!empty($dimensionSQL['conditions'])) {
            $whereConditions[] = '(' . implode(' OR ', $dimensionSQL['conditions']) . ')';
            $bindings = array_merge($bindings, $dimensionSQL['bindings']);
        }
        
        $sql = "SELECT id, nom, taille, saison, image, decibels, adherenceRouillee, specifications, prix, lienProduit, description, stock_disponible 
                FROM Pneus 
                WHERE " . implode(' AND ', $whereConditions) . "
                ORDER BY 
                    CASE 
                        WHEN taille LIKE :priority_exact THEN 1
                        WHEN taille LIKE :priority_start THEN 2
                        ELSE 3
                    END,
                    nom ASC 
                LIMIT 50";
        
        
        $bindings[':priority_exact'] = $query . '%';
        $bindings[':priority_start'] = $query . '/%';
        
    } else {
        
        $search_term = '%' . $query . '%';
        
        $sql = "SELECT id, nom, taille, saison, image, decibels, adherenceRouillee, specifications, prix, lienProduit, description, stock_disponible 
                FROM Pneus 
                WHERE est_actif = TRUE 
                AND (
                    nom LIKE :search1 
                    OR taille LIKE :search2 
                    OR saison LIKE :search3 
                    OR specifications LIKE :search4
                    OR description LIKE :search5
                )
                ORDER BY 
                    CASE 
                        WHEN nom LIKE :exact_search THEN 1
                        WHEN nom LIKE :start_search THEN 2
                        WHEN taille LIKE :start_search THEN 3
                        ELSE 4
                    END,
                    nom ASC 
                LIMIT 50";
        
        $exact_search = $query;
        $start_search = $query . '%';
        
        $bindings = [
            ':search1' => $search_term,
            ':search2' => $search_term,
            ':search3' => $search_term,
            ':search4' => $search_term,
            ':search5' => $search_term,
            ':exact_search' => $exact_search,
            ':start_search' => $start_search
        ];
    }
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bindings);
    $pneus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    if (empty($pneus)) {
        echo '<div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="no-results-text">
                    <h3>Aucun résultat trouvé</h3>
                    <p>Aucun pneu ne correspond à votre recherche "' . htmlspecialchars($query) . '".</p>
                    <p>Essayez avec des termes différents ou vérifiez l\'orthographe.</p>
                </div>
            </div>';
    } else {
        foreach ($pneus as $index => $pneu) {
            $parsed_specs_for_data = parseProductSpecifications($pneu['specifications']);
            $display_details = getProductDisplayDetails($pneu);
            $marque_nom_pour_data = extractBrandFromName($pneu['nom']);
            $taille_parsed = parseTireSize($pneu['taille']);
            $prix_pour_data = convertPriceToFloat($pneu['prix']);
            ?>
            <div class="product-card"
                data-aos="fade-up"
                data-aos-duration="500"
                data-aos-delay="<?php echo ($index % 4 + 1) * 50; ?>"
                data-aos-once="true"
                data-runflat="<?php echo $parsed_specs_for_data['is_runflat'] ? 'true' : 'false'; ?>"
                data-reinforced="<?php echo $parsed_specs_for_data['is_reinforced'] ? 'true' : 'false'; ?>"
                data-brand="<?php echo sanitize_html_output($marque_nom_pour_data); ?>"
                data-name="<?php echo sanitize_html_output($pneu['nom']); ?>"
                data-price="<?php echo $prix_pour_data; ?>"
                data-type="<?php echo sanitize_html_output($pneu['saison']); ?>"
                data-width="<?php echo sanitize_html_output($taille_parsed['data_width']); ?>"
                data-ratio="<?php echo sanitize_html_output($taille_parsed['data_ratio']); ?>"
                data-diameter="<?php echo sanitize_html_output($taille_parsed['data_diameter']); ?>">
                
                <div class="product-image-placeholder">
                    <img loading="lazy" 
                         src="<?php echo sanitize_html_output(!empty($pneu['image']) ? $pneu['image'] : 'https://placehold.co/400x300/121212/ffdd03?text=Image+Indisponible'); ?>"
                         alt="<?php echo sanitize_html_output($pneu['nom']); ?>" 
                         width="400" 
                         height="300">
                    <?php echo $display_details['badge_html']; ?>
                </div>
                
                <div class="product-card-content">
                    <h3 class="product-name"><?php echo sanitize_html_output($pneu['nom']); ?></h3>
                    <p class="product-brand">Marque: <?php echo sanitize_html_output($marque_nom_pour_data); ?></p>
                    <p class="product-specs">
                        <?php echo sanitize_html_output($pneu['taille']); ?> | <?php echo sanitize_html_output($pneu['saison']); ?>
                        <?php if (!empty($pneu['specifications'])):
                            $specs_text_to_display = trim($pneu['specifications']);
                            if (!empty($specs_text_to_display)) {
                                echo ' | ' . sanitize_html_output($specs_text_to_display);
                            }
                        endif; ?>
                    </p>
                    <div class="product-price-stock">
                        <p class="product-price"><?php echo sanitize_html_output($pneu['prix']); ?></p>
                        <p class="product-stock <?php echo $display_details['stock_class']; ?>"><?php echo sanitize_html_output($display_details['stock_text']); ?></p>
                    </div>
                    <a href="produit.php?id=<?php echo $pneu['id']; ?>" 
                       class="cta-button product-cta secondary" 
                       aria-label="Voir détails pour <?php echo sanitize_html_output($pneu['nom']); ?>">
                       Voir Détails
                    </a>
                </div>
            </div>
            <?php
        }
    }

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la recherche de pneus : " . $e->getMessage());
    http_response_code(500);
    echo '<div class="error-message">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="error-text">
                Une erreur est survenue lors de la recherche.
            </div>
            <div class="error-support">
                Veuillez réessayer dans quelques instants.
            </div>
        </div>';
}
?>