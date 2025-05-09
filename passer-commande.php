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

// Redirect to cart if empty
$has_items = false;

if (isset($_SESSION['client_id'])) {
    // Check from database for authenticated users
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Panier WHERE UtilisateurID = ?");
    $stmt->execute([$_SESSION['client_id']]);
    $has_items = ($stmt->fetchColumn() > 0);
} else if (isset($_SESSION['panier']) && count($_SESSION['panier']) > 0) {
    // Check from session for non-authenticated users
    $has_items = true;
}

// If cart is empty, redirect to cart page
if (!$has_items) {
    $_SESSION['message'] = "Votre panier est vide. Veuillez ajouter des articles avant de passer une commande.";
    $_SESSION['message_type'] = "error";
    header("Location: panier.php");
    exit;
}

// Get cart items and total
$cart_items = [];
$total = 0;

if (isset($_SESSION['client_id'])) {
    // Get items from database for authenticated users
    $stmt = $conn->prepare("
        SELECT p.PanierID, p.UtilisateurID, p.MenuID, 
               IFNULL(p.Quantite, 1) as Quantite, 
               p.DateAjout, 
               m.NomItem, m.Prix, m.Description
        FROM Panier p
        JOIN Menus m ON p.MenuID = m.MenuID
        WHERE p.UtilisateurID = ?
    ");
    $stmt->execute([$_SESSION['client_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($cart_items) {
        // Loggons le contenu brut du panier pour diagnostic
        error_log("Panier utilisateur (avant validation): " . print_r($cart_items, true));
    }
    
    // Calculate total and ensure data integrity
    foreach ($cart_items as $key => $item) {
        // Ensure all required keys exist with validation stricte
        if (!isset($item['MenuID']) || empty($item['MenuID'])) {
            $cart_items[$key]['MenuID'] = 0;
            error_log("MenuID manquant ou invalide pour l'article à l'index {$key}");
        }
        
        if (!isset($item['Quantite']) || empty($item['Quantite'])) {
            $cart_items[$key]['Quantite'] = 1;
            error_log("Quantite manquante ou invalide pour l'article à l'index {$key}, MenuID: {$item['MenuID']}");
        }
        
        if (!isset($item['Prix']) || empty($item['Prix'])) {
            $cart_items[$key]['Prix'] = 0;
            error_log("Prix manquant ou invalide pour l'article à l'index {$key}, MenuID: {$item['MenuID']}");
        }
        
        if (!isset($item['NomItem']) || empty($item['NomItem'])) {
            $cart_items[$key]['NomItem'] = "Inconnu";
            error_log("NomItem manquant ou invalide pour l'article à l'index {$key}, MenuID: {$item['MenuID']}");
        }
        
        // Convertir explicitement les valeurs numériques
        $cart_items[$key]['MenuID'] = intval($cart_items[$key]['MenuID']);
        $cart_items[$key]['Quantite'] = intval($cart_items[$key]['Quantite']);
        $cart_items[$key]['Prix'] = floatval($cart_items[$key]['Prix']);
        
        $total += $cart_items[$key]['Prix'] * $cart_items[$key]['Quantite'];
    }
} else if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
    // Get items from session for non-authenticated users
    $cart_items = $_SESSION['panier'];
    
    if ($cart_items) {
        // Loggons le contenu brut du panier pour diagnostic
        error_log("Panier session (avant validation): " . print_r($cart_items, true));
    }
    
    // Calculate total and ensure data integrity
    foreach ($cart_items as $key => $item) {
        // Ensure all required keys exist with validation stricte
        if (!isset($item['MenuID']) || empty($item['MenuID'])) {
            $cart_items[$key]['MenuID'] = 0;
            error_log("Session panier: MenuID manquant ou invalide pour l'article à l'index {$key}");
        }
        
        if (!isset($item['Quantite']) || empty($item['Quantite'])) {
            $cart_items[$key]['Quantite'] = 1;
            error_log("Session panier: Quantite manquante ou invalide pour l'article à l'index {$key}, MenuID: {$item['MenuID']}");
        }
        
        if (!isset($item['Prix']) || empty($item['Prix'])) {
            $cart_items[$key]['Prix'] = 0;
            error_log("Session panier: Prix manquant ou invalide pour l'article à l'index {$key}, MenuID: {$item['MenuID']}");
        }
        
        if (!isset($item['NomItem']) || empty($item['NomItem'])) {
            $cart_items[$key]['NomItem'] = "Inconnu";
            error_log("Session panier: NomItem manquant ou invalide pour l'article à l'index {$key}, MenuID: {$item['MenuID']}");
        }
        
        // Convertir explicitement les valeurs numériques
        $cart_items[$key]['MenuID'] = intval($cart_items[$key]['MenuID']);
        $cart_items[$key]['Quantite'] = intval($cart_items[$key]['Quantite']);
        $cart_items[$key]['Prix'] = floatval($cart_items[$key]['Prix']);
        
        $total += $cart_items[$key]['Prix'] * $cart_items[$key]['Quantite'];
    }
}

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['nom', 'prenom', 'email', 'telephone', 'adresse', 'mode_paiement'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ " . ucfirst($field) . " est requis";
        }
    }
    
    if (empty($errors)) {
        // Save order to database
        try {
            $conn->beginTransaction();
            
            // Create new order
            $stmt = $conn->prepare("
                INSERT INTO Commandes (UtilisateurID, DateCommande, Statut, MontantTotal, 
                                       NomClient, PrenomClient, EmailClient, TelephoneClient, 
                                       AdresseLivraison, ModePaiement)
                VALUES (?, NOW(), 'En attente', ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $user_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;
            $stmt->execute([
                $user_id,
                $total,
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['email'],
                $_POST['telephone'],
                $_POST['adresse'],
                $_POST['mode_paiement']
            ]);
            
            $commande_id = $conn->lastInsertId();
            
            // Add order items
            $stmt = $conn->prepare("
                INSERT INTO DetailsCommande (CommandeID, MenuID, NomItem, Prix, Quantite, SousTotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            // Debug log - toutes les clés disponibles dans le panier avant le traitement
            $cart_keys = [];
            foreach ($cart_items as $item) {
                foreach (array_keys($item) as $key) {
                    if (!in_array($key, $cart_keys)) {
                        $cart_keys[] = $key;
                    }
                }
            }
            error_log("Clés disponibles dans les articles du panier: " . implode(", ", $cart_keys));
            
            foreach ($cart_items as $item) {
                // Log de débogage pour voir les valeurs
                error_log("Contenu de l'item dans le panier: " . print_r($item, true));
                
                // Récupérer les données avec des valeurs par défaut sécurisées
                $menuID = isset($item['MenuID']) && is_numeric($item['MenuID']) ? intval($item['MenuID']) : 0;
                $nomItem = isset($item['NomItem']) && !empty($item['NomItem']) ? $item['NomItem'] : "Produit sans nom";
                $prix = isset($item['Prix']) && is_numeric($item['Prix']) ? floatval($item['Prix']) : 0;
                $quantite = isset($item['Quantite']) && is_numeric($item['Quantite']) ? intval($item['Quantite']) : 1;
                
                // Validation finale avant insertion
                if ($menuID <= 0) {
                    throw new Exception("MenuID invalide pour l'article: $nomItem");
                }
                
                if ($quantite <= 0) {
                    $quantite = 1; // Garantir une quantité minimale
                }
                
                $sous_total = $prix * $quantite;
                
                error_log("Insertion DetailsCommande: MenuID={$menuID}, Quantite={$quantite}, Prix={$prix}, SousTotal={$sous_total}");
                
                // Exécuter l'insertion avec des valeurs explicitement typées
                $stmt->execute([
                    $commande_id,
                    $menuID,
                    $nomItem,
                    $prix,
                    $quantite,
                    $sous_total
                ]);
            }
            
            // Clear cart
            if (isset($_SESSION['client_id'])) {
                $stmt = $conn->prepare("DELETE FROM Panier WHERE UtilisateurID = ?");
                $stmt->execute([$_SESSION['client_id']]);
            } else {
                unset($_SESSION['panier']);
            }
            
            $conn->commit();
            
            // Redirect to confirmation page
            $_SESSION['message'] = "Votre commande a été enregistrée avec succès. Numéro de commande: " . $commande_id;
            $_SESSION['message_type'] = "success";
            
            // For cash payment, go to confirmation
            if ($_POST['mode_paiement'] == 'especes') {
                header("Location: confirmation-commande.php?id=" . $commande_id);
                exit;
            } else {
                // For card payment, go to payment page
                header("Location: payer-commande.php?id=" . $commande_id);
                exit;
            }
        } catch (Exception $e) {
            $conn->rollBack();
            
            // Log détaillé de l'erreur pour le débogage
            error_log("Erreur SQL dans passer-commande.php: " . $e->getMessage() . 
                     " - Code: " . $e->getCode() . 
                     " - Trace: " . $e->getTraceAsString());
            
            // Log du contexte: contenu du panier
            error_log("Contexte de l'erreur - Contenu du panier: " . print_r($cart_items, true));
            
            // Message utilisateur plus convivial, mais conserve les détails techniques pour le débogage
            $errors[] = "Une erreur est survenue lors de l'enregistrement de votre commande: " . $e->getMessage();
            
            // En cas d'erreur SQL spécifique liée aux champs manquants
            if (strpos($e->getMessage(), "Field 'Quantite' doesn't have a default value") !== false) {
                $errors[] = "Erreur: Quantité manquante pour un ou plusieurs produits. Veuillez vider votre panier et réessayer.";
                
                // Option permettant de corriger automatiquement le panier
                if (isset($_SESSION['client_id'])) {
                    try {
                        // Nettoyer le panier en supprimant les éléments problématiques
                        $clean_stmt = $conn->prepare("DELETE FROM Panier WHERE UtilisateurID = ? AND (Quantite IS NULL OR Quantite <= 0)");
                        $clean_stmt->execute([$_SESSION['client_id']]);
                        error_log("Tentative de nettoyage du panier effectuée pour l'utilisateur " . $_SESSION['client_id']);
                    } catch (Exception $cleanEx) {
                        error_log("Échec de la tentative de nettoyage du panier: " . $cleanEx->getMessage());
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Passer Commande - La Mangeoire</title>
    <meta name="description" content="Finaliser votre commande à La Mangeoire" />
    <meta name="keywords" content="restaurant, commande, checkout" />
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
        .checkout-section {
            padding: 80px 0;
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
                    <li><a href="panier.php"><i class="bi bi-cart"></i> Panier 
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
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (strpos(implode(" ", $errors), "Quantité manquante") !== false || strpos(implode(" ", $errors), "doesn't have a default value") !== false): ?>
                <div class="mt-3">
                    <a href="reparer-panier.php?show_results=1" class="btn btn-primary me-2">
                        <i class="bi bi-wrench"></i> Réparer mon panier
                    </a>
                    <a href="vider-panier.php?redirect=passer-commande.php" class="btn btn-warning me-2">
                        <i class="bi bi-cart-x"></i> Vider mon panier
                    </a>
                    <a href="diagnostic-panier.php" class="btn btn-info">
                        <i class="bi bi-search"></i> Diagnostiquer le problème
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <main class="main">
        <section class="checkout-section">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <h2>Finaliser la Commande</h2>
                    <p>
                        <span>Détails de</span>
                        <span class="description-title">Votre Commande</span>
                    </p>
                </div>
                
                <div class="row" data-aos="fade-up">
                    <div class="col-lg-8">
                        <form method="POST" action="passer-commande.php">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Informations personnelles</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nom" name="nom" required 
                                                value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : (isset($_SESSION['nom']) ? htmlspecialchars($_SESSION['nom']) : ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" required
                                                value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : (isset($_SESSION['prenom']) ? htmlspecialchars($_SESSION['prenom']) : ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" required
                                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="telephone" name="telephone" required
                                                value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : (isset($_SESSION['telephone']) ? htmlspecialchars($_SESSION['telephone']) : ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Adresse de livraison</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="adresse" class="form-label">Adresse complète <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="adresse" name="adresse" rows="3" required><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : (isset($_SESSION['adresse']) ? htmlspecialchars($_SESSION['adresse']) : ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="instructions" class="form-label">Instructions spéciales (facultatif)</label>
                                        <textarea class="form-control" id="instructions" name="instructions" rows="2"><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Mode de paiement</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="mode_paiement" id="especes" value="especes" checked>
                                        <label class="form-check-label" for="especes">
                                            <i class="bi bi-cash"></i> Paiement à la livraison
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mode_paiement" id="carte" value="carte">
                                        <label class="form-check-label" for="carte">
                                            <i class="bi bi-credit-card"></i> Paiement par carte bancaire
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Valider la commande</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Résumé de la commande</h5>
                            </div>
                            <div class="card-body">
                                <div class="order-summary">
                                    <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo htmlspecialchars($item['NomItem']); ?> x <?php echo $item['Quantite']; ?></span>
                                        <span><?php echo number_format($item['Prix'] * $item['Quantite'], 0, ',', ' '); ?> XAF</span>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between">
                                        <span>Sous-total:</span>
                                        <span><?php echo number_format($total, 0, ',', ' '); ?> XAF</span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <span>Frais de livraison:</span>
                                        <span>0 XAF</span>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span><?php echo number_format($total, 0, ',', ' '); ?> XAF</span>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <a href="panier.php" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-arrow-left"></i> Retour au panier
                                    </a>
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
</body>
</html>