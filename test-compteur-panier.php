<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Compteur Panier - Restaurant La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <?php 
    session_start();
    include 'includes/header.php'; 
    ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0">
                            <i class="bi bi-cart-check"></i>
                            Test du Compteur de Panier
                        </h1>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-info">
                            <strong>Instructions :</strong> Regardez le compteur de panier dans le header en haut à droite pendant que vous testez.
                        </div>
                        
                        <!-- Test 1: Manipulation localStorage -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Test localStorage</h5>
                                <p>Test direct du localStorage (simulation JavaScript)</p>
                                <button id="addLocalStorage" class="btn btn-primary me-2">
                                    Ajouter 1 article (localStorage)
                                </button>
                                <button id="clearLocalStorage" class="btn btn-warning">
                                    Vider localStorage
                                </button>
                                <div id="localStorageResult" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <!-- Test 2: Appel serveur -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Test serveur</h5>
                                <p>Test d'ajout via le serveur PHP</p>
                                <button id="addServer" class="btn btn-success me-2">
                                    Ajouter 1 article (serveur)
                                </button>
                                <button id="getServerCount" class="btn btn-info">
                                    Récupérer du serveur
                                </button>
                                <div id="serverResult" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <!-- Test 3: État actuel -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">État actuel</h5>
                                <button id="checkState" class="btn btn-outline-primary">
                                    Vérifier l'état du panier
                                </button>
                                <div id="stateResult" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <!-- Test 4: Navigation -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Test de navigation</h5>
                                <p>Le compteur doit persister entre les pages</p>
                                <a href="menu.php" class="btn btn-secondary me-2">Aller au menu</a>
                                <a href="index.php" class="btn btn-secondary">Aller à l'accueil</a>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attendre que le système de panier soit chargé
        document.addEventListener('DOMContentLoaded', function() {
            
            // Test localStorage
            document.getElementById('addLocalStorage').addEventListener('click', function() {
                try {
                    let cart = JSON.parse(localStorage.getItem('restaurant_cart') || '[]');
                    cart.push({
                        id: Date.now(),
                        name: 'Test Article',
                        price: 10.00,
                        quantity: 1,
                        total: 10.00
                    });
                    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                    
                    if (window.CartCounter) {
                        window.CartCounter.updateDisplay();
                    }
                    
                    document.getElementById('localStorageResult').innerHTML = 
                        '<div class="alert alert-success">Article ajouté au localStorage !</div>';
                        
                } catch (e) {
                    document.getElementById('localStorageResult').innerHTML = 
                        '<div class="alert alert-danger">Erreur: ' + e.message + '</div>';
                }
            });
            
            // Vider localStorage
            document.getElementById('clearLocalStorage').addEventListener('click', function() {
                localStorage.removeItem('restaurant_cart');
                if (window.CartCounter) {
                    window.CartCounter.updateDisplay();
                }
                document.getElementById('localStorageResult').innerHTML = 
                    '<div class="alert alert-warning">localStorage vidé !</div>';
            });
            
            // Test serveur
            document.getElementById('addServer').addEventListener('click', async function() {
                try {
                    const formData = new FormData();
                    formData.append('menu_id', '1');
                    formData.append('quantity', '1');
                    formData.append('ajax', 'true');
                    
                    const response = await fetch('ajouter-au-panier.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        if (window.CartCounter) {
                            window.CartCounter.updateDisplay();
                        }
                        document.getElementById('serverResult').innerHTML = 
                            '<div class="alert alert-success">Article ajouté via serveur ! ' + result.message + '</div>';
                    } else {
                        document.getElementById('serverResult').innerHTML = 
                            '<div class="alert alert-danger">Erreur serveur: ' + result.message + '</div>';
                    }
                    
                } catch (e) {
                    document.getElementById('serverResult').innerHTML = 
                        '<div class="alert alert-danger">Erreur: ' + e.message + '</div>';
                }
            });
            
            // Récupérer état serveur
            document.getElementById('getServerCount').addEventListener('click', async function() {
                try {
                    const response = await fetch('api/cart-summary.php', {
                        credentials: 'same-origin'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        document.getElementById('serverResult').innerHTML = 
                            '<div class="alert alert-info">Serveur: ' + result.data.total_items + ' articles, Total: ' + result.data.formatted_total + '</div>';
                    } else {
                        document.getElementById('serverResult').innerHTML = 
                            '<div class="alert alert-warning">Erreur serveur: ' + result.error + '</div>';
                    }
                    
                } catch (e) {
                    document.getElementById('serverResult').innerHTML = 
                        '<div class="alert alert-danger">Erreur: ' + e.message + '</div>';
                }
            });
            
            // Vérifier état
            document.getElementById('checkState').addEventListener('click', async function() {
                try {
                    const localCart = JSON.parse(localStorage.getItem('restaurant_cart') || '[]');
                    const localCount = localCart.reduce((total, item) => total + (item.quantity || 0), 0);
                    
                    let serverCount = 0;
                    try {
                        const response = await fetch('api/cart-summary.php', { credentials: 'same-origin' });
                        const result = await response.json();
                        if (result.success) {
                            serverCount = result.data.total_items;
                        }
                    } catch (e) {
                        console.warn('Erreur serveur:', e);
                    }
                    
                    document.getElementById('stateResult').innerHTML = `
                        <div class="alert alert-info">
                            <strong>État actuel :</strong><br>
                            localStorage: ${localCount} articles<br>
                            Serveur: ${serverCount} articles<br>
                            Compteur affiché: ${document.getElementById('cartCounter').textContent}
                        </div>
                    `;
                    
                } catch (e) {
                    document.getElementById('stateResult').innerHTML = 
                        '<div class="alert alert-danger">Erreur: ' + e.message + '</div>';
                }
            });
            
        });
    </script>
</body>
</html>
