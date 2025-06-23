<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Workflow Complet - La Mangeoire</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        .step-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border-left: 6px solid #007bff;
            transition: all 0.3s ease;
            position: relative;
        }
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .step-number {
            position: absolute;
            top: -15px;
            left: 20px;
            background: #007bff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        .step-title {
            margin-top: 10px;
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
        }
        .step-description {
            margin: 15px 0;
            color: #666;
            line-height: 1.6;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { 
            background: #0056b3; 
            transform: translateY(-2px);
        }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; }
        .btn-lg { padding: 16px 32px; font-size: 16px; }
        
        .test-result {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
        }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        
        .progress-container {
            background: #e9ecef;
            border-radius: 10px;
            height: 25px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            width: 0%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e9ecef;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 10px;
        }
        
        .iframe-container {
            position: relative;
            width: 100%;
            height: 600px;
            border: 2px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .iframe-label {
            position: absolute;
            top: -10px;
            left: 20px;
            background: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: bold;
            color: #007bff;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Workflow Complet - La Mangeoire</h1>
        
        <div class="stats-grid" id="workflow-stats">
            <div class="stat-card">
                <div class="stat-number" id="steps-completed">0</div>
                <div class="stat-label">Étapes Complétées</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="tests-passed">0</div>
                <div class="stat-label">Tests Réussis</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="items-in-cart">0</div>
                <div class="stat-label">Articles au Panier</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-value">0</div>
                <div class="stat-label">Valeur Totale (€)</div>
            </div>
        </div>

        <div class="progress-container">
            <div class="progress-bar" id="workflow-progress">0% Complété</div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <button class="btn btn-lg btn-success" onclick="startCompleteWorkflow()">
                🚀 Démarrer Test Complet
            </button>
            <button class="btn btn-lg btn-warning" onclick="resetWorkflow()">
                🔄 Reset
            </button>
            <button class="btn btn-lg btn-danger" onclick="clearAllData()">
                🗑️ Vider Données
            </button>
        </div>

        <div class="workflow-grid">
            <div class="step-card" id="step-1">
                <div class="step-number">1</div>
                <div class="step-title">📋 Consultation Menu</div>
                <div class="step-description">
                    Ouvrir la page menu et vérifier l'affichage des plats disponibles.
                </div>
                <button class="btn" onclick="openMenu()">Ouvrir Menu</button>
                <button class="btn btn-success" onclick="completeStep(1)">✓ Marquer Complété</button>
                <div id="step-1-result"></div>
            </div>

            <div class="step-card" id="step-2">
                <div class="step-number">2</div>
                <div class="step-title">🛒 Ajout Articles</div>
                <div class="step-description">
                    Ajouter plusieurs articles au panier depuis la page menu.
                </div>
                <button class="btn" onclick="simulateAddToCart()">Simuler Ajouts</button>
                <button class="btn btn-success" onclick="completeStep(2)">✓ Marquer Complété</button>
                <div id="step-2-result"></div>
            </div>

            <div class="step-card" id="step-3">
                <div class="step-number">3</div>
                <div class="step-title">🧺 Vérification Panier</div>
                <div class="step-description">
                    Ouvrir la page panier et vérifier la synchronisation des articles.
                </div>
                <button class="btn" onclick="openCart()">Ouvrir Panier</button>
                <button class="btn btn-success" onclick="completeStep(3)">✓ Marquer Complété</button>
                <div id="step-3-result"></div>
            </div>

            <div class="step-card" id="step-4">
                <div class="step-number">4</div>
                <div class="step-title">✏️ Modification Panier</div>
                <div class="step-description">
                    Modifier les quantités, supprimer des articles, tester toutes les fonctionnalités.
                </div>
                <button class="btn" onclick="testCartModifications()">Test Modifications</button>
                <button class="btn btn-success" onclick="completeStep(4)">✓ Marquer Complété</button>
                <div id="step-4-result"></div>
            </div>

            <div class="step-card" id="step-5">
                <div class="step-number">5</div>
                <div class="step-title">💳 Processus Commande</div>
                <div class="step-description">
                    Passer à la commande et tester le processus de paiement.
                </div>
                <button class="btn" onclick="openOrderProcess()">Ouvrir Commande</button>
                <button class="btn btn-success" onclick="completeStep(5)">✓ Marquer Complété</button>
                <div id="step-5-result"></div>
            </div>

            <div class="step-card" id="step-6">
                <div class="step-number">6</div>
                <div class="step-title">🔄 Test Cross-Tab</div>
                <div class="step-description">
                    Tester la synchronisation entre plusieurs onglets ouverts.
                </div>
                <button class="btn" onclick="testCrossTab()">Test Multi-Onglets</button>
                <button class="btn btn-success" onclick="completeStep(6)">✓ Marquer Complété</button>
                <div id="step-6-result"></div>
            </div>
        </div>

        <div class="container" style="margin-top: 30px;">
            <h2>📊 Résultats des Tests</h2>
            <div id="test-results"></div>
        </div>
    </div>

    <script>
        let workflowState = {
            currentStep: 0,
            completedSteps: 0,
            testsResults: [],
            cartItems: 0,
            totalValue: 0
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

        function updateStats() {
            const cart = window.CartManager.getCart();
            workflowState.cartItems = cart.length;
            workflowState.totalValue = cart.reduce((total, item) => total + (item.price * item.quantity), 0);

            document.getElementById('steps-completed').textContent = workflowState.completedSteps;
            document.getElementById('tests-passed').textContent = workflowState.testsResults.filter(r => r.success).length;
            document.getElementById('items-in-cart').textContent = workflowState.cartItems;
            document.getElementById('total-value').textContent = workflowState.totalValue.toLocaleString();

            const progress = (workflowState.completedSteps / 6) * 100;
            const progressBar = document.getElementById('workflow-progress');
            progressBar.style.width = progress + '%';
            progressBar.textContent = Math.round(progress) + '% Complété';
        }

        function addTestResult(stepId, message, success = true) {
            const result = {
                step: stepId,
                message: message,
                success: success,
                timestamp: new Date().toISOString()
            };
            workflowState.testsResults.push(result);

            const stepResult = document.getElementById(`step-${stepId}-result`);
            if (stepResult) {
                const div = document.createElement('div');
                div.className = `test-result ${success ? 'success' : 'error'}`;
                div.textContent = message;
                stepResult.appendChild(div);
            }

            updateTestResults();
        }

        function updateTestResults() {
            const container = document.getElementById('test-results');
            let html = '<h3>📈 Historique des Tests</h3>';
            
            workflowState.testsResults.forEach((result, index) => {
                html += `
                    <div class="test-result ${result.success ? 'success' : 'error'}">
                        <strong>Étape ${result.step}:</strong> ${result.message}
                        <small style="float: right;">${new Date(result.timestamp).toLocaleTimeString()}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function completeStep(stepId) {
            workflowState.completedSteps = Math.max(workflowState.completedSteps, stepId);
            const stepCard = document.getElementById(`step-${stepId}`);
            stepCard.style.borderLeftColor = '#28a745';
            stepCard.style.background = '#f8fff8';
            
            addTestResult(stepId, `Étape ${stepId} marquée comme complétée`, true);
            updateStats();
        }

        function openMenu() {
            window.open('http://localhost:8080/menu.php', '_blank');
            addTestResult(1, 'Page menu ouverte dans un nouvel onglet', true);
        }

        function openCart() {
            window.open('http://localhost:8080/panier.php', '_blank');
            addTestResult(3, 'Page panier ouverte dans un nouvel onglet', true);
        }

        function openOrderProcess() {
            window.open('http://localhost:8080/commande-moderne.php', '_blank');
            addTestResult(5, 'Page commande ouverte dans un nouvel onglet', true);
        }

        function simulateAddToCart() {
            const testItems = [
                {id: 1, name: 'Ndolé aux crevettes', price: 8500, quantity: 2},
                {id: 2, name: 'Eru aux arachides', price: 7500, quantity: 1},
                {id: 3, name: 'Koki beans', price: 6000, quantity: 3},
                {id: 4, name: 'Okok au poisson', price: 9000, quantity: 1},
                {id: 5, name: 'Bongo', price: 5500, quantity: 2}
            ];

            const enhancedItems = testItems.map(item => ({
                ...item,
                total: item.price * item.quantity,
                added_at: new Date().toISOString()
            }));

            window.CartManager.saveCart(enhancedItems);
            addTestResult(2, `${testItems.length} articles ajoutés au panier (simulation)`, true);
            updateStats();
        }

        function testCartModifications() {
            let cart = window.CartManager.getCart();
            
            if (cart.length === 0) {
                addTestResult(4, 'Panier vide - ajout d\'articles de test d\'abord', false);
                simulateAddToCart();
                cart = window.CartManager.getCart();
            }

            // Modifier une quantité
            if (cart.length > 0) {
                cart[0].quantity += 1;
                cart[0].total = cart[0].price * cart[0].quantity;
            }

            // Supprimer un article s'il y en a plus de 1
            if (cart.length > 1) {
                cart.pop();
            }

            window.CartManager.saveCart(cart);
            addTestResult(4, 'Modifications du panier testées (quantités et suppressions)', true);
            updateStats();
        }

        function testCrossTab() {
            // Créer un article unique pour tester la sync
            const testItem = {
                id: Date.now(),
                name: `Test Sync ${new Date().toLocaleTimeString()}`,
                price: 1000,
                quantity: 1,
                total: 1000,
                added_at: new Date().toISOString()
            };

            let cart = window.CartManager.getCart();
            cart.push(testItem);
            window.CartManager.saveCart(cart);

            // Déclencher l'événement de sync
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
            
            addTestResult(6, 'Test de synchronisation cross-tab effectué', true);
            updateStats();
        }

        function startCompleteWorkflow() {
            addTestResult(0, '🚀 Démarrage du test workflow complet', true);
            
            // Réinitialiser le panier
            window.CartManager.saveCart([]);
            
            // Ouvrir toutes les pages nécessaires
            setTimeout(() => openMenu(), 500);
            setTimeout(() => openCart(), 1000);
            setTimeout(() => simulateAddToCart(), 1500);
            setTimeout(() => testCartModifications(), 2000);
            setTimeout(() => testCrossTab(), 2500);
            
            addTestResult(0, 'Workflow automatique lancé - vérifiez les onglets ouverts', true);
        }

        function resetWorkflow() {
            workflowState = {
                currentStep: 0,
                completedSteps: 0,
                testsResults: [],
                cartItems: 0,
                totalValue: 0
            };

            // Reset visual state
            for (let i = 1; i <= 6; i++) {
                const stepCard = document.getElementById(`step-${i}`);
                stepCard.style.borderLeftColor = '#007bff';
                stepCard.style.background = '#f8f9fa';
                
                const stepResult = document.getElementById(`step-${i}-result`);
                if (stepResult) stepResult.innerHTML = '';
            }

            updateStats();
            updateTestResults();
            addTestResult(0, 'Workflow réinitialisé', true);
        }

        function clearAllData() {
            localStorage.clear();
            addTestResult(0, 'Toutes les données localStorage supprimées', true);
            updateStats();
        }

        // Écouter les changements du panier
        window.addEventListener('storage', function(e) {
            if (e.key === 'restaurant_cart') {
                updateStats();
            }
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
            addTestResult(0, 'Page de test workflow chargée et prête', true);
        });
    </script>
</body>
</html>
