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

// Get cart items
$cart_items = [];
$total = 0;

if (isset($_SESSION['client_id'])) {
    // Get items from database for authenticated users
    $stmt = $conn->prepare("
        SELECT p.*, m.NomItem, m.Prix, m.Description
        FROM Panier p
        JOIN Menus m ON p.MenuID = m.MenuID
        WHERE p.UtilisateurID = ?
    ");
    $stmt->execute([$_SESSION['client_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    foreach ($cart_items as $item) {
        $total += $item['Prix'] * $item['Quantite'];
    }
} else if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
    // Get items from session for non-authenticated users
    $cart_items = $_SESSION['panier'];
    
    // Calculate total
    foreach ($cart_items as $item) {
        $total += $item['Prix'] * $item['Quantite'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Mon Panier - La Mangeoire</title>
    <meta name="description" content="Panier d'achat La Mangeoire" />
    <meta name="keywords" content="restaurant, panier, commande" />
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
        .cart-section {
            padding: 80px 0;
        }
        .cart-img {
            max-width: 100px;
            height: auto;
        }
        .cart-empty {
            text-align: center;
            padding: 40px 0;
        }
        .cart-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .quantity-input {
            max-width: 80px;
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
                    <li><a href="panier.php" class="active"><i class="bi bi-cart"></i> Panier 
                        <?php 
                        $cart_count = 0;
                        if (isset($_SESSION['client_id'])) {
                            // Count from database
                            $stmt = $conn->prepare("SELECT SUM(Quantite) FROM Panier WHERE UtilisateurID = ?");
                            $stmt->execute([$_SESSION['client_id']]);
                            $cart_count = $stmt->fetchColumn() ?: 0;
                        } else if (isset($_SESSION['panier']) && count($_SESSION['panier']) > 0) {
                            // Count from session
                            foreach ($_SESSION['panier'] as $item) {
                                $cart_count += $item['Quantite'];
                            }
                        }
                        if ($cart_count > 0) {
                            echo '<span class="badge bg-danger rounded-pill">' . $cart_count . '</span>';
                        }
                        ?>
                    </a></li>
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
        <section class="cart-section">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <h2>Mon Panier</h2>
                    <p>
                        <span>Vos plats</span>
                        <span class="description-title">Sélectionnés</span>
                    </p>
                </div>
                
                <?php if (empty($cart_items)): ?>
                <div class="cart-empty" data-aos="fade-up">
                    <i class="bi bi-cart-x" style="font-size: 5rem; color: #ccc;"></i>
                    <h3 class="mt-3">Votre panier est vide</h3>
                    <p class="text-muted">Parcourez notre menu et ajoutez des plats à votre panier</p>
                    <a href="index.php#menu" class="btn btn-primary mt-3">Voir notre menu</a>
                </div>
                <?php else: ?>
                <div class="row" data-aos="fade-up">
                    <div class="col-lg-8">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item row align-items-center">
                            <div class="col-md-2">
                                <!-- Using default image as Image column is not in database -->
                                <img src="assets/img/menu/menu-item-1.png" alt="<?php echo htmlspecialchars($item['NomItem']); ?>" class="cart-img img-fluid">
                            </div>
                            <div class="col-md-4">
                                <h4><?php echo htmlspecialchars($item['NomItem']); ?></h4>
                                <p class="text-muted"><?php echo isset($item['Description']) ? htmlspecialchars($item['Description']) : ''; ?></p>
                                <p class="price"><?php echo number_format($item['Prix'], 0, ',', ' '); ?> XAF</p>
                            </div>
                            <div class="col-md-3">
                                <form action="ajouter-au-panier.php" method="post">
                                    <input type="hidden" name="menu_id" value="<?php echo $item['MenuID']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <div class="input-group">
                                        <span class="input-group-text">Qté</span>
                                        <input type="number" name="quantite" class="form-control quantity-input" value="<?php echo $item['Quantite']; ?>" min="1">
                                        <button type="submit" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-2 text-end">
                                <p class="fw-bold"><?php echo number_format($item['Prix'] * $item['Quantite'], 0, ',', ' '); ?> XAF</p>
                            </div>
                            <div class="col-md-1 text-end">
                                <form action="ajouter-au-panier.php" method="post">
                                    <input type="hidden" name="menu_id" value="<?php echo $item['MenuID']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Résumé de la commande</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Sous-total:</span>
                                    <span><?php echo number_format($total, 0, ',', ' '); ?> XAF</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Frais de livraison:</span>
                                    <span>0 XAF</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3 fw-bold">
                                    <span>Total:</span>
                                    <span><?php echo number_format($total, 0, ',', ' '); ?> XAF</span>
                                </div>
                                <a href="passer-commande.php" class="btn btn-primary w-100">Passer la commande</a>
                                <a href="index.php#menu" class="btn btn-outline-secondary w-100 mt-2">Continuer les achats</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
?>