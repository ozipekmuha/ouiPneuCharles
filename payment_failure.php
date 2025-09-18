<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échec du Paiement - Ouipneu.fr</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header id="main-header">
        <div class="container">
            <div class="logo">
               <a href="index.php"><img src="./assets/images/logobg.png" alt="Logo Ouipneu.fr"></a>
            </div>
        </div>
    </header>

    <main class="site-main-content">
        <section id="payment-failure-section" class="section-padding">
            <div class="container">
                <h1 class="page-title">Échec du Paiement</h1>
                <p>Une erreur est survenue lors du traitement de votre paiement.</p>
                <p>Veuillez réessayer ou contacter le support si le problème persiste.</p>
                <a href="checkout.php" class="cta-button">Réessayer le paiement</a>
            </div>
        </section>
    </main>

    <footer id="main-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <span id="current-year"></span> Ouipneu.fr. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
