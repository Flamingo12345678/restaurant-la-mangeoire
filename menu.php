<?php
require_once 'includes/https-security.php'; // Sécurité HTTPS
require_once 'includes/common.php';
require_once 'includes/currency_manager.php';
require_once 'db_connexion.php';

$page_title = "Menu - Restaurant La Mangeoire";

// Gestion du changement de devise
if (isset($_GET['currency'])) {
    CurrencyManager::setCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$current_currency = CurrencyManager::getCurrentCurrency();

// Récupérer les menus depuis la base de données
$menus_data = [];
try {
  $stmt = $pdo->prepare("SELECT MenuID, NomItem, Description, Prix FROM Menus ORDER BY MenuID");
  $stmt->execute();
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($menus as $menu) {
    $menus_data[] = [
      'id' => $menu['MenuID'],
      'nom' => $menu['NomItem'],
      'description' => $menu['Description'] ?? 'Plat traditionnel délicieux',
      'prix' => $menu['Prix'],
      'prix_formate' => CurrencyManager::formatPrice($menu['Prix'], true)
    ];
  }
} catch (Exception $e) {
  error_log("Erreur récupération menus: " . $e->getMessage());
  // Fallback avec des données par défaut si la base ne fonctionne pas
  $menus_data = [
    ['id' => 1, 'nom' => 'Menu du jour', 'description' => 'Plat traditionnel', 'prix' => 12.50, 'prix_formate' => CurrencyManager::formatPrice(12.50, true)]
  ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ce1212;
            --primary-hover: #b01e28;
            --text-dark: #2c3e50;
            --text-light: #666;
            --border-light: #e8e8e8;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-hover: 0 5px 20px rgba(206,18,18,0.2);
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
        }
        
        .menu-section {
            margin-bottom: 60px;
        }
        
        .menu-title {
            color: var(--primary-color);
            text-align: center;
            padding-bottom: 40px;
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .menu-title::after {
            content: '';
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
            border-radius: 2px;
        }
        
        .menu-category {
            color: var(--primary-color);
            margin-bottom: 30px;
            padding: 15px 0;
            border-bottom: 3px solid var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            background: linear-gradient(135deg, rgba(206,18,18,0.05) 0%, transparent 100%);
            border-radius: 8px 8px 0 0;
            padding-left: 20px;
        }
        
        .menu-category::before {
            content: '🍽️';
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .menu-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
        }
        
        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-color);
        }
        
        .menu-item-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .menu-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            margin-right: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        
        .menu-item:hover .menu-img {
            transform: scale(1.05);
        }
        
        .menu-item-info {
            flex: 1;
        }
        
        .menu-item-name {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 8px;
            color: var(--text-dark);
            line-height: 1.3;
        }
        
        .menu-item-description {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .menu-item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-light);
        }
        
        .menu-item-price {
            font-weight: 800;
            color: var(--primary-color);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .menu-item-price::before {
            content: '💰';
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .add-to-cart-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-to-cart-btn:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .add-to-cart-btn i {
            font-size: 1rem;
        }
        
        .menu-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .currency-selector {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            margin-bottom: 40px;
            text-align: center;
        }
        
        .currency-selector .dropdown-toggle {
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: white;
        }
        
        .currency-selector .dropdown-toggle:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .empty-menu {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
        }
        
        .empty-menu i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .menu-title {
                font-size: 2.2rem;
            }
            
            .menu-category {
                font-size: 1.6rem;
                padding-left: 15px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .menu-item {
                padding: 20px;
            }
            
            .menu-item-header {
                flex-direction: column;
                text-align: center;
            }
            
            .menu-img {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .menu-item-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .add-to-cart-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .menu-container {
                padding: 40px 15px;
            }
            
            .menu-title {
                font-size: 1.8rem;
            }
            
            .menu-category {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="menu-container">
        <h1 class="menu-title">Notre Menu</h1>
        
        <!-- Sélecteur de devise -->
        <div class="currency-selector">
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-currency-exchange"></i> <?php echo $current_currency['name'] . ' (' . $current_currency['symbol'] . ')'; ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?currency=FR">🇫🇷 Euro (€)</a></li>
                    <li><a class="dropdown-item" href="?currency=US">🇺🇸 Dollar US ($)</a></li>
                    <li><a class="dropdown-item" href="?currency=GB">🇬🇧 Livre Sterling (£)</a></li>
                    <li><a class="dropdown-item" href="?currency=CA">🇨🇦 Dollar Canadien (C$)</a></li>
                    <li><a class="dropdown-item" href="?currency=CH">🇨🇭 Franc Suisse (CHF)</a></li>
                    <li><a class="dropdown-item" href="?currency=AU">🇦🇺 Dollar Australien (A$)</a></li>
                </ul>
            </div>
            <small class="text-muted d-block mt-2">Prix affichés en <?php echo $current_currency['name']; ?></small>
        </div>
        
        <!-- Menu dynamique depuis la base de données -->
        <div class="menu-section">
            <h2 class="menu-category">Notre Menu Traditionnel</h2>
            
            <?php if (empty($menus_data)): ?>
                <div class="empty-menu">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h3>Menu en cours de mise à jour</h3>
                    <p>Veuillez nous contacter pour connaître nos plats du jour.</p>
                    <a href="contact.php" class="btn btn-primary mt-3">
                        <i class="bi bi-telephone"></i> Nous contacter
                    </a>
                </div>
            <?php else: ?>
                <div class="menu-grid">
                    <?php foreach ($menus_data as $menu): ?>
                        <div class="menu-item">
                            <div class="menu-item-header">
                                <?php 
                                // Mapping des noms de plats vers les images existantes
                                $nom_lower = strtolower($menu['nom']);
                                $image_mapping = [
                                    'ndole' => 'ndole.png',
                                    'eru' => 'eru.png',
                                    'koki' => 'koki.png',
                                    'okok' => 'okok.png',
                                    'bongo' => 'bongo.png',
                                    'taro' => 'taro.png',
                                    'poisson braisé' => 'poisson_braisé.png'
                                ];
                                
                                $image_file = 'menu-item-1.png'; // Image par défaut
                                foreach ($image_mapping as $plat => $img) {
                                    if (strpos($nom_lower, $plat) !== false) {
                                        $image_file = $img;
                                        break;
                                    }
                                }
                                ?>
                                <img src="assets/img/menu/<?php echo $image_file; ?>" 
                                     alt="<?php echo htmlspecialchars($menu['nom']); ?>" 
                                     class="menu-img"
                                     onerror="this.src='assets/img/menu/menu-item-1.png'">
                                <div class="menu-item-info">
                                    <h3 class="menu-item-name"><?php echo htmlspecialchars($menu['nom']); ?></h3>
                                </div>
                            </div>
                            <p class="menu-item-description"><?php echo htmlspecialchars($menu['description']); ?></p>
                            <div class="menu-item-footer">
                                <div class="menu-item-price"><?php echo $menu['prix_formate']; ?></div>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $menu['id']; ?>, '<?php echo htmlspecialchars($menu['nom'], ENT_QUOTES); ?>', <?php echo $menu['prix']; ?>, '<?php echo htmlspecialchars($menu['prix_formate'], ENT_QUOTES); ?>')">
                                    <i class="bi bi-cart-plus"></i>
                                    Ajouter au panier
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
    
    <!-- Script pour l'ajout au panier -->
    <script>
        // Système de panier côté client - Namespace global pour éviter les conflits
        window.CartManager = window.CartManager || {
            getCart: function() {
                try {
                    return JSON.parse(localStorage.getItem('restaurant_cart')) || [];
                } catch (e) {
                    console.error('Erreur lecture panier:', e);
                    return [];
                }
            },
            saveCart: function(cart) {
                try {
                    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                    console.log('Panier sauvegardé:', cart);
                    return true;
                } catch (e) {
                    console.error('Erreur sauvegarde panier:', e);
                    return false;
                }
            }
        };
        
        async function addToCart(menuId, menuName, menuPrice, menuPriceFormatted) {
            console.log('Ajout au panier:', {menuId, menuName, menuPrice, menuPriceFormatted});
            
            // Animation du bouton
            const button = event.target.closest('.add-to-cart-btn');
            const originalText = button.innerHTML;
            
            // Désactiver le bouton pendant le traitement
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Ajout...';
            button.disabled = true;
            
            try {
                // 1. Ajout côté serveur via AJAX (HTTPS sécurisé)
                const formData = new FormData();
                formData.append('menu_id', menuId);
                formData.append('quantity', 1);
                formData.append('ajax', 'true');
                
                // Construire l'URL sécurisée HTTPS
                const secureUrl = window.location.protocol === 'https:' 
                    ? 'ajouter-au-panier.php' 
                    : window.location.origin.replace('http:', 'https:') + '/ajouter-au-panier.php';
                
                const response = await fetch(secureUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // Inclure les cookies de session
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Réponse serveur:', result);
                
                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de l\'ajout au panier');
                }
                
                // 2. Mise à jour du localStorage pour l'interface utilisateur
                let cart = window.CartManager.getCart();
                
                // Vérifier si l'article existe déjà dans le panier local
                const existingItem = cart.find(item => item.id === menuId);
                
                if (existingItem) {
                    existingItem.quantity += 1;
                    existingItem.total = existingItem.quantity * existingItem.price;
                    console.log('Article existant mis à jour localement:', existingItem);
                } else {
                    const newItem = {
                        id: menuId,
                        name: menuName,
                        price: menuPrice,
                        priceFormatted: menuPriceFormatted,
                        quantity: 1,
                        total: menuPrice
                    };
                    cart.push(newItem);
                    console.log('Nouvel article ajouté localement:', newItem);
                }
                
                // Sauvegarder dans localStorage pour l'interface
                if (window.CartManager.saveCart(cart)) {
                    console.log('Panier sauvegardé localement:', cart);
                    
                    // Déclencher un événement pour synchroniser les autres pages
                    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
                }
                
                // Animation de succès
                button.innerHTML = '<i class="bi bi-check-circle"></i> Ajouté !';
                button.style.background = '#28a745';
                
                // Afficher une notification de succès
                const quantity = existingItem ? existingItem.quantity : 1;
                showNotification(result.message || `"${menuName}" ajouté au panier (${quantity}x)`, 'success');
                
                // Mettre à jour le compteur dans le header
                if (window.CartCounter) {
                    window.CartCounter.updateDisplay();
                }
                
            } catch (error) {
                console.error('Erreur ajout au panier:', error);
                
                // Animation d'erreur
                button.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Erreur';
                button.style.background = '#dc3545';
                
                // Afficher une notification d'erreur
                showNotification(error.message || 'Erreur lors de l\'ajout au panier', 'error');
            }
            
            // Mettre à jour l'affichage du panier
            updateCartDisplay();
            
            // Restaurer le bouton après 2 secondes
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '#ce1212';
                button.disabled = false;
            }, 2000);
        }
        
        function updateCartDisplay() {
            const cart = window.CartManager.getCart();
            const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
            const cartTotal = cart.reduce((total, item) => total + item.total, 0);
            
            // Mettre à jour le compteur dans le header (nouveau système)
            if (window.CartCounter) {
                window.CartCounter.updateDisplay();
            }
            
            // Compatibilité avec l'ancien système
            const cartIcon = document.querySelector('.cart-icon');
            if (cartIcon) {
                cartIcon.innerHTML = `<i class="bi bi-cart"></i> (${cartCount})`;
            }
            
            // Dispatcher un événement pour informer d'autres composants
            window.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: { count: cartCount, total: cartTotal, items: cart }
            }));
        }
        
        function showNotification(message, type = 'info') {
            // Créer la notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="notification-close">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            // Ajouter les styles si pas encore présents
            if (!document.getElementById('notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: white;
                        padding: 15px 20px;
                        border-radius: 10px;
                        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                        z-index: 1000;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        max-width: 350px;
                        animation: slideIn 0.3s ease;
                        font-size: 0.9rem;
                    }
                    .notification-success {
                        border-left: 4px solid #28a745;
                        color: #155724;
                    }
                    .notification-info {
                        border-left: 4px solid #17a2b8;
                        color: #0c5460;
                    }
                    .notification-close {
                        background: none;
                        border: none;
                        font-size: 1.2rem;
                        cursor: pointer;
                        color: #999;
                        margin-left: auto;
                        min-width: 24px;
                    }
                    .notification-close:hover {
                        color: #333;
                    }
                    @keyframes slideIn {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            // Ajouter au DOM
            document.body.appendChild(notification);
            
            // Supprimer automatiquement après 5 secondes
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Fonctions utilitaires pour le panier
        function getCart() {
            return cart;
        }
        
        function getCartCount() {
            return cart.reduce((total, item) => total + item.quantity, 0);
        }
        
        function getCartTotal() {
            return cart.reduce((total, item) => total + item.total, 0);
        }
        
        function clearCart() {
            window.CartManager.saveCart([]);
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
            updateCartDisplay();
        }
        
        function removeFromCart(menuId) {
            let cart = window.CartManager.getCart();
            cart = cart.filter(item => item.id !== menuId);
            window.CartManager.saveCart(cart);
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
            updateCartDisplay();
        }
        
        function updateCartItemQuantity(menuId, newQuantity) {
            let cart = window.CartManager.getCart();
            const item = cart.find(item => item.id === menuId);
            if (item) {
                if (newQuantity <= 0) {
                    removeFromCart(menuId);
                } else {
                    item.quantity = newQuantity;
                    item.total = item.quantity * item.price;
                    window.CartManager.saveCart(cart);
                    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
                    updateCartDisplay();
                }
            }
        }
        
        // Animation d'apparition des éléments au scroll
        function animateOnScroll() {
            const items = document.querySelectorAll('.menu-item');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { 
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            items.forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(item);
            });
        }
        
        // Initialiser les animations et le panier au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            animateOnScroll();
            updateCartDisplay();
            
            // Afficher un message de bienvenue avec le contenu du panier
            const cartCount = getCartCount();
            if (cartCount > 0) {
                showNotification(`Vous avez ${cartCount} article(s) dans votre panier`, 'info');
            }
        });
        
        // Exposer les fonctions globalement pour pouvoir les utiliser depuis d'autres pages
        window.restaurantCart = {
            add: addToCart,
            get: getCart,
            count: getCartCount,
            total: getCartTotal,
            clear: clearCart,
            remove: removeFromCart,
            updateQuantity: updateCartItemQuantity
        };
    </script>
    </script>
</body>
</html>