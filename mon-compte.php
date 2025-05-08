<?php
require_once 'includes/common.php'; // Inclure common.php avant tout pour gérer la session
require_once 'db_connexion.php';

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

// Essayer d'abord la table Clients
$query = "SELECT * FROM Clients WHERE ClientID = ?";
$stmt = $conn->prepare($query);
$stmt->bindValue(1, $client_id, PDO::PARAM_INT);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    // Essayer la table Utilisateurs
    $query = "SELECT * FROM Utilisateurs WHERE UtilisateurID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $client_id, PDO::PARAM_INT);
    $stmt->execute();
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
    $_SESSION = array();
    session_destroy();
    header("Location: connexion-unifiee.php?error=profile_not_found");
    exit;
}

// Récupérer les commandes du client
// Comme la table Commandes utilise UtilisateurID et non ClientID
$commandes_query = "SELECT c.*, m.NomItem, m.Prix
                   FROM Commandes c 
                   LEFT JOIN Menus m ON c.MenuID = m.MenuID 
                   WHERE c.UtilisateurID = ? 
                   ORDER BY c.DateCommande DESC";
$commandes_stmt = $conn->prepare($commandes_query);
$commandes_stmt->bindValue(1, $client_id, PDO::PARAM_INT);
$commandes_stmt->execute();
$commandes = $commandes_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Récupérer les paiements du client
// La table Paiements est liée aux réservations, qui sont liées aux clients ou utilisateurs
if ($using_utilisateurs_table) {
    // Pour la table Utilisateurs, nous devons vérifier dans les Commandes puis les Reservations
    $paiements_query = "SELECT p.* 
                        FROM Paiements p
                        JOIN Reservations r ON p.ReservationID = r.ReservationID
                        JOIN Commandes c ON r.ReservationID = c.ReservationID
                        WHERE c.UtilisateurID = ? 
                        ORDER BY p.DatePaiement DESC";
} else {
    // Pour la table Clients, on peut directement joindre avec les Reservations
    $paiements_query = "SELECT p.* 
                        FROM Paiements p
                        JOIN Reservations r ON p.ReservationID = r.ReservationID
                        WHERE r.ClientID = ? 
                        ORDER BY p.DatePaiement DESC";
}
$paiements_stmt = $conn->prepare($paiements_query);
$paiements_stmt->bindValue(1, $client_id, PDO::PARAM_INT);
$paiements_stmt->execute();
$paiements = $paiements_stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <?php if (count($paiements) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th>Commande</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paiements as $paiement): ?>
                            <tr>
                                <td><?php echo $paiement['PaiementID']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($paiement['DatePaiement'])); ?></td>
                                <td><?php echo number_format($paiement['Montant'], 2, ',', ' '); ?> €</td>
                                <td><?php echo $paiement['ModePaiement'] ?? 'Non spécifié'; ?></td>
                                <td><?php echo $paiement['Statut']; ?></td>
                                <td>
                                    <a href="detail-commande.php?reservation_id=<?php echo $paiement['ReservationID']; ?>" class="btn">Voir détails</a>
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
