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
$additional_css = ['assets/css/admin-messages.css'];

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
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-center border-0 shadow-sm stats-card">
                    <div class="card-body">
                        <i class="bi bi-envelope-open display-4 text-primary mb-2"></i>
                        <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                        <p class="text-muted mb-0">Total Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-0 shadow-sm stats-card">
                    <div class="card-body">
                        <i class="bi bi-exclamation-circle display-4 text-danger mb-2"></i>
                        <h3 class="text-danger"><?php echo $stats['Nouveau'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Nouveaux</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-0 shadow-sm stats-card">
                    <div class="card-body">
                        <i class="bi bi-eye display-4 text-warning mb-2"></i>
                        <h3 class="text-warning"><?php echo $stats['Lu'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Lus</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-0 shadow-sm stats-card">
                    <div class="card-body">
                        <i class="bi bi-check-circle display-4 text-success mb-2"></i>
                        <h3 class="text-success"><?php echo $stats['Traité'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Traités</p>
                    </div>
                </div>
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

<?php
// Inclure le footer admin harmonisé
require_once 'admin/footer_template.php';
?>
