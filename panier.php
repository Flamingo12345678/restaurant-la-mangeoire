<?php
// Démarrer la session en premier, avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/currency_manager.php';

$page_title = "Panier - Restaurant La Mangeoire";
$current_currency = CurrencyManager::getCurrentCurrency();
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
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* Header styles */
        .site-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        
        .logo h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .logo a {
            text-decoration: none;
        }
        
        .nav-menu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 2rem;
        }
        
        .nav-menu a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-menu a:hover {
            color: var(--primary-color);
        }
        
        .cart-icon {
            position: relative;
        }
        
        .cart-icon a {
            color: var(--primary-color);
            font-size: 1.5rem;
            text-decoration: none;
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        /* Footer styles */
        .site-footer {
            background: #2c3e50;
            color: white;
            margin-top: auto;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 3rem 0;
        }
        
        .footer-info h3, .footer-links h4, .footer-social h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-links a {
            color: #bdc3c7;
            font-size: 1.5rem;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: var(--primary-color);
        }
        
        .footer-bottom {
            border-top: 1px solid #34495e;
            padding: 1.5rem 0;
            text-align: center;
            color: #bdc3c7;
        }
        
        .footer-legal a {
            color: #bdc3c7;
            text-decoration: none;
        }
        
        .footer-legal a:hover {
            color: white;
        }
        
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .cart-title {
            color: var(--primary-color);
            text-align: center;
            padding-bottom: 40px;
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .cart-title::after {
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
        
        .cart-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-light);
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            border: 1px solid var(--border-light);
        }
        
        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            border-radius: 25px;
            padding: 5px;
        }
        
        .quantity-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        .quantity-btn:hover {
            background: var(--primary-hover);
        }
        
        .quantity-display {
            min-width: 40px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .cart-item-total {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--primary-color);
            min-width: 100px;
            text-align: right;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .remove-btn:hover {
            background: #c82333;
        }
        
        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            margin-top: 30px;
            text-align: center;
        }
        
        .cart-total {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-outline-secondary {
            border: 2px solid var(--text-light);
            color: var(--text-light);
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-outline-secondary:hover {
            background: var(--text-light);
            color: white;
        }
        
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: var(--text-light);
            margin-bottom: 30px;
        }
        
        .empty-cart h3 {
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .cart-item-controls {
                width: 100%;
                justify-content: space-between;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .cart-title {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Header simplifié pour éviter les conflits de session
    ?>
    <header class="site-header">
        <div class="container">
            <nav class="main-nav">
                <div class="logo">
                    <a href="index.php">
                        <h1>La Mangeoire</h1>
                    </a>
                </div>
                <div class="nav-menu">
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="menu.php">Menu</a></li>
                        <li><a href="index.php#book-a-table">Réservation</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                        <li><a href="connexion-unifiee.php">Connexion</a></li>
                    </ul>
                </div>
                <div class="cart-icon">
                    <a href="panier.php">
                        <i class="bi bi-cart"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <div class="cart-container">
        <h1 class="cart-title">
            <i class="bi bi-cart"></i> Mon Panier
        </h1>
        
        <!-- Boutons de debug (temporaires) -->
        <div style="text-align: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
            <h5>🔧 Debug du panier</h5>
            <button onclick="testAndSyncCart()" class="btn btn-sm btn-info me-2">
                <i class="bi bi-arrow-clockwise"></i> Recharger panier
            </button>
            <button onclick="showLocalStorageInfo()" class="btn btn-sm btn-secondary me-2">
                <i class="bi bi-info-circle"></i> Info localStorage
            </button>
            <button onclick="addTestItem()" class="btn btn-sm btn-warning">
                <i class="bi bi-plus-circle"></i> Ajouter item test
            </button>
        </div>
        
        <!-- Contenu du panier (géré par JavaScript) -->
        <div id="cart-content">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <h3>Restaurant La Mangeoire</h3>
                    <p>123 Rue de la Gastronomie<br>75000 Paris, France</p>
                    <p><i class="bi bi-telephone"></i> +33 1 23 45 67 89</p>
                    <p><i class="bi bi-envelope"></i> contact@la-mangeoire.fr</p>
                </div>
                <div class="footer-links">
                    <h4>Liens rapides</h4>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="menu.php">Menu</a></li>
                        <li><a href="index.php#book-a-table">Réservation</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-social">
                    <h4>Suivez-nous</h4>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Restaurant La Mangeoire. Tous droits réservés.</p>
                <div class="footer-legal">
                    <a href="mentions-legales.php">Mentions légales</a> |
                    <a href="politique-confidentialite.php">Politique de confidentialité</a> |
                    <a href="#" onclick="openCookieSettings()">Gérer mes préférences de cookies</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
    
    <script>
        // Configuration des devises côté client
        const currencyConfig = {
            symbol: '<?php echo addslashes($current_currency['symbol']); ?>',
            rate: <?php echo floatval($current_currency['rate']); ?>
        };
        
        function getCurrencySymbol() {
            return currencyConfig.symbol;
        }
        
        function getCurrencyRate() {
            return currencyConfig.rate;
        }
        
        // Système de panier unifié - utilise le même système que menu.php
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
        
        // Debug approfondi: afficher le contenu du localStorage
        console.log('=== DEBUG PANIER ===');
        const cart = window.CartManager.getCart();
        console.log('localStorage complet:', localStorage);
        console.log('Clé restaurant_cart existe:', localStorage.getItem('restaurant_cart') !== null);
        console.log('Valeur brute restaurant_cart:', localStorage.getItem('restaurant_cart'));
        console.log('Panier parsé:', cart);
        console.log('Nombre d\'articles:', cart.length);
        console.log('Configuration devise:', currencyConfig);
        console.log('===================');
        
        function renderCart() {
            const cart = window.CartManager.getCart(); // Toujours récupérer les données fraîches
            const cartContent = document.getElementById('cart-content');
            console.log('=== RENDU DU PANIER ===');
            console.log('Nombre d\'articles à afficher:', cart.length);
            console.log('Articles détaillés:', cart);
            
            if (cart.length === 0) {
                console.log('Panier vide - affichage du message');
                cartContent.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <h3>Votre panier est vide</h3>
                        <p>Découvrez nos délicieux plats traditionnels</p>
                        <div style="margin-top: 20px;">
                            <a href="menu.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Voir le menu
                            </a>
                        </div>
                        <div style="margin-top: 15px; font-size: 0.9rem; color: #666;">
                            <strong>Debug:</strong> ${cart.length} article(s) dans le panier<br>
                            localStorage: ${localStorage.getItem('restaurant_cart') ? 'TROUVÉ' : 'VIDE'}
                        </div>
                    </div>
                `;
                return;
            }
            
            let cartHTML = '';
            let total = 0;
            
            cart.forEach(item => {
                total += item.total;
                cartHTML += `
                    <div class="cart-item" data-id="${item.id}">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">${item.priceFormatted} l'unité</div>
                        </div>
                        <div class="cart-item-controls">
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="quantity-display">${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <div class="cart-item-total">${getCurrencySymbol()}${(item.total * getCurrencyRate()).toFixed(2)}</div>
                            <button class="remove-btn" onclick="removeItem(${item.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            const formattedTotal = `${getCurrencySymbol()}${(total * getCurrencyRate()).toFixed(2)}`;
            
            cartHTML += `
                <div class="cart-summary">
                    <div class="cart-total">
                        Total: ${formattedTotal}
                    </div>
                    <div class="cart-actions">
                        <button class="btn btn-outline-secondary" onclick="clearCart()">
                            <i class="bi bi-trash"></i> Vider le panier
                        </button>
                        <a href="menu.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Continuer mes achats
                        </a>
                        <button class="btn btn-primary" onclick="proceedToOrder()">
                            <i class="bi bi-credit-card"></i> Passer commande
                        </button>
                    </div>
                </div>
            `;
            
            cartContent.innerHTML = cartHTML;
        }
        
        function updateQuantity(itemId, newQuantity) {
            let cart = window.CartManager.getCart();
            const item = cart.find(item => item.id === itemId);
            if (item) {
                if (newQuantity <= 0) {
                    removeItem(itemId);
                } else {
                    item.quantity = newQuantity;
                    item.total = item.quantity * item.price;
                    window.CartManager.saveCart(cart);
                    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
                    renderCart();
                    showNotification(`Quantité mise à jour pour "${item.name}"`, 'info');
                }
            }
        }
        
        function removeItem(itemId) {
            let cart = window.CartManager.getCart();
            const item = cart.find(item => item.id === itemId);
            if (item && confirm(`Supprimer "${item.name}" du panier ?`)) {
                cart = cart.filter(item => item.id !== itemId);
                window.CartManager.saveCart(cart);
                window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
                renderCart();
                showNotification(`"${item.name}" supprimé du panier`, 'info');
            }
        }
        
        function clearCart() {
            if (confirm('Vider complètement le panier ?')) {
                window.CartManager.saveCart([]);
                window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
                renderCart();
                showNotification('Panier vidé', 'info');
            }
        }
        
        // Fonction de test et synchronisation du panier
        function testAndSyncCart() {
            console.log('=== TEST SYNCHRONISATION PANIER ===');
            
            // Tester si localStorage fonctionne
            try {
                localStorage.setItem('test', 'ok');
                const testValue = localStorage.getItem('test');
                localStorage.removeItem('test');
                console.log('localStorage fonctionne:', testValue === 'ok');
            } catch (e) {
                console.error('Erreur localStorage:', e);
            }
            
            // Vérifier toutes les clés du localStorage
            console.log('Toutes les clés dans localStorage:');
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                console.log(`  ${key}: ${localStorage.getItem(key)}`);
            }
            
            // Recharger le panier
            const rawCart = localStorage.getItem('restaurant_cart');
            if (rawCart) {
                try {
                    cart = JSON.parse(rawCart);
                    console.log('Panier rechargé avec succès:', cart);
                } catch (e) {
                    console.error('Erreur parsing panier:', e);
                    cart = [];
                }
            } else {
                console.log('Aucune donnée de panier trouvée dans localStorage');
                cart = [];
            }
            
            // Mettre à jour l'affichage
            renderCart();
            updateCartCount();
            
            console.log('=== FIN TEST SYNCHRONISATION ===');
        }
        
        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
            const cartCountElement = document.getElementById('cart-count');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            if (cartCountElement) {
                cartCountElement.textContent = totalItems;
                console.log('Compteur panier mis à jour:', totalItems);
            }
        }
        
        function proceedToOrder() {
            const cart = window.CartManager.getCart();
            if (cart.length === 0) {
                showNotification('Votre panier est vide', 'warning');
                return;
            }
            
            // Rediriger vers la nouvelle page de commande moderne
            window.location.href = 'commande-moderne.php';
        }
        
        function showNotification(message, type = 'info') {
            // Créer la notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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
                    .notification-success { border-left: 4px solid #28a745; color: #155724; }
                    .notification-info { border-left: 4px solid #17a2b8; color: #0c5460; }
                    .notification-warning { border-left: 4px solid #ffc107; color: #856404; }
                    .notification-close {
                        background: none; border: none; font-size: 1.2rem; cursor: pointer;
                        color: #999; margin-left: auto; min-width: 24px;
                    }
                    .notification-close:hover { color: #333; }
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Fonctions de debug
        function showLocalStorageInfo() {
            console.log('=== INFORMATIONS LOCALSTORAGE ===');
            console.log('Support localStorage:', typeof(Storage) !== "undefined");
            console.log('Nombre de clés:', localStorage.length);
            
            let info = 'INFORMATIONS LOCALSTORAGE:\\n\\n';
            info += `Support: ${typeof(Storage) !== "undefined" ? 'OUI' : 'NON'}\\n`;
            info += `Nombre de clés: ${localStorage.length}\\n\\n`;
            
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                const value = localStorage.getItem(key);
                info += `${key}: ${value.substring(0, 100)}${value.length > 100 ? '...' : ''}\\n`;
                console.log(`Clé ${i}: ${key} = ${value}`);
            }
            
            alert(info);
            console.log('=== FIN INFORMATIONS ===');
        }
        
        function addTestItem() {
            const testItem = {
                id: 999,
                name: 'Article Test',
                price: 10.00,
                priceFormatted: '10,00 €',
                quantity: 1,
                total: 10.00
            };
            
            let cart = window.CartManager.getCart();
            cart.push(testItem);
            window.CartManager.saveCart(cart);
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
            
            console.log('Article test ajouté:', testItem);
            console.log('Panier après ajout:', cart);
            
            renderCart();
            updateCartCount();
            showNotification('Article test ajouté au panier', 'success');
        }
        
        // Initialiser la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé - initialisation du panier');
            testAndSyncCart();
            
            // Écouter les changements de localStorage (si modifié depuis un autre onglet)
            window.addEventListener('storage', function(e) {
                if (e.key === 'restaurant_cart') {
                    console.log('Panier modifié dans un autre onglet, rechargement...');
                    testAndSyncCart();
                }
            });
            
            // Écouter les événements de mise à jour du panier depuis d'autres pages
            window.addEventListener('cartUpdated', function(e) {
                console.log('Événement cartUpdated reçu:', e.detail);
                renderCart(); // Re-rendre le panier avec les nouvelles données
            });
        });
    </script>
</body>
</html>