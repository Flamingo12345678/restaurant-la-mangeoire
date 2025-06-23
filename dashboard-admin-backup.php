<?php
/**
 * Dashboard Administrateur - La Mangeoire
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

require_once 'db_connexion.php';

// Configuration pour le template header
define('INCLUDED_IN_PAGE', true);
$page_title = "Dashboard Système";

// Utiliser la connexion PDO (renommage pour cohérence)
$pdo = $pdo;

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

// Inclusion du fichier des fonctions système
require_once 'includes/system-stats.php';

// Récupération des vraies métriques système
$system_stats = getSystemStats();
$system_services = checkSystemServices($pdo);
$system_uptime = getSystemUptime();
$recent_events = getRecentSystemEvents($pdo, 4);

// Inclure le header admin harmonisé (identique aux autres pages)
require_once 'admin/header_template.php';
?>

<!-- Container avec classe spécifique pour les styles du dashboard -->
<div class="admin-dashboard">

<!-- Contenu spécifique au Dashboard Système -->
<div class="row mb-4">
<div class="col-12">
<div class="card bg-primary text-white">
<div class="card-body text-center py-4">
<h1 class="display-6 mb-3">
<i class="bi bi-speedometer2 me-3"></i><?php echo htmlspecialchars($page_title); ?>
</h1>
<p class="lead mb-0">Surveillance et Gestion Avancée du Système - Métriques en Temps Réel</p>
</div>
</div>
</div>
</div>

<style>
/* ===== STYLES CARTES STATISTIQUES IDENTIQUES AU DASHBOARD ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: none;
    border-radius: 20px;
    padding: 30px 25px;
    box-shadow: 
        0 8px 25px rgba(0,0,0,0.08),
        0 4px 10px rgba(0,0,0,0.03);
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, 
        var(--card-color, #17a2b8), 
        var(--card-color-light, #4dc3db)
    );
    border-radius: 20px 20px 0 0;
}

.stat-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 
        0 20px 40px rgba(0,0,0,0.15),
        0 8px 20px rgba(0,0,0,0.08);
}

/* Couleurs des cartes avec variables CSS */
.stat-card.success { 
    --card-color: #28a745; 
    --card-color-light: #5cbf2a;
}
.stat-card.warning { 
    --card-color: #ffc107; 
    --card-color-light: #ffcd39;
}
.stat-card.danger { 
    --card-color: #dc3545; 
    --card-color-light: #e4606d;
}
.stat-card.info { 
    --card-color: #17a2b8; 
    --card-color-light: #4dc3db;
}

.stat-value {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #2c3e50, #34495e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
    letter-spacing: -1px;
}

.stat-label {
    color: #6c757d;
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.stat-description {
    color: #8e9aaf;
    font-size: 0.85rem;
    font-style: italic;
    margin-top: 5px;
    opacity: 0.8;
}

.stat-card .card-icon {
    position: absolute;
    top: 25px;
    right: 25px;
    font-size: 2.5rem;
    color: var(--card-color);
    opacity: 0.2;
    transition: all 0.3s ease;
}

.stat-card:hover .card-icon {
    opacity: 0.4;
    transform: scale(1.1);
}

/* Animations */
.stat-card {
    animation: slideInUp 0.6s ease-out;
}

.stat-card:nth-child(1) { animation-delay: 0s; }
.stat-card:nth-child(2) { animation-delay: 0.1s; }
.stat-card:nth-child(3) { animation-delay: 0.2s; }
.stat-card:nth-child(4) { animation-delay: 0.3s; }

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .stat-card {
        padding: 25px 20px;
    }
    
    .stat-value {
        font-size: 2.5rem;
    }
    
    .stat-card .card-icon {
        font-size: 2rem;
        top: 20px;
        right: 20px;
    }
}

/* Styles spécifiques au dashboard système */
.system-services-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.service-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    margin-bottom: 10px;
    background: rgba(255,255,255,0.7);
    border-radius: 15px;
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.service-item:hover {
    background: rgba(255,255,255,0.9);
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Styles pour les status des services */
.system-status {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
}

.status-online {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
}

.status-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    box-shadow: 0 0 8px rgba(255, 193, 7, 0.5);
}

.status-offline {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    box-shadow: 0 0 8px rgba(220, 53, 69, 0.5);
}

.status-text {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 2px;
}

/* Styles pour les métriques de performance */
.performance-metric {
    background: rgba(255,255,255,0.8);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(0,0,0,0.05);
}

.performance-metric .progress {
    height: 8px;
    border-radius: 10px;
    background-color: rgba(0,0,0,0.1);
}

.performance-metric .progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}

/* Styles pour les logs */
.log-entry {
    padding: 12px 15px;
    margin-bottom: 8px;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 10px;
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}

.log-entry:hover {
    background: rgba(255, 255, 255, 0.9);
    border-left-color: #007bff;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.log-severity {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.severity-info { 
    background: linear-gradient(135deg, #e3f2fd, #bbdefb); 
    color: #0277bd; 
}
.severity-warning { 
    background: linear-gradient(135deg, #fff8e1, #ffecb3); 
    color: #f57f17; 
}
.severity-error { 
    background: linear-gradient(135deg, #ffebee, #ffcdd2); 
    color: #c62828; 
}
.severity-success { 
    background: linear-gradient(135deg, #e8f5e8, #c8e6c9); 
    color: #2e7d32; 
}

/* ===== RESPONSIVE AMÉLIORÉ ===== */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .stat-card {
        padding: 25px 20px;
    }
    
    .stat-value {
        font-size: 2.5rem;
    }
}

/* ===== ANIMATIONS GLOBALES ===== */
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

<!-- Cartes Statistiques du Dashboard Système -->
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

<!-- Services Système et Métriques -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="system-services-card">
            <h5 class="card-title mb-4">
                <i class="bi bi-activity me-2"></i>État des Services Système
            </h5>
            <?php if (isset($system_services) && is_array($system_services)): ?>
                <?php foreach ($system_services as $service => $status): ?>
                <div class="service-item" data-service="<?php echo htmlspecialchars($service); ?>">
                    <span class="system-status status-<?php echo $status; ?>"></span>
                    <div class="flex-grow-1">
                        <strong><?php echo htmlspecialchars($service); ?></strong>
                        <div class="status-text">
                            <?php echo $status === 'online' ? 'En ligne' : ($status === 'warning' ? 'Attention' : 'Hors ligne'); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="service-item">
                    <span class="system-status status-online"></span>
                    <div class="flex-grow-1">
                        <strong>Système de Base</strong>
                        <div class="status-text">Surveillance en cours</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="system-services-card">
            <h5 class="card-title mb-4">
                <i class="bi bi-graph-up me-2"></i>Performances Système
            </h5>
            
            <div class="performance-metric mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-cpu me-2 text-primary"></i>
                        <span>Utilisation CPU</span>
                    </div>
                    <span class="badge bg-<?php echo ($system_stats['cpu'] ?? 0) > 80 ? 'danger' : (($system_stats['cpu'] ?? 0) > 60 ? 'warning' : 'success'); ?> cpu-percent">
                        <?php echo $system_stats['cpu'] ?? 0; ?>%
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar cpu-progress bg-<?php echo ($system_stats['cpu'] ?? 0) > 80 ? 'danger' : (($system_stats['cpu'] ?? 0) > 60 ? 'warning' : 'success'); ?>" 
                         style="width: <?php echo $system_stats['cpu'] ?? 0; ?>%"></div>
                </div>
            </div>
            
            <div class="performance-metric mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-memory me-2 text-info"></i>
                        <span>Utilisation RAM</span>
                    </div>
                    <span class="badge bg-<?php echo ($system_stats['memory'] ?? 0) > 80 ? 'danger' : (($system_stats['memory'] ?? 0) > 60 ? 'warning' : 'success'); ?> memory-percent">
                        <?php echo $system_stats['memory'] ?? 0; ?>%
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar memory-progress bg-<?php echo ($system_stats['memory'] ?? 0) > 80 ? 'danger' : (($system_stats['memory'] ?? 0) > 60 ? 'warning' : 'success'); ?>" 
                         style="width: <?php echo $system_stats['memory'] ?? 0; ?>%"></div>
                </div>
            </div>
            
            <div class="performance-metric mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-hdd me-2 text-warning"></i>
                        <span>Espace Disque</span>
                    </div>
                    <span class="badge bg-<?php echo ($system_stats['disk'] ?? 0) > 80 ? 'danger' : (($system_stats['disk'] ?? 0) > 60 ? 'warning' : 'info'); ?> disk-percent">
                        <?php echo $system_stats['disk'] ?? 0; ?>%
                    </span>
                </div>
                <div class="progress">
                    <div class="progress-bar disk-progress bg-<?php echo ($system_stats['disk'] ?? 0) > 80 ? 'danger' : (($system_stats['disk'] ?? 0) > 60 ? 'warning' : 'info'); ?>" 
                         style="width: <?php echo $system_stats['disk'] ?? 0; ?>%"></div>
                </div>
            </div>
            
            <div class="uptime-section text-center">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="bi bi-clock me-2 text-success"></i>
                    <span class="fw-semibold">Uptime:</span>
                    <span class="badge bg-success ms-2 uptime-display"><?php echo $system_uptime ?? 'N/A'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Système Récents -->
<div class="row mb-4">
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
                    // Utiliser les événements récents du système
                    if (empty($recent_events)) {
                        // Fallback vers les logs d'audit si pas d'événements système
                        try {
                            $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 10");
                            $logs = $stmt->fetchAll();
                            
                            if (empty($logs)) {
                                echo '<p class="text-muted">Aucun événement récent disponible.</p>';
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
                    } else {
                        // Afficher les événements système récents
                        foreach ($recent_events as $event): ?>
                            <div class="log-entry">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="log-severity severity-<?php echo htmlspecialchars($event['severity'] ?? 'info'); ?>">
                                            <?php echo htmlspecialchars($event['type'] ?? 'system'); ?>
                                        </span>
                                        <span class="ms-2"><?php echo htmlspecialchars($event['message'] ?? 'Événement système'); ?></span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($event['timestamp'] ?? 'now')); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- Fermeture du container admin-dashboard -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation de comptage pour les valeurs statistiques
    function animateCountUp(element, start, end, duration) {
        let startTimestamp = null;
        
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        
        window.requestAnimationFrame(step);
    }
    
    // Animer les valeurs numériques
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach((value, index) => {
        const text = value.textContent.replace(/[€\s,]/g, '');
        const finalValue = parseInt(text) || 0;
        value.textContent = '0';
        
        setTimeout(() => {
            animateCountUp(value, 0, finalValue, 2000);
            // Remettre le symbole € si nécessaire
            setTimeout(() => {
                if (text.includes('€')) {
                    value.textContent = value.textContent + '€';
                }
            }, 2000);
        }, 300 + (index * 200));
    });
    
    // Actualisation automatique des métriques système
    setInterval(function() {
        console.log('Actualisation des métriques système...');
        updateSystemStats();
    }, 30000); // Toutes les 30 secondes
    
    // Première mise à jour après 5 secondes
    setTimeout(updateSystemStats, 5000);
});

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

// Actualisation automatique des statistiques via AJAX
function updateSystemStats() {
    fetch('api/system-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour les barres de progression
                updateProgressBar('cpu', data.stats.cpu);
                updateProgressBar('memory', data.stats.memory);
                updateProgressBar('disk', data.stats.disk);
                
                // Mettre à jour l'uptime
                const uptimeElement = document.querySelector('.uptime-display');
                if (uptimeElement) {
                    uptimeElement.textContent = data.uptime;
                }
                
                // Mettre à jour les statuts des services
                updateServiceStatus(data.services);
                
                console.log('Statistiques mises à jour:', data.timestamp);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour des statistiques:', error);
        });
}

function updateProgressBar(type, value) {
    const percentElement = document.querySelector(`.${type}-percent`);
    const progressBar = document.querySelector(`.${type}-progress`);
    
    if (percentElement) {
        percentElement.textContent = value + '%';
    }
    
    if (progressBar) {
        progressBar.style.width = value + '%';
        
        // Changer la couleur selon le seuil
        let colorClass = 'bg-success';
        if (value > 80) colorClass = 'bg-danger';
        else if (value > 60) colorClass = 'bg-warning';
        
        progressBar.className = progressBar.className.replace(/bg-\w+/, colorClass);
    }
}

function updateServiceStatus(services) {
    Object.keys(services).forEach(serviceName => {
        const serviceElement = document.querySelector(`[data-service="${serviceName}"]`);
        if (serviceElement) {
            const statusElement = serviceElement.querySelector('.system-status');
            const textElement = serviceElement.querySelector('.status-text');
            
            if (statusElement) {
                statusElement.className = `system-status status-${services[serviceName]}`;
            }
            
            if (textElement) {
                const statusText = services[serviceName] === 'online' ? 'En ligne' : 
                                 (services[serviceName] === 'warning' ? 'Attention' : 'Hors ligne');
                textElement.textContent = statusText;
            }
        }
    });
}
</script>

<?php
// Inclure le footer admin harmonisé
require_once 'admin/footer_template.php';
?>
