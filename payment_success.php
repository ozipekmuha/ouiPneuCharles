<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/stripe_config.php';

$payment_intent_id = $_GET['payment_intent'];
$order_id = $_SESSION['order_id'];

if ($payment_intent_id && $order_id) {
    try {
        $stmt = $pdo->prepare("UPDATE Commandes SET statut_commande = 'Payée', id_transaction_paiement = :payment_intent_id WHERE id_commande = :order_id");
        $stmt->execute([
            ':payment_intent_id' => $payment_intent_id,
            ':order_id' => $order_id
        ]);

       
        unset($_SESSION['panier']);
        unset($_SESSION['order_id']);

    } catch (Exception $e) {
      
        error_log($e->getMessage());
        
        header('Location: payment_failure.php');
        exit;
    }
} else {

    header('Location: payment_failure.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Réussi - Ouipneu.fr</title>
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
        <section id="payment-success-section" class="section-padding">
            <div class="container">
                <h1 class="page-title">Paiement Réussi!</h1>
                <p>Votre commande a été traitée avec succès.</p>
                <p>Votre numéro de commande est le : <?php echo htmlspecialchars($order_id); ?></p>
                <a href="index.php" class="cta-button">Retour à l'accueil</a>
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
