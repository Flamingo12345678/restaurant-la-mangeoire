<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';
require_once 'includes/paypal-config.php';

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

// Get status and PayPal parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$paymentId = isset($_GET['paymentId']) ? $_GET['paymentId'] : (isset($_GET['token']) ? $_GET['token'] : '');
$PayerID = isset($_GET['PayerID']) ? $_GET['PayerID'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$order_id = isset($_SESSION['paypal_order_id']) ? intval($_SESSION['paypal_order_id']) : 0;
$reservation_id = isset($_SESSION['paypal_reservation_id']) ? intval($_SESSION['paypal_reservation_id']) : 0;
$payment_amount = isset($_SESSION['paypal_amount']) ? floatval($_SESSION['paypal_amount']) : 0;

// Si les variables de session ne sont pas présentes mais que nous avons un type, essayons de les récupérer depuis les paramètres
if (($order_id == 0 && $reservation_id == 0) && !empty($type)) {
    if ($type === 'order' && isset($_GET['order_id'])) {
        $order_id = intval($_GET['order_id']);
        $_SESSION['paypal_order_id'] = $order_id;
    } elseif ($type === 'reservation' && isset($_GET['reservation_id'])) {
        $reservation_id = intval($_GET['reservation_id']);
        $_SESSION['paypal_reservation_id'] = $reservation_id;
    }
    
    // Essayer de récupérer le montant depuis la base de données
    if ($order_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT MontantTotal FROM Commandes WHERE CommandeID = ?");
            $stmt->execute([$order_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $payment_amount = floatval($result['MontantTotal']);
                $_SESSION['paypal_amount'] = $payment_amount;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du montant de commande: " . $e->getMessage());
        }
    } elseif ($reservation_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT MontantDepot FROM Reservations WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $payment_amount = floatval($result['MontantDepot'] ?? 10.00);
                $_SESSION['paypal_amount'] = $payment_amount;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du montant de réservation: " . $e->getMessage());
        }
    }
}

// Default values
$payment_success = false;
$payment_error = '';
$transaction_id = '';

// Verify PayPal payment if status is success
if ($status === 'success' && !empty($paymentId) && !empty($PayerID)) {
    // We need to execute the payment to complete the transaction
    $token = getPayPalAccessToken();
    
    if ($token) {
        $payment_success = true;
        $transaction_id = $paymentId;
        
        // Log successful PayPal token verification
        error_log("PayPal payment verification successful for token: " . $paymentId);
        
        // Process the payment based on payment type or session data
        if ($order_id > 0 || $type === 'order') {
            // Process order payment
            try {
                // Update order status
                $stmt = $pdo->prepare("
                    UPDATE Commandes 
                    SET Statut = 'Payé', DatePaiement = NOW() 
                    WHERE CommandeID = ?
                ");
                $stmt->execute([$order_id]);
                
                // Record payment in database
                $stmt = $pdo->prepare("
                    INSERT INTO Paiements (CommandeID, Montant, ModePaiement, TransactionID, DatePaiement)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $order_id,
                    $payment_amount,
                    'PayPal',
                    $transaction_id
                ]);
                
                $_SESSION['message'] = "Votre paiement PayPal a été traité avec succès. Merci pour votre commande!";
                $_SESSION['message_type'] = "success";
            } catch (Exception $e) {
                $payment_success = false;
                $payment_error = "Erreur lors de l'enregistrement du paiement : " . $e->getMessage();
                
                $_SESSION['message'] = "Une erreur est survenue lors de l'enregistrement du paiement.";
                $_SESSION['message_type'] = "error";
            }
        } elseif ($reservation_id > 0 || $type === 'reservation') {
            // Process reservation payment
            try {
                // Update reservation status
                $stmt = $pdo->prepare("
                    UPDATE Reservations 
                    SET Statut = 'Confirmé', DateMiseAJour = NOW() 
                    WHERE ReservationID = ?
                ");
                $stmt->execute([$reservation_id]);
                
                // Record payment in database
                $stmt = $pdo->prepare("
                    INSERT INTO Paiements (ReservationID, Montant, ModePaiement, TransactionID, DatePaiement)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $reservation_id,
                    $payment_amount,
                    'PayPal',
                    $transaction_id
                ]);
                
                $_SESSION['message'] = "Votre paiement PayPal a été traité avec succès. Votre réservation est confirmée!";
                $_SESSION['message_type'] = "success";
            } catch (Exception $e) {
                $payment_success = false;
                $payment_error = "Erreur lors de l'enregistrement du paiement : " . $e->getMessage();
                
                $_SESSION['message'] = "Une erreur est survenue lors de l'enregistrement du paiement.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $payment_success = false;
            $payment_error = "Aucune commande ou réservation associée à ce paiement.";
            
            $_SESSION['message'] = "Aucune commande ou réservation n'a pu être identifiée pour ce paiement.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $payment_success = false;
        $payment_error = "Erreur lors de la vérification du paiement PayPal.";
        
        $_SESSION['message'] = "Erreur lors de la vérification du paiement PayPal.";
        $_SESSION['message_type'] = "error";
    }
} elseif ($status === 'cancel') {
    $payment_success = false;
    $payment_error = "Paiement annulé par l'utilisateur.";
    
    $_SESSION['message'] = "Votre paiement PayPal a été annulé.";
    $_SESSION['message_type'] = "info";
} else {
    $payment_success = false;
    
    // Log debugging information
    error_log("PayPal confirmation failed - Status: $status, PaymentId: $paymentId, PayerID: $PayerID, Type: $type");
    error_log("Session data - OrderID: $order_id, ReservationID: $reservation_id, Amount: $payment_amount");
    
    if (empty($status)) {
        $payment_error = "Aucun statut de paiement fourni.";
        $_SESSION['message'] = "Aucun statut de paiement n'a été fourni.";
    } elseif (empty($paymentId)) {
        $payment_error = "Aucun identifiant de paiement fourni.";
        $_SESSION['message'] = "Aucun identifiant de paiement n'a été fourni.";
    } elseif (empty($PayerID)) {
        $payment_error = "Aucun identifiant PayPal fourni.";
        $_SESSION['message'] = "Aucun identifiant PayPal n'a été fourni.";
    } else {
        $payment_error = "Paramètres de paiement invalides.";
        $_SESSION['message'] = "Paramètres de paiement invalides.";
    }
    
    $_SESSION['message_type'] = "error";
}

// Get order details
$order = null;
$reservation = null;
$paiement = null;

if ($order_id > 0) {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT c.*, cl.Nom, cl.Prenom, cl.Email, cl.Telephone
        FROM Commandes c
        LEFT JOIN Clients cl ON c.ClientID = cl.ClientID
        WHERE c.CommandeID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment details
    if ($payment_success) {
        $stmt = $pdo->prepare("
            SELECT * FROM Paiements 
            WHERE CommandeID = ? 
            ORDER BY DatePaiement DESC 
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} elseif ($reservation_id > 0) {
    // Get reservation details
    $stmt = $pdo->prepare("
        SELECT * FROM Reservations 
        WHERE ReservationID = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment details
    if ($payment_success) {
        $stmt = $pdo->prepare("
            SELECT * FROM Paiements 
            WHERE ReservationID = ? 
            ORDER BY DatePaiement DESC 
            LIMIT 1
        ");
        $stmt->execute([$reservation_id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Clean up session variables
unset($_SESSION['paypal_order_id']);
unset($_SESSION['paypal_reservation_id']);
unset($_SESSION['paypal_amount']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Confirmation de Paiement PayPal - La Mangeoire</title>
    <meta name="description" content="Confirmation de paiement PayPal La Mangeoire" />
    <meta name="keywords" content="restaurant, paiement, confirmation, paypal" />
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
        .payment-status-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .success-icon {
            color: #28a745;
        }
        .error-icon {
            color: #dc3545;
        }
        .transaction-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .payment-logo {
            max-height: 40px;
            margin-bottom: 20px;
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
                            <a href="admin/login.php"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
                        <?php endif; ?>
                    </li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
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
                <div class="section-title text-center" data-aos="fade-up">
                    <h2>Confirmation de Paiement</h2>
                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" alt="PayPal" class="payment-logo">
                </div>
                
                <div class="row justify-content-center" data-aos="fade-up">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if ($payment_success): ?>
                                    <div class="payment-status-icon success-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <h3 class="mb-4">Paiement réussi !</h3>
                                    <p class="lead">Votre paiement a été traité avec succès.</p>
                                    
                                    <?php if ($order): ?>
                                        <p>Commande #<?php echo $order_id; ?> confirmée.</p>
                                        <div class="mt-4">
                                            <a href="mon-compte.php" class="btn btn-primary">Voir mes commandes</a>
                                        </div>
                                    <?php elseif ($reservation): ?>
                                        <p>Réservation #<?php echo $reservation_id; ?> confirmée.</p>
                                        <div class="mt-4">
                                            <a href="mon-compte.php" class="btn btn-primary">Voir mes réservations</a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($paiement): ?>
                                        <div class="transaction-details text-start mt-5">
                                            <h5 class="mb-3">Détails de la transaction</h5>
                                            <p class="mb-2"><strong>ID de transaction:</strong> <?php echo htmlspecialchars($paiement['TransactionID']); ?></p>
                                            <p class="mb-2"><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($paiement['DatePaiement'])); ?></p>
                                            <p class="mb-2"><strong>Montant:</strong> <?php echo number_format($paiement['Montant'], 0, ',', ' '); ?> XAF</p>
                                            <p class="mb-2"><strong>Mode de paiement:</strong> PayPal</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <div class="payment-status-icon error-icon">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <h3 class="mb-4">Paiement non complété</h3>
                                    <p class="lead"><?php echo $payment_error; ?></p>
                                    
                                    <div class="mt-4">
                                        <?php if ($order_id > 0): ?>
                                            <a href="payer-commande.php?id=<?php echo $order_id; ?>" class="btn btn-primary">Réessayer le paiement</a>
                                        <?php elseif ($reservation_id > 0): ?>
                                            <a href="payer-commande.php?reservation_id=<?php echo $reservation_id; ?>" class="btn btn-primary">Réessayer le paiement</a>
                                        <?php else: ?>
                                            <a href="panier.php" class="btn btn-primary">Retour au panier</a>
                                        <?php endif; ?>
                                    </div>
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

                <div class="col-lg-3 col-md-6">
                    <h4>Suivez Nous</h4>
                    <div class="social-links d-flex">
                        <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container copyright text-center mt-4">
            <p>
                © <span>Copyright</span>
                <strong class="px-1 sitename">La Mangeoire</strong>
                <span>All Rights Reserved</span>
            </p>
            <div class="credits">
                Designed by <a href="https://bootstrapmade.com/">FLAMINGO</a> Distributed by <a href="https://themewagon.com">JOSEPH</a>
            </div>
        </div>
    </footer>
    
    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>
</body>
</html>
