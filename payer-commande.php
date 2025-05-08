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

// Check if we have a valid order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$order = null;

if ($order_id > 0) {
    // Get order details
    $stmt = $conn->prepare("
        SELECT * FROM Commandes WHERE CommandeID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items
    if ($order) {
        $stmt = $conn->prepare("
            SELECT * FROM DetailsCommande WHERE CommandeID = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// If no valid order found, redirect to home
if (!$order) {
    $_SESSION['message'] = "Commande non trouvée.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit;
}

// Process payment form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate payment form
    $required_fields = ['card_number', 'card_holder', 'expiry_date', 'cvv'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ " . ucfirst(str_replace('_', ' ', $field)) . " est requis";
        }
    }
    
    // Simple validation for card number - should be numeric and 16 digits
    if (!empty($_POST['card_number']) && (!is_numeric(str_replace(' ', '', $_POST['card_number'])) || strlen(str_replace(' ', '', $_POST['card_number'])) !== 16)) {
        $errors[] = "Le numéro de carte doit comporter 16 chiffres";
    }
    
    // Simple validation for CVV - should be numeric and 3 digits
    if (!empty($_POST['cvv']) && (!is_numeric($_POST['cvv']) || strlen($_POST['cvv']) !== 3)) {
        $errors[] = "Le code CVV doit comporter 3 chiffres";
    }
    
    // Simple validation for expiry date format (MM/YY)
    if (!empty($_POST['expiry_date']) && !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $_POST['expiry_date'])) {
        $errors[] = "La date d'expiration doit être au format MM/YY";
    }
    
    if (empty($errors)) {
        // In a real application, you would integrate with a payment gateway here
        // For now, we'll simulate a successful payment
        
        // Update order status to paid
        $stmt = $conn->prepare("
            UPDATE Commandes 
            SET Statut = 'Payé', DatePaiement = NOW() 
            WHERE CommandeID = ?
        ");
        $stmt->execute([$order_id]);
        
        // Store the payment information (in a real app, you would NOT store full card details)
        $cardLast4 = substr(str_replace(' ', '', $_POST['card_number']), -4);
        
        $stmt = $conn->prepare("
            INSERT INTO Paiements (CommandeID, Montant, MethodePaiement, NumeroTransaction, DatePaiement)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $order_id,
            $order['MontantTotal'],
            'Carte bancaire',
            'TR-' . time() . '-' . $order_id // Generate a mock transaction number
        ]);
        
        // Redirect to confirmation page
        $_SESSION['message'] = "Votre paiement a été traité avec succès. Merci pour votre commande!";
        $_SESSION['message_type'] = "success";
        header("Location: confirmation-paiement.php?id=" . $order_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Paiement - La Mangeoire</title>
    <meta name="description" content="Paiement de commande La Mangeoire" />
    <meta name="keywords" content="restaurant, paiement, commande" />
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
        .payment-section {
            padding: 80px 0;
        }
        .payment-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .card-wrapper {
            border: 1px solid #ddd;
            padding: 25px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-info {
            margin-top: 20px;
        }
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
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
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <main class="main">
        <section class="payment-section">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <h2>Paiement</h2>
                    <p>
                        <span>Commande</span>
                        <span class="description-title">#<?php echo $order['CommandeID']; ?></span>
                    </p>
                </div>
                
                <div class="row" data-aos="fade-up">
                    <div class="col-lg-7 mb-4 mb-lg-0">
                        <div class="card-wrapper">
                            <div class="d-flex align-items-center mb-4">
                                <span class="payment-icon me-3"><i class="bi bi-credit-card"></i></span>
                                <h3 class="mb-0">Informations de paiement</h3>
                            </div>
                            
                            <form method="POST" action="payer-commande.php?id=<?php echo $order['CommandeID']; ?>">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="card_number" class="form-label">Numéro de carte <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="card_holder" class="form-label">Titulaire de la carte <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="card_holder" name="card_holder" placeholder="JEAN DUPONT" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="expiry_date" class="form-label">Date d'expiration <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cvv" class="form-label">CVV <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="cvv" name="cvv" placeholder="123" maxlength="3" required>
                                    </div>
                                </div>
                                
                                <div class="card-info d-flex mb-3 p-2 bg-light rounded">
                                    <i class="bi bi-shield-lock me-2 text-success"></i>
                                    <small>Vos informations de paiement sont sécurisées. Nous n'enregistrons pas vos données de carte.</small>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Payer <?php echo number_format($order['MontantTotal'], 0, ',', ' '); ?> XAF</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <h5>Résumé de la commande</h5>
                            </div>
                            <div class="card-body">
                                <div class="order-summary">
                                    <?php foreach ($order_items as $item): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo htmlspecialchars($item['NomItem']); ?> x <?php echo $item['Quantite']; ?></span>
                                        <span><?php echo number_format($item['SousTotal'], 0, ',', ' '); ?> XAF</span>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span><?php echo number_format($order['MontantTotal'], 0, ',', ' '); ?> XAF</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h6 class="mb-3">Informations du client:</h6>
                                    <p class="mb-1"><strong>Nom:</strong> <?php echo htmlspecialchars($order['PrenomClient'] . ' ' . $order['NomClient']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['EmailClient']); ?></p>
                                    <p class="mb-1"><strong>Téléphone:</strong> <?php echo htmlspecialchars($order['TelephoneClient']); ?></p>
                                    <p class="mb-1"><strong>Adresse:</strong> <?php echo htmlspecialchars($order['AdresseLivraison']); ?></p>
                                </div>
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

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    
    <!-- Custom Card Formatting Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Format credit card number with spaces
            const cardNumberInput = document.getElementById('card_number');
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                e.target.value = formattedValue;
            });
            
            // Format expiry date with slash
            const expiryDateInput = document.getElementById('expiry_date');
            expiryDateInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });
        });
    </script>
</body>
</html>