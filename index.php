<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; 


$best_sellers = [];
try {
    $stmt_bs = $pdo->query("SELECT id, nom, taille, saison, image, specifications, prix, stock_disponible FROM Pneus WHERE est_actif = TRUE ORDER BY id ASC LIMIT 4");
    $best_sellers = $stmt_bs->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des meilleures ventes : " . $e->getMessage());
}


$new_arrivals = [];
try {
  
    $ids_best_sellers = array_map(function($p) { return $p['id']; }, $best_sellers);
    $na_query = "SELECT id, nom, taille, saison, image, specifications, prix, stock_disponible FROM Pneus WHERE est_actif = TRUE";
    if (!empty($ids_best_sellers)) {
        $na_query .= " AND id NOT IN (" . implode(',', array_fill(0, count($ids_best_sellers), '?')) . ")";
    }
    $na_query .= " ORDER BY id DESC LIMIT 3"; 

    $stmt_na = $pdo->prepare($na_query);
    if (!empty($ids_best_sellers)) {
        $stmt_na->execute($ids_best_sellers);
    } else {
        $stmt_na->execute();
    }
    $new_arrivals = $stmt_na->fetchAll();

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des nouveautés : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magasin de Pneus - Pneus et Services Premium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header id="main-header">
        <div class="container">
            <div class="logo">
                <a href="index.php"><img src="./assets/images/logobg.png" alt="Logo Ouipneu.fr"></a>
            </div>
            <nav class="main-nav">
                <ul id="main-nav-links">
                    <li><a href="index.php" class="active" aria-label="Accueil">Accueil</a></li>
                    <li><a href="produits.php" aria-label="Nos Pneus">Nos Pneus</a></li>
                    <li><a href="contact.php" aria-label="Contact">Contact</a></li>
                    <li><a href="index.php#about-us" aria-label="À propos de nous">À Propos</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <form class="search-bar" role="search">
                    <input type="search" placeholder="Rechercher des pneus..." aria-label="Rechercher des pneus">
                    <button type="submit" aria-label="Lancer la recherche"><i class="fas fa-search"></i></button>
                </form>
                <div class="account-icon">
                    <?php if (isset($_SESSION['id_utilisateur'])): ?>
                        <a href="dashboard.php" aria-label="Mon Compte"><i class="fas fa-user-circle"></i></a>
                        <?php  ?>
                    <?php else: ?>
                        <a href="login.php" aria-label="Mon Compte"><i class="fas fa-user-circle"></i></a>
                    <?php endif; ?>
                </div>
                <div class="cart-icon">
                    <a href="panier.php" aria-label="Panier"><i class="fas fa-shopping-cart"></i><span class="cart-item-count"><?php echo array_sum($_SESSION['panier'] ?? []); ?></span></a>
                </div>
                <button type="button" id="mobile-search-toggle-button" class="mobile-search-toggle" aria-label="Ouvrir la recherche" aria-expanded="false">
                    <i class="fas fa-search"></i>
                </button>
                <button id="hamburger-button" class="hamburger-button" aria-label="Ouvrir le menu" aria-expanded="false">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </header>

    <div id="global-promo-banner" style="background-color: var(--accent-primary); color: var(--text-on-accent); text-align: center; padding: 0.75rem 1rem; font-size: 0.9rem; font-weight: 500;">
        <p style="margin:0; color: black;">🔥 Livraison Gratuite sur toutes les commandes ! 🔥</p>
    </div>
<!-- 
    <section id="home" class="hero-section section-padding">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Votre Destination Ultime pour des Pneus de Qualité</h1>
                <p class="hero-subtitle">Découvrez une large gamme de pneus adaptés à tous les véhicules et budgets. Performance, sécurité et les meilleures marques vous attendent.</p>
                <div class="hero-cta-buttons">
                    <a href="produits.php" class="cta-button hero-cta-primary">Trouver Vos Pneus</a>
                    <a href="#our-advantages" class="cta-button hero-cta-secondary">Nos Services</a>
                </div>
            </div>
        </div>
    </section> -->
    <style>
        .hero-section {
    position: relative;
    overflow: hidden;
    background-color: #000; 
}

.hero-bg-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
}
      
        .cta-button.hero-cta-secondary {
            border: 2px solid #FFD700;
            color: #FFD700;
            background-color: transparent;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .cta-button.hero-cta-secondary:hover {
            background-color: #FFD700;
            color: #000;
        }
    </style>
    <section id="home" class="hero-section section-padding">
    <video class="hero-bg-video" autoplay muted loop playsinline>
        <source src="./assets/video.mp4" type="video/mp4">
        Votre navigateur ne supporte pas la vidéo HTML5.
    </video>
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Votre Destination Ultime pour des Pneus de Qualité</h1>
            <p class="hero-subtitle">Découvrez une large gamme de pneus adaptés à tous les véhicules et budgets. Performance, sécurité et les meilleures marques vous attendent.</p>
            <div class="hero-cta-buttons">
                <a href="produits.php" class="cta-button hero-cta-primary">Trouver Vos Pneus</a>
                <a href="#our-advantages" class="cta-button hero-cta-secondary">Nos Services</a>
            </div>
        </div>
    </div>
</section>

    <section id="quick-filter-section" class="section-padding">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Trouvez vos Pneus Rapidement</h2>
            <form id="quick-filter-form" class="quick-filter-form" data-aos="fade-up" data-aos-delay="100">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="qf-width">Largeur</label>
                        <select id="qf-width" name="width">
                            <option value="">Largeur</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="qf-ratio">Hauteur</label>
                        <select id="qf-ratio" name="ratio">
                            <option value="">Hauteur</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="qf-diameter">Diamètre</label>
                        <select id="qf-diameter" name="diameter">
                            <option value="">Diamètre</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="qf-type">Saison</label>
                        <select id="qf-type" name="type">
                            <option value="">Saison</option>
                            <option value="Été">Été</option>
                            <option value="Hiver">Hiver</option>
                            <option value="Toutes Saisons">Toutes Saisons</option>
                        </select>
                    </div>
                    <!-- Champs supplémentaires -->
                    <div class="filter-group">
                        <label for="charge">Charge</label>
                        <select name="charge" id="charge">
                            <option value="">Toutes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="vitesse">Vitesse</label>
                        <select name="vitesse" id="vitesse">
                            <option value="">Toutes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="marque">Marque</label>
                        <select name="marque" id="marque">
                            <option value="">Toutes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="specificite">Spécificité</label>
                        <select name="specificite" id="specificite">
                            <option value="">Spécificité</option>
                        </select>
                    </div>
                    <div class="form-group checkbox-group">
                        <label><input type="checkbox" name="runflat"> Runflat</label>
                        <label><input type="checkbox" name="renforce"> Renforcé</label>
                    </div>
                </div>
                <button type="submit" class="cta-button qf-submit-button">Rechercher Pneus</button>
            </form>
        </div>
    </section>

    <main class="site-main-content">
        <section id="how-it-works" class="how-it-works-section section-padding">
            <div class="container">
            <h2 class="section-title" data-aos="fade-up">Comment Ça Marche</h2>
                <div class="steps-container">
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="step-number-container">
                            <span class="step-number">01</span>
                        </div>
                        <div class="step-icon-container">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                        <h3 class="step-title">Commandez vos pneus</h3>
                        <p class="step-description">Parcourez notre sélection et passez commande en quelques clics.</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="250">
                        <div class="step-number-container">
                            <span class="step-number">02</span>
                        </div>
                        <div class="step-icon-container">
                            <i class="fas fa-truck fa-2x"></i>
                        </div>
                        <h3 class="step-title">Recevez-les rapidement</h3>
                        <p class="step-description">Livraison directe à votre domicile ou garage partenaire.</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="400">
                        <div class="step-number-container">
                            <span class="step-number">03</span>
                        </div>
                        <div class="step-icon-container">
                            <i class="fas fa-tools fa-2x"></i>
                        </div>
                        <h3 class="step-title">Montage Facilité</h3>
                        <p class="step-description">Choisissez le montage à domicile ou chez l'un de nos partenaires agréés.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="best-sellers" class="best-sellers-section section-padding">
            <div class="container">
            <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">Nos Meilleures Ventes</h2>
                <div class="product-grid">
                    <?php if (!empty($best_sellers)): ?>
                        <?php foreach ($best_sellers as $index => $pneu): ?>
                            <?php
                                $marque = extractBrandFromName($pneu['nom']); 
                               
                                $display_details = getProductDisplayDetails($pneu); 
                            ?>
                            <div class="product-card" data-aos="fade-up" data-aos-delay="<?php echo ($index % 4 + 1) * 50; ?>">
                                <div class="product-image-placeholder">
                                    <img loading="lazy" width="400" height="300" src="<?php echo sanitize_html_output(!empty($pneu['image']) ? $pneu['image'] : 'https://placehold.co/400x300/1e1e1e/ffdd03?text=Image+Pneu'); ?>" alt="<?php echo sanitize_html_output($pneu['nom']); ?>">
                                    <?php echo $display_details['badge_html'];  ?>
                                </div>
                                <div class="product-card-content">
                                    <h3 class="product-name"><?php echo sanitize_html_output($pneu['nom']); ?></h3>
                                    <p class="product-brand">Marque: <?php echo sanitize_html_output($marque); ?></p>
                                    <p class="product-specs"><?php echo sanitize_html_output($pneu['taille']); ?> | <?php echo sanitize_html_output($pneu['saison']); ?></p>
                                    <div class="product-price-stock">
                                        <p class="product-price"><?php echo sanitize_html_output($pneu['prix']); ?></p>
                                        <p class="product-stock <?php echo $display_details['stock_class']; ?>"><?php echo sanitize_html_output($display_details['stock_text']); ?></p>
                                    </div>
                                    <a href="produit.php?id=<?php echo $pneu['id']; ?>" class="cta-button product-cta secondary">Voir Détails</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucun produit à afficher dans les meilleures ventes pour le moment.</p>
                    <?php endif; ?>
                </div>
                <div class="section-cta-container">
                    <a href="produits.php" class="cta-button">Voir Tous Nos Pneus</a>
                </div>
            </div>
        </section>

        <section id="trusted-brands" class="trusted-brands-section section-padding">
            </section>


            <!-- test marque partenaire -->




            <style>
             
.our-brands-section {
    background: #1e1e1e;
    position: relative;
    overflow: hidden;
    padding: 5rem 0;
}

.our-brands-section::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 10% 20%, rgba(255, 215, 0, 0.05) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(255, 215, 0, 0.05) 0%, transparent 20%);
    z-index: 0;
}

.brands-container {
    position: relative;
    z-index: 1;
}

.brand-tier {
    margin-bottom: 4rem;
}

.brand-tier-title {
    text-align: center;
    font-size: 1.8rem;
    color: #FFD700;
    margin-bottom: 2.5rem;
    position: relative;
    padding-bottom: 15px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.brand-tier-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: #FFD700;
    border-radius: 2px;
}

.brand-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    justify-content: center;
}

.brand-card {
    background: rgba(30, 30, 30, 0.9);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    transition: all 0.4s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 1px solid rgba(255, 215, 0, 0.15);
    position: relative;
}

.brand-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 25px rgba(255, 215, 0, 0.2);
    border-color: rgba(255, 215, 0, 0.3);
}

.brand-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #FFD700, transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.brand-card:hover::before {
    opacity: 1;
}

.brand-logo-container {
    padding: 40px 20px 30px;
    text-align: center;
    background: rgba(20, 20, 20, 0.7);
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
}

.brand-logo-container img {
    max-width: 160px;
    max-height: 70px;
    width: auto;
    height: auto;
    object-fit: contain;
    transition: transform 0.3s ease;
    filter: brightness(0.9);
}

.brand-card:hover .brand-logo-container img {
    transform: scale(1.08);
    filter: brightness(1.1);
}

.brand-info {
    padding: 25px 20px;
    text-align: center;
}

.brand-info h4 {
    margin: 0 0 10px;
    font-size: 1.3rem;
    color: #fff;
    font-weight: 500;
}

.brand-info p {
    margin: 0;
    font-size: 0.95rem;
    color: #aaa;
    line-height: 1.5;
}


@media (max-width: 768px) {
    .brand-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .brand-logo-container {
        padding: 30px 15px 20px;
    }
    
    .brand-info {
        padding: 20px 15px;
    }
    
    .brand-tier-title {
        font-size: 1.6rem;
    }
}

@media (max-width: 480px) {
    .brand-grid {
        grid-template-columns: 1fr;
    }
}
            </style>
<section id="our-brands" class="our-brands-section section-padding">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Nos Marques Partenaires</h2>
        <p class="section-intro" data-aos="fade-up" data-aos-delay="100">
            Nous collaborons avec les meilleurs fabricants pour vous offrir des pneus de qualité supérieure
        </p>

        <div class="brands-container">
            <!-- Premium -->
            <div class="brand-tier" data-aos="fade-up" data-aos-delay="200">
                <h3 class="brand-tier-title">Gamme Premium</h3>
                <div class="brand-grid">
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/michelin.png" alt="Michelin" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Michelin</h4>
                            <p>Performance et durabilité exceptionnelles</p>
                        </div>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/continental.png" alt="Continental" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Continental</h4>
                            <p>Technologie allemande de pointe</p>
                        </div>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/bridgeston.webp" alt="Bridgestone" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Bridgestone</h4>
                            <p>Innovation et sécurité optimale</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confort & Performance -->
            <div class="brand-tier" data-aos="fade-up" data-aos-delay="300">
                <h3 class="brand-tier-title">Confort & Performance</h3>
                <div class="brand-grid">
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/goodyear.png" alt="Goodyear" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Goodyear</h4>
                            <p>Confort de conduite premium</p>
                        </div>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/dunlop2.png" alt="Dunlop" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Dunlop</h4>
                            <p>Performance sportive</p>
                        </div>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/kanhook.webp" alt="Hankook" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Hankook</h4>
                            <p>Équilibre parfait qualité/prix</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Économique -->
            <div class="brand-tier" data-aos="fade-up" data-aos-delay="400">
                <h3 class="brand-tier-title">Gamme Économique</h3>
                <div class="brand-grid">
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/nexen.png" alt="Nexen" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Nexen</h4>
                            <p>Fiabilité à prix abordable</p>
                        </div>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/kuhmo.png" alt="Kumho" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Kumho</h4>
                            <p>Durabilité en toutes conditions</p>
                        </div>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo-container">
                            <img src="./assets/images/icone/accelera.png" alt="Accelera" loading="lazy">
                        </div>
                        <div class="brand-info">
                            <h4>Accelera</h4>
                            <p>Performance urbaine</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- fin test partenaire  -->


        

        <section id="new-arrivals" class="new-arrivals-section section-padding">
            <div class="container">
            <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">Nouveautés</h2>
                <div class="product-grid">
                    <?php if (!empty($new_arrivals)): ?>
                        <?php foreach ($new_arrivals as $index => $pneu):  ?>
                            <?php
                                $marque = extractBrandFromName($pneu['nom']); 
                              
                                $display_details = getProductDisplayDetails($pneu);
                            ?>
                             <div class="product-card" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3 + 1) * 50; ?>">
                                <div class="product-image-placeholder">
                                     <img loading="lazy" width="400" height="300" src="<?php echo sanitize_html_output(!empty($pneu['image']) ? $pneu['image'] : 'https://placehold.co/400x300/1e1e1e/ffdd03?text=Nouveau+Pneu'); ?>" alt="<?php echo sanitize_html_output($pneu['nom']); ?>">
                                     <?php echo $display_details['badge_html']; "Nouveau" ?>
                                   
                                </div>
                                <div class="product-card-content">
                                    <h3 class="product-name"><?php echo sanitize_html_output($pneu['nom']); ?></h3>
                                    <p class="product-brand">Marque: <?php echo sanitize_html_output($marque); ?></p>
                                    <p class="product-specs"><?php echo sanitize_html_output($pneu['taille']); ?> | <?php echo sanitize_html_output($pneu['saison']); ?></p>
                                    <div class="product-price-stock">
                                        <p class="product-price"><?php echo sanitize_html_output($pneu['prix']); ?></p>
                                        <p class="product-stock <?php echo $display_details['stock_class']; ?>"><?php echo sanitize_html_output($display_details['stock_text']); ?></p>
                                    </div>
                                    <a href="produit.php?id=<?php echo $pneu['id']; ?>" class="cta-button product-cta secondary">Voir Détails</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Pas de nouveautés à afficher pour le moment.</p>
                    <?php endif; ?>
                </div>
                <div class="section-cta-container">
                    <a href="produits.php" class="cta-button">Découvrir Toutes les Nouveautés</a>
                </div>
            </div>
        </section>

        <section id="promotions" class="promotions-section section-padding">
            <div class="container">
            <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">Offres Spéciales</h2>
                <div class="promotion-grid">
                    <div class="promotion-card">
                    <h3>Soldes de Printemps - 20% sur Pirelli</h3>
                    <p>Réductions sur tous les modèles Pirelli. Préparez-vous pour la saison !</p>
                    <a href="#" class="cta-button promo-cta">Acheter Soldes Pirelli</a>
                    </div>
                    <div class="promotion-card">
                    <h3>Bridgestone: 3 Achetés = 1 Offert</h3>
                    <p>Offre à durée limitée sur une sélection de pneus Bridgestone. Ne manquez pas ça !</p>
                    <a href="#" class="cta-button promo-cta">Découvrir Offres Bridgestone</a>
                    </div>
                    <div class="promotion-card">
                    <h3>Parallélisme Offert avec un jeu Dunlop</h3>
                    <p>Achetez 4 pneus Dunlop et obtenez un service de parallélisme gratuit.</p>
                    <a href="#" class="cta-button promo-cta">Voir Offre Dunlop</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="testimonials" class="testimonials-section section-padding">
            <div class="container">
            <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">Ce Que Disent Nos Clients</h2>
            <div class="testimonial-carousel-wrapper">
                <div id="testimonial-carousel" class="testimonial-carousel-placeholder">
                    <div class="testimonial-slide" data-aos="fade-up" data-aos-delay="100">
                        <div class="testimonial-quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-quote">"Service incroyable et pneus de haute qualité ! J'ai trouvé exactement ce dont j'avais besoin pour mon SUV, et le montage a été rapide et professionnel. Je recommande vivement !"</p>
                        <p class="testimonial-author">- Alexandre P.</p>
                    </div>
                    <div class="testimonial-slide" data-aos="fade-up" data-aos-delay="200">
                        <div class="testimonial-quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-quote">"Très satisfait de mon achat. Livraison rapide et excellent service client. Les conseils pour choisir mes pneus étaient parfaits."</p>
                        <p class="testimonial-author">- Marie L.</p>
                    </div>
                    <div class="testimonial-slide" data-aos="fade-up" data-aos-delay="300">
                        <div class="testimonial-quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-quote">"Les meilleurs prix que j'ai pu trouver en ligne et une navigation facile sur le site. Le processus de commande était simple et clair."</p>
                        <p class="testimonial-author">- Jean D.</p>
                    </div>
                </div>
                <div class="testimonial-nav-buttons">
                    <button id="testimonial-prev" class="testimonial-nav-button prev" aria-label="Avis précédent">&lt;</button>
                    <button id="testimonial-next" class="testimonial-nav-button next" aria-label="Avis suivant">&gt;</button>
                    </div>
                </div>
            </div>
        </section>

        <section id="our-advantages" class="our-advantages-section section-padding section-animate">
            <div class="container">
                <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">Pourquoi choisir Ouipneu.fr ?</h2>
                <p class="section-intro">Chez Ouipneu.fr, nous nous engageons à vous offrir la meilleure expérience d'achat de pneus en ligne, avec des avantages qui font la différence.</p>
                <div class="advantages-grid">
                    <div class="advantage-item">
                        <div class="advantage-icon-container">
                            <i class="fas fa-layer-group fa-2x"></i>
                        </div>
                        <h3 class="advantage-title">Large Choix de Pneus</h3>
                        <p class="advantage-description">Des milliers de références parmi les plus grandes marques pour tous les véhicules et budgets.</p>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon-container">
                            <i class="fas fa-shipping-fast fa-2x"></i>
                        </div>
                        <h3 class="advantage-title">Livraison Rapide et Flexible</h3>
                        <p class="advantage-description">Recevez vos pneus chez vous ou dans l'un de nos centres de montage partenaires, selon votre convenance.</p>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon-container">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                        <h3 class="advantage-title">Retour Facile & Garantie Constructeur</h3>
                        <p class="advantage-description">Achetez en toute confiance avec notre politique de retour simplifié et la garantie fabricant préservée.</p>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon-container">
                            <i class="fas fa-headset fa-2x"></i>
                        </div>
                        <h3 class="advantage-title">Service Client Réactif & Conseil d'Expert</h3>
                        <p class="advantage-description">Notre équipe est à votre écoute pour vous guider et répondre à toutes vos questions techniques.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="about-us" class="about-us-section section-padding section-animate">
            <div class="container">
                <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">À Propos de Nous</h2>
                <div class="about-us-content">
                    <div class="about-us-text">
                        <p>Bienvenue chez Ouipneu.fr, votre partenaire de confiance pour l'achat de pneus en ligne. Depuis notre création, nous nous engageons à offrir à nos clients une expérience d'achat simple, rapide et sécurisée, avec un catalogue complet de pneus pour tous types de véhicules et tous budgets.</p>
                        <p>Notre mission est de vous fournir non seulement des produits de qualité supérieure provenant des meilleures marques, mais aussi un service client exceptionnel. Notre équipe d'experts est toujours prête à vous conseiller et à vous aider à trouver les pneus parfaitement adaptés à vos besoins et à votre style de conduite. Nous croyons en la transparence, des prix compétitifs et une livraison efficace pour garantir votre satisfaction totale.</p>
                        <p>Merci de faire confiance à Ouipneu.fr pour la sécurité et la performance de votre véhicule.</p>
                    </div>
                    <div class="about-us-image-container">
                        <img loading="lazy" width="500" height="350" src="./assets/images/ouiPneu.png" alt="L'équipe Ouipneu.fr">
                    </div>
                </div>
            </div>
        </section>

        <section id="newsletter-signup" class="newsletter-section section-padding">
            <div class="container">
            <h2 class="section-title" data-aos="fade-up" data-aos-duration="700" data-aos-once="true">Restez Informé</h2>
            <p>Abonnez-vous à notre newsletter pour les dernières offres, conseils d'entretien des pneus et nouveautés.</p>
                <form class="newsletter-form">
                <input type="email" placeholder="Entrez votre adresse e-mail" required autocomplete="email">
                <button type="submit" class="cta-button">S'abonner</button>
                </form>
            </div>
        </section>
    </main>

    <footer id="main-footer">
        <div class="container">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3>Ouipneu.fr</h3>
                    <p>Votre partenaire de confiance pour des pneus premium, un montage expert et un service client exceptionnel.</p>
                </div>
                <div class="footer-column">
                    <h3>Navigation</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="index.php#promotions">Promotions</a></li>
                        <li><a href="contact.php">Contactez-nous</a></li>
                        <li><a href="dashboard.php">Mon Compte</a></li>
                        <li><a href="devenir_partenaire.php">Devenir Garage Partenaire</a></li>
                        <li><a href="nos_garages_partenaires.php">Nos Garages Partenaires</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Informations</h3>
                    <ul>
                        <li><a href="legal-notice.php">Mentions Légales</a></li>
                        <li><a href="privacy-policy.php">Politique de Confidentialité</a></li>
                        <li><a href="cgv.php">Conditions Générales de Vente</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Suivez-Nous</h3>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span id="current-year"><?php echo date('Y'); ?></span> Ouipneu.fr. Tous droits réservés. <span style="margin-left: 10px;">|</span> <a href="admin_login.php" style="font-size: 0.8em; color: var(--text-secondary);">Admin</a></p>
            </div>
        </div>
    </footer>

</body>
<!-- Swiper JS CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const swipers = document.querySelectorAll('.brand-swiper');
    swipers.forEach(container => {
      new Swiper(container, {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        autoplay: { delay: 2500, disableOnInteraction: false },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
        breakpoints: {
          576: { slidesPerView: 2 },
          768: { slidesPerView: 3 },
          1024: { slidesPerView: 4 },
        },
      });
    });
  });
</script>
<script src="https://unpkg.com/aos@next/dist/aos.js" defer></script>
<script src="js/main.js" defer></script>
</body>
</html>