<?php
// Test rapide du dashboard intégré
session_start();

// Session de test
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';
$_SESSION['admin_username'] = 'superadmin';

// Définir la constante pour l'inclusion du header
define('INCLUDED_IN_PAGE', true);

$page_title = "Dashboard Système";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dashboard-container {
            margin-left: 0;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        @media (min-width: 992px) {
            .dashboard-container {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin/header_template.php'; ?>
    
    <div class="dashboard-container">
        <!-- En-tête du Dashboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center py-5">
                        <h1 class="display-6 mb-3">
                            <i class="bi bi-speedometer2 me-3"></i>Dashboard Système
                        </h1>
                        <p class="lead mb-0">Surveillance et Gestion Avancée du Système</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test des statistiques -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h2 mb-0">12</div>
                                <div class="small">Commandes Aujourd'hui</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-basket-fill fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h2 mb-0">245€</div>
                                <div class="small">CA Aujourd'hui</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-currency-euro fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h2 mb-0">8</div>
                                <div class="small">Sessions Actives</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people-fill fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h2 mb-0">2</div>
                                <div class="small">Erreurs Aujourd'hui</div>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-exclamation-triangle-fill fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions Rapides -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning-charge-fill me-2"></i>Actions Rapides
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100">
                                    <i class="bi bi-database-gear"></i> Optimiser BD
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-success w-100">
                                    <i class="bi bi-download"></i> Exporter Logs
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-warning w-100">
                                    <i class="bi bi-trash"></i> Nettoyer Logs
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-danger w-100">
                                    <i class="bi bi-arrow-clockwise"></i> Vider Cache
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="test-workflow-complet.php" class="btn btn-outline-info w-100">
                                    <i class="bi bi-bug"></i> Tests Système
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="check-production-setup.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-search"></i> Vérif Production
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- État du Système -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-heart-pulse-fill me-2"></i>État du Système
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <span class="badge bg-success me-2">
                                        <i class="bi bi-database"></i>
                                    </span>
                                    <span class="small">Base de Données</span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <span class="badge bg-warning me-2">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <span class="small">Email SMTP</span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <span class="badge bg-success me-2">
                                        <i class="bi bi-journal-text"></i>
                                    </span>
                                    <span class="small">Logs Audit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Dashboard intégré avec succès !</strong>
            Le dashboard système est maintenant accessible depuis la sidebar d'administration, uniquement pour les superadmins.
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
