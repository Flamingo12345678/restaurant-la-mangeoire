<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - Restaurant La Mangeoire</title>
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
            font-family: 'Poppins', sans-serif;
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
        
        .back-link {
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            box-shadow: var(--shadow-light);
        }
        
        .back-link:hover {
            background: var(--primary-hover);
            color: white;
        }
    </style>
</head>
<body>
    <a href="menu.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Retour au menu
    </a>
    
    <div class="cart-container">
        <h1 class="cart-title">
            <i class="bi bi-cart"></i> Mon Panier
        </h1>
        
        <!-- Contenu du panier (géré par JavaScript) -->
        <div id="cart-content">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configuration des devises côté client (hardcodée pour éviter les erreurs PHP)
        const currencyConfig = {
            symbol: '€',
            rate: 1.0
        };
        
        function getCurrencySymbol() {
            return currencyConfig.symbol;
        }
        
        function getCurrencyRate() {
            return currencyConfig.rate;
        }
        
        // Récupérer le panier depuis localStorage
        let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
        
        // Debug: afficher le contenu du panier dans la console
        console.log('Panier chargé:', cart);
        console.log('Nombre d\'articles:', cart.length);
        console.log('Configuration devise:', currencyConfig);
        
        function renderCart() {
            const cartContent = document.getElementById('cart-content');
            console.log('Rendu du panier - articles:', cart.length);
            
            if (cart.length === 0) {
                console.log('Panier vide - affichage du message');
                cartContent.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <h3>Votre panier est vide</h3>
                        <p>Découvrez nos délicieux plats traditionnels</p>
                        <a href="menu.php" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-left"></i> Voir le menu
                        </a>
                    </div>
                `;
                return;
            }
            
            console.log('Génération du HTML pour les articles');
            let cartHTML = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                console.log(`Traitement article ${index}:`, item);
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
            
            console.log('HTML généré, mise à jour du DOM');
            cartContent.innerHTML = cartHTML;
        }
        
        function updateQuantity(itemId, newQuantity) {
            console.log(`Mise à jour quantité: ${itemId} -> ${newQuantity}`);
            const item = cart.find(item => item.id === itemId);
            if (item) {
                if (newQuantity <= 0) {
                    removeItem(itemId);
                } else {
                    item.quantity = newQuantity;
                    item.total = item.quantity * item.price;
                    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                    renderCart();
                    showNotification(`Quantité mise à jour pour "${item.name}"`, 'info');
                }
            }
        }
        
        function removeItem(itemId) {
            console.log(`Suppression article: ${itemId}`);
            const item = cart.find(item => item.id === itemId);
            if (item && confirm(`Supprimer "${item.name}" du panier ?`)) {
                cart = cart.filter(item => item.id !== itemId);
                localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                renderCart();
                showNotification(`"${item.name}" supprimé du panier`, 'info');
            }
        }
        
        function clearCart() {
            console.log('Vidage du panier');
            if (confirm('Vider complètement le panier ?')) {
                cart = [];
                localStorage.removeItem('restaurant_cart');
                renderCart();
                showNotification('Panier vidé', 'info');
            }
        }
        
        function proceedToOrder() {
            if (cart.length === 0) {
                showNotification('Votre panier est vide', 'warning');
                return;
            }
            
            // Rediriger vers la page de commande
            window.location.href = 'passer-commande.php';
        }
        
        function showNotification(message, type = 'info') {
            console.log(`Notification: ${message} (${type})`);
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
        
        // Initialiser la page
        console.log('Initialisation de la page panier');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, rendu du panier');
            renderCart();
        });
        
        // Au cas où le DOM serait déjà chargé
        if (document.readyState === 'loading') {
            console.log('Document en cours de chargement');
        } else {
            console.log('Document déjà chargé, rendu immédiat');
            renderCart();
        }
    </script>
</body>
</html>
