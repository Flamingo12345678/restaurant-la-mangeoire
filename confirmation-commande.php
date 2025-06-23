<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir cette constante pour éviter l'auto-configuration
define('HTTPS_MANAGER_NO_AUTO', true);

require_once 'includes/common.php';
require_once 'includes/payment_manager.php';
require_once 'db_connexion.php';
require_once 'includes/https_manager.php';

// Configuration environnement sécurisé sans forcer HTTPS en développement
if (!isset($_GET['test']) && !isset($_GET['dev'])) {
    // Seulement ajouter les headers de sécurité, pas forcer HTTPS en développement
    HTTPSManager::addSecurityHeaders();
}

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
    $stmt = $pdo->prepare("
        SELECT * FROM Commandes WHERE CommandeID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items
    if ($order) {
        $stmt = $pdo->prepare("
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

// Vérifier si la commande est déjà payée
$stmt = $pdo->prepare("SELECT * FROM Paiements WHERE CommandeID = ? AND Statut = 'Confirme'");
$stmt->execute([$order_id]);
$paiement_existant = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialiser le gestionnaire de paiements
$paymentManager = new PaymentManager();
$public_keys = $paymentManager->getPublicKeys();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Confirmation de Commande - La Mangeoire</title>
    <meta name="description" content="Confirmation de commande La Mangeoire" />
    <meta name="keywords" content="restaurant, commande, confirmation" />
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
            color: #ffc107;
            margin-bottom: 20px;
        }
        .order-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        
        /* Styles pour l'étape 3 de paiement */
        .step-number {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .payment-methods {
            margin: 30px 0;
        }
        
        .payment-method-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method-card:hover .card {
            border-color: #007bff !important;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .payment-method-card .card {
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .payment-icon {
            transition: transform 0.3s ease;
        }
        
        .payment-method-card:hover .payment-icon {
            transform: scale(1.1);
        }
        
        #card-element {
            background: white;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
        }
    </style>
    
    <!-- Stripe SDK -->
    <?php if (!$paiement_existant && $public_keys['stripe_publishable_key']): ?>
        <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>
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
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h2>Commande Confirmée!</h2>
                        <p class="lead">Votre commande a été enregistrée avec succès.</p>
                        
                        <?php if ($paiement_existant): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <strong>Paiement confirmé!</strong> Votre commande sera livrée prochainement.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-credit-card"></i> <strong>Étape 3 - Finaliser votre paiement</strong>
                                <p class="mb-0 mt-2">Veuillez choisir votre mode de paiement pour confirmer définitivement votre commande.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="order-details mt-5 text-start">
                            <h4 class="mb-4">Détails de la commande</h4>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Numéro de commande:</strong> #<?php echo $order['CommandeID']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['DateCommande'])); ?></p>
                                    <p><strong>Statut:</strong> 
                                    <?php if ($paiement_existant): ?>
                                        <span class="badge bg-success">Payée</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">En attente de paiement</span>
                                    <?php endif; ?>
                                    </p>
                                    <p><strong>Mode de paiement:</strong> 
                                    <?php echo $paiement_existant ? 'Carte bancaire (payé)' : 'En attente de paiement'; ?>
                                    </p>
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
                                            <td class="text-center"><?php echo number_format($item['Prix'], 2, ',', ' '); ?> €</td>
                                            <td class="text-center"><?php echo $item['Quantite']; ?></td>
                                            <td class="text-end"><?php echo number_format($item['SousTotal'], 2, ',', ' '); ?> €</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total</th>
                                            <th class="text-end"><?php echo number_format($order['MontantTotal'], 2, ',', ' '); ?> €</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-5">
                            <?php if ($paiement_existant): ?>
                                <p>Un e-mail de confirmation a été envoyé à votre adresse. Votre commande sera livrée prochainement.</p>
                            <?php else: ?>
                                <!-- Système de paiement moderne - Étape 3 -->
                                <div class="payment-section">
                                    <div class="step-header mb-4">
                                        <span class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">3</span>
                                        <h4 class="d-inline-block ms-3 mb-0">Choisissez votre mode de paiement</h4>
                                    </div>
                                    
                                    <div class="payment-methods">
                                        <div class="row g-3">
                                            <!-- Stripe (Carte bancaire) -->
                                            <div class="col-md-4">
                                                <div class="payment-method-card h-100" data-method="stripe">
                                                    <div class="card h-100 border-2">
                                                        <div class="card-body text-center p-4">
                                                            <div class="payment-icon mb-3">
                                                                <i class="bi bi-credit-card text-primary" style="font-size: 2.5rem;"></i>
                                                            </div>
                                                            <h5 class="card-title">Carte Bancaire</h5>
                                                            <p class="card-text text-muted">Paiement sécurisé Stripe</p>
                                                            <small class="text-success">
                                                                <i class="bi bi-shield-check"></i> 3D Secure
                                                            </small>
                                                            <div class="mt-3">
                                                                <button class="btn btn-primary w-100" onclick="initiateStripePayment()">
                                                                    <i class="bi bi-lock"></i> Payer <?php echo number_format($order['MontantTotal'], 2, ',', ' '); ?> €
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- PayPal -->
                                            <div class="col-md-4">
                                                <div class="payment-method-card h-100" data-method="paypal">
                                                    <div class="card h-100 border-2">
                                                        <div class="card-body text-center p-4">
                                                            <div class="payment-icon mb-3">
                                                                <i class="bi bi-paypal text-warning" style="font-size: 2.5rem;"></i>
                                                            </div>
                                                            <h5 class="card-title">PayPal</h5>
                                                            <p class="card-text text-muted">Compte ou carte via PayPal</p>
                                                            <small class="text-info">
                                                                <i class="bi bi-shield-check"></i> Protection acheteur
                                                            </small>
                                                            <div class="mt-3">
                                                                <button class="btn btn-warning w-100" onclick="initiatePayPalPayment()">
                                                                    <i class="bi bi-paypal"></i> Payer <?php echo number_format($order['MontantTotal'], 2, ',', ' '); ?> €
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Stripe Alternative -->
                                            <div class="col-md-4">
                                                <div class="payment-method-card h-100" data-method="stripe-alt">
                                                    <div class="card h-100 border-2">
                                                        <div class="card-body text-center p-4">
                                                            <div class="payment-icon mb-3">
                                                                <i class="bi bi-credit-card-2-front text-info" style="font-size: 2.5rem;"></i>
                                                            </div>
                                                            <h5 class="card-title">Stripe</h5>
                                                            <p class="card-text text-muted">Paiement sécurisé Stripe</p>
                                                            <small class="text-info">
                                                                <i class="bi bi-lightning"></i> Instantané
                                                            </small>
                                                            <div class="mt-3">
                                                                <button class="btn btn-info w-100" onclick="initiateStripePayment()">
                                                                    <i class="bi bi-credit-card"></i> Payer <?php echo number_format($order['MontantTotal'], 2, ',', ' '); ?> €
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Éléments Stripe (cachés initialement) -->
                                    <div id="stripe-payment-form" class="mt-4" style="display: none;">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="bi bi-credit-card"></i> Paiement par carte bancaire</h5>
                                            </div>
                                            <div class="card-body">
                                                <form id="payment-form">
                                                    <div class="mb-3">
                                                        <label class="form-label">Informations de la carte</label>
                                                        <div id="card-element" style="padding: 12px; border: 1px solid #ced4da; border-radius: 0.375rem;">
                                                            <!-- Stripe Elements sera injecté ici -->
                                                        </div>
                                                        <div id="card-errors" class="text-danger mt-2"></div>
                                                    </div>
                                                    <button type="submit" id="submit-payment" class="btn btn-primary w-100">
                                                        <span id="button-text">Confirmer le paiement</span>
                                                        <div id="spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                                            <span class="visually-hidden">Traitement...</span>
                                                        </div>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                                <?php if (isset($_SESSION['client_id'])): ?>
                                <a href="mon-compte.php" class="btn btn-outline-secondary">Voir mes commandes</a>
                                <?php endif; ?>
                                <?php if (!$paiement_existant): ?>
                                <a href="contact.php" class="btn btn-outline-info">Besoin d'aide ?</a>
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
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Traitement en cours...</span>
            </div>
            <h4>Traitement du paiement...</h4>
            <p class="text-muted">Veuillez patienter, ne fermez pas cette page.</p>
        </div>
    </div>
    
    <!-- Scripts de paiement -->
    <?php if (!$paiement_existant): ?>
    <script>
        // Configuration
        const ORDER_ID = <?php echo $order_id; ?>;
        const ORDER_AMOUNT = <?php echo $order['MontantTotal']; ?>;
        
        <?php if ($public_keys['stripe_publishable_key']): ?>
        // Configuration Stripe
        const stripe = Stripe('<?php echo $public_keys['stripe_publishable_key']; ?>');
        const elements = stripe.elements();
        let cardElement = null;
        let paymentForm = null;
        <?php endif; ?>
        
        // Fonctions de paiement
        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }
        
        function showError(message) {
            hideLoading();
            alert('Erreur: ' + message);
        }
        
        // Stripe Payment
        function initiateStripePayment() {
            <?php if ($public_keys['stripe_publishable_key']): ?>
            // Cacher les autres méthodes et afficher le formulaire Stripe
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.style.display = 'none';
            });
            
            const stripeForm = document.getElementById('stripe-payment-form');
            stripeForm.style.display = 'block';
            
            // Initialiser Stripe Elements si pas déjà fait
            if (!cardElement) {
                cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#424770',
                            '::placeholder': {
                                color: '#aab7c4',
                            },
                        },
                    },
                });
                cardElement.mount('#card-element');
                
                cardElement.on('change', ({error}) => {
                    const displayError = document.getElementById('card-errors');
                    if (error) {
                        displayError.textContent = error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });
                
                // Gestion du formulaire
                paymentForm = document.getElementById('payment-form');
                paymentForm.addEventListener('submit', handleStripeSubmit);
            }
            <?php else: ?>
            showError('Stripe non configuré');
            <?php endif; ?>
        }
        
        async function handleStripeSubmit(event) {
            event.preventDefault();
            showLoading();
            
            const {token, error} = await stripe.createToken(cardElement);
            
            if (error) {
                showError(error.message);
                return;
            }
            
            // Créer le PaymentMethod avec le token
            const {paymentMethod, error: pmError} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });
            
            if (pmError) {
                showError(pmError.message);
                return;
            }
            
            // Envoyer à l'API
            try {
                const response = await fetch('/api/payments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'stripe_payment',
                        payment_method_id: paymentMethod.id,
                        montant: ORDER_AMOUNT,
                        commande_id: ORDER_ID
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'confirmation-paiement.php?commande=' + ORDER_ID + '&status=success';
                } else if (result.requires_action) {
                    const {error: confirmError} = await stripe.confirmCardPayment(result.client_secret);
                    if (confirmError) {
                        showError(confirmError.message);
                    } else {
                        window.location.href = 'confirmation-paiement.php?commande=' + ORDER_ID + '&status=success';
                    }
                } else {
                    showError(result.error || 'Erreur de paiement');
                }
            } catch (error) {
                showError('Erreur de communication: ' + error.message);
            }
        }
        
        // PayPal Payment
        async function initiatePayPalPayment() {
            showLoading();
            
            try {
                const response = await fetch('/api/payments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create_paypal_payment',
                        montant: ORDER_AMOUNT,
                        commande_id: ORDER_ID,
                        return_url: window.location.origin + '/api/paypal_return.php',
                        cancel_url: window.location.href
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.approval_url) {
                    // Rediriger vers PayPal
                    window.location.href = result.approval_url;
                } else {
                    showError(result.error || 'Erreur PayPal');
                }
            } catch (error) {
                showError('Erreur de communication: ' + error.message);
            }
        }
        

    </script>
    <?php endif; ?>
</body>
</html>
