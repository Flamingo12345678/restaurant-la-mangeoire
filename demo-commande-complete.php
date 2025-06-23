<?php
/**
 * Démonstration complète du système de commande et paiement
 * avec support multi-devises et types de paiement
 */

session_start();
require_once 'db_connexion.php';
require_once 'includes/currency_manager.php';
require_once 'includes/CartManager.php';

// Initialiser le gestionnaire de panier et de devises
$cartManager = new CartManager($pdo);

// Détecter la devise de l'utilisateur
$userCountry = CurrencyManager::detectCountry();
$userCurrency = CurrencyManager::getCurrencyForCountry($userCountry);

// Gérer le changement de devise
if (isset($_POST['change_currency'])) {
    $newCurrency = $_POST['currency_code'];
    $_SESSION['selected_currency'] = $newCurrency;
    $userCurrency = CurrencyManager::getCurrencyByCode($newCurrency);
}

// Utiliser la devise sélectionnée ou détectée
if (isset($_SESSION['selected_currency'])) {
    $userCurrency = CurrencyManager::getCurrencyByCode($_SESSION['selected_currency']);
}

// Récupérer quelques articles pour la démonstration
$stmt = $pdo->query("SELECT MenuID, NomItem, Prix, Description FROM Menus LIMIT 8");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le résumé du panier
$cartSummary = $cartManager->getSummary();

// Types de paiement disponibles
$paymentMethods = [
    'especes' => ['name' => 'Espèces', 'icon' => '💵', 'description' => 'Paiement à la livraison'],
    'carte' => ['name' => 'Carte Bancaire', 'icon' => '💳', 'description' => 'Paiement en ligne sécurisé'],
    'stripe' => ['name' => 'Stripe', 'icon' => '🔷', 'description' => 'Paiement par Stripe'],
    'paypal' => ['name' => 'PayPal', 'icon' => '🟦', 'description' => 'Paiement via PayPal'],
    'virement' => ['name' => 'Virement', 'icon' => '🏦', 'description' => 'Virement bancaire'],
    'mobile' => ['name' => 'Mobile Money', 'icon' => '📱', 'description' => 'Paiement mobile (Orange Money, MTN, etc.)']
];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $menuId = intval($_POST['menu_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($menuId > 0 && $quantity > 0) {
            $cartManager->addItem($menuId, $quantity);
            $_SESSION['cart_message'] = "Article ajouté au panier !";
            $_SESSION['cart_message_type'] = "success";
        }
        
        // Recharger la page pour éviter la resoumission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['process_order'])) {
        // Traitement de la commande
        $paymentMethod = $_POST['payment_method'] ?? 'especes';
        $deliveryType = $_POST['delivery_type'] ?? 'livraison';
        $totalAmount = floatval($_POST['total_amount']);
        
        // Ici vous pourriez traiter la commande réelle
        $_SESSION['cart_message'] = "Commande simulée avec succès ! Méthode: " . $paymentMethods[$paymentMethod]['name'] . " - Montant: " . CurrencyManager::formatPrice($totalAmount, $userCurrency['code']);
        $_SESSION['cart_message_type'] = "success";
        
        // Vider le panier après commande
        $cartManager->clearCart();
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fonction pour afficher les messages
function display_message() {
    if (isset($_SESSION['cart_message'])) {
        $type = $_SESSION['cart_message_type'] ?? 'info';
        $class = $type === 'error' ? 'alert-danger' : 'alert-success';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['cart_message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Commande Complet - Restaurant La Mangeoire</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px 0;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .currency-selector {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .menu-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .cart-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .payment-method.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 10px 0;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
        }
        
        .price-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <!-- En-tête avec sélecteur de devise -->
            <div class="header-section">
                <h1><i class="bi bi-shop"></i> Restaurant La Mangeoire</h1>
                <p class="mb-0">Système de Commande et Paiement Complet</p>
                
                <div class="currency-selector">
                    <h5><i class="bi bi-globe"></i> Devise Détectée</h5>
                    <p><strong>Pays:</strong> <?= $userCountry ?> | <strong>Devise:</strong> <?= $userCurrency['name'] ?> (<?= $userCurrency['symbol'] ?>)</p>
                    
                    <form method="POST" class="d-inline">
                        <select name="currency_code" class="form-select form-select-sm d-inline w-auto me-2" onchange="this.form.submit()">
                            <option value="">Changer de devise</option>
                            <?php
                            $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'MAD', 'TND'];
                            foreach ($currencies as $code) {
                                $currency = CurrencyManager::getCurrencyByCode($code);
                                if ($currency) {
                                    $selected = ($code === $userCurrency['code']) ? 'selected' : '';
                                    echo "<option value='$code' $selected>{$currency['name']} ({$currency['symbol']})</option>";
                                }
                            }
                            ?>
                        </select>
                        <input type="hidden" name="change_currency" value="1">
                    </form>
                </div>
            </div>

            <div class="container-fluid p-4">
                <!-- Messages -->
                <?php display_message(); ?>

                <div class="row">
                    <!-- Section Menu -->
                    <div class="col-lg-8">
                        <h3><i class="bi bi-menu-button-wide"></i> Notre Menu</h3>
                        <div class="row">
                            <?php foreach ($menu_items as $item): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card menu-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($item['NomItem']) ?></h5>
                                            <p class="card-text text-muted"><?= htmlspecialchars($item['Description']) ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="price-badge">
                                                    <?= CurrencyManager::formatPrice($item['Prix'], $userCurrency['code']) ?>
                                                </span>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="menu_id" value="<?= $item['MenuID'] ?>">
                                                    <input type="number" name="quantity" value="1" min="1" max="10" class="form-control form-control-sm d-inline w-auto me-2" style="width: 70px !important;">
                                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-cart-plus"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Section Panier et Commande -->
                    <div class="col-lg-4">
                        <!-- Statistiques du panier -->
                        <div class="stats-card">
                            <h4><i class="bi bi-cart"></i> Panier</h4>
                            <div class="row text-center">
                                <div class="col-6">
                                    <h3><?= $cartSummary['total_quantity'] ?></h3>
                                    <small>Articles</small>
                                </div>
                                <div class="col-6">
                                    <h3><?= CurrencyManager::formatPrice($cartSummary['total_amount'], $userCurrency['code']) ?></h3>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>

                        <!-- Détails du panier -->
                        <?php if (!empty($cartSummary['items'])): ?>
                            <div class="cart-section">
                                <h5><i class="bi bi-list-ul"></i> Détails du Panier</h5>
                                <?php foreach ($cartSummary['items'] as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded">
                                        <div>
                                            <strong><?= htmlspecialchars($item['NomItem']) ?></strong><br>
                                            <small class="text-muted">Qté: <?= $item['Quantite'] ?></small>
                                        </div>
                                        <span class="badge bg-primary">
                                            <?= CurrencyManager::formatPrice($item['Prix'] * $item['Quantite'], $userCurrency['code']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Formulaire de commande -->
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="total_amount" value="<?= $cartSummary['total_amount'] ?>">
                                    
                                    <h6><i class="bi bi-truck"></i> Type de Livraison</h6>
                                    <div class="mb-3">
                                        <label class="form-check-label">
                                            <input type="radio" name="delivery_type" value="livraison" class="form-check-input" checked>
                                            Livraison à domicile
                                        </label><br>
                                        <label class="form-check-label">
                                            <input type="radio" name="delivery_type" value="emporter" class="form-check-input">
                                            À emporter
                                        </label>
                                    </div>

                                    <h6><i class="bi bi-credit-card"></i> Mode de Paiement</h6>
                                    <?php foreach ($paymentMethods as $key => $method): ?>
                                        <label class="payment-method w-100">
                                            <input type="radio" name="payment_method" value="<?= $key ?>" <?= $key === 'especes' ? 'checked' : '' ?> style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <span class="me-3" style="font-size: 1.5em;"><?= $method['icon'] ?></span>
                                                <div>
                                                    <strong><?= $method['name'] ?></strong><br>
                                                    <small class="text-muted"><?= $method['description'] ?></small>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>

                                    <button type="submit" name="process_order" class="btn btn-primary w-100 mt-3">
                                        <i class="bi bi-check-circle"></i> Commander - <?= CurrencyManager::formatPrice($cartSummary['total_amount'], $userCurrency['code']) ?>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="cart-section text-center">
                                <i class="bi bi-cart-x" style="font-size: 3em; color: #6c757d;"></i>
                                <h5 class="mt-3">Panier Vide</h5>
                                <p class="text-muted">Ajoutez des articles pour commencer</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gestion de la sélection des méthodes de paiement
        document.querySelectorAll('.payment-method').forEach(function(element) {
            element.addEventListener('click', function() {
                // Déselectionner tous
                document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
                
                // Sélectionner le cliqué
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Marquer la méthode par défaut comme sélectionnée
        document.querySelector('.payment-method input[checked]').closest('.payment-method').classList.add('selected');
    </script>
</body>
</html>
