<?php
session_start();
require_once 'db_connexion.php';
require_once 'includes/common.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header("Location: connexion-unifiee.php");
    exit;
}

$client_id = $_SESSION['client_id'];
$error_message = '';
$commandes = [];
$reservation = null;
$menus = [];
$total = 0;

// Déterminer quelle requête utiliser en fonction des paramètres reçus
if (isset($_GET['id'])) {
    // Cas où on a un ID de commande spécifique
    $commande_id = $_GET['id'];
    
    // Vérifier que cette commande appartient bien à l'utilisateur connecté
    $check_query = "SELECT c.*, m.NomItem, m.Prix
                   FROM Commandes c
                   LEFT JOIN Menus m ON c.MenuID = m.MenuID
                   WHERE c.CommandeID = ? AND c.UtilisateurID = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$commande_id, $client_id]);
    $commandes = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($commandes) > 0) {
        // Récupérer les infos de la réservation
        $reservation_id = $commandes[0]['ReservationID'];
        $reservation_query = "SELECT * FROM Reservations WHERE ReservationID = ?";
        $reservation_stmt = $conn->prepare($reservation_query);
        $reservation_stmt->execute([$reservation_id]);
        $reservation = $reservation_stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error_message = "Cette commande n'existe pas ou ne vous appartient pas.";
    }
} elseif (isset($_GET['reservation_id'])) {
    // Cas où on a un ID de réservation
    $reservation_id = $_GET['reservation_id'];
    
    // Récupérer les infos de la réservation
    $reservation_query = "SELECT * FROM Reservations WHERE ReservationID = ?";
    $reservation_stmt = $conn->prepare($reservation_query);
    $reservation_stmt->execute([$reservation_id]);
    $reservation = $reservation_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reservation) {
        // Vérifier que cette réservation est bien liée à l'utilisateur connecté
        // Soit directement par ClientID, soit via une commande avec UtilisateurID
        $is_owner = false;
        
        // Vérifier si l'utilisateur est directement lié à la réservation (table Clients)
        if (isset($reservation['ClientID']) && $reservation['ClientID'] == $client_id) {
            $is_owner = true;
        } else {
            // Vérifier si l'utilisateur est lié à une commande pour cette réservation (table Utilisateurs)
            $check_query = "SELECT COUNT(*) FROM Commandes WHERE ReservationID = ? AND UtilisateurID = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$reservation_id, $client_id]);
            $count = $check_stmt->fetchColumn();
            
            if ($count > 0) {
                $is_owner = true;
            }
        }
        
        if ($is_owner) {
            // Récupérer toutes les commandes liées à cette réservation
            $commandes_query = "SELECT c.*, m.NomItem, m.Prix, m.Description
                               FROM Commandes c
                               LEFT JOIN Menus m ON c.MenuID = m.MenuID
                               WHERE c.ReservationID = ?";
            $commandes_stmt = $conn->prepare($commandes_query);
            $commandes_stmt->execute([$reservation_id]);
            $commandes = $commandes_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Cette réservation n'existe pas ou ne vous appartient pas.";
        }
    } else {
        $error_message = "Cette réservation n'existe pas.";
    }
} else {
    $error_message = "ID de commande ou de réservation manquant.";
}

// Calculer le total
foreach ($commandes as $commande) {
    $total += $commande['Prix'] * $commande['Quantite'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail de la commande - Restaurant La Mangeoire</title>
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
        .detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 25px;
            background-color: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-radius: 10px;
        }
        h1 {
            color: #9E2A2B;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .info-box, .commandes-box {
            margin-bottom: 30px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #9E2A2B;
        }
        h2 {
            color: #333;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 15px;
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
        .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .text-right {
            text-align: right;
        }
        .actions-box {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
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
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #721c24;
        }
        
        /* Responsive design improvements */
        @media (max-width: 768px) {
            .detail-container {
                padding: 15px;
                margin: 20px auto;
            }
            .table {
                display: block;
                overflow-x: auto;
            }
            .actions-box {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="detail-container">
            <h1>Détail de la commande</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>
                <?php if (isset($_GET['id']) && !empty($commandes)): ?>
                    <div class="info-box">
                        <h2>Informations de commande</h2>
                        <p><strong>Numéro de commande :</strong> #<?php echo htmlspecialchars($commandes[0]['CommandeID']); ?></p>
                        <p><strong>Date :</strong> <?php echo isset($commandes[0]['DateCommande']) ? date('d/m/Y à H:i', strtotime($commandes[0]['DateCommande'])) : 'Non disponible'; ?></p>
                        <p><strong>Statut :</strong> <?php echo htmlspecialchars($commandes[0]['Statut'] ?? 'Non spécifié'); ?></p>
                        <?php if(isset($commandes[0]['MontantTotal'])): ?>
                        <p><strong>Montant total :</strong> <?php echo number_format($commandes[0]['MontantTotal'], 0, ',', ' '); ?> XAF</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($reservation): ?>
                    <div class="info-box">
                        <h2>Informations de réservation</h2>
                        <p><strong>Date :</strong> <?php echo date('d/m/Y à H:i', strtotime($reservation['DateReservation'])); ?></p>
                        <p><strong>Nom :</strong> <?php echo htmlspecialchars($reservation['nom_client'] ?? 'Non spécifié'); ?></p>
                        <p><strong>Email :</strong> <?php echo htmlspecialchars($reservation['email_client'] ?? 'Non spécifié'); ?></p>
                        <p><strong>Nombre de personnes :</strong> <?php echo htmlspecialchars($reservation['nb_personnes'] ?? 'Non spécifié'); ?></p>
                        <p><strong>Statut :</strong> <?php echo htmlspecialchars($reservation['Statut'] ?? 'Non spécifié'); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="commandes-box">
                    <h2>Articles commandés</h2>
                    <?php if (count($commandes) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Description</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($commande['NomItem']); ?></td>
                                        <td><?php echo htmlspecialchars($commande['Description'] ?? ''); ?></td>
                                        <td><?php echo number_format($commande['Prix'], 2, ',', ' '); ?> €</td>
                                        <td><?php echo htmlspecialchars($commande['Quantite']); ?></td>
                                        <td><?php echo number_format($commande['Prix'] * $commande['Quantite'], 2, ',', ' '); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="4" class="text-right"><strong>Total</strong></td>
                                    <td><strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Aucun article trouvé pour cette commande.</p>
                    <?php endif; ?>
                </div>
                
                <div class="actions-box">
                    <a href="mon-compte.php" class="btn">Retour à mon compte</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
    
    <!-- Script pour la navbar mobile -->
    <script>
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
</body>
</html>
