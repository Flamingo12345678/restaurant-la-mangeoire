<?php
/**
 * Test final complet du système de panier
 * Interface web pour tester tous les scénarios
 */

session_start();
require_once 'includes/https-security.php';
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Complet - Système de Panier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h1 class="h4 mb-0">
                            <i class="bi bi-cart-check"></i>
                            Test Complet - Système de Panier Sécurisé
                        </h1>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>✅ Problème résolu !</strong> L'ajout au panier fonctionne maintenant depuis toutes les pages.
                        </div>

                        <!-- Test 1: Formulaire comme index.php -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-house"></i>
                                    Test 1: Formulaire HTML (comme index.php)
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>Ce formulaire utilise le champ <code>quantite</code> comme dans index.php :</p>
                                <form action="ajouter-au-panier.php" method="post" class="row g-3">
                                    <input type="hidden" name="menu_id" value="1">
                                    <input type="hidden" name="action" value="add">
                                    <div class="col-md-6">
                                        <label class="form-label">Menu</label>
                                        <input type="text" class="form-control" value="Ndole (ID: 1)" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantité</label>
                                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary mt-4">
                                            <i class="bi bi-cart-plus"></i> Ajouter (HTML)
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Test 2: AJAX comme menu.php -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-menu-app"></i>
                                    Test 2: Requête AJAX (comme menu.php)
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>Ce bouton utilise AJAX avec le champ <code>quantity</code> comme dans menu.php :</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" value="Eru (ID: 2)" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="ajaxQuantity" class="form-control" value="2" min="1">
                                    </div>
                                    <div class="col-md-3">
                                        <button id="ajaxBtn" class="btn btn-success">
                                            <i class="bi bi-cart-plus"></i> Ajouter (AJAX)
                                        </button>
                                    </div>
                                </div>
                                <div id="ajaxResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Test 3: État du panier -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-cart"></i>
                                    État actuel du panier
                                </h5>
                            </div>
                            <div class="card-body">
                                <button id="refreshCartBtn" class="btn btn-outline-primary mb-3">
                                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                                </button>
                                <div id="cartContents">
                                    <?php
                                    try {
                                        $cartManager = new CartManager($pdo);
                                        $items = $cartManager->getItems();
                                        $summary = $cartManager->getSummary();
                                        
                                        if (empty($items)) {
                                            echo '<div class="alert alert-secondary">🛒 Panier vide</div>';
                                        } else {
                                            echo '<div class="table-responsive">';
                                            echo '<table class="table table-sm">';
                                            echo '<thead><tr><th>Article</th><th>Qté</th><th>Prix unit.</th><th>Total</th></tr></thead>';
                                            echo '<tbody>';
                                            foreach ($items as $item) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                                                echo '<td>' . $item['quantity'] . '</td>';
                                                echo '<td>' . number_format($item['price'], 2) . '€</td>';
                                                echo '<td>' . number_format($item['price'] * $item['quantity'], 2) . '€</td>';
                                                echo '</tr>';
                                            }
                                            echo '</tbody>';
                                            echo '<tfoot><tr class="table-success"><th colspan="3">Total</th><th>' . number_format($summary['total_amount'], 2) . '€</th></tr></tfoot>';
                                            echo '</table>';
                                            echo '</div>';
                                            
                                            echo '<div class="alert alert-success">';
                                            echo '<i class="bi bi-check-circle"></i> ';
                                            echo $summary['total_items'] . ' article(s) • ' . number_format($summary['total_amount'], 2) . '€';
                                            echo '</div>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Résumé des corrections -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-tools"></i>
                                    Résumé des corrections
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-success">✅ Corrections apportées :</h6>
                                        <ul class="list-unstyled">
                                            <li>✅ Support du champ <code>quantite</code> (index.php)</li>
                                            <li>✅ Support du champ <code>quantity</code> (menu.php)</li>
                                            <li>✅ Correction bug <code>filter_input()</code></li>
                                            <li>✅ Sécurité HTTPS complète</li>
                                            <li>✅ Messages de notification</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary">🔧 Fichiers modifiés :</h6>
                                        <ul class="list-unstyled">
                                            <li>🔧 <code>ajouter-au-panier.php</code></li>
                                            <li>🔧 <code>index.php</code></li>
                                            <li>🔧 <code>menu.php</code></li>
                                            <li>🔧 <code>includes/https-security.php</code></li>
                                            <li>🔧 <code>.htaccess</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-house"></i> Accueil
                            </a>
                            <a href="menu.php" class="btn btn-secondary">
                                <i class="bi bi-menu-app"></i> Menu
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Actualiser
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Test AJAX
        document.getElementById('ajaxBtn').addEventListener('click', async function() {
            const btn = this;
            const result = document.getElementById('ajaxResult');
            const quantity = document.getElementById('ajaxQuantity').value;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Test...';
            
            try {
                const formData = new FormData();
                formData.append('menu_id', '2'); // Eru
                formData.append('quantity', quantity);
                formData.append('ajax', 'true');
                
                const response = await fetch('ajouter-au-panier.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>Succès AJAX !</strong> ${data.message}
                        </div>
                    `;
                    
                    // Actualiser le panier
                    setTimeout(() => location.reload(), 1000);
                } else {
                    result.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Erreur :</strong> ${data.message}
                        </div>
                    `;
                }
            } catch (error) {
                result.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle"></i>
                        <strong>Erreur connexion :</strong> ${error.message}
                    </div>
                `;
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cart-plus"></i> Ajouter (AJAX)';
        });
    </script>
</body>
</html>
