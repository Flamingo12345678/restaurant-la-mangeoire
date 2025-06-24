<?php
/**
 * Test propre sans warnings - Simulation de index.php
 */

// 1. Inclure la sécurité HTTPS en premier (AVANT toute sortie)
require_once 'includes/https-security.php';

// 2. Démarrer la session de manière sécurisée
startSecureSession();

// 3. Maintenant on peut faire des sorties HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Clean - Restaurant La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success { color: #198754; background: #d1e7dd; }
        .error { color: #dc3545; background: #f8d7da; }
        .info { color: #0dcaf0; background: #d1ecf1; }
        .alert-custom { padding: 15px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h1 class="h4 mb-0">🎉 Test Clean - Aucun Warning</h1>
                    </div>
                    <div class="card-body">
                        
                        <h5>✅ État de la sécurité HTTPS</h5>
                        <div class="alert-custom info">
                            <ul class="mb-0">
                                <li><strong>Session active:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? "✅ Oui" : "❌ Non"; ?></li>
                                <li><strong>HTTPS détecté:</strong> <?php echo IS_HTTPS ? "✅ Oui" : "⚠️ Non (normal en local)"; ?></li>
                                <li><strong>Session ID:</strong> <?php echo session_id() ? "✅ " . substr(session_id(), 0, 8) . "..." : "❌ Aucun"; ?></li>
                                <li><strong>En-têtes sécurisés:</strong> ✅ Configurés</li>
                            </ul>
                        </div>

                        <h5>🛒 Test d'ajout au panier (simulation index.php)</h5>
                        
                        <?php
                        // Simuler une soumission de formulaire depuis index.php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_cart'])) {
                            
                            echo '<div class="alert-custom info"><strong>Données reçues:</strong><br>';
                            echo "menu_id: " . ($_POST['menu_id'] ?? 'non défini') . "<br>";
                            echo "quantite: " . ($_POST['quantite'] ?? 'non défini') . "<br>";
                            echo '</div>';
                            
                            try {
                                require_once 'db_connexion.php';
                                require_once 'includes/CartManager.php';
                                
                                $cartManager = new CartManager($pdo);
                                
                                // Utiliser la même logique que ajouter-au-panier.php
                                $menu_id = isset($_POST['menu_id']) ? filter_var($_POST['menu_id'], FILTER_VALIDATE_INT) : false;
                                $quantity = isset($_POST['quantite']) ? filter_var($_POST['quantite'], FILTER_VALIDATE_INT) : false;
                                
                                if (!$quantity) {
                                    $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 1;
                                }
                                
                                if ($menu_id && $quantity) {
                                    $result = $cartManager->addItem($menu_id, $quantity);
                                    
                                    if ($result['success']) {
                                        echo '<div class="alert-custom success">';
                                        echo '✅ <strong>Succès:</strong> ' . $result['message'];
                                        echo '</div>';
                                        
                                        // Afficher le contenu du panier
                                        $summary = $cartManager->getSummary();
                                        echo '<div class="alert-custom info">';
                                        echo '<strong>Résumé du panier:</strong><br>';
                                        echo "Total: {$summary['total_amount']}€<br>";
                                        echo "Articles: {$summary['total_items']}<br>";
                                        echo '</div>';
                                        
                                    } else {
                                        echo '<div class="alert-custom error">';
                                        echo '❌ <strong>Erreur:</strong> ' . $result['message'];
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="alert-custom error">';
                                    echo '❌ <strong>Paramètres invalides:</strong> menu_id=' . var_export($menu_id, true) . ', quantity=' . var_export($quantity, true);
                                    echo '</div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="alert-custom error">';
                                echo '❌ <strong>Exception:</strong> ' . $e->getMessage();
                                echo '</div>';
                            }
                        }
                        ?>
                        
                        <!-- Formulaire de test comme dans index.php -->
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="test_cart" value="1">
                            <input type="hidden" name="menu_id" value="1">
                            <div class="mb-3">
                                <label for="quantite" class="form-label">Quantité:</label>
                                <select name="quantite" id="quantite" class="form-select">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                🛒 Ajouter au panier (test)
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <h5>🔧 Tests techniques</h5>
                        <div class="alert-custom info">
                            <strong>Variables serveur HTTPS:</strong><br>
                            <small>
                                HTTPS: <?php echo $_SERVER['HTTPS'] ?? 'non défini'; ?><br>
                                SERVER_PORT: <?php echo $_SERVER['SERVER_PORT'] ?? 'non défini'; ?><br>
                                X-Forwarded-Proto: <?php echo $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'non défini'; ?><br>
                            </small>
                        </div>
                        
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">← Accueil</a>
                            <a href="menu.php" class="btn btn-primary">Menu →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
