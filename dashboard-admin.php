<?php
/**
 * Dashboard Administrateur avec Monitoring Paiements - La Mangeoire
 * Date: 23 juin 2025
 * 
 * Dashboard avancé pour le monitoring système et des paiements
 * Accessible uniquement aux superadmins
 */

// Protection contre l'inclusion directe - requis par les templates admin
define('INCLUDED_IN_PAGE', true);

// Démarrer la session
session_start();

// Vérification d'accès superadmin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: admin/login.php?error=access_denied');
    exit;
}

require_once 'db_connexion.php';
require_once 'includes/payment_manager.php';

// Configuration pour le template header
$page_title = "Dashboard Système";
$is_in_admin_folder = false; // On est à la racine, pas dans /admin/

// Utiliser la connexion PDO
$pdo = $pdo;

// Récupération des statistiques système
try {
    // Nombre total de commandes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Commandes WHERE 1");
    $total_commandes = $stmt->fetch()['total'] ?? 0;
    
    // Revenus du mois
    $stmt = $pdo->query("SELECT COALESCE(SUM(MontantTotal), 0) as revenus FROM Commandes WHERE DATE(DateCommande) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND Statut = 'Payée'");
    $revenus_mois = $stmt->fetch()['revenus'] ?? 0;
    
    // Clients actifs
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ClientID) as clients FROM Commandes WHERE DATE(DateCommande) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $clients_actifs = $stmt->fetch()['clients'] ?? 0;
    
    // Réservations en attente
    $stmt = $pdo->query("SELECT COUNT(*) as reservations FROM Reservations WHERE Statut = 'En attente'");
    $reservations_attente = $stmt->fetch()['reservations'] ?? 0;
    
} catch(PDOException $e) {
    // Valeurs par défaut en cas d'erreur
    $total_commandes = 0;
    $revenus_mois = 0;
    $clients_actifs = 0;
    $reservations_attente = 0;
}

// Récupération des statistiques de paiements
try {
    // Statistiques Stripe
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_stripe,
        COALESCE(SUM(montant), 0) as volume_stripe,
        COUNT(CASE WHEN statut = 'completed' THEN 1 END) as success_stripe,
        COUNT(CASE WHEN statut = 'failed' THEN 1 END) as failed_stripe
        FROM paiements 
        WHERE mode_paiement = 'stripe' 
        AND DATE(date_creation) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stats_stripe = $stmt->fetch();
    
    // Statistiques PayPal
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_paypal,
        COALESCE(SUM(montant), 0) as volume_paypal,
        COUNT(CASE WHEN statut = 'completed' THEN 1 END) as success_paypal,
        COUNT(CASE WHEN statut = 'failed' THEN 1 END) as failed_paypal
        FROM paiements 
        WHERE mode_paiement = 'paypal' 
        AND DATE(date_creation) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stats_paypal = $stmt->fetch();
    
    // Paiements récents
    $stmt = $pdo->query("SELECT 
        p.*, 
        c.nom as client_nom, 
        c.email as client_email,
        cmd.montant_total as commande_montant
        FROM paiements p
        LEFT JOIN clients c ON p.client_id = c.id
        LEFT JOIN commandes cmd ON p.commande_id = cmd.id
        ORDER BY p.date_creation DESC 
        LIMIT 10");
    $paiements_recents = $stmt->fetchAll();
    
    // Taux de conversion
    $total_tentatives = ($stats_stripe['total_stripe'] ?? 0) + ($stats_paypal['total_paypal'] ?? 0);
    $total_succes = ($stats_stripe['success_stripe'] ?? 0) + ($stats_paypal['success_paypal'] ?? 0);
    $taux_conversion = $total_tentatives > 0 ? ($total_succes / $total_tentatives) * 100 : 0;
    
} catch(PDOException $e) {
    // Valeurs par défaut en cas d'erreur
    $stats_stripe = ['total_stripe' => 0, 'volume_stripe' => 0, 'success_stripe' => 0, 'failed_stripe' => 0];
    $stats_paypal = ['total_paypal' => 0, 'volume_paypal' => 0, 'success_paypal' => 0, 'failed_paypal' => 0];
    $paiements_recents = [];
    $taux_conversion = 0;
}

// Inclure le header admin avec sidebar
require_once 'admin/header_template.php';
?>

<style>
        /* ===== VARIABLES CSS ===== */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --danger-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            --card-radius: 20px;
            --transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        /* ===== ADAPTATION POUR LA SIDEBAR ===== */
        .admin-main-content {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container-fluid {
            padding: 0;
        }

        /* Adaptation pour desktop avec sidebar */
        @media (min-width: 992px) {
            .admin-main-content {
                margin-left: 250px;
                padding: 30px;
            }
        }

        /* ===== HEADER AVEC ONGLETS ===== */
        .admin-header {
            background: var(--primary-gradient);
            border-radius: var(--card-radius);
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            margin: 0;
            font-weight: 700;
            font-size: 2.5rem;
        }

        .admin-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        /* ===== NAVIGATION ONGLETS ===== */
        .custom-tabs {
            margin-bottom: 30px;
        }

        .custom-tabs .nav-link {
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 15px 15px 0 0;
            margin-right: 10px;
            padding: 15px 25px;
            font-weight: 600;
            color: #666;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .custom-tabs .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .custom-tabs .nav-link.active {
            background: white;
            color: #333;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
        }

        .custom-tabs .nav-link.active::before {
            transform: scaleX(1);
        }

        .custom-tabs .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* ===== CARTES STATISTIQUES ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.95);
            padding: 30px 25px;
            border-radius: var(--card-radius);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-gradient, var(--primary-gradient));
            border-radius: var(--card-radius) var(--card-radius) 0 0;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-card.success { --card-gradient: var(--success-gradient); }
        .stat-card.warning { --card-gradient: var(--warning-gradient); }
        .stat-card.danger { --card-gradient: var(--danger-gradient); }
        .stat-card.info { --card-gradient: var(--primary-gradient); }

        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            background: var(--card-gradient, var(--primary-gradient));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-description {
            font-size: 0.9rem;
            color: #666;
        }

        .card-icon {
            position: absolute;
            top: 25px;
            right: 25px;
            font-size: 2.5rem;
            opacity: 0.2;
            transition: var(--transition);
        }

        .stat-card:hover .card-icon {
            opacity: 0.3;
            transform: scale(1.1);
        }

        /* ===== CARTES DE CONTENU ===== */
        .content-card {
            background: rgba(255,255,255,0.95);
            border-radius: var(--card-radius);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }

        .content-card .card-header {
            background: transparent;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            padding: 25px;
        }

        .content-card .card-body {
            padding: 25px;
        }

        /* ===== TABLEAU DES PAIEMENTS ===== */
        .payment-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .payment-table th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            padding: 15px;
            border: none;
        }

        .payment-table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .payment-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        /* ===== BADGES ET STATUTS ===== */
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completed {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            color: #2e7d32;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff8e1, #ffecb3);
            color: #f57f17;
        }

        .status-failed {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
        }

        /* ===== GRAPHIQUES ===== */
        .chart-container {
            position: relative;
            height: 350px;
            padding: 20px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 25px 20px;
            }
            
            .stat-value {
                font-size: 2.5rem;
            }
            
            .container-fluid {
                padding: 15px;
            }
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:nth-child(4) { animation-delay: 0.3s; }
    </style>

<!-- Navigation par onglets -->
<div class="container-fluid">
    <ul class="nav nav-tabs custom-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                    <i class="bi bi-cpu me-2"></i>Dashboard Système
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                    <i class="bi bi-credit-card me-2"></i>Monitoring Paiements
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="adminTabsContent">
            <!-- ONGLET SYSTÈME -->
            <div class="tab-pane fade show active" id="system" role="tabpanel">
                <!-- Statistiques Système -->
                <div class="stats-grid">
                    <div class="stat-card success">
                        <i class="bi bi-bag-check card-icon"></i>
                        <div class="stat-value"><?php echo number_format($total_commandes); ?></div>
                        <div class="stat-label">Total Commandes</div>
                        <div class="stat-description">Toutes les commandes enregistrées</div>
                    </div>
                    
                    <div class="stat-card info">
                        <i class="bi bi-currency-euro card-icon"></i>
                        <div class="stat-value"><?php echo number_format($revenus_mois, 0); ?>€</div>
                        <div class="stat-label">Revenus (30j)</div>
                        <div class="stat-description">Chiffre d'affaires du mois</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <i class="bi bi-people card-icon"></i>
                        <div class="stat-value"><?php echo number_format($clients_actifs); ?></div>
                        <div class="stat-label">Clients Actifs</div>
                        <div class="stat-description">Clients ayant commandé récemment</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <i class="bi bi-calendar-check card-icon"></i>
                        <div class="stat-value"><?php echo number_format($reservations_attente); ?></div>
                        <div class="stat-label">Réservations en Attente</div>
                        <div class="stat-description">À confirmer ou traiter</div>
                    </div>
                </div>

                <!-- Contenu système existant (services, métriques, logs...) -->
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-activity me-2"></i>État des Services Système
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <div class="p-3 rounded bg-light">
                                            <i class="bi bi-server text-success" style="font-size: 2rem;"></i>
                                            <h6 class="mt-2">Serveur Web</h6>
                                            <span class="badge bg-success">En ligne</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="p-3 rounded bg-light">
                                            <i class="bi bi-database text-success" style="font-size: 2rem;"></i>
                                            <h6 class="mt-2">Base de Données</h6>
                                            <span class="badge bg-success">Connectée</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="p-3 rounded bg-light">
                                            <i class="bi bi-envelope text-success" style="font-size: 2rem;"></i>
                                            <h6 class="mt-2">Service Email</h6>
                                            <span class="badge bg-success">Opérationnel</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="p-3 rounded bg-light">
                                            <i class="bi bi-credit-card text-success" style="font-size: 2rem;"></i>
                                            <h6 class="mt-2">API Paiements</h6>
                                            <span class="badge bg-success">Actives</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ONGLET PAIEMENTS -->
            <div class="tab-pane fade" id="payments" role="tabpanel">
                <!-- Statistiques Paiements -->
                <div class="stats-grid">
                    <div class="stat-card success">
                        <i class="bi bi-credit-card card-icon"></i>
                        <div class="stat-value"><?php echo number_format($stats_stripe['total_stripe'] ?? 0); ?></div>
                        <div class="stat-label">Paiements Stripe</div>
                        <div class="stat-description">Volume : <?php echo number_format($stats_stripe['volume_stripe'] ?? 0, 0); ?>€</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <i class="bi bi-paypal card-icon"></i>
                        <div class="stat-value"><?php echo number_format($stats_paypal['total_paypal'] ?? 0); ?></div>
                        <div class="stat-label">Paiements PayPal</div>
                        <div class="stat-description">Volume : <?php echo number_format($stats_paypal['volume_paypal'] ?? 0, 0); ?>€</div>
                    </div>
                    
                    <div class="stat-card info">
                        <i class="bi bi-graph-up card-icon"></i>
                        <div class="stat-value"><?php echo number_format($taux_conversion, 1); ?>%</div>
                        <div class="stat-label">Taux de Conversion</div>
                        <div class="stat-description">Succès / Total des tentatives</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <i class="bi bi-exclamation-triangle card-icon"></i>
                        <div class="stat-value"><?php echo number_format(($stats_stripe['failed_stripe'] ?? 0) + ($stats_paypal['failed_paypal'] ?? 0)); ?></div>
                        <div class="stat-label">Échecs de Paiement</div>
                        <div class="stat-description">Paiements échoués (30j)</div>
                    </div>
                </div>

                <!-- Graphiques et analyses -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pie-chart me-2"></i>Répartition des Paiements
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="content-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-bar-chart me-2"></i>Taux de Succès par Méthode
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="successRateChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des paiements récents -->
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>Paiements Récents
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table payment-table">
                                <thead>
                                    <tr>
                                        <th>ID Transaction</th>
                                        <th>Client</th>
                                        <th>Méthode</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paiements_recents)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="bi bi-inbox me-2"></i>Aucun paiement récent
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($paiements_recents as $paiement): ?>
                                            <tr>
                                                <td>
                                                    <code><?php echo htmlspecialchars(substr($paiement['transaction_id'] ?? 'N/A', 0, 20)); ?>...</code>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($paiement['client_nom'] ?? 'Client'); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($paiement['client_email'] ?? ''); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $paiement['mode_paiement'] === 'stripe' ? 'primary' : 'warning'; ?>">
                                                        <i class="bi bi-<?php echo $paiement['mode_paiement'] === 'stripe' ? 'credit-card' : 'paypal'; ?> me-1"></i>
                                                        <?php echo ucfirst($paiement['mode_paiement'] ?? 'Inconnu'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo number_format($paiement['montant'] ?? 0, 2); ?>€</strong>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($paiement['statut'] ?? 'pending'); ?>">
                                                        <?php echo ucfirst($paiement['statut'] ?? 'En attente'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y H:i', strtotime($paiement['date_creation'] ?? 'now')); ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" title="Voir détails">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-secondary" title="Télécharger reçu">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin du contenu principal -->

    <script>
        // Graphique répartition des paiements
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        new Chart(paymentMethodsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Stripe', 'PayPal'],
                datasets: [{
                    data: [
                        <?php echo $stats_stripe['total_stripe'] ?? 0; ?>,
                        <?php echo $stats_paypal['total_paypal'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Graphique taux de succès
        const successRateCtx = document.getElementById('successRateChart').getContext('2d');
        new Chart(successRateCtx, {
            type: 'bar',
            data: {
                labels: ['Stripe', 'PayPal'],
                datasets: [{
                    label: 'Taux de Succès (%)',
                    data: [
                        <?php 
                        $stripe_rate = ($stats_stripe['total_stripe'] ?? 0) > 0 ? 
                            (($stats_stripe['success_stripe'] ?? 0) / ($stats_stripe['total_stripe'] ?? 1)) * 100 : 0;
                        echo number_format($stripe_rate, 1);
                        ?>,
                        <?php 
                        $paypal_rate = ($stats_paypal['total_paypal'] ?? 0) > 0 ? 
                            (($stats_paypal['success_paypal'] ?? 0) / ($stats_paypal['total_paypal'] ?? 1)) * 100 : 0;
                        echo number_format($paypal_rate, 1);
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.6)',
                        'rgba(255, 193, 7, 0.6)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Actualisation automatique des données toutes les 30 secondes
        setInterval(function() {
            refreshMonitoringData();
        }, 30000);

        // Fonction pour actualiser les données de monitoring
        async function refreshMonitoringData() {
            try {
                console.log('Actualisation des données de monitoring...');
                
                // Appel API monitoring
                const response = await fetch('api/monitoring.php');
                if (!response.ok) {
                    throw new Error('Erreur API: ' + response.status);
                }
                
                const data = await response.json();
                console.log('Données reçues:', data);
                
                // Mise à jour des cartes statistiques
                if (data.stats) {
                    updateStatsCards(data.stats);
                }
                
                // Mise à jour des alertes
                if (data.alerts) {
                    updateAlerts(data.alerts);
                }
                
            } catch (error) {
                console.error('Erreur lors de l\'actualisation:', error);
            }
        }

        // Mise à jour des cartes statistiques
        function updateStatsCards(stats) {
            const totalElement = document.querySelector('.card-body h3:first-child');
            const volumeElement = document.querySelector('.card-body h3:nth-child(2)');
            const rateElement = document.querySelector('.card-body h3:nth-child(3)');
            
            if (totalElement && stats.total_24h !== undefined) {
                totalElement.textContent = stats.total_24h + ' paiements';
            }
            if (volumeElement && stats.volume_24h !== undefined) {
                volumeElement.textContent = stats.volume_24h + ' EUR';
            }
            if (rateElement && stats.success_rate !== undefined) {
                rateElement.textContent = stats.success_rate + '%';
            }
        }

        // Mise à jour des alertes
        function updateAlerts(alerts) {
            const alertsContainer = document.querySelector('.alerts-container');
            if (alertsContainer) {
                alertsContainer.innerHTML = '';
                alerts.forEach(alert => {
                    const alertElement = document.createElement('div');
                    alertElement.className = `alert alert-${alert.level === 'high' ? 'danger' : 'warning'} alert-dismissible fade show`;
                    alertElement.innerHTML = `
                        <strong>${alert.level.toUpperCase()}</strong> ${alert.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    alertsContainer.appendChild(alertElement);
                });
            }
        }

        // Chargement initial des données
        document.addEventListener('DOMContentLoaded', function() {
            refreshMonitoringData();
        });
    </script>

    <!-- Scripts Chart.js spécifiques au dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
</div> <!-- Fermeture container-fluid -->

<?php
// Inclure le footer admin
require_once 'admin/footer_template.php';
?>
