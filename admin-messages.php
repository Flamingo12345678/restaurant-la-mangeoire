<?php
/**
 * Interface Administration - Messages de Contact
 * Date: 22 juin 2025
 * Interface modernisée intégrée avec l'admin harmonisé
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir la constante pour l'inclusion du header
define('INCLUDED_IN_PAGE', true);

require_once 'db_connexion.php';
require_once 'admin/check_admin_access.php';

// Vérifier l'accès admin ou employé (pas besoin d'être admin exclusivement)
check_admin_access(false);

// Récupérer les informations de l'utilisateur connecté
$current_user = get_current_admin_user();
$is_admin = $current_user['type'] === 'admin';
$is_employee = $current_user['type'] === 'employe';

// Définir le titre de la page et les styles spécifiques
$page_title = "Messages de Contact";
$additional_css = [
    'assets/css/admin-messages.css',
    'assets/css/admin-sidebar.css'
];

// Marquer un message comme lu/traité
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE Messages SET statut = 'Lu' WHERE MessageID = ?");
        $stmt->execute([$id]);
        $success = "Message marqué comme lu.";
    } elseif ($action === 'mark_processed') {
        $stmt = $pdo->prepare("UPDATE Messages SET statut = 'Traité' WHERE MessageID = ?");
        $stmt->execute([$id]);
        $success = "Message marqué comme traité.";
    } elseif ($action === 'delete') {
        // Seuls les admins peuvent supprimer des messages
        if ($is_admin) {
            $stmt = $pdo->prepare("DELETE FROM Messages WHERE MessageID = ?");
            $stmt->execute([$id]);
            $success = "Message supprimé avec succès.";
        } else {
            $error = "Seuls les administrateurs peuvent supprimer des messages.";
        }
    }
    
    // Redirection avec message si nécessaire
    if (isset($success)) {
        $_SESSION['admin_success'] = $success;
    } elseif (isset($error)) {
        $_SESSION['admin_error'] = $error;
    }
    
    header('Location: admin-messages.php');
    exit;
}

// Récupérer les messages
$messages = $pdo->query("
    SELECT MessageID, nom, email, objet, message, 
           DATE_FORMAT(date_creation, '%d/%m/%Y à %H:%i') as date_formatted,
           statut, date_creation
    FROM Messages 
    ORDER BY date_creation DESC
");

// Statistiques
$stats = [
    'total' => 0,
    'Nouveau' => 0,
    'Lu' => 0,
    'Traité' => 0
];

$stmt = $pdo->query("
    SELECT statut, COUNT(*) as count 
    FROM Messages 
    GROUP BY statut
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['total'] += $row['count'];
    $stats[$row['statut']] = $row['count'];
}

// Inclure le header admin harmonisé
require_once 'admin/header_template.php';
?>

<!-- Container avec classe spécifique pour les styles -->
<div class="admin-messages">

<!-- Contenu spécifique à la page Messages -->
<div class="row mb-4">
<div class="col-12">
<div class="card bg-primary text-white">
<div class="card-body text-center py-4">
<h1 class="display-6 mb-3">
<i class="bi bi-envelope me-3"></i><?php echo htmlspecialchars($page_title); ?>
</h1>
<p class="lead mb-0">Gestion des messages de contact reçus</p>
</div>
</div>
</div>
</div>

<!-- Messages de notification -->
<?php if (isset($_SESSION['admin_success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
<i class="bi bi-check-circle"></i> <?php echo $_SESSION['admin_success']; unset($_SESSION['admin_success']); ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
<i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['admin_error']; unset($_SESSION['admin_error']); ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<!-- Statistiques -->
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
.stat-card.info,
.stat-card.primary { 
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
</style>
<div class="stats-grid">
    <div class="stat-card primary">
        <i class="bi bi-envelope-open card-icon"></i>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Messages</div>
        <div class="stat-description">Messages reçus au total</div>
    </div>
    
    <div class="stat-card danger">
        <i class="bi bi-exclamation-circle card-icon"></i>
        <div class="stat-value"><?php echo $stats['Nouveau'] ?? 0; ?></div>
        <div class="stat-label">Nouveaux</div>
        <div class="stat-description">Messages non lus</div>
    </div>
    
    <div class="stat-card warning">
        <i class="bi bi-eye card-icon"></i>
        <div class="stat-value"><?php echo $stats['Lu'] ?? 0; ?></div>
        <div class="stat-label">Lus</div>
        <div class="stat-description">Messages consultés</div>
    </div>
    
    <div class="stat-card success">
        <i class="bi bi-check-circle card-icon"></i>
        <div class="stat-value"><?php echo $stats['Traité'] ?? 0; ?></div>
        <div class="stat-label">Traités</div>
        <div class="stat-description">Messages résolus</div>
    </div>
</div>
<!-- Liste des messages -->
<div class="card shadow-sm">
<div class="card-header bg-light">
<h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Messages Reçus</h5>
</div>
<div class="card-body">
<div class="row">
<?php while ($msg = $messages->fetch(PDO::FETCH_ASSOC)): ?>
<div class="col-12 mb-3">
<div class="card border-0 shadow-sm message-card status-<?php echo $msg['statut']; ?>">
<div class="card-body">
<div class="row">
<div class="col-md-8">
<div class="d-flex align-items-center mb-2">
<span class="badge bg-<?php echo $msg['statut'] === 'Nouveau' ? 'danger' : ($msg['statut'] === 'Lu' ? 'warning' : 'success'); ?> me-2">
<?php echo ucfirst($msg['statut']); ?>
</span>
<small class="text-muted"><?php echo $msg['date_formatted']; ?></small>
</div>
<h6 class="card-title mb-1">
<i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($msg['nom']); ?>
</h6>
<p class="text-muted small mb-2">
<i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($msg['email']); ?>
</p>
<h6 class="text-primary mb-2">
<i class="bi bi-chat-square-text me-1"></i> <?php echo htmlspecialchars($msg['objet']); ?>
</h6>
<p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
</div>
<div class="col-md-4 text-end">
<div class="btn-group-vertical d-grid gap-2">
<?php if ($msg['statut'] === 'Nouveau'): ?>
<a href="?action=mark_read&id=<?php echo $msg['MessageID']; ?>" 
                                                   class="btn btn-sm btn-outline-warning">
<i class="bi bi-eye me-1"></i> Marquer comme lu
                                                </a>
<?php endif; ?>
<?php if ($msg['statut'] !== 'Traité'): ?>
<a href="?action=mark_processed&id=<?php echo $msg['MessageID']; ?>" 
                                                   class="btn btn-sm btn-outline-success">
<i class="bi bi-check-circle me-1"></i> Marquer comme traité
                                                </a>
<?php endif; ?>
<a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo urlencode($msg['objet']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
<i class="bi bi-reply me-1"></i> Répondre
                                            </a>
<?php if ($is_admin): ?>
<a href="?action=delete&id=<?php echo $msg['MessageID']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
<i class="bi bi-trash me-1"></i> Supprimer
                                                </a>
<?php else: ?>
<span class="btn btn-sm btn-outline-secondary disabled" title="Seuls les administrateurs peuvent supprimer">
<i class="bi bi-trash me-1"></i> Supprimer
                                                </span>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>
<?php endwhile; ?>
<?php if ($stats['total'] === 0): ?>
<div class="col-12">
<div class="text-center py-5">
<i class="bi bi-inbox display-1 text-muted"></i>
<h4 class="text-muted mt-3">Aucun message reçu</h4>
<p class="text-muted">Les messages envoyés via le formulaire de contact apparaîtront ici.</p>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>

</div><!-- Fermeture du container admin-messages -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation de comptage pour les valeurs numériques
    function animateCountUp(element, start, end, duration) {
        let startTimestamp = null;
        
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            element.textContent = current;
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        
        window.requestAnimationFrame(step);
    }
    
    // Initialiser les animations de comptage
    const statValues = document.querySelectorAll('.admin-messages .card-value');
    statValues.forEach((value, index) => {
        const finalValue = parseInt(value.textContent) || 0;
        value.textContent = '0';
        
        // Démarrer l'animation avec un délai progressif
        setTimeout(() => {
            animateCountUp(value, 0, finalValue, 1500);
        }, 200 + (index * 150));
    });
    
    // Effets de survol améliorés
    const statsCards = document.querySelectorAll('.admin-messages .stats-card');
    statsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
            
            // Animation de l'icône
            const icon = this.querySelector('.card-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            
            // Réinitialiser l'icône
            const icon = this.querySelector('.card-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
        
        // Effet de clic
        card.addEventListener('click', function() {
            this.style.transform = 'translateY(-4px) scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            }, 150);
        });
    });
    
    // Animation de pulsation pour les nouveaux messages
    const nouveauxCard = document.querySelector('.admin-messages .stats-card.danger .card-value');
    if (nouveauxCard && parseInt(nouveauxCard.textContent) > 0) {
        nouveauxCard.parentElement.parentElement.classList.add('pulse');
    }
    
    // Rafraîchissement périodique des statistiques (simulation)
    setInterval(function() {
        // Ici vous pouvez ajouter un appel AJAX pour actualiser les stats
        console.log('Vérification des nouvelles statistiques...');
    }, 30000); // Toutes les 30 secondes
});
</script>

<?php
// Inclure le footer admin harmonisé
require_once 'admin/footer_template.php';
?>
