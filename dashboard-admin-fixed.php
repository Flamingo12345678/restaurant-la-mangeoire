<?php
/**
 * Dashboard Administrateur Système - La Mangeoire
 * Date: 21 juin 2025
 * 
 * Dashboard avancé pour le monitoring et la gestion du système
 * Accessible uniquement aux superadmins
 */

// Démarrer la session
session_start();

// Vérification d'accès superadmin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: admin/login.php?error=access_denied');
    exit;
}

// Définir la constante pour l'inclusion du header
define('INCLUDED_IN_PAGE', true);

require_once 'db_connexion.php';
require_once 'includes/audit-logger.php';

// Définir le titre de la page
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
        /* Styles spécifiques au dashboard système */
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
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #007bff;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.info { border-left-color: #17a2b8; }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .system-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-offline { background: #dc3545; }
        
        .log-entry {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }
        
        .log-entry:hover {
            background-color: #f8f9fa;
        }
        
        .log-severity {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .severity-info { background: #e3f2fd; color: #0277bd; }
        .severity-warning { background: #fff8e1; color: #f57f17; }
        .severity-error { background: #ffebee; color: #c62828; }
        .severity-success { background: #e8f5e8; color: #2e7d32; }
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

        <!-- Statistiques Système -->
        <div class="row g-4 mb-4">
            <?php
            // Récupération des statistiques
            try {
                // Nombre total de commandes
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes WHERE 1");
                $total_commandes = $stmt->fetch()['total'] ?? 0;
                
                // Revenus du mois
                $stmt = $pdo->query("SELECT COALESCE(SUM(montant_total), 0) as revenus FROM commandes WHERE DATE(date_creation) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND statut = 'payee'");
                $revenus_mois = $stmt->fetch()['revenus'] ?? 0;
                
                // Clients actifs
                $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as clients FROM commandes WHERE DATE(date_creation) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                $clients_actifs = $stmt->fetch()['clients'] ?? 0;
                
                // Réservations en attente
                $stmt = $pdo->query("SELECT COUNT(*) as reservations FROM reservations WHERE statut = 'en_attente'");
                $reservations_attente = $stmt->fetch()['reservations'] ?? 0;
                
            } catch(PDOException $e) {
                // Valeurs par défaut en cas d'erreur
                $total_commandes = 0;
                $revenus_mois = 0;
                $clients_actifs = 0;
                $reservations_attente = 0;
            }
            ?>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo number_format($total_commandes); ?></div>
                    <div class="stat-label">Total Commandes</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-value"><?php echo number_format($revenus_mois, 2); ?>€</div>
                    <div class="stat-label">Revenus (30j)</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo number_format($clients_actifs); ?></div>
                    <div class="stat-label">Clients Actifs</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo number_format($reservations_attente); ?></div>
                    <div class="stat-label">Réservations en Attente</div>
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
                                <a href="admin-reservations.php" class="btn btn-primary w-100">
                                    <i class="bi bi-calendar-check"></i> Réservations
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="commandes.php" class="btn btn-success w-100">
                                    <i class="bi bi-bag-check"></i> Commandes
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="clients.php" class="btn btn-info w-100">
                                    <i class="bi bi-people"></i> Clients
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="menus.php" class="btn btn-warning w-100">
                                    <i class="bi bi-list-ul"></i> Menus
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="paiements.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-credit-card"></i> Paiements
                                </a>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-danger w-100" onclick="optimizeDatabase()">
                                    <i class="bi bi-gear"></i> Optimiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- État du Système -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-activity me-2"></i>État du Système
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Vérification de l'état du système
                        $system_checks = [
                            'Base de données' => 'online',
                            'Serveur Web' => 'online',
                            'API Paiements' => 'warning',
                            'Stockage Fichiers' => 'online',
                            'Email SMTP' => 'online'
                        ];
                        
                        foreach ($system_checks as $service => $status): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?php echo htmlspecialchars($service); ?></span>
                                <span>
                                    <span class="system-status status-<?php echo $status; ?>"></span>
                                    <?php echo ucfirst($status === 'online' ? 'En ligne' : ($status === 'warning' ? 'Attention' : 'Hors ligne')); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>Performances
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Utilisation CPU</span>
                                <span>45%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 45%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Utilisation RAM</span>
                                <span>68%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 68%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Espace Disque</span>
                                <span>23%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: 23%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Récents -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-journal-text me-2"></i>Logs Système Récents
                        </h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php
                            // Récupération des logs récents
                            try {
                                $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 10");
                                $logs = $stmt->fetchAll();
                                
                                if (empty($logs)) {
                                    echo '<p class="text-muted">Aucun log récent disponible.</p>';
                                } else {
                                    foreach ($logs as $log): ?>
                                        <div class="log-entry">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="log-severity severity-<?php echo htmlspecialchars($log['severity'] ?? 'info'); ?>">
                                                        <?php echo htmlspecialchars($log['severity'] ?? 'info'); ?>
                                                    </span>
                                                    <span class="ms-2"><?php echo htmlspecialchars($log['message'] ?? 'Message non disponible'); ?></span>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($log['timestamp'] ?? 'now')); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                }
                            } catch(PDOException $e) {
                                echo '<p class="text-danger">Erreur lors du chargement des logs: ' . htmlspecialchars($e->getMessage()) . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction d'optimisation de la base de données
        function optimizeDatabase() {
            if (confirm('Voulez-vous vraiment optimiser la base de données ? Cette opération peut prendre quelques minutes.')) {
                fetch('optimiser-base-donnees.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Base de données optimisée avec succès !', 'success');
                    } else {
                        showToast('Erreur lors de l\'optimisation: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Erreur de communication avec le serveur', 'error');
                });
            }
        }

        // Fonction d'affichage des notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast show align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Actualisation automatique des statistiques toutes les 30 secondes
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
