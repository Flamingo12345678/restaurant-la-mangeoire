<?php
// Panneau d'administration pour voir les messages de contact
// Accessible aux administrateurs et employés connectés
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_                                    <a href="?action=mark_read&id=<?php echo $msg['id']; ?>" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-eye"></i> Marquer comme lu
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($msg['statut'] !== 'traite'): ?>
                                    <a href="?action=mark_processed&id=<?php echo $msg['id']; ?>" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Marquer comme traité
                                    </a>hp';
require_once 'admin/check_admin_access.php';

// Vérifier l'accès admin ou employé (pas besoin d'être admin exclusivement)
check_admin_access(false);

// Récupérer les informations de l'utilisateur connecté
$current_user = get_current_admin_user();
$is_admin = $current_user['type'] === 'admin';
$is_employee = $current_user['type'] === 'employe';

// Marquer un message comme lu/traité
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'mark_read') {
        $stmt = $conn->prepare("UPDATE Messages SET statut = 'lu' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'mark_processed') {
        $stmt = $conn->prepare("UPDATE Messages SET statut = 'traite' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'delete') {
        // Seuls les admins peuvent supprimer des messages
        if ($is_admin) {
            $stmt = $conn->prepare("DELETE FROM Messages WHERE id = ?");
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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Messages de Contact</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .message-card {
            border-left: 4px solid #ce1212;
            transition: all 0.3s ease;
        }
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-nouveau { border-left-color: #dc3545; }
        .status-lu { border-left-color: #ffc107; }
        .status-traite { border-left-color: #28a745; }
        
        .header-admin {
            background: linear-gradient(135deg, #ce1212 0%, #e74c3c 100%);
            color: white;
            padding: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="header-admin">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="bi bi-envelope"></i> Messages de Contact</h1>
                    <p class="mb-0">Administration - La Mangeoire</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="me-3">
                            <small>Connecté en tant que :</small><br>
                            <strong><?php echo htmlspecialchars($current_user['prenom'] . ' ' . $current_user['nom']); ?></strong>
                            <br><span class="badge bg-light text-dark"><?php echo $is_admin ? 'Administrateur' : 'Employé'; ?></span>
                        </div>
                        <div>
                            <a href="admin/index.php" class="btn btn-light btn-sm me-2">
                                <i class="bi bi-house"></i> Tableau de bord
                            </a>
                            <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
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
            <?php
            // Statistiques
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'Nouveau' THEN 1 ELSE 0 END) as nouveaux,
                    SUM(CASE WHEN statut = 'Lu' THEN 1 ELSE 0 END) as lus,
                    SUM(CASE WHEN statut = 'Traité' THEN 1 ELSE 0 END) as traites
                FROM Messages
            ")->fetch();
            ?>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p class="mb-0">Total Messages</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['nouveaux']; ?></h3>
                            <p class="mb-0">Nouveaux</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['lus']; ?></h3>
                            <p class="mb-0">Lus</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['traites']; ?></h3>
                            <p class="mb-0">Traités</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des messages -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list"></i> Tous les Messages</h5>
                </div>
                <div class="card-body">
                    <?php
                    $messages = $conn->query("
                        SELECT * FROM Messages 
                        ORDER BY date_creation DESC
                    ");
                    
                    while ($msg = $messages->fetch()):
                    ?>
                    <div class="message-card card mb-3 status-<?php echo strtolower($msg['statut']); ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($msg['nom']); ?></strong>
                                <span class="text-muted">
                                    &lt;<?php echo htmlspecialchars($msg['email']); ?>&gt;
                                </span>
                                <span class="badge bg-<?php 
                                    echo $msg['statut'] === 'Nouveau' ? 'danger' : 
                                        ($msg['statut'] === 'Lu' ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo $msg['statut']; ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($msg['date_creation'])); ?>
                            </small>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($msg['objet']); ?></h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                            
                            <div class="btn-group" role="group">
                                <?php if ($msg['statut'] === 'Nouveau'): ?>
                                    <a href="?action=mark_read&id=<?php echo $msg['MessageID']; ?>" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-eye"></i> Marquer comme lu
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($msg['statut'] !== 'Traité'): ?>
                                    <a href="?action=mark_processed&id=<?php echo $msg['MessageID']; ?>" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Marquer comme traité
                                    </a>
                                <?php endif; ?>
                                
                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo urlencode($msg['objet']); ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-reply"></i> Répondre
                                </a>
                                
                                <?php if ($is_admin): ?>
                                    <a href="?action=delete&id=<?php echo $msg['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                        <i class="bi bi-trash"></i> Supprimer
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-outline-secondary disabled" title="Seuls les administrateurs peuvent supprimer">
                                        <i class="bi bi-trash"></i> Supprimer
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($stats['total'] === 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4 class="text-muted">Aucun message reçu</h4>
                            <p class="text-muted">Les messages envoyés via le formulaire de contact apparaîtront ici.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
