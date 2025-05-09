<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to capture any errors
ob_start();

// Initialiser les variables de débogage
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == 1;
$debug_output = '';

// Fonction pour ajouter des informations de débogage
function add_debug($message) {
    global $debug_mode, $debug_output;
    if ($debug_mode) {
        $debug_output .= $message . "\n";
    }
}

require_once 'includes/common.php';
require_once 'db_connexion.php';

// Vérifier l'état de la connexion PDO
if ($debug_mode) {
    try {
        $conn->query("SELECT 1");
        add_debug("✅ Database connection is active");
    } catch (PDOException $e) {
        add_debug("❌ Database connection error: " . $e->getMessage());
    }
}

// Vérifier si l'utilisateur est connecté en tant que client
if (!isset($_SESSION['client_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: connexion-unifiee.php");
    exit;
}

$client_id = $_SESSION['client_id'];
$success_message = "";
$error_message = "";

// Déterminer si l'utilisateur est dans la table Clients ou Utilisateurs
$user_found = false;
$using_utilisateurs_table = false;

try {
    // Essayer d'abord la table Clients
    $query = "SELECT * FROM Clients WHERE ClientID = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        // Essayer la table Utilisateurs
        $query = "SELECT * FROM Utilisateurs WHERE UtilisateurID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $user_found = true;
            $using_utilisateurs_table = true;
        }
    } else {
        $user_found = true;
        $using_utilisateurs_table = false;
    }

    if (!$user_found) {
        // Journalisation détaillée de l'erreur
        error_log("User not found for ID: " . $client_id . " in either Clients or Utilisateurs tables");
        $_SESSION = array();
        session_destroy();
        header("Location: connexion-unifiee.php?error=profile_not_found");
        exit;
    }
} catch (PDOException $e) {
    // Journalisation détaillée de l'erreur avec le code et la requête
    error_log("Error fetching client data in mon-compte.php: " . $e->getMessage() . 
              " [Code: " . $e->getCode() . "] - ClientID: " . $client_id);
    
    // Déboguer les variables de session
    error_log("Session data: " . print_r($_SESSION, true));
    
    $error_message = "Une erreur est survenue lors de la récupération de vos informations. Veuillez contacter le support.";
    
    // Initialiser client comme tableau vide pour éviter les erreurs
    $client = [];
    $user_found = false;
}

// Récupérer les commandes du client
// Comme la table Commandes utilise UtilisateurID et non ClientID
try {
    $commandes_query = "SELECT c.*, m.NomItem, m.Prix
                       FROM Commandes c 
                       LEFT JOIN Menus m ON c.MenuID = m.MenuID 
                       WHERE c.UtilisateurID = ? 
                       ORDER BY c.DateCommande DESC";
    $commandes_stmt = $conn->prepare($commandes_query);
    $commandes_stmt->bindValue(1, $client_id, PDO::PARAM_INT);
    $commandes_stmt->execute();
    $commandes = $commandes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($debug_mode) {
        add_debug("✅ Récupération des commandes réussie: " . count($commandes) . " commandes trouvées");
    }
} catch (PDOException $e) {
    // Journalisation détaillée de l'erreur
    error_log("Error retrieving commandes in mon-compte.php: " . $e->getMessage() . 
              " [Code: " . $e->getCode() . "] - Query: " . $commandes_query);
    
    if ($debug_mode) {
        add_debug("❌ Erreur lors de la récupération des commandes: " . $e->getMessage());
    }
    
    // Message pour l'utilisateur
    $error_message = "Une erreur est survenue lors de la récupération de vos commandes.";
    
    // Initialiser un tableau vide pour éviter les erreurs
    $commandes = [];
    
    // Plan B : essayer une requête simplifiée
    try {
        if ($debug_mode) {
            add_debug("⚠️ Tentative de requête simplifiée pour les commandes");
        }
        
        $fallback_query = "SELECT CommandeID, DateCommande, Statut, MontantTotal FROM Commandes WHERE UtilisateurID = ? LIMIT 100";
        $fallback_stmt = $conn->prepare($fallback_query);
        $fallback_stmt->bindValue(1, $client_id, PDO::PARAM_INT);
        $fallback_stmt->execute();
        $commandes = $fallback_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($debug_mode) {
            add_debug("✅ Requête simplifiée réussie: " . count($commandes) . " commandes trouvées");
        }
    } catch (PDOException $e2) {
        // Log l'erreur de secours
        error_log("Fallback commandes query error: " . $e2->getMessage());
        
        if ($debug_mode) {
            add_debug("❌ Échec de la requête simplifiée pour les commandes: " . $e2->getMessage());
        }
    }
}

// Traitement de la mise à jour du profil
if (isset($_POST['update_profile'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $adresse = isset($_POST['adresse']) ? $_POST['adresse'] : '';
    $code_postal = isset($_POST['code_postal']) ? $_POST['code_postal'] : '';
    $ville = isset($_POST['ville']) ? $_POST['ville'] : '';
    
    if ($using_utilisateurs_table) {
        // Mettre à jour la table Utilisateurs
        $update_query = "UPDATE Utilisateurs SET Nom = ?, Prenom = ?, Email = ?, Telephone = ?, 
                        Adresse = ?, CodePostal = ?, Ville = ? WHERE UtilisateurID = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $code_postal, $ville, $client_id]);
    } else {
        // Mettre à jour la table Clients
        $update_query = "UPDATE Clients SET Nom = ?, Prenom = ?, Email = ?, Telephone = ? WHERE ClientID = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$nom, $prenom, $email, $telephone, $client_id]);
    }
    
    if ($update_stmt->rowCount() > 0) {
        // Mettre à jour les variables de session
        $_SESSION['client_nom'] = $nom;
        $_SESSION['client_prenom'] = $prenom;
        $_SESSION['client_email'] = $email;
        
        $success_message = "Votre profil a été mis à jour avec succès";
        
        // Recharger les informations client
        if ($using_utilisateurs_table) {
            $query = "SELECT * FROM Utilisateurs WHERE UtilisateurID = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$client_id]);
        } else {
            $query = "SELECT * FROM Clients WHERE ClientID = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$client_id]);
        }
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error_message = "Erreur lors de la mise à jour du profil";
    }
}

// Traitement du changement de mot de passe
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérifier si le client a un mot de passe défini
    if (isset($client['MotDePasse']) && $client['MotDePasse']) {
        if (password_verify($current_password, $client['MotDePasse'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                if ($using_utilisateurs_table) {
                    $password_query = "UPDATE Utilisateurs SET MotDePasse = ? WHERE UtilisateurID = ?";
                    $password_stmt = $conn->prepare($password_query);
                    $password_stmt->execute([$hashed_password, $client_id]);
                } else {
                    $password_query = "UPDATE Clients SET MotDePasse = ? WHERE ClientID = ?";
                    $password_stmt = $conn->prepare($password_query);
                    $password_stmt->execute([$hashed_password, $client_id]);
                }
                
                if ($password_stmt->rowCount() > 0) {
                    $success_message = "Votre mot de passe a été modifié avec succès";
                } else {
                    $error_message = "Erreur lors de la modification du mot de passe";
                }
            } else {
                $error_message = "Les nouveaux mots de passe ne correspondent pas";
            }
        } else {
            $error_message = "Mot de passe actuel incorrect";
        }
    } else {
        $error_message = "Impossible de modifier le mot de passe pour ce compte";
    }
}

// Start output buffering to capture any errors
ob_start();

// Récupérer les paiements du client
// La table Paiements peut être liée soit aux commandes, soit aux réservations
try {
    if ($using_utilisateurs_table) {
        // Pour la table Utilisateurs, chercher tous les paiements liés aux commandes de l'utilisateur
        // Simplifié - sans UNION ALL pour éviter les problèmes de compatibilité de colonnes
        $paiements_query = "SELECT p.*, c.CommandeID, NULL as ReservationID, 'Commande' as TypePaiement,
                            c.DateCommande as DateReference, c.statut, c.MontantTotal, p.DatePaiement
                            FROM Commandes c
                            JOIN Paiements p ON p.CommandeID = c.CommandeID
                            WHERE c.UtilisateurID = ?
                            ORDER BY p.DatePaiement DESC";
        $paiements_stmt = $conn->prepare($paiements_query);
        $paiements_stmt->bindValue(1, $client_id, PDO::PARAM_INT);
    } else {
        // Pour la table Clients, chercher tous les paiements liés aux réservations du client
        $paiements_query = "SELECT p.*, NULL as CommandeID, r.ReservationID, 'Réservation' as TypePaiement,
                            r.DateReservation as DateReference, r.statut, NULL as MontantTotal, p.DatePaiement
                            FROM Reservations r
                            JOIN Paiements p ON p.ReservationID = r.ReservationID
                            WHERE r.ClientID = ? 
                            ORDER BY p.DatePaiement DESC";
        $paiements_stmt = $conn->prepare($paiements_query);
        $paiements_stmt->bindValue(1, $client_id, PDO::PARAM_INT);
    }
    
    $paiements_stmt->execute();
    $paiements = $paiements_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($debug_mode) {
        add_debug("✅ Récupération des paiements réussie: " . count($paiements) . " paiements trouvés");
    }
} catch (PDOException $e) {
    // Journalisation détaillée de l'erreur
    error_log("Error retrieving payments in mon-compte.php: " . $e->getMessage() . 
              " [Code: " . $e->getCode() . "] - Query: " . $paiements_query);
    
    if ($debug_mode) {
        add_debug("❌ Erreur lors de la récupération des paiements: " . $e->getMessage());
    }
    
    // Message pour l'utilisateur
    $error_message = "Une erreur est survenue lors de la récupération de vos paiements.";
    
    // Initialiser un tableau vide pour éviter les erreurs
    $paiements = [];
    
    // Plan B : essayer une requête simplifiée
    try {
        if ($debug_mode) {
            add_debug("⚠️ Tentative de requête simplifiée pour les paiements");
        }
        
        // Requête simplifiée qui devrait fonctionner même si les requêtes complexes échouent
        $fallback_query = "SELECT PaiementID, Montant, MethodePaiement, DatePaiement 
                          FROM Paiements 
                          WHERE ReservationID IN (SELECT ReservationID FROM Reservations WHERE ClientID = ?)
                          OR CommandeID IN (SELECT CommandeID FROM Commandes WHERE UtilisateurID = ?)
                          ORDER BY DatePaiement DESC
                          LIMIT 100";
        
        $fallback_stmt = $conn->prepare($fallback_query);
        $fallback_stmt->execute([$client_id, $client_id]);
        $paiements = $fallback_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($debug_mode) {
            add_debug("✅ Requête simplifiée réussie: " . count($paiements) . " paiements trouvés");
        }
    } catch (PDOException $e2) {
        // Log l'erreur de secours
        error_log("Fallback query error in mon-compte.php: " . $e2->getMessage());
        
        if ($debug_mode) {
            add_debug("❌ Échec de la requête simplifiée: " . $e2->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - Restaurant La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .account-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 25px;
            background-color: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-radius: 10px;
        }
        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .account-header h1 {
            color: #9E2A2B;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            overflow-x: auto;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            background-color: #f5f5f5;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .tab.active {
            background-color: #9E2A2B;
            color: white;
        }
        .tab:hover:not(.active) {
            background-color: #f0f0f0;
        }
        .tab-content {
            display: none;
            padding: 25px;
            background-color: #fff;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .tab-content.active {
            display: block;
        }
        .tab-content h2 {
            color: #9E2A2B;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #9E2A2B;
        }
        .btn {
            padding: 12px 20px;
            background-color: #9E2A2B;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 16px;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #7E1A1B;
        }
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #721c24;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }
        .table th, .table td {
            padding: 12px 15px;
            border: 1px solid #eee;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #333;
        }
        .table tr:hover {
            background-color: #f9f9f9;
        }
        .table a.btn {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        /* Responsive design improvements */
        @media (max-width: 768px) {
            .tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                padding-bottom: 5px;
            }
            
            .tab {
                flex: 0 0 auto;
                white-space: nowrap;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .account-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .account-header .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php if (!empty($debug_output)): ?>
    <div style="background-color: #fee; padding: 10px; margin: 10px; border: 1px solid #f00;">
        <h2>PHP Debug Information:</h2>
        <pre><?php echo htmlspecialchars($debug_output); ?></pre>
    </div>
    <?php endif; ?>

    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="account-container">
        <div class="account-header">
            <h1>Bienvenue, <?php echo htmlspecialchars(($client['Prenom'] ?? '') . ' ' . ($client['Nom'] ?? '')); ?></h1>
            <a href="deconnexion.php" class="btn">Déconnexion</a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab(event, 'profile')" data-tab="profile">Mon Profil</div>
            <div class="tab" onclick="openTab(event, 'orders')" data-tab="orders">Mes Commandes</div>
            <div class="tab" onclick="openTab(event, 'cart')" data-tab="cart">Mon Panier</div>
            <div class="tab" onclick="openTab(event, 'menu')" data-tab="menu">Menu</div>
            <div class="tab" onclick="openTab(event, 'payments')" data-tab="payments">Mes Paiements</div>
        </div>
        
        <div id="profile" class="tab-content active">
            <h2>Mes Informations</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($client['Nom'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($client['Prenom'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($client['Email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['Telephone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <textarea id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($client['Adresse'] ?? ''); ?></textarea>
                </div>
                <?php if ($using_utilisateurs_table): ?>
                <div class="form-row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="code_postal">Code postal</label>
                        <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($client['CodePostal'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($client['Ville'] ?? ''); ?>">
                    </div>
                </div>
                <?php endif; ?>
                <button type="submit" name="update_profile" class="btn">Mettre à jour mon profil</button>
            </form>
            
            <h3 style="margin-top: 30px;">Changer mon mot de passe</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn">Changer mon mot de passe</button>
            </form>
        </div>
        
        <div id="orders" class="tab-content">
            <h2>Mes Commandes</h2>
            <?php if (count($commandes) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $commande): ?>
                            <tr class="commande-row" data-id="<?php echo htmlspecialchars($commande['CommandeID']); ?>">
                                <td><?php echo htmlspecialchars($commande['CommandeID']); ?></td>
                                <td><?php echo isset($commande['DateCommande']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($commande['DateCommande']))) : 'Non disponible'; ?></td>
                                <td><?php echo isset($commande['Statut']) ? htmlspecialchars($commande['Statut']) : 'En cours'; ?></td>
                                <td><?php echo htmlspecialchars(number_format($commande['Prix'] * $commande['Quantite'], 2, ',', ' ')); ?> €</td>
                                <td class="actions">
                                    <a href="detail-commande.php?id=<?php echo htmlspecialchars($commande['CommandeID']); ?>" class="btn">Voir détails</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Vous n'avez pas encore passé de commande.</p>
            <?php endif; ?>
        </div>
        
        <div id="cart" class="tab-content">
            <h2>Mon Panier</h2>
            <p><a href="panier.php" class="btn">Accéder à mon panier</a></p>
        </div>
        
        <div id="menu" class="tab-content">
            <h2>Notre Menu</h2>
            <p><a href="menu.php" class="btn">Voir le menu</a></p>
        </div>
        
        <div id="payments" class="tab-content">
            <h2>Mes Paiements</h2>
            
            <!-- Filtres pour les paiements -->
            <div class="filters-container" style="margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px;">
                <h3 style="margin-top: 0; font-size: 16px; margin-bottom: 10px;">Filtrer mes paiements</h3>
                <form method="get" action="" id="payment-filter-form" style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <label for="payment-type">Type:</label>
                        <select id="payment-type" style="padding: 5px; border-radius: 3px; border: 1px solid #ddd;">
                            <option value="all">Tous</option>
                            <option value="commande">Commandes</option>
                            <option value="reservation">Réservations</option>
                        </select>
                    </div>
                    <div>
                        <label for="payment-date">Date:</label>
                        <select id="payment-date" style="padding: 5px; border-radius: 3px; border: 1px solid #ddd;">
                            <option value="all">Toutes les dates</option>
                            <option value="last-month">Dernier mois</option>
                            <option value="last-3-months">3 derniers mois</option>
                            <option value="last-year">Dernière année</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="apply-filters" class="btn" style="padding: 5px 10px; margin-top: 0;">Filtrer</button>
                    </div>
                </form>
            </div>
            
            <?php if (count($paiements) > 0): ?>
                <table class="table payment-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paiements as $paiement): ?>
                            <?php 
                            // Gestion sécurisée des références
                            $reference_id = isset($paiement['CommandeID']) && !is_null($paiement['CommandeID']) 
                                ? $paiement['CommandeID'] 
                                : (isset($paiement['ReservationID']) && !is_null($paiement['ReservationID']) 
                                    ? $paiement['ReservationID'] 
                                    : 'N/A');
                                    
                            $reference_type = isset($paiement['CommandeID']) && !is_null($paiement['CommandeID']) 
                                ? 'commande' 
                                : (isset($paiement['ReservationID']) && !is_null($paiement['ReservationID']) 
                                    ? 'reservation' 
                                    : 'inconnu');
                                    
                            // Vérifier si nous avons une référence valide
                            $has_valid_reference = $reference_id !== 'N/A';
                            
                            // URL sécurisée
                            $reference_url = $has_valid_reference 
                                ? ($reference_type === 'commande' 
                                    ? "detail-commande.php?id=" . htmlspecialchars($reference_id)
                                    : "detail-commande.php?reservation_id=" . htmlspecialchars($reference_id))
                                : "#";
                                
                            // Type de paiement sécurisé
                            $payment_type = isset($paiement['TypePaiement']) ? htmlspecialchars($paiement['TypePaiement']) : 'Paiement';
                            
                            // Date sécurisée
                            $payment_date = isset($paiement['DatePaiement']) && !empty($paiement['DatePaiement'])
                                ? htmlspecialchars(date('Y-m-d', strtotime($paiement['DatePaiement'])))
                                : date('Y-m-d');
                                
                            // Format date pour affichage
                            $display_date = isset($paiement['DatePaiement']) && !empty($paiement['DatePaiement'])
                                ? htmlspecialchars(date('d/m/Y', strtotime($paiement['DatePaiement'])))
                                : 'Date inconnue';
                            ?>
                            <tr class="payment-row" data-type="<?php echo strtolower($reference_type); ?>" data-date="<?php echo $payment_date; ?>">
                                <td><?php echo htmlspecialchars($paiement['PaiementID'] ?? 'N/A'); ?></td>
                                <td><?php echo $payment_type; ?></td>
                                <td><?php echo $reference_id !== 'N/A' ? '#'.htmlspecialchars($reference_id) : 'N/A'; ?></td>
                                <td><?php echo $display_date; ?></td>
                                <td><?php echo isset($paiement['Montant']) ? number_format($paiement['Montant'], 2, ',', ' ') . ' €' : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($paiement['MethodePaiement'] ?? 'Non spécifié'); ?></td>
                                <td>
                                    <?php if ($has_valid_reference): ?>
                                    <a href="<?php echo $reference_url; ?>" class="btn btn-sm">Voir détails</a>
                                    <?php else: ?>
                                    <span class="btn btn-sm" style="opacity: 0.5; cursor: not-allowed;">Non disponible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Vous n'avez pas encore effectué de paiement.</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            // Masquer tous les contenus d'onglets
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].className = tabcontent[i].className.replace(" active", "");
            }
            
            // Désactiver tous les boutons d'onglets
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            
            // Afficher l'onglet actif et activer le bouton correspondant
            document.getElementById(tabName).className += " active";
            evt.currentTarget.className += " active";
            
            // Mettre à jour l'URL avec le paramètre de l'onglet
            history.replaceState(null, null, '?tab=' + tabName);
        }
        
        // Filtrer les paiements par type et date
        function filterPayments() {
            var typeFilter = document.getElementById('payment-type').value;
            var dateFilter = document.getElementById('payment-date').value;
            var rows = document.querySelectorAll('.payment-row');
            
            var today = new Date();
            var oneMonthAgo = new Date();
            oneMonthAgo.setMonth(today.getMonth() - 1);
            
            var threeMonthsAgo = new Date();
            threeMonthsAgo.setMonth(today.getMonth() - 3);
            
            var oneYearAgo = new Date();
            oneYearAgo.setFullYear(today.getFullYear() - 1);
            
            rows.forEach(function(row) {
                var rowType = row.getAttribute('data-type');
                var rowDate = new Date(row.getAttribute('data-date'));
                var showRow = true;
                
                // Filtre par type
                if (typeFilter !== 'all' && rowType !== typeFilter) {
                    showRow = false;
                }
                
                // Filtre par date
                if (dateFilter === 'last-month' && rowDate < oneMonthAgo) {
                    showRow = false;
                } else if (dateFilter === 'last-3-months' && rowDate < threeMonthsAgo) {
                    showRow = false;
                } else if (dateFilter === 'last-year' && rowDate < oneYearAgo) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
            
            // Afficher un message si aucun résultat
            var paymentTable = document.querySelector('.payment-table');
            var noResultsMsg = document.getElementById('no-filtered-results');
            
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('p');
                noResultsMsg.id = 'no-filtered-results';
                noResultsMsg.style.display = 'none';
                noResultsMsg.textContent = 'Aucun paiement ne correspond à ces critères';
                paymentTable.parentNode.insertBefore(noResultsMsg, paymentTable.nextSibling);
            }
            
            var visibleRows = document.querySelectorAll('.payment-row[style=""]');
            if (visibleRows.length === 0) {
                paymentTable.style.display = 'none';
                noResultsMsg.style.display = 'block';
            } else {
                paymentTable.style.display = '';
                noResultsMsg.style.display = 'none';
            }
        }
        
        // Au chargement de la page, vérifier si un onglet est spécifié dans l'URL
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer le paramètre tab de l'URL
            var urlParams = new URLSearchParams(window.location.search);
            var activeTab = urlParams.get('tab');
            
            // Si un onglet est spécifié et qu'il existe, l'ouvrir
            if (activeTab && document.getElementById(activeTab)) {
                var tabLink = document.querySelector('.tab[data-tab="' + activeTab + '"]');
                if (tabLink) {
                    tabLink.click();
                } else {
                    // Par défaut, ouvrir le premier onglet
                    document.querySelector('.tab').click();
                }
            } else {
                // Par défaut, ouvrir le premier onglet
                document.querySelector('.tab').click();
            }
            
            // Ajouter des écouteurs d'événements pour les filtres de paiement
            var applyFiltersBtn = document.getElementById('apply-filters');
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', filterPayments);
            }
            
            // Appliquer les filtres par défaut au chargement de la page paiements
            var paymentTypeSelect = document.getElementById('payment-type');
            var paymentDateSelect = document.getElementById('payment-date');
            
            if (paymentTypeSelect && paymentDateSelect) {
                paymentTypeSelect.addEventListener('change', function() {
                    if (document.getElementById('payments').classList.contains('active')) {
                        filterPayments();
                    }
                });
                
                paymentDateSelect.addEventListener('change', function() {
                    if (document.getElementById('payments').classList.contains('active')) {
                        filterPayments();
                    }
                });
                
                // Appliquer les filtres par défaut si on est sur l'onglet paiements
                if (activeTab === 'payments') {
                    setTimeout(filterPayments, 100);
                }
            }
        });
        
        // Gestion du menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
</body>
</html>