<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Panier Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .cart-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .debug { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .empty-cart { text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="bi bi-cart"></i> Test Panier Simple</h1>
        
        <div class="debug">
            <strong>Debug Info</strong>
            <div id="debug-info"></div>
        </div>
        
        <div id="cart-content">
            <p>Chargement du panier...</p>
        </div>
        
        <div class="mt-3">
            <button class="btn btn-success" onclick="addTestItem()">Ajouter un article de test</button>
            <button class="btn btn-warning" onclick="clearCart()">Vider le panier</button>
            <button class="btn btn-info" onclick="refreshCart()">Rafraîchir</button>
        </div>
    </div>

    <script>
        // Test simple du panier
        let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
        
        function updateDebugInfo() {
            const debugDiv = document.getElementById('debug-info');
            debugDiv.innerHTML = `
                <p><strong>Contenu localStorage:</strong></p>
                <pre>${JSON.stringify(cart, null, 2)}</pre>
                <p><strong>Nombre d'articles:</strong> ${cart.length}</p>
                <p><strong>Clé localStorage:</strong> ${localStorage.getItem('restaurant_cart') || 'null'}</p>
            `;
        }
        
        function renderSimpleCart() {
            const cartContent = document.getElementById('cart-content');
            console.log('Rendu du panier - articles:', cart);
            
            if (cart.length === 0) {
                console.log('Panier vide');
                cartContent.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: #999;"></i>
                        <h3>Panier vide</h3>
                        <p>Aucun article dans le panier</p>
                    </div>
                `;
                return;
            }
            
            console.log('Génération du HTML pour', cart.length, 'articles');
            let cartHTML = '<h3>Articles dans le panier:</h3>';
            let total = 0;
            
            cart.forEach((item, index) => {
                console.log(`Article ${index}:`, item);
                total += item.total || 0;
                cartHTML += `
                    <div class="cart-item">
                        <h5>${item.name || 'Nom manquant'}</h5>
                        <p>Prix: ${item.priceFormatted || item.price + '€'}</p>
                        <p>Quantité: ${item.quantity || 1}</p>
                        <p>Total: ${(item.total || 0).toFixed(2)}€</p>
                        <button class="btn btn-sm btn-danger" onclick="removeItem(${item.id})">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </div>
                `;
            });
            
            cartHTML += `
                <div class="mt-3 p-3 bg-light rounded">
                    <h4>Total général: ${total.toFixed(2)}€</h4>
                </div>
            `;
            
            console.log('HTML généré:', cartHTML);
            cartContent.innerHTML = cartHTML;
        }
        
        function addTestItem() {
            const testItem = {
                id: Date.now(),
                name: 'Article de test',
                price: 10.50,
                priceFormatted: '10,50 €',
                quantity: 1,
                total: 10.50
            };
            
            cart.push(testItem);
            localStorage.setItem('restaurant_cart', JSON.stringify(cart));
            console.log('Article ajouté:', testItem);
            refreshCart();
        }
        
        function removeItem(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            localStorage.setItem('restaurant_cart', JSON.stringify(cart));
            console.log('Article supprimé, panier restant:', cart);
            refreshCart();
        }
        
        function clearCart() {
            cart = [];
            localStorage.removeItem('restaurant_cart');
            console.log('Panier vidé');
            refreshCart();
        }
        
        function refreshCart() {
            cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
            console.log('Panier rechargé:', cart);
            renderSimpleCart();
            updateDebugInfo();
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page chargée, initialisation du panier');
            refreshCart();
        });
    </script>
</body>
</html>
