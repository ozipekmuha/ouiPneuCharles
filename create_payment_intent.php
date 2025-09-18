<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/stripe_config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $_SESSION['shipping_address_id'] = $data['shipping_address_id'];
    $_SESSION['billing_address_id'] = $data['billing_address_id'];

    if (!isset($_SESSION['id_utilisateur'])) {
        throw new Exception("User not logged in.");
    }

    if (empty($_SESSION['panier'])) {
        throw new Exception("Cart is empty.");
    }

    $user_id = $_SESSION['id_utilisateur'];
    $TVA_RATE = 0.20;

    
    $subtotal = 0;
    $product_ids_in_cart = array_keys($_SESSION['panier']);
    $placeholders = implode(',', array_fill(0, count($product_ids_in_cart), '?'));
    $stmt = $pdo->prepare("SELECT id, prix FROM Pneus WHERE id IN ($placeholders)");
    $stmt->execute($product_ids_in_cart);
    $products_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($_SESSION['panier'] as $product_id => $quantity) {
        if (isset($products_data[$product_id])) {
            $price = convertPriceToFloat($products_data[$product_id]);
            $subtotal += $price * $quantity;
        }
    }

    $shipping_cost = 5.00;  
    $total = $subtotal + $shipping_cost;
    $total_in_cents = (int)($total * 100);


    $pdo->beginTransaction();

    $stmt_order = $pdo->prepare(
        "INSERT INTO Commandes (id_utilisateur, id_adresse_livraison, id_adresse_facturation, statut_commande,
                           montant_sous_total, montant_livraison, montant_reduction,
                           montant_total_ht, montant_tva, montant_total_ttc, methode_paiement)
         VALUES (:uid, :adr_liv, :adr_fact, :statut, :sous_total_ht, :liv_ht, :reduc_ht, :total_ht, :tva, :total_ttc, :methode)"
    );

    $stmt_order->execute([
        ':uid' => $user_id,
        ':adr_liv' => $_SESSION['shipping_address_id'],
        ':adr_fact' => $_SESSION['billing_address_id'],
        ':statut' => 'En attente de paiement',
        ':sous_total_ht' => $subtotal / (1 + $TVA_RATE),
        ':liv_ht' => $shipping_cost / (1 + $TVA_RATE),
        ':reduc_ht' => 0,
        ':total_ht' => $total / (1 + $TVA_RATE),
        ':tva' => $total - ($total / (1 + $TVA_RATE)),
        ':total_ttc' => $total,
        ':methode' => 'Stripe'
    ]);
    $order_id = $pdo->lastInsertId();
    $_SESSION['order_id'] = $order_id;

    $stmt_item = $pdo->prepare(
        "INSERT INTO Lignes_Commande (id_commande, id_pneu, quantite, prix_unitaire_ht_commande, taux_tva_applique)
         VALUES (:order_id, :pneu_id, :qty, :prix_ht, :tva_rate)"
    );

    $stmt_pneu = $pdo->prepare("SELECT prix, nom, taille FROM Pneus WHERE id = :pneu_id");

    foreach ($_SESSION['panier'] as $product_id => $quantity) {
        $stmt_pneu->execute([':pneu_id' => $product_id]);
        $pneu = $stmt_pneu->fetch();
        $price = convertPriceToFloat($pneu['prix']);
        $price_ht = $price / (1 + $TVA_RATE);
        $stmt_item->execute([
            ':order_id' => $order_id,
            ':pneu_id' => $product_id,
            ':qty' => $quantity,
            ':prix_ht' => $price_ht,
            ':tva_rate' => $TVA_RATE * 100
        ]);
    }

    $pdo->commit();



    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $total_in_cents,
        'currency' => 'eur',
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'metadata' => [
            'order_id' => $order_id
        ]
    ]);

    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
