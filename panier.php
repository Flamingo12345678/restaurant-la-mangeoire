<?php
/**
 * Page Panier - Interface utilisateur moderne
 * 
 * Affichage du panier avec possibilité de modifier les quantités,
 * supprimer des articles et procéder au paiement
 */

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

// Initialiser le gestionnaire de panier
$cartManager = new CartManager($pdo);

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_quantity':
            $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            
            if ($menu_id && $quantity !== false) {
                $result = $cartManager->updateItem($menu_id, $quantity);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'remove_item':
            $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
            
            if ($menu_id) {
                $result = $cartManager->removeItem($menu_id);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'clear_cart':
            $result = $cartManager->clear();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
    }
    
    // Redirection pour éviter la resoumission
    header("Location: panier.php" . (isset($message) ? "?msg=" . urlencode($message) . "&type=" . $message_type : ""));
    exit;
}

// Récupérer les articles du panier
$cart_items = $cartManager->getItems();
$cart_summary = $cartManager->getSummary();

// Message de notification
$notification_message = '';
$notification_type = '';

if (isset($_GET['msg'])) {
    $notification_message = $_GET['msg'];
    $notification_type = $_GET['type'] ?? 'info';
}

// Vérifier les messages de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['cart_message'])) {
    $notification_message = $_SESSION['cart_message']['text'];
    $notification_type = $_SESSION['cart_message']['type'];
    unset($_SESSION['cart_message']);
}

$page_title = "Mon Panier";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Restaurant La Mangeoire</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .cart-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #2980b9;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        
        .cart-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #3498db !important;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.1em;
            border-radius: 25px;
            width: 100%;
            transition: transform 0.3s ease;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-cart i {
            font-size: 5em;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .alert-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-remove:hover {
            background: #c0392b;
        }
        
        @media (max-width: 768px) {
            .cart-container {
                padding: 10px;
            }
            
            .cart-item .row {
                text-align: center;
            }
            
            .cart-item .col-md-3,
            .cart-item .col-md-2 {
                margin-bottom: 15px;
            }
            
            .quantity-controls {
                justify-content: center;
            }
            
            .item-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

    <!-- Notification -->
    <?php if ($notification_message): ?>
    <div class="alert alert-<?php echo $notification_type === 'error' ? 'danger' : $notification_type; ?> alert-dismissible fade show alert-notification" role="alert">
        <i class="fas fa-<?php echo $notification_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($notification_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="cart-container">
        <!-- En-tête -->
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Mon Panier</h1>
            <p class="mb-0">
                <?php echo $cart_summary['total_items']; ?> article<?php echo $cart_summary['total_items'] > 1 ? 's' : ''; ?>
                <?php if ($cart_summary['total_items'] > 0): ?>
                    - Total: <?php echo number_format($cart_summary['total_amount'], 2); ?> €
                <?php endif; ?>
            </p>
        </div>

        <?php if ($cart_summary['is_empty']): ?>
            <!-- Panier vide -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Votre panier est vide</h3>
                <p>Découvrez notre délicieuse carte et ajoutez vos plats préférés!</p>
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-utensils"></i> Voir la carte
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Articles du panier -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Articles dans votre panier</h4>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn btn-outline-danger btn-sm" 
                                    onclick="return confirm('Êtes-vous sûr de vouloir vider votre panier ?')">
                                <i class="fas fa-trash"></i> Vider le panier
                            </button>
                        </form>
                    </div>

                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="p-3">
                            <div class="row align-items-center">
                                <!-- Image -->
                                <div class="col-md-3 col-sm-4">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="item-image">
                                </div>
                                
                                <!-- Détails -->
                                <div class="col-md-4 col-sm-8">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <?php if (!empty($item['description'])): ?>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <?php endif; ?>
                                    <strong class="text-primary"><?php echo number_format($item['price'], 2); ?> €</strong>
                                </div>
                                
                                <!-- Quantité -->
                                <div class="col-md-3 col-sm-6">
                                    <form method="post" class="quantity-form">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="menu_id" value="<?php echo $item['menu_id']; ?>">
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="99" class="quantity-input"
                                                   onchange="this.form.submit()">
                                            <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Actions -->
                                <div class="col-md-2 col-sm-6">
                                    <div class="item-actions">
                                        <div class="text-center">
                                            <strong><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</strong>
                                            <form method="post" style="margin-top: 10px;">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="menu_id" value="<?php echo $item['menu_id']; ?>">
                                                <button type="submit" class="btn-remove" 
                                                        onclick="return confirm('Supprimer cet article ?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Résumé et commande -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-4">Résumé de la commande</h4>
                        
                        <div class="summary-row">
                            <span>Articles (<?php echo $cart_summary['total_items']; ?>)</span>
                            <span><?php echo number_format($cart_summary['total_amount'], 2); ?> €</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Livraison</span>
                            <span class="text-success">Gratuite</span>
                        </div>
                        
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span><?php echo number_format($cart_summary['total_amount'], 2); ?> €</span>
                        </div>
                        
                        <div class="mt-4">
                            <a href="passer-commande.php" class="btn btn-checkout">
                                <i class="fas fa-credit-card"></i> Passer la commande
                            </a>
                        </div>
                        
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left"></i> Continuer mes achats
                            </a>
                        </div>
                        
                        <!-- Informations supplémentaires -->
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Paiement sécurisé<br>
                                <i class="fas fa-truck"></i> Livraison gratuite<br>
                                <i class="fas fa-undo"></i> Satisfaction garantie
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Contrôles de quantité
        function changeQuantity(button, change) {
            const input = button.parentElement.querySelector('input[name="quantity"]');
            const currentValue = parseInt(input.value);
            const newValue = currentValue + change;
            
            if (newValue >= 1 && newValue <= 99) {
                input.value = newValue;
                input.form.submit();
            }
        }
        
        // Auto-masquer les notifications
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert-notification');
            if (alert) {
                setTimeout(() => {
                    alert.classList.remove('show');
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            }
        });
        
        // Confirmation avant suppression
        document.querySelectorAll('.btn-remove').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>