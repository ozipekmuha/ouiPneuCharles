<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


$settings_file = 'config/settings.json';
$settings = [];
$default_settings = [
    'admin_firstname' => '',
    'admin_name' => '',
    'admin_email' => '',
    'stripe_pk' => '',
    'stripe_sk' => '',
    'carriers' => [['name' => '', 'price' => '', 'delay' => '']]
];

if (file_exists($settings_file)) {
    $settings_json = file_get_contents($settings_file);
    $loaded_settings = json_decode($settings_json, true);
    if ($loaded_settings !== null) {

        $settings = array_merge($default_settings, $loaded_settings);
    } else {
        $settings = $default_settings;
        error_log("Erreur de décodage JSON dans $settings_file");
    }
} else {
    $settings = $default_settings;
}


if (!isset($settings['carriers']) || !is_array($settings['carriers']) || empty($settings['carriers'])) {
    $settings['carriers'] = [['name' => '', 'price' => '', 'delay' => '']];
}



$admin_message_display = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Erreur de sécurité (CSRF). Action annulée.'];
    } else {

        if ($_POST['action'] == 'approve_candidature_garage' && isset($_POST['id_candidat_modal_garage'])) {
            $id_candidat = (int)$_POST['id_candidat_modal_garage'];
            $nom_garage = trim($_POST['nom_garage_modal']);
            $adresse_complete = trim($_POST['adresse_complete_modal']);
            $telephone = trim($_POST['telephone_modal']);
            $email = trim($_POST['email_modal']);
            $services_offerts = trim($_POST['services_offerts_modal']);
            $description_courte = trim($_POST['description_courte_modal']);
            $latitude = !empty($_POST['latitude_modal']) ? filter_var($_POST['latitude_modal'], FILTER_VALIDATE_FLOAT) : null;
            $longitude = !empty($_POST['longitude_modal']) ? filter_var($_POST['longitude_modal'], FILTER_VALIDATE_FLOAT) : null;
            $horaires_ouverture = trim($_POST['horaires_ouverture_modal']);
            $url_website = trim($_POST['url_website_modal']);

            if (empty($nom_garage) || empty($adresse_complete)) {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Le nom du garage et l'adresse sont requis pour l'approbation."];
            } else {
                try {
                    $pdo->beginTransaction();
                    $sql_insert_partenaire = "INSERT INTO GaragesPartenaires (nom_garage, adresse_complete, telephone, email, services_offerts, description_courte, latitude, longitude, horaires_ouverture, url_website, est_visible) VALUES (:nom, :adresse, :tel, :email, :services, :desc, :lat, :lon, :horaires, :site, TRUE)";
                    $stmt_insert = $pdo->prepare($sql_insert_partenaire);
                    $stmt_insert->execute([':nom' => $nom_garage, ':adresse' => $adresse_complete, ':tel' => $telephone, ':email' => $email, ':services' => $services_offerts, ':desc' => $description_courte, ':lat' => $latitude, ':lon' => $longitude, ':horaires' => $horaires_ouverture, ':site' => $url_website]);
                    $sql_update_candidat = "UPDATE GaragesCandidats SET statut = 'approuve' WHERE id_candidat = :id_candidat";
                    $stmt_update = $pdo->prepare($sql_update_candidat);
                    $stmt_update->execute([':id_candidat' => $id_candidat]);
                    $pdo->commit();
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => "Candidature approuvée et ajoutée aux partenaires."];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Erreur approve_candidature_garage: " . $e->getMessage());
                    $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Erreur base de données approbation: " . $e->getMessage()];
                }
            }
            header("Location: admin_dashboard.php#admin-garages-section");
            exit;
        } elseif ($_POST['action'] == 'reject_candidature_garage' && isset($_POST['id_candidat_garage'])) {
            $id_candidat = (int)$_POST['id_candidat_garage'];
            try {
                $sql_update_candidat = "UPDATE GaragesCandidats SET statut = 'rejete' WHERE id_candidat = :id_candidat";
                $stmt_update = $pdo->prepare($sql_update_candidat);
                $stmt_update->execute([':id_candidat' => $id_candidat]);
                $_SESSION['admin_message'] = ['type' => 'success', 'text' => "Candidature rejetée."];
            } catch (PDOException $e) {
                error_log("Erreur reject_candidature_garage: " . $e->getMessage());
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Erreur base de données rejet."];
            }
            header("Location: admin_dashboard.php#admin-garages-section");
            exit;
        } elseif ($_POST['action'] == 'update_partenaire_garage' && isset($_POST['id_garage_modal'])) {
            $id_garage = (int)$_POST['id_garage_modal'];
            $nom_garage = trim($_POST['nom_garage_modal']);
            $adresse_complete = trim($_POST['adresse_complete_modal']);
            $telephone = trim($_POST['telephone_modal']);
            $email = trim($_POST['email_modal']);
            $services_offerts = trim($_POST['services_offerts_modal']);
            $description_courte = trim($_POST['description_courte_modal']);
            $latitude = !empty($_POST['latitude_modal']) ? filter_var($_POST['latitude_modal'], FILTER_VALIDATE_FLOAT) : null;
            $longitude = !empty($_POST['longitude_modal']) ? filter_var($_POST['longitude_modal'], FILTER_VALIDATE_FLOAT) : null;
            $horaires_ouverture = trim($_POST['horaires_ouverture_modal']);
            $url_website = trim($_POST['url_website_modal']);
            $est_visible = isset($_POST['est_visible_modal']) ? (int)$_POST['est_visible_modal'] : 0;

            if (empty($nom_garage) || empty($adresse_complete)) {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Le nom du garage et l'adresse sont requis."];
            } else {
                try {
                    $sql_update_partenaire = "UPDATE GaragesPartenaires SET nom_garage = :nom, adresse_complete = :adresse, telephone = :tel, email = :email, services_offerts = :services, description_courte = :desc, latitude = :lat, longitude = :lon, horaires_ouverture = :horaires, url_website = :site, est_visible = :visible WHERE id_garage = :id_garage";
                    $stmt_update = $pdo->prepare($sql_update_partenaire);
                    $stmt_update->execute([':nom' => $nom_garage, ':adresse' => $adresse_complete, ':tel' => $telephone, ':email' => $email, ':services' => $services_offerts, ':desc' => $description_courte, ':lat' => $latitude, ':lon' => $longitude, ':horaires' => $horaires_ouverture, ':site' => $url_website, ':visible' => $est_visible, ':id_garage' => $id_garage]);
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => "Informations du partenaire mises à jour."];
                } catch (PDOException $e) {
                    error_log("Erreur update_partenaire_garage: " . $e->getMessage());
                    $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Erreur base de données mise à jour partenaire."];
                }
            }
            header("Location: admin_dashboard.php#admin-garages-section");
            exit;
        } elseif ($_POST['action'] == 'delete_partenaire_garage' && isset($_POST['id_garage'])) {
            $id_garage = (int)$_POST['id_garage'];
            try {
                $sql_delete = "DELETE FROM GaragesPartenaires WHERE id_garage = :id_garage";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->execute([':id_garage' => $id_garage]);
                $_SESSION['admin_message'] = ['type' => 'success', 'text' => "Garage partenaire supprimé."];
            } catch (PDOException $e) {
                error_log("Erreur delete_partenaire_garage: " . $e->getMessage());
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => "Erreur base de données suppression partenaire."];
            }
            header("Location: admin_dashboard.php#admin-garages-section");
            exit;
        } elseif ($_POST['action'] == 'add_edit_product') {

            $nom = trim($_POST['nom'] ?? '');
            $taille = trim($_POST['taille'] ?? '');
            $saison = $_POST['saison'] ?? 'Été';



            $prix_raw_input = trim($_POST['prix'] ?? '');
            $prix_clean_for_validation = str_replace(',', '.', $prix_raw_input);
            $prix = filter_var($prix_clean_for_validation, FILTER_VALIDATE_FLOAT);

            $stock_disponible = filter_input(INPUT_POST, 'stock_disponible', FILTER_VALIDATE_INT);
            $description = trim($_POST['description'] ?? '');
            $specifications = trim($_POST['specifications'] ?? '');
            $est_actif = isset($_POST['est_actif']) ? (int)$_POST['est_actif'] : 0;
            $id_pneu_edit = filter_input(INPUT_POST, 'id_pneu_edit', FILTER_VALIDATE_INT);


            if (empty($nom) || empty($taille) || $prix === false || $prix < 0 || $stock_disponible === false || $stock_disponible < 0) {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Veuillez remplir tous les champs obligatoires correctement (le prix doit être un nombre positif).'];
            } else {
                $image_path_db = $id_pneu_edit ? ($_POST['current_image_path'] ?? '') : '';


                if (isset($_FILES['image_produit']) && $_FILES['image_produit']['error'] == UPLOAD_ERR_OK) {
                    $upload_dir = 'assets/images/pneus/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $filename = uniqid('pneu_', true) . '_' . basename($_FILES['image_produit']['name']);
                    $target_file = $upload_dir . $filename;
                    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));


                    $check = getimagesize($_FILES['image_produit']['tmp_name']);
                    if ($check === false) {
                        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Le fichier n\'est pas une image valide.'];
                    } elseif ($_FILES['image_produit']['size'] > 2000000) {
                        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'L\'image est trop volumineuse (max 2MB).'];
                    } elseif (!in_array($image_file_type, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Seuls les formats JPG, JPEG, PNG & WEBP sont autorisés.'];
                    } else {
                        if (move_uploaded_file($_FILES['image_produit']['tmp_name'], $target_file)) {
                            $image_path_db = $target_file;
                            if ($id_pneu_edit && !empty($_POST['current_image_path']) && file_exists($_POST['current_image_path']) && $_POST['current_image_path'] !== $image_path_db) {
                                unlink($_POST['current_image_path']);
                            }
                        } else {
                            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Erreur lors du téléchargement de l\'image.'];
                        }
                    }
                } elseif ($id_pneu_edit && !empty($_POST['current_image_path'])) {
                    $image_path_db = $_POST['current_image_path'];
                }


                if (!isset($_SESSION['admin_message'])) {
                    try {
                        if ($id_pneu_edit) {
                            if (empty($image_path_db)) {
                                $stmt_check_image = $pdo->prepare("SELECT image FROM Pneus WHERE id = :id");
                                $stmt_check_image->execute([':id' => $id_pneu_edit]);
                                $current_db_image = $stmt_check_image->fetchColumn();
                                if (!empty($current_db_image)) {
                                    $image_path_db = $current_db_image;
                                }
                            }

                            $sql = "UPDATE Pneus SET nom = :nom, taille = :taille, saison = :saison, prix = :prix, stock_disponible = :stock, description = :description, image = :image, specifications = :specifications, est_actif = :est_actif WHERE id = :id";
                            $stmt = $pdo->prepare($sql);
                            $params = [
                                ':nom' => $nom,
                                ':taille' => $taille,
                                ':saison' => $saison,
                                ':prix' => $prix,
                                ':stock' => $stock_disponible,
                                ':description' => $description,
                                ':image' => $image_path_db,
                                ':specifications' => $specifications,
                                ':est_actif' => $est_actif,
                                ':id' => $id_pneu_edit
                            ];
                        } else {

                            $sql = "INSERT INTO Pneus (nom, taille, saison, prix, stock_disponible, description, image, specifications, est_actif) 
                                    VALUES (:nom, :taille, :saison, :prix, :stock, :description, :image, :specifications, :est_actif)";
                            $stmt = $pdo->prepare($sql);
                            $params = [
                                ':nom' => $nom,
                                ':taille' => $taille,
                                ':saison' => $saison,
                                ':prix' => $prix,
                                ':stock' => $stock_disponible,
                                ':description' => $description,
                                ':image' => $image_path_db,
                                ':specifications' => $specifications,
                                ':est_actif' => $est_actif
                            ];
                        }

                        if ($stmt->execute($params)) {
                            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Produit ' . ($id_pneu_edit ? 'mis à jour' : 'ajouté') . ' avec succès.'];
                        } else {
                            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'enregistrement du produit.'];
                        }
                    } catch (PDOException $e) {
                        error_log("Erreur PDO add_edit_product: " . $e->getMessage());
                        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Erreur base de données lors de l\'enregistrement du produit. Debug: ' . $e->getMessage()];
                    }
                }
            }
            header("Location: admin_dashboard.php#admin-products-content-NEW");
            exit;
        } elseif ($_POST['action'] == 'update_settings') {
            if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                $new_settings_input = $_POST['settings'];
                $current_settings = $settings;

                $settings_to_save = [];


                $settings_to_save['admin_firstname'] = trim($new_settings_input['admin_firstname'] ?? '');
                $settings_to_save['admin_name'] = trim($new_settings_input['admin_name'] ?? '');
                $settings_to_save['admin_email'] = trim($new_settings_input['admin_email'] ?? '');


                $settings_to_save['stripe_pk'] = trim($new_settings_input['stripe_pk'] ?? '');

                if (isset($new_settings_input['stripe_sk']) && !empty($new_settings_input['stripe_sk'])) {
                    $settings_to_save['stripe_sk'] = trim($new_settings_input['stripe_sk']);
                } elseif (isset($current_settings['stripe_sk'])) {
                    $settings_to_save['stripe_sk'] = $current_settings['stripe_sk'];
                } else {
                    $settings_to_save['stripe_sk'] = '';
                }


                $updated_carriers = [];
                if (isset($new_settings_input['carriers']) && is_array($new_settings_input['carriers'])) {
                    foreach ($new_settings_input['carriers'] as $carrier_data) {
                        if (isset($carrier_data['name']) && trim($carrier_data['name']) !== '') {
                            $updated_carriers[] = [
                                'name' => trim($carrier_data['name']),
                                'price' => !empty($carrier_data['price']) ? (float)str_replace(',', '.', $carrier_data['price']) : 0.0,
                                'delay' => trim($carrier_data['delay'] ?? '')
                            ];
                        }
                    }
                }
                $settings_to_save['carriers'] = $updated_carriers;


                if (!is_dir('config')) {
                    mkdir('config', 0777, true);
                }

                if (file_put_contents($settings_file, json_encode($settings_to_save, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Paramètres mis à jour avec succès.'];
                    $settings = $settings_to_save;
                } else {
                    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'enregistrement des paramètres. Vérifiez les permissions du dossier config.'];
                }
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Données de paramètres invalides.'];
            }
            header("Location: admin_dashboard.php#admin-settings-content");
            exit;
        }
    }
}

if (isset($_SESSION['admin_message'])) {
    $admin_message_display = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}



$stats = [
    'chiffre_affaires_30j' => 0,
    'commandes_24h' => 0,
    'nouveaux_clients_7j' => 0,
    'stock_faible' => 0,
    'activite_recente' => []
];

try {

    $stmt_ca = $pdo->query("SELECT SUM(montant_total_ttc) as ca FROM Commandes WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['chiffre_affaires_30j'] = $stmt_ca->fetchColumn() ?: 0;


    $stmt_commandes = $pdo->query("SELECT COUNT(*) FROM Commandes WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['commandes_24h'] = $stmt_commandes->fetchColumn() ?: 0;


    $stmt_clients = $pdo->query("SELECT COUNT(*) FROM Utilisateurs WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['nouveaux_clients_7j'] = $stmt_clients->fetchColumn() ?: 0;


    $stmt_stock = $pdo->query("SELECT COUNT(*) FROM Pneus WHERE stock_disponible < 5 AND est_actif = 1");
    $stats['stock_faible'] = $stmt_stock->fetchColumn() ?: 0;


    $stmt_activite = $pdo->query(
        "(SELECT 'commande' as type, c.id_commande as id, CONCAT('Nouvelle commande #', SUBSTRING(c.id_commande, 1, 8), '...') as description, c.date_commande as date FROM Commandes c ORDER BY c.date_commande DESC LIMIT 3)
        UNION
        (SELECT 'client' as type, u.id_utilisateur as id, CONCAT('Nouveau client: ', u.email) as description, u.date_inscription as date FROM Utilisateurs u WHERE u.est_admin = 0 ORDER BY u.date_inscription DESC LIMIT 2)
        ORDER BY date DESC LIMIT 5"
    );
    $stats['activite_recente'] = $stmt_activite->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur Dashboard Stats: " . $e->getMessage());

    $admin_message_display = ['type' => 'error', 'text' => 'Erreur lors du chargement des statistiques du tableau de bord.'];
}


$liste_pneus = [];
try {
    $stmt_pneus = $pdo->query("SELECT id, nom, image, taille, saison, prix, stock_disponible, est_actif, description, specifications FROM Pneus ORDER BY nom ASC");
    $liste_pneus = $stmt_pneus->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur Admin - Récupération pneus: " . $e->getMessage());
    $admin_message_display = ['type' => 'error', 'text' => 'Erreur de chargement des pneus.'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - Ouipneu.fr</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <style>
        :root {
            --bg-dark: #121212;
            --bg-surface: #1e1e1e;
            --text-light: #e0e0e0;
            --text-secondary: #b0b0b0;
            --accent-primary: #ffdd03;
            --text-on-accent: #1a1a1a;
            --border-color: #333333;
            --font-weight-regular: 400;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        .admin-header {
            background-color: var(--bg-surface);
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-header .logo img {
            max-width: 150px;
            filter: invert(1) brightness(1.8) contrast(1.1);
        }

        .admin-header .admin-user-info a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: var(--font-weight-medium);
        }

        .admin-main-layout {
            display: flex;
            flex-grow: 1;
        }

        .admin-sidebar {
            width: 250px;
            background-color: var(--bg-surface);
            padding: 1.5rem 1rem;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .admin-sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .admin-sidebar nav ul li a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            transition: background-color 0.2s ease, color 0.2s ease;
            font-weight: var(--font-weight-medium);
        }

        .admin-sidebar nav ul li a i {
            width: 20px;
            text-align: center;
        }

        .admin-sidebar nav ul li a:hover,
        .admin-sidebar nav ul li a.active {
            background-color: var(--accent-primary);
            color: var(--text-on-accent);
        }

        .admin-sidebar nav ul li a.active i {
            color: var(--text-on-accent);
        }

        .admin-content-area {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .admin-content-section {
            display: none;
        }

        .admin-content-section.is-active {
            display: block;
        }

        .admin-content-header {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }

        .admin-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }

        .admin-content-header h1 {
            font-size: 1.8rem;
            color: var(--accent-primary);
            margin: 0;
        }

        .date-filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .date-filter-btn {
            background-color: var(--bg-surface);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .date-filter-btn:hover {
            background-color: var(--accent-primary);
            color: var(--text-on-accent);
            border-color: var(--accent-primary);
        }

        .date-filter-btn.active {
            background-color: var(--accent-primary);
            color: var(--text-on-accent);
            border-color: var(--accent-primary);
            font-weight: var(--font-weight-semibold);
        }

        .dashboard-main-content {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }

        .dashboard-left {
            flex: 3;
        }

        .dashboard-right {
            flex: 1;
        }

        #recent-activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #recent-activity-list li {
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        #recent-activity-list li a {
            color: var(--text-light);
            text-decoration: none;
        }

        #recent-activity-list li a:hover {
            text-decoration: underline;
        }

        .activity-date {
            font-size: 0.8em;
            color: var(--text-secondary);
            margin-left: 0.5rem;
        }

        .admin-content-header .cta-button {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }


        .admin-table-container {
            background-color: var(--bg-surface);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .admin-table th {
            color: var(--text-light);
            font-weight: var(--font-weight-semibold);
            background-color: rgba(0, 0, 0, 0.1);
        }

        .admin-table td {
            color: var(--text-secondary);
        }

        .admin-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }

        .status-pending {
            color: #f39c12;
        }

        .status-shipped {
            color: #3498db;
        }

        .status-delivered {
            color: #2ecc71;
        }

        .status-cancelled {
            color: #e74c3c;
        }

        .admin-table .actions a {
            margin-right: 0.5rem;
            color: var(--accent-primary);
            font-size: 0.85rem;
        }

        .admin-table img.product-thumbnail {
            width: 60px;
            height: auto;
            max-height: 50px;
            object-fit: contain;
            border-radius: 4px;
            background-color: var(--bg-dark);
        }



        .admin-settings-form .detail-card,
        .admin-content-section .detail-card {
            background-color: var(--bg-surface);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
        }

        .admin-settings-form .detail-card h2,
        .admin-content-section .detail-card h2 {
            font-size: 1.2rem;
            color: var(--accent-primary);
            margin-top: 0;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-settings-form .form-group {
            margin-bottom: 1rem;
        }

        .admin-settings-form label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }

        .admin-settings-form input,
        .admin-settings-form select,
        .admin-settings-form textarea {
            width: 100%;
            padding: 0.7rem 0.9rem;
            background-color: var(--bg-dark);
            color: var(--text-light);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        .admin-footer {

            .dashboard-stats-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
            }

            .stat-card {
                flex: 1;
                min-width: 220px;
            }

            .stat-card {
                background-color: var(--bg-surface);
                padding: 1.5rem;
                border-radius: 10px;
                text-align: center;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .stat-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }

            .stat-card h3 {
                font-size: 1rem;
                color: var(--accent-primary);
                margin-bottom: 0.5rem;
            }

            .stat-card p {
                font-size: 1.6rem;
                font-weight: var(--font-weight-semibold);
                color: var(--text-light);
                margin: 0;
            }

            text-align: center;
            padding: 1rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border-top: 1px solid var(--border-color);
            background-color: var(--bg-surface);
        }

        .admin-table .actions .admin-action-btn {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid;
            margin-bottom: 0.25rem;
        }

        .admin-table .actions .edit-btn {
            background-color: var(--bg-surface);
            color: var(--accent-primary);
            border-color: var(--accent-primary);
        }

        .admin-table .actions .toggle-status-btn.status-active-btn {
            background-color: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }

        .admin-table .actions .toggle-status-btn.status-inactive-btn {
            background-color: #2ecc71;
            color: white;
            border-color: #2ecc71;
        }

        .admin-table .actions .delete-btn {
            background-color: var(--bg-surface);
            color: #e74c3c;
            border-color: #e74c3c;
        }

        #product-modal-overlay,
        #promo-code-modal-overlay,
        .modal-admin

  
            {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1050;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        #product-modal-content,
        #promo-code-modal-content,
        .modal-admin-content

       
            {
            background-color: var(--bg-surface);
            color: var(--text-light);
            border-radius: 8px;
            width: 100%;
            max-width: 650px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 40px);
            overflow: hidden;
        }

        .product-modal-header,
        .promo-code-modal-header

        
            {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-modal-header h2,
        .promo-code-modal-header h2

       
            {
            margin: 0;
            font-size: 1.4rem;
            color: var(--accent-primary);
        }

        .product-modal-body,
        .promo-code-modal-body

       
            {
            padding: 1.5rem;
            overflow-y: auto;
        }

        .product-modal-body .form-group,
        .promo-code-modal-body .form-group

       
            {
            margin-bottom: 1rem;
        }

        .product-modal-footer,
        .promo-code-modal-footer

 
            {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }

        #close-product-modal-btn,
        #close-promo-code-modal-btn,
        .close-garage-admin-modal

      
            {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .search-bar-container {
            margin-bottom: 1.5rem;
        }

        .search-bar-container input {
            width: 100%;
            padding: 0.7rem 1rem;
            background-color: var(--bg-dark);
            color: var(--text-light);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 0.9rem;
            background-color: var(--bg-dark);
            color: var(--text-light);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <header class="admin-header">
        <div class="logo">
            <a href="admin_dashboard.php"><img src="assets/images/logobg.png" alt="Logo Ouipneu.fr" style="max-width: 150px; filter: invert(1) brightness(1.8) contrast(1.1);"></a>
        </div>
        <div class="admin-user-info">
            <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?> | <a href="admin_logout.php">Déconnexion</a></span>
        </div>
    </header>

    <div class="admin-main-layout">
        <aside class="admin-sidebar">
            <nav>
                <ul>
                    <li><a href="#" class="active" data-target="admin-dashboard-main"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
                    <li><a href="#" data-target="admin-orders-content"><i class="fas fa-shopping-cart"></i> Commandes</a></li>
                    <li><a href="#" data-target="admin-products-content-NEW"><i class="fas fa-box-open"></i> Produits</a></li>
                    <li><a href="#" data-target="admin-promo-codes-content"><i class="fas fa-percentage"></i> Codes Promo</a></li>
                    <li><a href="#" data-target="admin-clients-content"><i class="fas fa-users"></i> Clients</a></li>
                    <li><a href="#" data-target="admin-garages-section"><i class="fas fa-warehouse"></i> Gestion Garages</a></li>
                    <li><a href="#" data-target="admin-settings-content"><i class="fas fa-cog"></i> Paramètres</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content-area">
            <?php if (!empty($admin_message_display)): ?>
                <div class="global-notification-bar <?php echo sanitize_html_output($admin_message_display['type']); ?> show">
                    <?php echo sanitize_html_output($admin_message_display['text']); ?>
                </div>
            <?php endif; ?>

            <div id="admin-dashboard-main" class="admin-content-section is-active">
                <div class="admin-content-header">
                    <h1>Aperçu Général</h1>
                    <div class="date-filter-buttons">
                        <button class="date-filter-btn active" data-period="today">Aujourd'hui</button>
                        <button class="date-filter-btn" data-period="7days">7 jours</button>
                        <button class="date-filter-btn" data-period="30days">30 jours</button>
                        <button class="date-filter-btn" data-period="year">Année</button>
                    </div>
                </div>
                <div class="dashboard-stats-grid">
                    <div class="stat-card" id="ca-card">
                        <h3><i class="fas fa-euro-sign"></i> Chiffre d'Affaires</h3>
                        <p data-stat="ca">0,00 €</p>
                    </div>
                    <div class="stat-card" id="orders-card">
                        <h3><i class="fas fa-shopping-cart"></i> Commandes</h3>
                        <p data-stat="orders">0</p>
                    </div>
                    <div class="stat-card" id="clients-card">
                        <h3><i class="fas fa-users"></i> Nouveaux Clients</h3>
                        <p data-stat="clients">0</p>
                    </div>
                    <div class="stat-card" id="stock-card">
                        <h3><i class="fas fa-box"></i> Stock Faible</h3>
                        <p data-stat="stock">0</p>
                    </div>
                </div>

                <div class="dashboard-main-content">
                    <div class="dashboard-left">
                        <div class="detail-card">
                            <h2>Ventes</h2>
                            <div style="height: 300px; width: 100%;"><canvas id="salesChart"></canvas></div>
                        </div>
                        <div class="admin-table-container detail-card" style="margin-top: 2rem;">
                            <h2>Commandes à traiter</h2>
                            <table class="admin-table" id="orders-to-process-table">
                                <thead>
                                    <tr>
                                        <th>ID Commande</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="dashboard-right">
                        <div class="admin-table-container detail-card">
                            <h2>Activité Récente</h2>
                            <ul id="recent-activity-list">

                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div id="admin-orders-content" class="admin-content-section">
                <div class="admin-content-header">
                    <h1>Gestion des Commandes</h1>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt_orders = $pdo->query("SELECT c.id_commande, CONCAT(u.prenom, ' ', u.nom) AS nom_client, c.date_commande, c.montant_total_ttc, c.statut_commande FROM Commandes c LEFT JOIN Utilisateurs u ON c.id_utilisateur = u.id_utilisateur ORDER BY c.date_commande DESC");
                                while ($order = $stmt_orders->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>#" . sanitize_html_output(strtoupper(substr($order['id_commande'], 0, 8))) . "...</td>";
                                    echo "<td>" . sanitize_html_output($order['nom_client']) . "</td>";
                                    echo "<td>" . sanitize_html_output(date("d/m/Y H:i", strtotime($order['date_commande']))) . "</td>";
                                    echo "<td>" . sanitize_html_output(number_format($order['montant_total_ttc'], 2, ',', ' ')) . " €</td>";
                                    echo "<td><span class='status-" . strtolower(str_replace(' ', '-', sanitize_html_output($order['statut_commande']))) . "'>" . sanitize_html_output($order['statut_commande']) . "</span></td>";
                                    echo "<td><a href='admin_order_detail.php?id_commande=" . urlencode($order['id_commande']) . "' class='admin-action-btn view-btn'>Voir</a></td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6'>Erreur lors du chargement des commandes.</td></tr>";
                                error_log("Erreur chargement commandes: " . $e->getMessage());
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-products-content-NEW" class="admin-content-section">
                <div class="admin-content-header">
                    <h1>Gestion des Produits</h1>
                    <button class="cta-button secondary" id="add-product-button"><i class="fas fa-plus"></i> Ajouter un produit</button>
                </div>
                <div class="search-bar-container" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
                    <input type="text" id="product-search-input" class="form-control" style="min-width:180px;" placeholder="Rechercher...">

                    <select id="stock-filter-select" class="form-control" style="min-width:150px;">
                        <option value="">Stock</option>
                        <option value="0">Stock épuisé</option>
                        <option value="1-5">Stock 1–5</option>
                        <option value="6-20">Stock 6–20</option>
                        <option value="21+">Stock 21+</option>
                    </select>

                    <select id="statut-filter-select" class="form-control" style="min-width:140px;">
                        <option value="">Statut</option>
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>

                    <select id="saison-filter-select" class="form-control" style="min-width:140px;">
                        <option value="">Saison</option>
                        <option value="Été">Été</option>
                        <option value="Hiver">Hiver</option>
                        <option value="4 Saisons">4 Saisons</option>
                    </select>

                    <select id="brand-filter-select" class="form-control" multiple style="min-width:180px; height:90px;">
                        <option value="michelin">Michelin</option>
                        <option value="continental">Continental</option>
                        <option value="bridgestone">Bridgestone</option>
                        <option value="goodyear">Goodyear</option>
                        <option value="hankook">Hankook</option>
                        <option value="pirelli">Pirelli</option>
                        <option value="nokian">Nokian</option>
                        <option value="firestone">Firestone</option>
                        <option value="uniroyal">Uniroyal</option>
                        <option value="dunlop">Dunlop</option>
                        <option value="bf goodrich">BF Goodrich</option>
                        <option value="kleber">Kleber</option>
                        <option value="fulda">Fulda</option>
                        <option value="sava">Sava</option>
                        <option value="kumho">Kumho</option>
                        <option value="vredestein">Vredestein</option>
                        <option value="semperit">Semperit</option>
                        <option value="cooper">Cooper</option>
                        <option value="avon">Avon</option>
                    </select>

                    <button id="reset-filters-btn" class="btn btn-outline-secondary">Réinitialiser</button>
                </div>

                <div style="margin-top:1rem; display:flex; gap:1rem;">
                    <select id="bulk-action-select" class="form-control" style="max-width:200px;">
                        <option value="">Action groupée</option>
                        <option value="activate">Activer</option>
                        <option value="deactivate">Désactiver</option>
                        <option value="delete">Supprimer</option>
                    </select>
                    <button id="apply-bulk-action" class="btn btn-primary">Appliquer</button>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-products"></th>
                                <th style="width: 60px;">Image</th>
                                <th data-sort="nom">Nom du Produit <span class="sort-arrow" id="sort-arrow-nom"></span></th>
                                <th data-sort="taille">Taille <span class="sort-arrow" id="sort-arrow-taille"></span></th>
                                <th data-sort="saison">Saison <span class="sort-arrow" id="sort-arrow-saison"></span></th>
                                <th data-sort="prix">Prix <span class="sort-arrow" id="sort-arrow-prix"></span></th>
                                <th data-sort="stock">Stock <span class="sort-arrow" id="sort-arrow-stock"></span></th>
                                <th data-sort="statut">Statut <span class="sort-arrow" id="sort-arrow-statut"></span></th>
                                <th style="width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="products-table-body-NEW">
                            <?php if (empty($liste_pneus)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 1rem;">Aucun pneu trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($liste_pneus as $pneu): ?>
                                    <tr data-product-search="<?php echo htmlspecialchars(strtolower($pneu['nom'] . ' ' . $pneu['taille'] . ' ' . $pneu['saison'] . ' ' . str_replace('.', ',', $pneu['prix'])), ENT_QUOTES, 'UTF-8'); ?>" data-product-stock="<?php echo (int)$pneu['stock_disponible']; ?>" data-product-brand="<?php echo strtolower($pneu['nom']); ?>">
                                        <td><input type="checkbox" class="select-product-checkbox" data-product-id="<?php echo $pneu['id']; ?>"></td>
                                        <td><img src="<?php echo sanitize_html_output(!empty($pneu['image']) ? $pneu['image'] : 'https://placehold.co/50x50/121212/ffdd03?text=Image'); ?>" alt="<?php echo sanitize_html_output($pneu['nom']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"></td>
                                        <td data-key="nom"><?php echo sanitize_html_output($pneu['nom']); ?></td>
                                        <td data-key="taille"><?php echo sanitize_html_output($pneu['taille']); ?></td>
                                        <td data-key="saison"><?php echo sanitize_html_output($pneu['saison']); ?></td>
                                        <td data-key="prix"><?php echo sanitize_html_output(number_format((float)$pneu['prix'], 2, ',', ' ')); ?> €</td>
                                        <td data-key="stock"><?php echo sanitize_html_output($pneu['stock_disponible']); ?></td>
                                        <td data-key="statut"><span class="<?php echo $pneu['est_actif'] ? 'status-delivered' : 'status-cancelled'; ?>"><?php echo $pneu['est_actif'] ? 'Actif' : 'Inactif'; ?></span></td>
                                        <td class="actions">
                                            <a href="#" class="admin-action-btn edit-btn edit-product-btn-js" data-product='<?php echo htmlspecialchars(json_encode($pneu), ENT_QUOTES, 'UTF-8'); ?>'>Modifier</a>
                                            <form method="POST" action="admin_dashboard.php#admin-products-content-NEW" style="display: inline-block;"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><input type="hidden" name="id_pneu" value="<?php echo $pneu['id']; ?>"><input type="hidden" name="action" value="toggle_status"><button type="submit" class="admin-action-btn toggle-status-btn <?php echo $pneu['est_actif'] ? 'status-active-btn' : 'status-inactive-btn'; ?>"><?php echo $pneu['est_actif'] ? 'Désactiver' : 'Activer'; ?></button></form>
                                            <form method="POST" action="admin_dashboard.php#admin-products-content-NEW" style="display: inline-block;" onsubmit="return confirm('Êtes-vous sûr ?');"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><input type="hidden" name="id_pneu" value="<?php echo $pneu['id']; ?>"><input type="hidden" name="action" value="delete_pneu"><button type="submit" class="admin-action-btn delete-btn">Supprimer</button></form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div id="pagination-controls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                        <span id="product-count-display"></span>
                        <div>
                            <button id="prev-page-btn" class="btn btn-outline-secondary">&laquo; Précédent</button>
                            <span id="page-indicator" style="margin: 0 1rem;"></span>
                            <button id="next-page-btn" class="btn btn-outline-secondary">Suivant &raquo;</button>
                        </div>
                    </div>

                </div>
            </div>

            <div id="admin-promo-codes-content" class="admin-content-section">
            </div>

            <div id="admin-clients-content" class="admin-content-section">
                <div class="admin-content-header">
                    <h1>Liste des Clients</h1>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Date inscription</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt_clients = $pdo->query("SELECT id_utilisateur, nom, prenom, email, telephone, date_inscription, derniere_connexion FROM Utilisateurs WHERE est_admin = 0 OR est_admin IS NULL ORDER BY date_inscription DESC");
                                while ($client = $stmt_clients->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . sanitize_html_output($client['id_utilisateur']) . "</td>";
                                    echo "<td>" . sanitize_html_output(trim($client['prenom'] . ' ' . $client['nom'])) . "</td>";
                                    echo "<td>" . sanitize_html_output($client['email']) . "</td>";
                                    echo "<td>" . sanitize_html_output($client['telephone'] ?: 'Non fourni') . "</td>";
                                    echo "<td>" . sanitize_html_output(date("d/m/Y H:i", strtotime($client['date_inscription']))) . "</td>";
                                    echo "<td>" . ($client['derniere_connexion'] ? sanitize_html_output(date("d/m/Y H:i", strtotime($client['derniere_connexion']))) : 'Jamais') . "</td>";
                                    echo "<td><a href='admin_client_detail.php?id_client=" . urlencode($client['id_utilisateur']) . "' class='admin-action-btn view-btn'>Voir</a></td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7'>Erreur lors du chargement des clients.</td></tr>";
                                error_log("Erreur chargement clients: " . $e->getMessage());
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="admin-settings-content" class="admin-content-section">
                <div class="admin-content-header">
                    <h1>Paramètres du Site</h1>
                </div>
                <form id="admin-settings-form" class="admin-settings-form" method="POST" action="admin_dashboard.php#admin-settings-content">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="detail-card">
                        <h2>Informations Administrateur</h2>
                        <div class="form-group">
                            <label for="setting-admin-firstname">Prénom de l'administrateur:</label>
                            <input type="text" id="setting-admin-firstname" name="settings[admin_firstname]" value="<?php echo sanitize_html_output($settings['admin_firstname'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="setting-admin-name">Nom de l'administrateur:</label>
                            <input type="text" id="setting-admin-name" name="settings[admin_name]" value="<?php echo sanitize_html_output($settings['admin_name'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="setting-admin-email">Email de l'administrateur (pour notifications):</label>
                            <input type="email" id="setting-admin-email" name="settings[admin_email]" value="<?php echo sanitize_html_output($settings['admin_email'] ?? ''); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="detail-card">
                        <h2>Configuration des Paiements (Stripe)</h2>
                        <p>Laissez vide si non utilisé. Les clés sont stockées de manière sécurisée.</p>
                        <div class="form-group">
                            <label for="setting-stripe-pk">Stripe Clé Publique (Publishable Key):</label>
                            <input type="text" id="setting-stripe-pk" name="settings[stripe_pk]" value="<?php echo sanitize_html_output($settings['stripe_pk'] ?? ''); ?>" class="form-control" placeholder="pk_test_...">
                        </div>
                        <div class="form-group">
                            <label for="setting-stripe-sk">Stripe Clé Secrète (Secret Key):</label>
                            <input type="password" id="setting-stripe-sk" name="settings[stripe_sk]" value="<?php echo sanitize_html_output($settings['stripe_sk'] ?? ''); ?>" class="form-control" placeholder="sk_test_... ou rk_test_...">
                            <small>La clé secrète n'est affichée qu'une seule fois. Si vous la modifiez, entrez la nouvelle clé.</small>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h2>Configuration des Transporteurs</h2>
                        <div id="carriers-container">
                            <?php
                            $carriers = isset($settings['carriers']) && is_array($settings['carriers']) ? $settings['carriers'] : [['name' => '', 'price' => '', 'delay' => '']];
                            foreach ($carriers as $index => $carrier): ?>
                                <div class="carrier-group" data-index="<?php echo $index; ?>">
                                    <h4>Transporteur <?php echo $index + 1; ?> <?php if ($index > 0) echo '<button type="button" class="remove-carrier-btn" style="font-size:0.8em;padding:0.2em 0.5em; margin-left:10px;">&times; Supprimer</button>'; ?></h4>
                                    <div class="form-group">
                                        <label for="carrier-name-<?php echo $index; ?>">Nom du transporteur:</label>
                                        <input type="text" id="carrier-name-<?php echo $index; ?>" name="settings[carriers][<?php echo $index; ?>][name]" value="<?php echo sanitize_html_output($carrier['name'] ?? ''); ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="carrier-price-<?php echo $index; ?>">Prix (€):</label>
                                        <input type="number" step="0.01" id="carrier-price-<?php echo $index; ?>" name="settings[carriers][<?php echo $index; ?>][price]" value="<?php echo sanitize_html_output($carrier['price'] ?? ''); ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="carrier-delay-<?php echo $index; ?>">Délai de livraison (ex: 2-3 jours ouvrés):</label>
                                        <input type="text" id="carrier-delay-<?php echo $index; ?>" name="settings[carriers][<?php echo $index; ?>][delay]" value="<?php echo sanitize_html_output($carrier['delay'] ?? ''); ?>" class="form-control">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="add-carrier-btn" class="cta-button secondary" style="margin-top:1rem;"><i class="fas fa-plus"></i> Ajouter un transporteur</button>
                    </div>

                    <div style="text-align: right; margin-top:1.5rem;">
                        <button type="submit" class="cta-button">Enregistrer les Paramètres</button>
                    </div>
                </form>
            </div>


            <div id="admin-garages-section" class="admin-content-section">
                <div class="admin-content-header">
                    <h1>Gestion des Garages Partenaires</h1>
                </div>

                <?php

                $candidatures_garages_section = [];
                try {
                    $stmt_candidats_g_section = $pdo->query("SELECT * FROM GaragesCandidats WHERE statut = 'en_attente' ORDER BY date_soumission DESC");
                    if ($stmt_candidats_g_section) $candidatures_garages_section = $stmt_candidats_g_section->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) { 
                }


                $partenaires_garages_section = [];
                try {
                    $stmt_partenaires_g_section = $pdo->query("SELECT * FROM GaragesPartenaires ORDER BY nom_garage ASC");
                    if ($stmt_partenaires_g_section) $partenaires_garages_section = $stmt_partenaires_g_section->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                }
                ?>

                <div class="admin-table-container detail-card" id="candidatures-garages-sub">
                    <h2>Candidatures en attente</h2>
                    <?php if (empty($candidatures_garages_section)): ?>
                        <p>Aucune nouvelle candidature pour le moment.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nom Garage</th>
                                    <th>Contact</th>
                                    <th>Services Proposés</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidatures_garages_section as $candidat_g): ?>
                                    <tr>
                                        <td><?php echo sanitize_html_output($candidat_g['nom_garage']); ?></td>
                                        <td>
                                            Email: <?php echo sanitize_html_output($candidat_g['email_contact']); ?><br>
                                            Tél: <?php echo sanitize_html_output($candidat_g['telephone_garage']); ?><br>
                                            Adresse: <?php echo sanitize_html_output($candidat_g['adresse_garage']); ?>
                                        </td>
                                        <td><?php echo nl2br(sanitize_html_output($candidat_g['services_proposes'])); ?></td>
                                        <td><?php echo nl2br(sanitize_html_output($candidat_g['message_partenaire'])); ?></td>
                                        <td><?php echo date("d/m/Y H:i", strtotime($candidat_g['date_soumission'])); ?></td>
                                        <td class="actions">
                                            <button type="button" class="admin-action-btn edit-btn open-approve-garage-modal"
                                                data-id="<?php echo $candidat_g['id_candidat']; ?>"
                                                data-nom="<?php echo sanitize_html_output($candidat_g['nom_garage']); ?>"
                                                data-adresse="<?php echo sanitize_html_output($candidat_g['adresse_garage']); ?>"
                                                data-tel="<?php echo sanitize_html_output($candidat_g['telephone_garage']); ?>"
                                                data-email="<?php echo sanitize_html_output($candidat_g['email_contact']); ?>"
                                                data-services="<?php echo sanitize_html_output($candidat_g['services_proposes']); ?>">Approuver</button>
                                            <form method="POST" action="admin_dashboard.php#admin-garages-section" class="form-admin-inline" onsubmit="return confirm('Rejeter cette candidature ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="action" value="reject_candidature_garage">
                                                <input type="hidden" name="id_candidat_garage" value="<?php echo $candidat_g['id_candidat']; ?>">
                                                <button type="submit" class="admin-action-btn delete-btn">Rejeter</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="admin-table-container detail-card" id="partenaires-garages-sub" style="margin-top: 2rem;">
                    <h2>Garages Partenaires Approuvés</h2>
                    <?php if (empty($partenaires_garages_section)): ?>
                        <p>Aucun garage partenaire approuvé pour le moment.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nom Garage</th>
                                    <th>Contact</th>
                                    <th>Adresse</th>
                                    <th>Coordonnées GPS</th>
                                    <th>Visible</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partenaires_garages_section as $partenaire_g): ?>
                                    <tr>
                                        <td><?php echo sanitize_html_output($partenaire_g['nom_garage']); ?></td>
                                        <td>
                                            Email: <?php echo sanitize_html_output($partenaire_g['email']); ?><br>
                                            Tél: <?php echo sanitize_html_output($partenaire_g['telephone']); ?>
                                        </td>
                                        <td><?php echo sanitize_html_output($partenaire_g['adresse_complete']); ?></td>
                                        <td>
                                            Lat: <?php echo sanitize_html_output($partenaire_g['latitude'] ?? 'N/A'); ?><br>
                                            Lon: <?php echo sanitize_html_output($partenaire_g['longitude'] ?? 'N/A'); ?>
                                        </td>
                                        <td><?php echo $partenaire_g['est_visible'] ? 'Oui' : 'Non'; ?></td>
                                        <td class="actions">
                                            <button type="button" class="admin-action-btn edit-btn open-edit-garage-modal"
                                                data-id="<?php echo $partenaire_g['id_garage']; ?>"
                                                data-nom="<?php echo sanitize_html_output($partenaire_g['nom_garage']); ?>"
                                                data-adresse="<?php echo sanitize_html_output($partenaire_g['adresse_complete']); ?>"
                                                data-tel="<?php echo sanitize_html_output($partenaire_g['telephone']); ?>"
                                                data-email="<?php echo sanitize_html_output($partenaire_g['email']); ?>"
                                                data-services="<?php echo sanitize_html_output($partenaire_g['services_offerts']); ?>"
                                                data-description="<?php echo sanitize_html_output($partenaire_g['description_courte']); ?>"
                                                data-lat="<?php echo sanitize_html_output($partenaire_g['latitude']); ?>"
                                                data-lon="<?php echo sanitize_html_output($partenaire_g['longitude']); ?>"
                                                data-horaires="<?php echo sanitize_html_output($partenaire_g['horaires_ouverture']); ?>"
                                                data-website="<?php echo sanitize_html_output($partenaire_g['url_website']); ?>"
                                                data-visible="<?php echo $partenaire_g['est_visible'] ? '1' : '0'; ?>">Modifier</button>
                                            <form method="POST" action="admin_dashboard.php#admin-garages-section" class="form-admin-inline" onsubmit="return confirm('Supprimer ce partenaire ? Cette action est irréversible.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="action" value="delete_partenaire_garage">
                                                <input type="hidden" name="id_garage" value="<?php echo $partenaire_g['id_garage']; ?>">
                                                <button type="submit" class="admin-action-btn delete-btn">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <footer class="admin-footer">
        <p>&copy; <span id="current-year-admin-dash"></span> Ouipneu.fr - Interface d'Administration</p>
    </footer>


    <div id="product-modal-overlay">
        <div id="product-modal-content">
            <div class="product-modal-header">
                <h2 id="product-form-title-NEW">Ajouter un Produit</h2>
                <button type="button" id="close-product-modal-btn" aria-label="Fermer">&times;</button>
            </div>
            <div class="product-modal-body">
                <form id="add-product-form-NEW" method="POST" action="admin_dashboard.php#admin-products-content-NEW" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_edit_product">
                    <input type="hidden" name="id_pneu_edit" id="edit-product-id-NEW" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="current_image_path" id="current-image-path-NEW" value="">
                    <div class="form-group"><label for="product-nom">Nom <span style="color:red;">*</span></label><input type="text" id="product-nom" name="nom" required class="form-control"></div>
                    <div class="form-group"><label for="product-taille">Taille <span style="color:red;">*</span></label><input type="text" id="product-taille" name="taille" required class="form-control"></div>
                    <div class="form-group"><label for="product-saison">Saison <span style="color:red;">*</span></label><select id="product-saison" name="saison" required class="form-control">
                            <option value="Été">Été</option>
                            <option value="Hiver">Hiver</option>
                            <option value="4 Saisons">4 Saisons</option>
                        </select></div>
                    <div class="form-group"><label for="product-prix">Prix (€) <span style="color:red;">*</span></label><input type="text" id="product-prix" name="prix" required class="form-control"></div>
                    <div class="form-group"><label for="product-stock">Stock <span style="color:red;">*</span></label><input type="number" id="product-stock" name="stock_disponible" min="0" required class="form-control"></div>
                    <div class="form-group"><label for="product-image">Image</label><input type="file" id="product-image" name="image_produit" class="form-control" accept="image/png, image/jpeg, image/webp"></div>
                    <div class="form-group"><label for="product-description">Description</label><textarea id="product-description" name="description" rows="3" class="form-control"></textarea></div>
                    <div class="form-group"><label for="product-specifications">Spécifications</label><input type="text" id="product-specifications" name="specifications" class="form-control"></div>
                    <div class="form-group"><label>Statut</label>
                        <div><input type="radio" id="product-status-active-NEW" name="est_actif" value="1" checked><label for="product-status-active-NEW">Actif</label><input type="radio" id="product-status-inactive-NEW" name="est_actif" value="0"><label for="product-status-inactive-NEW">Inactif</label></div>
                    </div>
                </form>
            </div>
            <div class="product-modal-footer">
                <button type="button" id="cancel-add-product-NEW" class="cta-button secondary">Annuler</button>
                <button type="submit" form="add-product-form-NEW" class="cta-button">Enregistrer</button>
            </div>
        </div>
    </div>


    <div id="garage-admin-modal-overlay" class="modal-admin" style="display: none;">
        <div class="modal-admin-content">
            <div class="product-modal-header">
                <h2 id="garage-modal-title">Approuver/Modifier Garage</h2>
                <button type="button" class="close-garage-admin-modal" aria-label="Fermer">&times;</button>
            </div>
            <div class="product-modal-body">
                <form id="garage-admin-form" method="POST" action="admin_dashboard.php#admin-garages-section">
                    <input type="hidden" name="action" id="garage-modal-action" value="">
                    <input type="hidden" name="id_candidat_modal_garage" id="garage-modal-id-candidat" value="">
                    <input type="hidden" name="id_garage_modal" id="garage-modal-id-garage" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="garage-modal-nom-garage">Nom du Garage:</label>
                        <input type="text" id="garage-modal-nom-garage" name="nom_garage_modal" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-adresse-complete">Adresse Complète:</label>
                        <textarea id="garage-modal-adresse-complete" name="adresse_complete_modal" rows="3" required class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-telephone">Téléphone:</label>
                        <input type="tel" id="garage-modal-telephone" name="telephone_modal" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-email">Email:</label>
                        <input type="email" id="garage-modal-email" name="email_modal" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-services-offerts">Services Offerts:</label>
                        <textarea id="garage-modal-services-offerts" name="services_offerts_modal" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-description-courte">Description Courte (pour page publique):</label>
                        <textarea id="garage-modal-description-courte" name="description_courte_modal" rows="3" class="form-control"></textarea>
                    </div>
                    <div style="display:flex; gap: 1rem;">
                        <div class="form-group" style="flex:1;">
                            <label for="garage-modal-latitude">Latitude (ex: 48.8566):</label>
                            <input type="number" step="any" id="garage-modal-latitude" name="latitude_modal" class="form-control">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label for="garage-modal-longitude">Longitude (ex: 2.3522):</label>
                            <input type="number" step="any" id="garage-modal-longitude" name="longitude_modal" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-horaires-ouverture">Horaires d'ouverture (texte libre):</label>
                        <input type="text" id="garage-modal-horaires-ouverture" name="horaires_ouverture_modal" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="garage-modal-url-website">Site Web (URL complète):</label>
                        <input type="text" id="garage-modal-url-website" name="url_website_modal" class="form-control">
                    </div>
                    <div class="form-group" id="garage-modal-visibility-field" style="display:none;">
                        <label for="garage-modal-est-visible">Visible sur le site public:</label>
                        <select id="garage-modal-est-visible" name="est_visible_modal" class="form-control">
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="product-modal-footer">
                <button type="button" id="cancel-garage-admin-action" class="cta-button secondary close-garage-admin-modal">Annuler</button>
                <button type="submit" form="garage-admin-form" class="cta-button">Enregistrer</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const dateFilterButtons = document.querySelectorAll('.date-filter-btn');
            let salesChart = null;

            function updateDashboard(period) {
                fetch(`get_dashboard_stats.php?period=${period}`)
                    .then(response => response.json())
                    .then(data => {

                        document.querySelector('[data-stat="ca"]').textContent = `${data.stats.chiffre_affaires.toFixed(2).replace('.', ',')} €`;
                        document.querySelector('[data-stat="orders"]').textContent = data.stats.commandes;
                        document.querySelector('[data-stat="clients"]').textContent = data.stats.nouveaux_clients;
                        document.querySelector('[data-stat="stock"]').textContent = data.stats.stock_faible;


                        const activityList = document.getElementById('recent-activity-list');
                        activityList.innerHTML = '';
                        if (data.recent_activity.length > 0) {
                            data.recent_activity.forEach(item => {
                                const link = item.type === 'commande' ?
                                    `admin_order_detail.php?id_commande=${item.id}` :
                                    `admin_client_detail.php?id_client=${item.id}`;
                                const date = new Date(item.date).toLocaleDateString('fr-FR', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric'
                                });
                                activityList.innerHTML += `<li><a href="${link}">${item.description}</a> <span class="activity-date">- ${date}</span></li>`;
                            });
                        } else {
                            activityList.innerHTML = '<li>Aucune activité récente.</li>';
                        }


                        const ordersToProcessTable = document.querySelector('#orders-to-process-table tbody');
                        ordersToProcessTable.innerHTML = '';
                        if (data.orders_to_process.length > 0) {
                            data.orders_to_process.forEach(order => {
                                const orderRow = `
                                <tr>
                                    <td>#${order.id_commande.substring(0, 8)}...</td>
                                    <td>${order.nom_client}</td>
                                    <td>${new Date(order.date_commande).toLocaleDateString('fr-FR')}</td>
                                    <td>${order.montant_total_ttc.toFixed(2).replace('.', ',')} €</td>
                                    <td><a href="admin_order_detail.php?id_commande=${order.id_commande}" class="admin-action-btn view-btn">Voir</a></td>
                                </tr>`;
                                ordersToProcessTable.innerHTML += orderRow;
                            });
                        } else {
                            ordersToProcessTable.innerHTML = '<tr><td colspan="5" style="text-align:center;">Aucune commande à traiter.</td></tr>';
                        }



                        const ctx = document.getElementById('salesChart').getContext('2d');
                        if (salesChart) {
                            salesChart.destroy();
                        }
                        salesChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.chart.labels,
                                datasets: [{
                                    label: 'Ventes',
                                    data: data.chart.data,
                                    borderColor: 'var(--accent-primary)',
                                    backgroundColor: 'rgba(255, 221, 3, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            color: 'var(--text-secondary)'
                                        },
                                        grid: {
                                            color: 'var(--border-color)'
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            color: 'var(--text-secondary)'
                                        },
                                        grid: {
                                            color: 'var(--border-color)'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    })
                    .catch(error => console.error('Erreur lors de la mise à jour du tableau de bord:', error));
            }

            dateFilterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    dateFilterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    updateDashboard(button.dataset.period);
                });
            });


            updateDashboard('today');



            document.getElementById('current-year-admin-dash').textContent = new Date().getFullYear();


            const productSearchInput = document.getElementById('product-search-input');
            const productsTableBody = document.getElementById('products-table-body-NEW');

            function normalizeText(str) {
                return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9,./\s]/gi, '');
            }

            function detectTireSizeFormat(input) {
                const parts = input.match(/\d{3}[\s/-]?\d{2}[\s/-]?\d{2}/);
                if (!parts) return null;

                const match = parts[0].match(/(\d{3})[\s/-]?(\d{2})[\s/-]?(\d{2})/);
                if (match) {
                    return `${match[1]}/${match[2]}R${match[3]}`;
                }
                return null;
            }

            function applyProductFilters() {
                const inputValue = normalizeText(productSearchInput.value.trim());
                const searchTerms = inputValue.split(/\s+/).filter(Boolean);
                const stockFilter = document.getElementById('stock-filter-select').value;
                const statutFilter = document.getElementById('statut-filter-select').value;
                const saisonFilter = document.getElementById('saison-filter-select').value;
                const brandSelect = document.getElementById('brand-filter-select');
                const selectedBrands = Array.from(brandSelect.selectedOptions).map(opt => normalizeText(opt.value));
                const normalizedSize = detectTireSizeFormat(productSearchInput.value);
                const rows = productsTableBody.querySelectorAll('tr');

                rows.forEach(row => {

                    row.classList.remove('matched-row');
                    const dataset = normalizeText(row.dataset.productSearch || '');
                    const stock = parseInt(row.dataset.productStock, 10) || 0;
                    const taille = normalizeText(row.querySelector('td:nth-child(3)').textContent);
                    const statut = row.querySelector('td:nth-child(7)').textContent.trim();
                    const saison = row.querySelector('td:nth-child(4)').textContent.trim();
                    const brand = normalizeText(row.dataset.productBrand || '');

                    let matchSearch = true;
                    if (normalizedSize) {
                        matchSearch = taille === normalizeText(normalizedSize);
                    } else {
                        matchSearch = searchTerms.every(term => dataset.includes(term));
                    }

                    let matchStock = true;
                    switch (stockFilter) {
                        case "0":
                            matchStock = stock === 0;
                            break;
                        case "1-5":
                            matchStock = stock >= 1 && stock <= 5;
                            break;
                        case "6-20":
                            matchStock = stock >= 6 && stock <= 20;
                            break;
                        case "21+":
                            matchStock = stock > 20;
                            break;
                    }

                    let matchStatut = !statutFilter || (statutFilter === "1" && statut === "Actif") || (statutFilter === "0" && statut === "Inactif");
                    let matchSaison = !saisonFilter || saison === saisonFilter;
                    let matchBrand = selectedBrands.length === 0 || selectedBrands.some(selected => brand.includes(selected));

                    if (matchSearch && matchStock && matchStatut && matchSaison && matchBrand) {
                        row.classList.add('matched-row');
                    }

                    row.style.display = 'none';
                });
            }

            const PRODUCTS_PER_PAGE = 10;
            let currentPage = 1;
            let filteredRows = [];

            function updateProductDisplay() {

                const allRows = Array.from(productsTableBody.querySelectorAll('tr.matched-row'));
                filteredRows = allRows;
                const total = filteredRows.length;
                const totalPages = Math.ceil(total / PRODUCTS_PER_PAGE);
                currentPage = Math.min(currentPage, totalPages || 1);


                productsTableBody.querySelectorAll('tr').forEach(row => row.style.display = 'none');
                const start = (currentPage - 1) * PRODUCTS_PER_PAGE;
                const end = start + PRODUCTS_PER_PAGE;


                filteredRows.slice(start, end).forEach(row => row.style.display = '');

                document.getElementById('product-count-display').textContent = `${Math.min(total, end) - start} produits affichés sur ${total}`;
                document.getElementById('page-indicator').textContent = `Page ${currentPage} sur ${totalPages || 1}`;
                document.getElementById('prev-page-btn').disabled = currentPage === 1;
                document.getElementById('next-page-btn').disabled = currentPage === totalPages;
            }

            document.getElementById('prev-page-btn').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    updateProductDisplay();
                }
            });

            document.getElementById('next-page-btn').addEventListener('click', () => {
                const totalPages = Math.ceil(filteredRows.length / PRODUCTS_PER_PAGE);
                if (currentPage < totalPages) {
                    currentPage++;
                    updateProductDisplay();
                }
            });

            const originalApplyProductFilters = applyProductFilters;
            applyProductFilters = function() {
                originalApplyProductFilters();
                updateProductDisplay();
            };

            applyProductFilters();

            if (productSearchInput && productsTableBody) {
                productSearchInput.addEventListener('input', applyProductFilters);
                document.getElementById('stock-filter-select').addEventListener('change', applyProductFilters);
                document.getElementById('statut-filter-select').addEventListener('change', applyProductFilters);
                document.getElementById('saison-filter-select').addEventListener('change', applyProductFilters);
            }

            document.getElementById('brand-filter-select').addEventListener('change', applyProductFilters);


            const selectAllProductsCheckbox = document.getElementById('select-all-products');
            if (selectAllProductsCheckbox) {
                selectAllProductsCheckbox.addEventListener('change', function() {
                    document.querySelectorAll('.select-product-checkbox').forEach(cb => {
                        if (cb.closest('tr').style.display !== 'none') {
                            cb.checked = this.checked;
                        }
                    });
                });
            }


            const resetFiltersBtn = document.getElementById('reset-filters-btn');
            if (resetFiltersBtn) {
                resetFiltersBtn.addEventListener('click', () => {
                    productSearchInput.value = '';
                    document.getElementById('stock-filter-select').value = '';
                    document.getElementById('statut-filter-select').value = '';
                    document.getElementById('saison-filter-select').value = '';
                    document.getElementById('brand-filter-select').selectedIndex = -1;
                    applyProductFilters();
                });
            }


            const applyBulkActionBtn = document.getElementById('apply-bulk-action');
            if (applyBulkActionBtn) {
                applyBulkActionBtn.addEventListener('click', () => {
                    const selectedAction = document.getElementById('bulk-action-select').value;
                    const selectedIds = Array.from(document.querySelectorAll('.select-product-checkbox:checked')).map(cb => cb.dataset.productId);

                    if (!selectedAction || selectedIds.length === 0) {
                        alert("Sélectionnez une action et au moins un produit.");
                        return;
                    }

                    if (selectedAction === 'delete' && !confirm("Confirmer la suppression des produits sélectionnés ?")) {
                        return;
                    }


                    fetch('admin_bulk_action.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: selectedAction,
                                ids: selectedIds
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert("Action appliquée avec succès !");
                                location.reload();
                            } else {
                                alert("Erreur : " + (data.message || "Action échouée."));
                            }
                        })
                        .catch(() => alert("Erreur lors de l’envoi de l’action."));
                });
            }


            const adminNavLinks = document.querySelectorAll('.admin-sidebar nav a[data-target], .admin-quick-link[data-target]');
            const adminContentSections = document.querySelectorAll('.admin-content-section');
            const defaultSectionId = 'admin-dashboard-main';

            function switchAdminSection(targetId) {
                adminContentSections.forEach(section => {
                    section.classList.toggle('is-active', section.id === targetId);
                });
                document.querySelectorAll('.admin-sidebar nav a').forEach(link => {
                    link.classList.toggle('active', link.dataset.target === targetId);
                });

                if (window.location.hash !== `#${targetId}`) {
                    window.location.hash = targetId;
                }
            }

            adminNavLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = e.currentTarget.dataset.target;
                    switchAdminSection(targetId);
                });
            });


            const productModalOverlay = document.getElementById('product-modal-overlay');
            const addProductButton = document.getElementById('add-product-button');
            const closeProductModalBtn = document.getElementById('close-product-modal-btn');
            const cancelAddProductBtn = document.getElementById('cancel-add-product-NEW');
            const productForm = document.getElementById('add-product-form-NEW');
            const productFormTitle = document.getElementById('product-form-title-NEW');
            const editProductIdInput = document.getElementById('edit-product-id-NEW');
            const currentImagePathInput = document.getElementById('current-image-path-NEW');


            function showProductModal() {
                if (productModalOverlay) productModalOverlay.style.display = 'flex';
            }

            function hideProductModal() {
                if (productModalOverlay) productModalOverlay.style.display = 'none';
            }

            if (addProductButton) {
                addProductButton.addEventListener('click', () => {
                    if (productForm) productForm.reset();
                    if (productFormTitle) productFormTitle.textContent = 'Ajouter un Nouveau Produit';
                    if (editProductIdInput) editProductIdInput.value = '';
                    if (currentImagePathInput) currentImagePathInput.value = '';

                    const activeStatusRadio = document.getElementById('product-status-active-NEW');
                    if (activeStatusRadio) activeStatusRadio.checked = true;
                    showProductModal();
                });
            }

            document.querySelectorAll('.edit-product-btn-js').forEach(button => {
                button.addEventListener('click', function() {
                    const productData = JSON.parse(this.dataset.product);
                    if (productFormTitle) productFormTitle.textContent = 'Modifier le Produit';
                    if (editProductIdInput) editProductIdInput.value = productData.id;
                    if (currentImagePathInput) currentImagePathInput.value = productData.image || '';

                    document.getElementById('product-nom').value = productData.nom;
                    document.getElementById('product-taille').value = productData.taille;
                    document.getElementById('product-saison').value = productData.saison;
                    document.getElementById('product-prix').value = String(productData.prix).replace('.', ',');
                    document.getElementById('product-stock').value = productData.stock_disponible;
                    document.getElementById('product-description').value = productData.description || '';
                    document.getElementById('product-specifications').value = productData.specifications || '';
                    document.getElementById(productData.est_actif == 1 ? 'product-status-active-NEW' : 'product-status-inactive-NEW').checked = true;
                    showProductModal();
                });
            });

            if (closeProductModalBtn) closeProductModalBtn.addEventListener('click', hideProductModal);
            if (cancelAddProductBtn) cancelAddProductBtn.addEventListener('click', hideProductModal);
            if (productModalOverlay) productModalOverlay.addEventListener('click', e => {
                if (e.target === productModalOverlay) hideProductModal();
            });


            document.querySelectorAll('#admin-products-content-NEW th[data-sort]').forEach(header => {
                let ascending = true;
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    const sortKey = header.getAttribute('data-sort');
                    const rowsArray = Array.from(productsTableBody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');

                    const getCellValue = (row, key) => {
                        switch (key) {
                            case 'nom':
                                return row.cells[1].textContent.trim().toLowerCase();
                            case 'taille':
                                return row.cells[2].textContent.trim().toLowerCase();
                            case 'saison':
                                return row.cells[3].textContent.trim().toLowerCase();
                            case 'prix':
                                return parseFloat(row.cells[4].textContent.replace(',', '.')) || 0;
                            case 'stock':
                                return parseInt(row.cells[5].textContent, 10) || 0;
                            case 'statut':
                                return row.cells[6].textContent.trim().toLowerCase();
                            default:
                                return '';
                        }
                    };

                    rowsArray.sort((a, b) => {
                        const aVal = getCellValue(a, sortKey);
                        const bVal = getCellValue(b, sortKey);
                        if (typeof aVal === 'number' && typeof bVal === 'number') {
                            return ascending ? aVal - bVal : bVal - aVal;
                        } else {
                            return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                        }
                    });


                    rowsArray.forEach(row => productsTableBody.appendChild(row));
                    ascending = !ascending;
                });
            });


            const garageModalOverlay = document.getElementById('garage-admin-modal-overlay');
            const garageModalForm = document.getElementById('garage-admin-form');
            const garageModalTitle = document.getElementById('garage-modal-title');
            const garageModalActionInput = document.getElementById('garage-modal-action');
            const garageModalIdCandidatInput = document.getElementById('garage-modal-id-candidat');
            const garageModalIdGarageInput = document.getElementById('garage-modal-id-garage');
            const garageModalVisibilityField = document.getElementById('garage-modal-visibility-field');

            function showGarageModal() {
                if (garageModalOverlay) garageModalOverlay.style.display = 'flex';
            }

            function hideGarageModal() {
                if (garageModalOverlay) garageModalOverlay.style.display = 'none';
            }

            document.querySelectorAll('.open-approve-garage-modal').forEach(button => {
                button.addEventListener('click', function() {
                    if (garageModalForm) garageModalForm.reset();
                    if (garageModalTitle) garageModalTitle.textContent = 'Approuver Candidature Garage';
                    if (garageModalActionInput) garageModalActionInput.value = 'approve_candidature_garage';
                    if (garageModalIdCandidatInput) garageModalIdCandidatInput.value = this.dataset.id;
                    if (garageModalIdGarageInput) garageModalIdGarageInput.value = '';

                    document.getElementById('garage-modal-nom-garage').value = this.dataset.nom || '';
                    document.getElementById('garage-modal-adresse-complete').value = this.dataset.adresse || '';
                    document.getElementById('garage-modal-telephone').value = this.dataset.tel || '';
                    document.getElementById('garage-modal-email').value = this.dataset.email || '';
                    document.getElementById('garage-modal-services-offerts').value = this.dataset.services || '';

                    document.getElementById('garage-modal-description-courte').value = '';
                    document.getElementById('garage-modal-latitude').value = '';
                    document.getElementById('garage-modal-longitude').value = '';
                    document.getElementById('garage-modal-horaires-ouverture').value = '';
                    document.getElementById('garage-modal-url-website').value = '';

                    if (garageModalVisibilityField) garageModalVisibilityField.style.display = 'none';
                    const estVisibleSelect = document.getElementById('garage-modal-est-visible');
                    if (estVisibleSelect) estVisibleSelect.value = '1';

                    showGarageModal();
                });
            });

            document.querySelectorAll('.open-edit-garage-modal').forEach(button => {
                button.addEventListener('click', function() {
                    if (garageModalForm) garageModalForm.reset();
                    if (garageModalTitle) garageModalTitle.textContent = 'Modifier Garage Partenaire';
                    if (garageModalActionInput) garageModalActionInput.value = 'update_partenaire_garage';
                    if (garageModalIdGarageInput) garageModalIdGarageInput.value = this.dataset.id;
                    if (garageModalIdCandidatInput) garageModalIdCandidatInput.value = '';

                    document.getElementById('garage-modal-nom-garage').value = this.dataset.nom || '';
                    document.getElementById('garage-modal-adresse-complete').value = this.dataset.adresse || '';
                    document.getElementById('garage-modal-telephone').value = this.dataset.tel || '';
                    document.getElementById('garage-modal-email').value = this.dataset.email || '';
                    document.getElementById('garage-modal-services-offerts').value = this.dataset.services || '';
                    document.getElementById('garage-modal-description-courte').value = this.dataset.description || '';
                    document.getElementById('garage-modal-latitude').value = this.dataset.lat || '';
                    document.getElementById('garage-modal-longitude').value = this.dataset.lon || '';
                    document.getElementById('garage-modal-horaires-ouverture').value = this.dataset.horaires || '';
                    document.getElementById('garage-modal-url-website').value = this.dataset.website || '';

                    const estVisibleSelect = document.getElementById('garage-modal-est-visible');
                    if (estVisibleSelect) estVisibleSelect.value = this.dataset.visible === '1' ? '1' : '0';


                    if (garageModalVisibilityField) garageModalVisibilityField.style.display = 'block';
                    showGarageModal();
                });
            });

            document.querySelectorAll('.close-garage-admin-modal').forEach(btn => {
                btn.addEventListener('click', hideGarageModal);
            });
            if (garageModalOverlay) {
                garageModalOverlay.addEventListener('click', e => {
                    if (e.target === garageModalOverlay) hideGarageModal();
                });
            }



            let initialSection = window.location.hash.substring(1) || defaultSectionId;
            if (!document.getElementById(initialSection)) {
                initialSection = defaultSectionId;
            }

            if (document.getElementById(initialSection)) {
                switchAdminSection(initialSection);
            } else {
                switchAdminSection(defaultSectionId);
            }




            const carriersContainer = document.getElementById('carriers-container');
            const addCarrierBtn = document.getElementById('add-carrier-btn');

            function createCarrierGroupHTML(index) {
                return `
                <h4>Transporteur ${index + 1} <button type="button" class="remove-carrier-btn" style="font-size:0.8em;padding:0.2em 0.5em; margin-left:10px;">&times; Supprimer</button></h4>
                <div class="form-group">
                    <label for="carrier-name-${index}">Nom du transporteur:</label>
                    <input type="text" id="carrier-name-${index}" name="settings[carriers][${index}][name]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="carrier-price-${index}">Prix (€):</label>
                    <input type="number" step="0.01" id="carrier-price-${index}" name="settings[carriers][${index}][price]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="carrier-delay-${index}">Délai de livraison (ex: 2-3 jours ouvrés):</label>
                    <input type="text" id="carrier-delay-${index}" name="settings[carriers][${index}][delay]" class="form-control">
                </div>
            `;
            }

            function renumberCarrierGroups() {
                const groups = carriersContainer.querySelectorAll('.carrier-group');
                groups.forEach((group, i) => {
                    group.dataset.index = i;
                    group.querySelector('h4').innerHTML = `Transporteur ${i + 1} ${i > 0 ? '<button type="button" class="remove-carrier-btn" style="font-size:0.8em;padding:0.2em 0.5em; margin-left:10px;">&times; Supprimer</button>' : ''}`;
                    group.querySelectorAll('label, input').forEach(el => {
                        if (el.hasAttribute('for')) {
                            el.setAttribute('for', el.getAttribute('for').replace(/-\d+$/, `-${i}`));
                        }
                        if (el.hasAttribute('id')) {
                            el.id = el.id.replace(/-\d+$/, `-${i}`);
                        }
                        if (el.hasAttribute('name')) {
                            el.name = el.name.replace(/\[\d+\]/, `[${i}]`);
                        }
                    });
                });
            }


            if (addCarrierBtn && carriersContainer) {
                addCarrierBtn.addEventListener('click', () => {
                    const newIndex = carriersContainer.querySelectorAll('.carrier-group').length;
                    const newGroup = document.createElement('div');
                    newGroup.classList.add('carrier-group');
                    newGroup.dataset.index = newIndex;
                    newGroup.innerHTML = createCarrierGroupHTML(newIndex);
                    carriersContainer.appendChild(newGroup);
                    renumberCarrierGroups();
                });
            }

            if (carriersContainer) {
                carriersContainer.addEventListener('click', function(event) {
                    if (event.target.classList.contains('remove-carrier-btn') || event.target.closest('.remove-carrier-btn')) {
                        const groupToRemove = event.target.closest('.carrier-group');
                        if (groupToRemove) {
                            if (carriersContainer.querySelectorAll('.carrier-group').length > 1) {
                                groupToRemove.remove();
                                renumberCarrierGroups();
                            } else {

                                groupToRemove.querySelectorAll('input').forEach(input => input.value = '');

                            }
                        }
                    }
                });

                renumberCarrierGroups();
            }


        });
    </script>
</body>

</html>

<script>
    let currentSortCol = null;
    let currentSortDir = 'asc';

    document.querySelectorAll('th[data-sort]').forEach(th => {
        const sortKey = th.getAttribute('data-sort');
        const arrowSpan = document.getElementById(`sort-arrow-${sortKey}`);
        th.style.cursor = 'pointer';

        th.addEventListener('click', () => {
            if (currentSortCol === sortKey) {
                currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortCol = sortKey;
                currentSortDir = 'asc';
            }


            document.querySelectorAll('.sort-arrow').forEach(el => el.textContent = '');
            if (arrowSpan) arrowSpan.textContent = currentSortDir === 'asc' ? ' ▲' : ' ▼';

            filteredRows.sort((a, b) => {
                const cellA = a.querySelector(`td[data-key="${sortKey}"]`);
                const cellB = b.querySelector(`td[data-key="${sortKey}"]`);
                const valA = cellA ? cellA.textContent.trim().toLowerCase() : '';
                const valB = cellB ? cellB.textContent.trim().toLowerCase() : '';
                return currentSortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            });

            updateProductDisplay();
        });
    });
</script>