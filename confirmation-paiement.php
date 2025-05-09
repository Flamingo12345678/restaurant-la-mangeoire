<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';

// Custom function to display cart messages
function display_cart_message() {
  if (isset($_SESSION['message'])) {
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    $alert_class = ($message_type == 'error') ? 'alert-danger' : 'alert-success';
    
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
    echo $_SESSION['message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    
    // Clear the message after displaying it
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
  }
}

// Check if we have a valid order ID or reservation ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$reservation_id = isset($_GET['reservation_id']) ? intval($_GET['reservation_id']) : 0;

$order = null;
$reservation = null;
$paiement = null;
$payment_type = '';

// Get order details if we have an order ID
if ($order_id > 0) {
    $payment_type = 'order';
    
    // Get order details
    $stmt = $conn->prepare("
        SELECT c.*, u.Nom, u.Prenom, u.Email, u.Telephone
        FROM Commandes c
        LEFT JOIN Utilisateurs u ON c.UtilisateurID = u.UtilisateurID
        WHERE c.CommandeID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment details
    if ($order) {
        $stmt = $conn->prepare("
            SELECT * FROM Paiements 
            WHERE CommandeID = ? 
            ORDER BY DatePaiement DESC 
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
// Get reservation details if we have a reservation ID
elseif ($reservation_id > 0) {
    $payment_type = 'reservation';
    
    // Get reservation details
    $stmt = $conn->prepare("
        SELECT * FROM Reservations 
        WHERE ReservationID = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment details
    if ($reservation) {
        $stmt = $conn->prepare("
            SELECT * FROM Paiements 
            WHERE ReservationID = ? 
            ORDER BY DatePaiement DESC 
            LIMIT 1
        ");
        $stmt->execute([$reservation_id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// If no valid payment found, redirect to home
if (!$paiement) {
    $_SESSION['message'] = "Aucun paiement trouvé.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit;
}

// If order or reservation data is missing, redirect to home
if ($payment_type === 'order' && !$order) {
    $_SESSION['message'] = "Commande non trouvée.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit;
} elseif ($payment_type === 'reservation' && !$reservation) {
    $_SESSION['message'] = "Réservation non trouvée.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Confirmation de Paiement - La Mangeoire</title>
    <meta name="description" content="Confirmation de paiement La Mangeoire" />
    <meta name="keywords" content="restaurant, paiement, confirmation" />
    <!-- Icone de favoris -->
    <link href="assets/img/favcon.jpeg" rel="icon" />
    <link href="assets/img/apple-touch-ico.png" rel="apple-touch-icon" />
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Amatic+SC:wght@400;700&display=swap"
      rel="stylesheet"
    />
    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/vendor/aos/aos.css" rel="stylesheet" />
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet" />
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet" />
    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet" />
    <style>
        .confirmation-section {
            padding: 80px 0;
        }
        .confirmation-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .order-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .payment-info {
            border-left: 4px solid #28a745;
            padding-left: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container position-relative d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">La Mangeoire</h1>
                <span>.</span>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="index.php#hero">Accueil</a></li>
                    <li><a href="index.php#about">A Propos</a></li>
                    <li><a href="index.php#menu">Menu</a></li>
                    <li><a href="index.php#events">Evenements</a></li>
                    <li><a href="index.php#chefs">Chefs</a></li>
                    <li><a href="index.php#gallery">Galeries</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                    <li><a href="panier.php"><i class="bi bi-cart"></i> Panier</a></li>
                    <li>
                        <?php if (isset($_SESSION['client_id'])): ?>
                            <a href="mon-compte.php"><i class="bi bi-person"></i> Mon Compte</a>
                        <?php else: ?>
                            <a href="connexion-unifiee.php"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>

            <a class="btn-getstarted" href="index.php#book-a-table">Réserver une Table</a>
        </div>
    </header>
    
    <!-- Display success/error messages -->
    <div class="container mt-2">
        <?php display_cart_message(); ?>
    </div>

    <main class="main">
        <section class="confirmation-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center" data-aos="fade-up">
                        <div class="confirmation-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h2>Paiement Confirmé!</h2>
                        <?php if ($payment_type === 'order'): ?>
                            <p class="lead">Merci pour votre commande. Votre paiement a été traité avec succès.</p>
                        <?php else: ?>
                            <p class="lead">Merci pour votre réservation. Votre paiement a été traité avec succès.</p>
                        <?php endif; ?>
                        
                        <div class="order-details">
                            <div class="payment-info">
                                <h4>Informations de paiement</h4>
                                <p><strong>Montant payé:</strong> <?php echo number_format($paiement['Montant'], 2, ',', ' '); ?> €</p>
                                <p><strong>Date du paiement:</strong> <?php echo date('d/m/Y à H:i', strtotime($paiement['DatePaiement'])); ?></p>
                                <p><strong>Mode de paiement:</strong> <?php echo htmlspecialchars($paiement['MethodePaiement']); ?></p>
                                <?php if(!empty($paiement['NumeroTransaction'])): ?>
                                    <p><strong>N° de transaction:</strong> <?php echo htmlspecialchars($paiement['NumeroTransaction']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($payment_type === 'order'): ?>
                            <!-- Affichage des détails de la commande -->
                            <h4>Détails de la commande</h4>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p><strong>Numéro de commande:</strong> #<?php echo $order['CommandeID']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['DateCommande'])); ?></p>
                                    <p><strong>Statut:</strong> <span class="badge bg-success">Payé</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Client:</strong> <?php echo htmlspecialchars($order['Prenom'] . ' ' . $order['Nom']); ?></p>
                                    <?php if(!empty($order['Email'])): ?>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['Email']); ?></p>
                                    <?php endif; ?>
                                    <?php if(!empty($order['Telephone'])): ?>
                                        <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($order['Telephone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p><strong>Montant total:</strong> <?php echo number_format($order['MontantTotal'], 2, ',', ' '); ?> €</p>
                            
                            <?php elseif ($payment_type === 'reservation'): ?>
                            <!-- Affichage des détails de la réservation -->
                            <h4>Détails de la réservation</h4>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p><strong>Numéro de réservation:</strong> #<?php echo $reservation['ReservationID']; ?></p>
                                    <p><strong>Date de réservation:</strong> <?php echo date('d/m/Y', strtotime($reservation['DateReservation'])); ?></p>
                                    <p><strong>Heure:</strong> <?php echo date('H:i', strtotime($reservation['HeureReservation'])); ?></p>
                                    <p><strong>Nombre de personnes:</strong> <?php echo $reservation['NombrePersonnes']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Client:</strong> <?php echo htmlspecialchars($reservation['ClientPrenom'] . ' ' . $reservation['ClientNom']); ?></p>
                                    <?php if(!empty($reservation['ClientEmail'])): ?>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($reservation['ClientEmail']); ?></p>
                                    <?php endif; ?>
                                    <?php if(!empty($reservation['ClientTelephone'])): ?>
                                        <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($reservation['ClientTelephone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if(!empty($reservation['Message'])): ?>
                                <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($reservation['Message'])); ?></p>
                            <?php endif; ?>
                            
                            <p><strong>Statut:</strong> <span class="badge bg-success">Confirmé</span></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-5">
                            <p>Un e-mail de confirmation a été envoyé à votre adresse.</p>
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                                <?php if ($payment_type === 'order'): ?>
                                    <a href="mon-compte.php?tab=orders" class="btn btn-outline-primary">Voir mes commandes</a>
                                <?php else: ?>
                                    <a href="mon-compte.php?tab=payments" class="btn btn-outline-primary">Voir mes paiements</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="container">
            <div class="row gy-3">
                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-geo-alt icon"></i>
                    <div class="address">
                        <h4>Adresse</h4>
                        <p>Hotel du plateau</p>
                        <p>ESSOS</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-telephone icon"></i>
                    <div>
                        <h4>Contact</h4>
                        <p>
                            <strong>telephone:</strong> <span>+237 6 96 56 85 20</span><br />
                            <strong>Email:</strong> <span>la-mangeoire@gmail.com</span><br />
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-clock icon"></i>
                    <div>
                        <h4>Heures d'ouverture</h4>
                        <p>
                            <strong>Lun-Sam:</strong> <span>11H - 23H</span><br />
                            <strong>Dimanche</strong>: <span>Fermé</span>
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-credit-card icon"></i>
                    <div>
                        <h4>Paiement</h4>
                        <p>
                            <strong>Modes acceptés:</strong> <span>CB, Espèces</span><br />
                            <strong>Paiement sécurisé</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="copyright">
                &copy; Copyright <strong><span>La Mangeoire</span></strong>. Tous droits réservés
            </div>
            <div class="credits">
                <a href="mentions-legales.php">Mentions légales</a> | 
                <a href="politique-confidentialite.php">Politique de confidentialité</a>
            </div>
        </div>
    </footer>

    <a href="#" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>
</body>
</html>
