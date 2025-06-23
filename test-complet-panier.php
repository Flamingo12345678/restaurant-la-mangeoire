<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Complet du Panier - La Mangeoire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .warning { background: #fff3cd; color: #856404; }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        #cart-display {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .item-card {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            background: white;
        }
        .controls {
            margin: 10px 0;
        }
        .test-menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #ccc;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            background: white;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Complet du Système Panier - La Mangeoire</h1>
    
    <div class="test-section">
        <h2>📋 État du localStorage</h2>
        <button class="btn" onclick="checkLocalStorage()">Vérifier localStorage</button>
        <button class="btn btn-warning" onclick="clearCart()">Vider le panier</button>
        <button class="btn btn-success" onclick="addTestData()">Ajouter données de test</button>
        <div id="localStorage-status"></div>
    </div>

    <div class="test-section">
        <h2>🛒 Simulation Menu - Ajout d'articles</h2>
        <div class="test-menu-item">
            <div>
                <strong>Ndolé aux crevettes</strong><br>
                <small>Prix: 8500 FCFA</small>
            </div>
            <button class="btn" onclick="addToCartTest(1, 'Ndolé aux crevettes', 8500)">Ajouter au panier</button>
        </div>
        <div class="test-menu-item">
            <div>
                <strong>Eru aux arachides</strong><br>
                <small>Prix: 7500 FCFA</small>
            </div>
            <button class="btn" onclick="addToCartTest(2, 'Eru aux arachides', 7500)">Ajouter au panier</button>
        </div>
        <div class="test-menu-item">
            <div>
                <strong>Koki beans</strong><br>
                <small>Prix: 6000 FCFA</small>
            </div>
            <button class="btn" onclick="addToCartTest(3, 'Koki beans', 6000)">Ajouter au panier</button>
        </div>
    </div>

    <div class="test-section">
        <h2>📦 Affichage du Panier</h2>
        <button class="btn" onclick="renderCart()">Actualiser l'affichage</button>
        <div id="cart-display">
            <p>Chargement du panier...</p>
        </div>
    </div>

    <div class="test-section">
        <h2>🔧 Tests Techniques</h2>
        <button class="btn" onclick="runAllTests()">Lancer tous les tests</button>
        <div id="technical-tests"></div>
    </div>

    <div class="test-section">
        <h2>📊 Logs en temps réel</h2>
        <button class="btn btn-danger" onclick="clearLogs()">Effacer les logs</button>
        <div id="logs-container"></div>
    </div>

    <script>
        // Système de logs
        let logs = [];
        
        function addLog(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            logs.push({timestamp, message, type});
            updateLogsDisplay();
        }
        
        function updateLogsDisplay() {
            const container = document.getElementById('logs-container');
            container.innerHTML = logs.map(log => 
                `<div class="test-result ${log.type}">[${log.timestamp}] ${log.message}</div>`
            ).join('');
            container.scrollTop = container.scrollHeight;
        }
        
        function clearLogs() {
            logs = [];
            updateLogsDisplay();
        }

        // Fonctions de test du panier
        function getCart() {
            try {
                const cartData = localStorage.getItem('restaurant_cart');
                addLog(`localStorage raw data: ${cartData}`, 'info');
                return cartData ? JSON.parse(cartData) : [];
            } catch (e) {
                addLog(`Erreur lors de la lecture du panier: ${e.message}`, 'error');
                return [];
            }
        }

        function saveCart(cart) {
            try {
                localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                addLog(`Panier sauvegardé: ${cart.length} éléments`, 'success');
                return true;
            } catch (e) {
                addLog(`Erreur lors de la sauvegarde: ${e.message}`, 'error');
                return false;
            }
        }

        function addToCartTest(id, name, price) {
            addLog(`Tentative d'ajout: ${name} (ID: ${id}, Prix: ${price})`, 'info');
            
            let cart = getCart();
            
            const existingItem = cart.find(item => item.id == id);
            
            if (existingItem) {
                existingItem.quantity += 1;
                addLog(`Article existant, quantité mise à jour: ${existingItem.quantity}`, 'info');
            } else {
                const newItem = {
                    id: parseInt(id),
                    name: name,
                    price: parseFloat(price),
                    quantity: 1,
                    added_at: new Date().toISOString()
                };
                cart.push(newItem);
                addLog(`Nouvel article ajouté: ${JSON.stringify(newItem)}`, 'success');
            }
            
            if (saveCart(cart)) {
                renderCart();
                showNotification(`${name} ajouté au panier !`, 'success');
            }
        }

        function removeFromCart(id) {
            let cart = getCart();
            const originalLength = cart.length;
            cart = cart.filter(item => item.id != id);
            
            if (cart.length < originalLength) {
                saveCart(cart);
                addLog(`Article supprimé (ID: ${id})`, 'warning');
                renderCart();
                showNotification('Article supprimé du panier', 'warning');
            }
        }

        function updateQuantity(id, quantity) {
            let cart = getCart();
            const item = cart.find(item => item.id == id);
            
            if (item) {
                if (quantity <= 0) {
                    removeFromCart(id);
                } else {
                    item.quantity = parseInt(quantity);
                    saveCart(cart);
                    addLog(`Quantité mise à jour pour ID ${id}: ${quantity}`, 'info');
                    renderCart();
                }
            }
        }

        function clearCart() {
            localStorage.removeItem('restaurant_cart');
            addLog('Panier vidé', 'warning');
            renderCart();
            showNotification('Panier vidé', 'warning');
        }

        function renderCart() {
            const cart = getCart();
            const container = document.getElementById('cart-display');
            
            addLog(`Rendu du panier: ${cart.length} éléments`, 'info');
            
            if (!cart || cart.length === 0) {
                container.innerHTML = '<p class="test-result info">🛒 Votre panier est vide</p>';
                return;
            }
            
            let total = 0;
            let html = '<h3>Contenu du panier:</h3>';
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                html += `
                    <div class="item-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${item.name}</strong><br>
                                <small>Prix unitaire: ${item.price.toLocaleString()} FCFA</small><br>
                                <small>Ajouté le: ${new Date(item.added_at || Date.now()).toLocaleString()}</small>
                            </div>
                            <div class="controls">
                                <button class="btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <span style="margin: 0 10px;"><strong>${item.quantity}</strong></span>
                                <button class="btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                                <button class="btn btn-danger" onclick="removeFromCart(${item.id})">Supprimer</button>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 10px;">
                            <strong>Sous-total: ${itemTotal.toLocaleString()} FCFA</strong>
                        </div>
                    </div>
                `;
            });
            
            html += `
                <div style="background: #e9ecef; padding: 15px; margin-top: 15px; border-radius: 4px;">
                    <h3 style="margin: 0;">Total: ${total.toLocaleString()} FCFA</h3>
                </div>
            `;
            
            container.innerHTML = html;
        }

        function checkLocalStorage() {
            const statusDiv = document.getElementById('localStorage-status');
            let html = '';
            
            // Test de base localStorage
            if (typeof(Storage) !== "undefined") {
                html += '<div class="test-result success">✅ localStorage est supporté</div>';
            } else {
                html += '<div class="test-result error">❌ localStorage n\'est pas supporté</div>';
                statusDiv.innerHTML = html;
                return;
            }
            
            // Vérification du contenu
            const cartData = localStorage.getItem('restaurant_cart');
            html += `<div class="test-result info">📦 Contenu brut: <pre>${cartData || 'null'}</pre></div>`;
            
            try {
                const cart = cartData ? JSON.parse(cartData) : [];
                html += `<div class="test-result success">✅ Données JSON valides: ${cart.length} éléments</div>`;
                
                if (cart.length > 0) {
                    html += '<div class="test-result info">📋 Détails des éléments:<pre>' + JSON.stringify(cart, null, 2) + '</pre></div>';
                }
            } catch (e) {
                html += `<div class="test-result error">❌ Erreur JSON: ${e.message}</div>`;
            }
            
            // Test de capacité
            try {
                localStorage.setItem('test', 'test');
                localStorage.removeItem('test');
                html += '<div class="test-result success">✅ Écriture/lecture fonctionnelle</div>';
            } catch (e) {
                html += `<div class="test-result error">❌ Problème d'écriture: ${e.message}</div>`;
            }
            
            statusDiv.innerHTML = html;
        }

        function addTestData() {
            const testItems = [
                {id: 100, name: 'Test Ndolé', price: 8500, quantity: 2, added_at: new Date().toISOString()},
                {id: 101, name: 'Test Eru', price: 7500, quantity: 1, added_at: new Date().toISOString()},
                {id: 102, name: 'Test Koki', price: 6000, quantity: 3, added_at: new Date().toISOString()}
            ];
            
            saveCart(testItems);
            addLog('Données de test ajoutées', 'success');
            renderCart();
            showNotification('Données de test ajoutées !', 'success');
        }

        function runAllTests() {
            const container = document.getElementById('technical-tests');
            let html = '<h3>Résultats des tests:</h3>';
            
            // Test 1: Compatibilité localStorage
            html += '<h4>Test 1: Compatibilité localStorage</h4>';
            if (typeof(Storage) !== "undefined") {
                html += '<div class="test-result success">✅ localStorage supporté</div>';
            } else {
                html += '<div class="test-result error">❌ localStorage non supporté</div>';
            }
            
            // Test 2: Persistance des données
            html += '<h4>Test 2: Persistance des données</h4>';
            const testKey = 'test_persistence_' + Date.now();
            const testValue = {test: 'data', timestamp: Date.now()};
            
            try {
                localStorage.setItem(testKey, JSON.stringify(testValue));
                const retrieved = JSON.parse(localStorage.getItem(testKey));
                
                if (JSON.stringify(retrieved) === JSON.stringify(testValue)) {
                    html += '<div class="test-result success">✅ Persistance fonctionnelle</div>';
                } else {
                    html += '<div class="test-result error">❌ Données corrompues lors de la persistance</div>';
                }
                
                localStorage.removeItem(testKey);
            } catch (e) {
                html += `<div class="test-result error">❌ Erreur de persistance: ${e.message}</div>`;
            }
            
            // Test 3: Gestion des erreurs JSON
            html += '<h4>Test 3: Gestion des erreurs JSON</h4>';
            localStorage.setItem('invalid_json_test', '{invalid json}');
            try {
                JSON.parse(localStorage.getItem('invalid_json_test'));
                html += '<div class="test-result error">❌ Erreur: JSON invalide accepté</div>';
            } catch (e) {
                html += '<div class="test-result success">✅ Erreurs JSON correctement gérées</div>';
            }
            localStorage.removeItem('invalid_json_test');
            
            // Test 4: Capacité de stockage
            html += '<h4>Test 4: Capacité de stockage</h4>';
            try {
                const bigData = 'x'.repeat(100000); // 100KB
                localStorage.setItem('capacity_test', bigData);
                localStorage.removeItem('capacity_test');
                html += '<div class="test-result success">✅ Capacité suffisante (>100KB)</div>';
            } catch (e) {
                html += `<div class="test-result warning">⚠️ Capacité limitée: ${e.message}</div>`;
            }
            
            // Test 5: Intégrité du panier actuel
            html += '<h4>Test 5: Intégrité du panier actuel</h4>';
            const currentCart = getCart();
            if (Array.isArray(currentCart)) {
                html += `<div class="test-result success">✅ Structure du panier valide (${currentCart.length} éléments)</div>`;
                
                let validItems = 0;
                currentCart.forEach((item, index) => {
                    if (item.id && item.name && item.price && item.quantity) {
                        validItems++;
                    } else {
                        html += `<div class="test-result warning">⚠️ Élément ${index} incomplet: ${JSON.stringify(item)}</div>`;
                    }
                });
                
                if (validItems === currentCart.length) {
                    html += '<div class="test-result success">✅ Tous les éléments sont valides</div>';
                }
            } else {
                html += '<div class="test-result error">❌ Structure du panier invalide</div>';
            }
            
            container.innerHTML = html;
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `test-result ${type}`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.maxWidth = '300px';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            addLog('Page de test chargée', 'success');
            checkLocalStorage();
            renderCart();
        });
        
        // Surveillance des changements localStorage
        window.addEventListener('storage', function(e) {
            if (e.key === 'restaurant_cart') {
                addLog('Changement détecté dans localStorage', 'info');
                renderCart();
            }
        });
    </script>
</body>
</html>
