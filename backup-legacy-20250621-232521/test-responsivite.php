<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Responsivité - La Mangeoire</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .viewport-selector {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .viewport-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .viewport-btn:hover { background: #0056b3; }
        .viewport-btn.active { background: #28a745; }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        .iframe-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .iframe-container:hover {
            border-color: #007bff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .iframe-wrapper {
            position: relative;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.5s ease;
        }
        .iframe-wrapper iframe {
            width: 100%;
            border: none;
            display: block;
        }
        .iframe-label {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
        }
        .iframe-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        .current-size {
            font-weight: bold;
            color: #007bff;
        }
        
        .controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn:hover { 
            background: #0056b3; 
            transform: translateY(-2px);
        }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; }
        
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
        
        @media (max-width: 768px) {
            .test-grid {
                grid-template-columns: 1fr;
            }
            .viewport-selector {
                justify-content: center;
            }
            .controls {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Test Responsivité Multi-Appareils</h1>
        
        <div class="viewport-selector">
            <button class="viewport-btn" onclick="setViewport('mobile', 375, 667)">📱 iPhone SE</button>
            <button class="viewport-btn" onclick="setViewport('mobile-large', 414, 896)">📱 iPhone 11</button>
            <button class="viewport-btn" onclick="setViewport('tablet', 768, 1024)">📱 iPad</button>
            <button class="viewport-btn" onclick="setViewport('tablet-large', 1024, 1366)">📱 iPad Pro</button>
            <button class="viewport-btn" onclick="setViewport('desktop', 1200, 800)">💻 Desktop</button>
            <button class="viewport-btn" onclick="setViewport('desktop-large', 1920, 1080)">🖥️ Large Desktop</button>
        </div>
        
        <div class="controls">
            <button class="btn btn-success" onclick="runResponsiveTests()">🧪 Lancer Tests Auto</button>
            <button class="btn btn-warning" onclick="simulateTouch()">👆 Simuler Touch</button>
            <button class="btn btn-danger" onclick="testCartSync()">🔄 Test Sync Panier</button>
        </div>
        
        <div class="test-grid">
            <div class="iframe-container">
                <div class="iframe-label">🍽️ Page Menu</div>
                <div class="iframe-info">
                    <span>Taille: <span class="current-size" id="menu-size">Auto</span></span>
                    <span>Status: <span id="menu-status">✅</span></span>
                </div>
                <div class="iframe-wrapper">
                    <iframe id="menu-frame" src="http://localhost:8080/menu.php" height="600"></iframe>
                </div>
            </div>
            
            <div class="iframe-container">
                <div class="iframe-label">🛒 Page Panier</div>
                <div class="iframe-info">
                    <span>Taille: <span class="current-size" id="cart-size">Auto</span></span>
                    <span>Status: <span id="cart-status">✅</span></span>
                </div>
                <div class="iframe-wrapper">
                    <iframe id="cart-frame" src="http://localhost:8080/panier.php" height="600"></iframe>
                </div>
            </div>
            
            <div class="iframe-container">
                <div class="iframe-label">💳 Page Commande</div>
                <div class="iframe-info">
                    <span>Taille: <span class="current-size" id="order-size">Auto</span></span>
                    <span>Status: <span id="order-status">✅</span></span>
                </div>
                <div class="iframe-wrapper">
                    <iframe id="order-frame" src="http://localhost:8080/commande-moderne.php" height="600"></iframe>
                </div>
            </div>
        </div>
        
        <div class="container" style="margin-top: 30px;">
            <h2>📊 Résultats des Tests</h2>
            <div id="test-results"></div>
        </div>
    </div>

    <script>
        let currentViewport = { name: 'auto', width: 'auto', height: 'auto' };
        let testResults = [];

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

        function setViewport(name, width, height) {
            currentViewport = { name, width, height };
            
            // Mettre à jour les boutons
            document.querySelectorAll('.viewport-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Redimensionner les iframes
            const frames = ['menu-frame', 'cart-frame', 'order-frame'];
            frames.forEach(frameId => {
                const frame = document.getElementById(frameId);
                const wrapper = frame.parentElement;
                
                if (width === 'auto') {
                    wrapper.style.width = '100%';
                    frame.style.width = '100%';
                } else {
                    wrapper.style.width = width + 'px';
                    frame.style.width = width + 'px';
                }
                
                frame.style.height = height + 'px';
                
                // Mettre à jour l'affichage de la taille
                const sizeElement = document.getElementById(frameId.replace('-frame', '-size'));
                if (sizeElement) {
                    sizeElement.textContent = width === 'auto' ? 'Auto' : `${width}×${height}`;
                }
            });
            
            addTestResult(`Viewport changé vers ${name} (${width}×${height})`, 'info');
        }

        function addTestResult(message, type = 'info') {
            testResults.push({
                message: message,
                type: type,
                timestamp: new Date().toISOString(),
                viewport: currentViewport.name
            });
            updateTestResults();
        }

        function updateTestResults() {
            const container = document.getElementById('test-results');
            let html = '<h3>📈 Historique des Tests Responsivité</h3>';
            
            testResults.slice(-10).forEach((result, index) => {
                html += `
                    <div class="test-result ${result.type}">
                        <strong>[${result.viewport}]:</strong> ${result.message}
                        <small style="float: right;">${new Date(result.timestamp).toLocaleTimeString()}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        async function runResponsiveTests() {
            addTestResult('🚀 Démarrage des tests automatiques de responsivité', 'info');
            
            const viewports = [
                { name: 'mobile', width: 375, height: 667 },
                { name: 'tablet', width: 768, height: 1024 },
                { name: 'desktop', width: 1200, height: 800 }
            ];
            
            for (const viewport of viewports) {
                setViewport(viewport.name, viewport.width, viewport.height);
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Tester le chargement de chaque page
                const frames = ['menu-frame', 'cart-frame', 'order-frame'];
                frames.forEach(frameId => {
                    try {
                        const frame = document.getElementById(frameId);
                        const pageName = frameId.replace('-frame', '');
                        
                        // Vérifier si l'iframe est chargée
                        frame.onload = function() {
                            addTestResult(`✅ Page ${pageName} chargée correctement en ${viewport.name}`, 'success');
                        };
                        
                        // Forcer le rechargement pour tester
                        frame.src = frame.src;
                        
                    } catch (error) {
                        addTestResult(`❌ Erreur sur page ${frameId} en ${viewport.name}: ${error.message}`, 'error');
                    }
                });
                
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
            
            addTestResult('✅ Tests automatiques terminés', 'success');
        }

        function simulateTouch() {
            addTestResult('👆 Simulation d\'événements tactiles activée', 'info');
            
            // Ajouter des styles pour simuler le touch
            const style = document.createElement('style');
            style.textContent = `
                .iframe-wrapper {
                    cursor: pointer;
                }
                .iframe-wrapper:active {
                    transform: scale(0.98);
                }
            `;
            document.head.appendChild(style);
            
            // Ajouter des événements touch simulés
            document.querySelectorAll('.iframe-wrapper').forEach(wrapper => {
                wrapper.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.98)';
                });
                
                wrapper.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                });
            });
            
            addTestResult('✅ Simulation tactile configurée', 'success');
        }

        function testCartSync() {
            addTestResult('🔄 Test de synchronisation du panier entre pages', 'info');
            
            // Ajouter un article de test
            const testItem = {
                id: Date.now(),
                name: `Test Sync ${new Date().toLocaleTimeString()}`,
                price: 1500,
                quantity: 1,
                total: 1500,
                added_at: new Date().toISOString()
            };
            
            let cart = window.CartManager.getCart();
            cart.push(testItem);
            window.CartManager.saveCart(cart);
            
            // Déclencher l'événement de synchronisation
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
            
            addTestResult(`✅ Article test ajouté: ${testItem.name}`, 'success');
            addTestResult('🔄 Vérifiez la synchronisation dans les autres pages', 'info');
            
            // Recharger les frames après un délai
            setTimeout(() => {
                document.getElementById('cart-frame').src = document.getElementById('cart-frame').src;
                addTestResult('🔄 Page panier rechargée pour vérifier la sync', 'info');
            }, 1000);
        }

        // Surveillance des erreurs iframe
        function monitorFrameErrors() {
            const frames = ['menu-frame', 'cart-frame', 'order-frame'];
            
            frames.forEach(frameId => {
                const frame = document.getElementById(frameId);
                
                frame.onerror = function() {
                    const pageName = frameId.replace('-frame', '');
                    addTestResult(`❌ Erreur de chargement: ${pageName}`, 'error');
                    document.getElementById(frameId.replace('-frame', '-status')).textContent = '❌';
                };
                
                frame.onload = function() {
                    const pageName = frameId.replace('-frame', '');
                    document.getElementById(frameId.replace('-frame', '-status')).textContent = '✅';
                };
            });
        }

        // Détection automatique du viewport
        function detectViewport() {
            const width = window.innerWidth;
            let viewport;
            
            if (width < 576) {
                viewport = { name: 'mobile', width: 375, height: 667 };
            } else if (width < 768) {
                viewport = { name: 'mobile-large', width: 414, height: 896 };
            } else if (width < 1024) {
                viewport = { name: 'tablet', width: 768, height: 1024 };
            } else if (width < 1200) {
                viewport = { name: 'tablet-large', width: 1024, height: 1366 };
            } else {
                viewport = { name: 'desktop', width: 1200, height: 800 };
            }
            
            setViewport(viewport.name, viewport.width, viewport.height);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            monitorFrameErrors();
            addTestResult('📱 Page de test responsivité chargée', 'success');
            addTestResult(`🖥️ Viewport détecté: ${window.innerWidth}×${window.innerHeight}`, 'info');
            
            // Détecter automatiquement le viewport initial
            setTimeout(detectViewport, 500);
        });

        // Surveiller les changements de taille de fenêtre
        window.addEventListener('resize', function() {
            addTestResult(`📐 Redimensionnement détecté: ${window.innerWidth}×${window.innerHeight}`, 'info');
        });
    </script>
</body>
</html>
