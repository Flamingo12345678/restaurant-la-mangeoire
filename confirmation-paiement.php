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
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center" data-aos="fade-up">
                        <div class="confirmation-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h2>Paiement Confirmé!</h2>
                        <p class="lead">Merci pour votre commande. Votre paiement a été traité avec succès.</p>
                        
                        <div class="order-details mt-5 text-start">
                            <h4 class="mb-4">Détails de la commande</h4>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Numéro de commande:</strong> #<?php echo $order['CommandeID']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['DateCommande'])); ?></p>
                                    <p><strong>Statut:</strong> <span class="badge bg-success">Payé</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Client:</strong> <?php echo htmlspecialchars($order['PrenomClient'] . ' ' . $order['NomClient']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['EmailClient']); ?></p>
                                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($order['TelephoneClient']); ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p><strong>Adresse de livraison:</strong><br>
                                <?php echo nl2br(htmlspecialchars($order['AdresseLivraison'])); ?></p>
                            </div>
                            
                            <h5 class="mb-3">Articles commandés</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th class="text-center">Prix</th>
                                            <th class="text-center">Quantité</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['NomItem']); ?></td>
                                            <td class="text-center"><?php echo number_format($item['Prix'], 0, ',', ' '); ?> XAF</td>
                                            <td class="text-center"><?php echo $item['Quantite']; ?></td>
                                            <td class="text-end"><?php echo number_format($item['SousTotal'], 0, ',', ' '); ?> XAF</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total</th>
                                            <th class="text-end"><?php echo number_format($order['MontantTotal'], 0, ',', ' '); ?> XAF</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-5">
                            <p>Un e-mail de confirmation a été envoyé à votre adresse.</p>
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                                <?php if (isset($_SESSION['client_id'])): ?>
                                <a href="mon-compte.php" class="btn btn-outline-secondary">Voir mes commandes</a>
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
</body>
</html>