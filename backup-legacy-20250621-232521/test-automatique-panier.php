<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Automatique Panier - La Mangeoire</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .test-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .test-result {
            margin: 10px 0;
            padding: 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            margin: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn:hover { background: #0056b3; transform: translateY(-2px); }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-lg { padding: 16px 32px; font-size: 16px; }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            width: 0%;
            transition: width 0.5s ease;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-ok { background: #28a745; }
        .status-error { background: #dc3545; }
        .status-warning { background: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Automatique du Système Panier</h1>
        
        <div class="stats" id="test-stats">
            <div class="stat-item">
                <div class="stat-number" id="tests-passed">0</div>
                <div>Tests Réussis</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="tests-failed">0</div>
                <div>Tests Échoués</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="tests-total">0</div>
                <div>Total Tests</div>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <button class="btn btn-lg btn-success" onclick="runAllTests()">
                🚀 Lancer Tous les Tests
            </button>
            <button class="btn btn-lg btn-warning" onclick="resetTests()">
                🔄 Reset
            </button>
            <button class="btn btn-lg btn-danger" onclick="clearAllData()">
                🗑️ Vider les Données
            </button>
        </div>

        <div class="test-grid">
            <div class="test-card">
                <h3>📦 Tests localStorage</h3>
                <div id="localStorage-tests"></div>
            </div>
            
            <div class="test-card">
                <h3>🛒 Tests Panier</h3>
                <div id="cart-tests"></div>
            </div>
            
            <div class="test-card">
                <h3>🔄 Tests Synchronisation</h3>
                <div id="sync-tests"></div>
            </div>
            
            <div class="test-card">
                <h3>⚡ Tests Performance</h3>
                <div id="performance-tests"></div>
            </div>
        </div>

        <div class="test-card" style="margin-top: 20px;">
            <h3>📊 Résultats Détaillés</h3>
            <div id="detailed-results"></div>
        </div>
    </div>

    <script>
        let testResults = {
            passed: 0,
            failed: 0,
            total: 0,
            details: []
        };

        // Système de panier unifié
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
                    return true;
                } catch (e) {
                    console.error('Erreur sauvegarde panier:', e);
                    return false;
                }
            }
        };

        function runTest(testName, testFunction, category = 'general') {
            testResults.total++;
            try {
                const startTime = performance.now();
                const result = testFunction();
                const endTime = performance.now();
                const duration = endTime - startTime;
                
                if (result) {
                    testResults.passed++;
                    addTestResult(testName, 'success', `✅ Réussi (${duration.toFixed(2)}ms)`, category);
                } else {
                    testResults.failed++;
                    addTestResult(testName, 'error', `❌ Échoué`, category);
                }
            } catch (error) {
                testResults.failed++;
                addTestResult(testName, 'error', `❌ Erreur: ${error.message}`, category);
            }
            updateStats();
        }

        function addTestResult(testName, type, message, category) {
            const container = document.getElementById(`${category}-tests`);
            if (container) {
                const div = document.createElement('div');
                div.className = `test-result ${type}`;
                div.innerHTML = `<span class="status-indicator status-${type === 'success' ? 'ok' : type === 'error' ? 'error' : 'warning'}"></span><strong>${testName}:</strong> ${message}`;
                container.appendChild(div);
            }
            
            testResults.details.push({
                name: testName,
                type: type,
                message: message,
                category: category,
                timestamp: new Date().toISOString()
            });
        }

        function updateStats() {
            document.getElementById('tests-passed').textContent = testResults.passed;
            document.getElementById('tests-failed').textContent = testResults.failed;
            document.getElementById('tests-total').textContent = testResults.total;
            
            const progress = testResults.total > 0 ? (testResults.passed / testResults.total) * 100 : 0;
            document.getElementById('progress-fill').style.width = progress + '%';
        }

        function resetTests() {
            testResults = { passed: 0, failed: 0, total: 0, details: [] };
            
            ['localStorage-tests', 'cart-tests', 'sync-tests', 'performance-tests', 'detailed-results'].forEach(id => {
                const container = document.getElementById(id);
                if (container) container.innerHTML = '';
            });
            
            updateStats();
        }

        function clearAllData() {
            localStorage.clear();
            addTestResult('Nettoyage', 'info', 'Toutes les données localStorage supprimées', 'general');
        }

        async function runAllTests() {
            resetTests();
            
            // Tests localStorage
            runTest('Support localStorage', () => {
                return typeof(Storage) !== "undefined";
            }, 'localStorage');
            
            runTest('Écriture/Lecture localStorage', () => {
                const testKey = 'test_' + Date.now();
                const testValue = { test: 'data', number: 42 };
                localStorage.setItem(testKey, JSON.stringify(testValue));
                const retrieved = JSON.parse(localStorage.getItem(testKey));
                localStorage.removeItem(testKey);
                return JSON.stringify(retrieved) === JSON.stringify(testValue);
            }, 'localStorage');
            
            runTest('Gestion erreurs JSON', () => {
                localStorage.setItem('invalid_json', '{invalid}');
                try {
                    JSON.parse(localStorage.getItem('invalid_json'));
                    localStorage.removeItem('invalid_json');
                    return false; // Ne devrait pas arriver
                } catch (e) {
                    localStorage.removeItem('invalid_json');
                    return true; // Erreur correctement gérée
                }
            }, 'localStorage');

            // Tests Panier
            runTest('CartManager disponible', () => {
                return typeof window.CartManager === 'object' && 
                       typeof window.CartManager.getCart === 'function' &&
                       typeof window.CartManager.saveCart === 'function';
            }, 'cart');
            
            runTest('Panier vide initial', () => {
                window.CartManager.saveCart([]);
                const cart = window.CartManager.getCart();
                return Array.isArray(cart) && cart.length === 0;
            }, 'cart');
            
            runTest('Ajout article au panier', () => {
                const testItem = { id: 1, name: 'Test', price: 10, quantity: 1 };
                window.CartManager.saveCart([testItem]);
                const cart = window.CartManager.getCart();
                return cart.length === 1 && cart[0].id === 1;
            }, 'cart');
            
            runTest('Modification quantité', () => {
                let cart = window.CartManager.getCart();
                if (cart.length > 0) {
                    cart[0].quantity = 5;
                    window.CartManager.saveCart(cart);
                    const updatedCart = window.CartManager.getCart();
                    return updatedCart[0].quantity === 5;
                }
                return false;
            }, 'cart');
            
            runTest('Suppression article', () => {
                window.CartManager.saveCart([]);
                const cart = window.CartManager.getCart();
                return cart.length === 0;
            }, 'cart');

            // Tests de synchronisation
            runTest('Événements personnalisés', () => {
                let eventReceived = false;
                const handler = () => { eventReceived = true; };
                window.addEventListener('cartUpdated', handler);
                window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
                window.removeEventListener('cartUpdated', handler);
                return eventReceived;
            }, 'sync');
            
            runTest('Persistance après rechargement simulé', () => {
                const testData = [{ id: 99, name: 'Persist Test', price: 15, quantity: 2 }];
                window.CartManager.saveCart(testData);
                // Simuler un rechargement en créant un nouveau CartManager
                const newManager = {
                    getCart: function() {
                        return JSON.parse(localStorage.getItem('restaurant_cart')) || [];
                    }
                };
                const persistedCart = newManager.getCart();
                return persistedCart.length === 1 && persistedCart[0].id === 99;
            }, 'sync');

            // Tests de performance
            runTest('Performance ajout 100 articles', () => {
                const startTime = performance.now();
                const bigCart = [];
                for (let i = 0; i < 100; i++) {
                    bigCart.push({
                        id: i,
                        name: `Article ${i}`,
                        price: Math.random() * 100,
                        quantity: Math.floor(Math.random() * 5) + 1
                    });
                }
                window.CartManager.saveCart(bigCart);
                const endTime = performance.now();
                return (endTime - startTime) < 100; // Moins de 100ms
            }, 'performance');
            
            runTest('Capacité stockage importante', () => {
                try {
                    const bigData = Array(1000).fill(0).map((_, i) => ({
                        id: i,
                        name: 'Article ' + i,
                        description: 'x'.repeat(100), // 100 caractères
                        price: 10.99,
                        quantity: 1
                    }));
                    window.CartManager.saveCart(bigData);
                    const retrieved = window.CartManager.getCart();
                    return retrieved.length === 1000;
                } catch (e) {
                    return false;
                }
            }, 'performance');

            // Tests de robustesse
            runTest('Résistance données corrompues', () => {
                localStorage.setItem('restaurant_cart', '{corrupted json}');
                const cart = window.CartManager.getCart();
                return Array.isArray(cart) && cart.length === 0;
            }, 'cart');
            
            // Afficher les résultats détaillés
            setTimeout(() => {
                displayDetailedResults();
            }, 100);
        }

        function displayDetailedResults() {
            const container = document.getElementById('detailed-results');
            let html = `
                <h4>📈 Résumé des Tests</h4>
                <div class="test-result ${testResults.failed === 0 ? 'success' : 'warning'}">
                    <strong>Résultat Global:</strong> ${testResults.passed}/${testResults.total} tests réussis 
                    ${testResults.failed === 0 ? '🎉' : '⚠️'}
                </div>
            `;
            
            if (testResults.details.length > 0) {
                html += '<h5>Détails par Catégorie:</h5>';
                
                const categories = [...new Set(testResults.details.map(d => d.category))];
                categories.forEach(category => {
                    const categoryTests = testResults.details.filter(d => d.category === category);
                    const categoryPassed = categoryTests.filter(d => d.type === 'success').length;
                    
                    html += `
                        <div class="test-result info">
                            <strong>${category}:</strong> ${categoryPassed}/${categoryTests.length} réussis
                            <details style="margin-top: 10px;">
                                <summary>Voir les détails</summary>
                                <pre>${JSON.stringify(categoryTests, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                });
            }
            
            container.innerHTML = html;
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            resetTests();
            
            // Test de détection automatique des problèmes
            setTimeout(() => {
                const cart = window.CartManager.getCart();
                if (cart.length > 0) {
                    addTestResult('Détection automatique', 'info', `Panier existant détecté avec ${cart.length} article(s)`, 'general');
                }
            }, 1000);
        });
    </script>
</body>
</html>
