<?php
/**
 * Vérification de la configuration HTTPS
 * Test des redirections et de la sécurité
 */

require_once 'includes/https-security.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification HTTPS - Restaurant La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .status-ok { color: #198754; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .test-card {
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-card.warning { border-left-color: #ffc107; }
        .test-card.error { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0">
                            <i class="bi bi-shield-lock"></i>
                            Vérification de la configuration HTTPS
                        </h1>
                    </div>
                    <div class="card-body">
                        
                        <!-- Test 1: Connexion sécurisée -->
                        <div class="card test-card mb-3 <?php echo IS_HTTPS ? '' : 'error'; ?>">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-<?php echo IS_HTTPS ? 'check-circle status-ok' : 'x-circle status-error'; ?>"></i>
                                    Connexion sécurisée
                                </h5>
                                <p class="card-text">
                                    <?php if (IS_HTTPS): ?>
                                        ✅ La connexion est sécurisée via HTTPS
                                    <?php else: ?>
                                        ❌ La connexion n'est pas sécurisée (HTTP)
                                    <?php endif; ?>
                                </p>
                                <small class="text-muted">
                                    URL actuelle : <?php echo SECURE_BASE_URL; ?>
                                </small>
                            </div>
                        </div>

                        <!-- Test 2: En-têtes de sécurité -->
                        <div class="card test-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-shield-check status-ok"></i>
                                    En-têtes de sécurité
                                </h5>
                                <p class="card-text">
                                    Les en-têtes de sécurité sont configurés automatiquement :
                                </p>
                                <ul class="list-unstyled">
                                    <li>✅ HSTS (Strict-Transport-Security)</li>
                                    <li>✅ Protection Clickjacking (X-Frame-Options)</li>
                                    <li>✅ Protection XSS (X-XSS-Protection)</li>
                                    <li>✅ Content Security Policy</li>
                                    <li>✅ Cookies sécurisés</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Test 3: Variables serveur -->
                        <div class="card test-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-info-circle status-ok"></i>
                                    Variables serveur
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>HTTPS</strong></td>
                                                <td><?php echo $_SERVER['HTTPS'] ?? 'non défini'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>SERVER_PORT</strong></td>
                                                <td><?php echo $_SERVER['SERVER_PORT'] ?? 'non défini'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>X-Forwarded-Proto</strong></td>
                                                <td><?php echo $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'non défini'; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>X-Forwarded-SSL</strong></td>
                                                <td><?php echo $_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'non défini'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>REQUEST_SCHEME</strong></td>
                                                <td><?php echo $_SERVER['REQUEST_SCHEME'] ?? 'non défini'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>HTTP_HOST</strong></td>
                                                <td><?php echo $_SERVER['HTTP_HOST'] ?? 'non défini'; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Test 4: Test du panier sécurisé -->
                        <div class="card test-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-cart-check status-ok"></i>
                                    Test du panier sécurisé
                                </h5>
                                <p class="card-text">
                                    Test de la fonction d'ajout au panier avec HTTPS :
                                </p>
                                <button id="testCartBtn" class="btn btn-primary">
                                    <i class="bi bi-cart-plus"></i>
                                    Tester l'ajout au panier
                                </button>
                                <div id="testResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Test 5: Recommandations -->
                        <div class="card test-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-lightbulb status-warning"></i>
                                    Recommandations
                                </h5>
                                <div class="alert alert-info">
                                    <h6>Pour la production :</h6>
                                    <ul class="mb-0">
                                        <li>Décommentez la ligne <code>forceHTTPS()</code> dans <code>includes/https-security.php</code></li>
                                        <li>Obtenez un certificat SSL valide (Let's Encrypt gratuit)</li>
                                        <li>Configurez votre serveur web pour HTTPS</li>
                                        <li>Testez la redirection HTTP → HTTPS</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6>Pour le développement local :</h6>
                                    <ul class="mb-0">
                                        <li>Utilisez <code>https://localhost</code> avec un certificat auto-signé</li>
                                        <li>Ou désactivez temporairement <code>forceHTTPS()</code></li>
                                        <li>Gardez la ligne commentée pour éviter les redirections infinies</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="menu.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i>
                                Retour au menu
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i>
                                Actualiser les tests
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Test du panier sécurisé
        document.getElementById('testCartBtn').addEventListener('click', async function() {
            const btn = this;
            const result = document.getElementById('testResult');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Test en cours...';
            
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
                
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>Succès !</strong> ${data.message}
                        </div>
                    `;
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
                        <strong>Erreur de connexion :</strong> ${error.message}
                    </div>
                `;
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cart-plus"></i> Tester l\'ajout au panier';
        });
    </script>
</body>
</html>
