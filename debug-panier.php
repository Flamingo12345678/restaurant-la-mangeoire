<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Panier - Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        button { margin: 5px; padding: 10px 15px; }
        pre { background: #333; color: #fff; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>🔧 Test du Panier - Debug localStorage</h1>
    
    <div class="debug-section">
        <h3>Actions de test</h3>
        <button onclick="addTestItem()">Ajouter un article test</button>
        <button onclick="showCart()">Afficher panier</button>
        <button onclick="clearTestCart()">Vider panier</button>
        <button onclick="showAllLocalStorage()">Voir tout le localStorage</button>
    </div>
    
    <div class="debug-section">
        <h3>Résultats</h3>
        <div id="results"></div>
    </div>
    
    <div class="debug-section">
        <h3>Navigation</h3>
        <a href="menu.php">Aller au menu</a> | 
        <a href="panier.php">Aller au panier</a>
    </div>

    <script>
        const CART_KEY = 'restaurant_cart';
        
        function log(message, type = 'info') {
            const results = document.getElementById('results');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ff0000' : type === 'success' ? '#00aa00' : '#333';
            
            results.innerHTML += `<div style="color: ${color}; margin: 5px 0;">
                [${timestamp}] ${message}
            </div>`;
            
            console.log(`[${timestamp}] ${message}`);
        }
        
        function addTestItem() {
            try {
                let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
                
                const testItem = {
                    id: Date.now(), // ID unique basé sur timestamp
                    name: `Article Test ${new Date().getSeconds()}`,
                    price: 12.50,
                    priceFormatted: '12,50 €',
                    quantity: 1,
                    total: 12.50
                };
                
                cart.push(testItem);
                localStorage.setItem(CART_KEY, JSON.stringify(cart));
                
                log(`Article ajouté: ${testItem.name} (ID: ${testItem.id})`, 'success');
                log(`Panier contient maintenant ${cart.length} article(s)`, 'info');
                
                // Vérifier immédiatement
                const verification = JSON.parse(localStorage.getItem(CART_KEY));
                log(`Vérification: ${verification.length} article(s) dans localStorage`, 'info');
                
            } catch (error) {
                log(`Erreur lors de l'ajout: ${error.message}`, 'error');
            }
        }
        
        function showCart() {
            try {
                const cartData = localStorage.getItem(CART_KEY);
                
                if (!cartData) {
                    log('Aucune donnée de panier trouvée dans localStorage', 'error');
                    return;
                }
                
                const cart = JSON.parse(cartData);
                log(`Panier trouvé avec ${cart.length} article(s):`, 'success');
                
                cart.forEach((item, index) => {
                    log(`  ${index + 1}. ${item.name} - ${item.priceFormatted} x${item.quantity}`, 'info');
                });
                
                log(`Total: ${cart.reduce((sum, item) => sum + item.total, 0).toFixed(2)} €`, 'info');
                
            } catch (error) {
                log(`Erreur lors de la lecture: ${error.message}`, 'error');
            }
        }
        
        function clearTestCart() {
            try {
                localStorage.removeItem(CART_KEY);
                log('Panier vidé avec succès', 'success');
                
                // Vérifier
                const verification = localStorage.getItem(CART_KEY);
                log(`Vérification: ${verification ? 'ÉCHEC - données encore présentes' : 'OK - données supprimées'}`, 
                    verification ? 'error' : 'success');
                    
            } catch (error) {
                log(`Erreur lors du vidage: ${error.message}`, 'error');
            }
        }
        
        function showAllLocalStorage() {
            log('=== CONTENU COMPLET DU LOCALSTORAGE ===', 'info');
            
            if (localStorage.length === 0) {
                log('localStorage est vide', 'info');
                return;
            }
            
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                const value = localStorage.getItem(key);
                
                log(`Clé: ${key}`, 'info');
                log(`Valeur: ${value.substring(0, 200)}${value.length > 200 ? '...' : ''}`, 'info');
                log('---', 'info');
            }
        }
        
        // Test initial au chargement
        document.addEventListener('DOMContentLoaded', function() {
            log('Page de debug chargée', 'success');
            log(`Support localStorage: ${typeof(Storage) !== "undefined" ? 'OUI' : 'NON'}`, 'info');
            
            // Vérifier l'état actuel
            showCart();
        });
        
        // Écouter les changements de localStorage
        window.addEventListener('storage', function(e) {
            if (e.key === CART_KEY) {
                log('Changement détecté dans le panier depuis un autre onglet', 'info');
                showCart();
            }
        });
    </script>
</body>
</html>
