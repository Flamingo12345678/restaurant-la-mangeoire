<?php
// Panneau d'administration pour voir les messages de contact
// Accessible aux administrateurs et employés connectés
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connexion.php';
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
        $success = "Message marqué comme lu.";
    } elseif ($action === 'mark_processed') {
        $stmt = $conn->prepare("UPDATE Messages SET statut = 'traite' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Message marqué comme traité.";
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

// Récupérer les messages
$messages = $conn->query("
    SELECT id, nom, email, objet, message, 
           DATE_FORMAT(date_creation, '%d/%m/%Y à %H:%i') as date_formatted,
           statut, date_creation
    FROM Messages 
    ORDER BY date_creation DESC
");

// Statistiques
$stats = [
    'total' => 0,
    'nouveau' => 0,
    'lu' => 0,
    'traite' => 0
];

$stmt = $conn->query("
    SELECT statut, COUNT(*) as count 
    FROM Messages 
    GROUP BY statut
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['total'] += $row['count'];
    $stats[$row['statut']] = $row['count'];
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

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <i class="bi bi-envelope-open display-4 text-primary"></i>
                        <h3 class="mt-2"><?php echo $stats['total']; ?></h3>
                        <p class="text-muted mb-0">Total Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <i class="bi bi-exclamation-circle display-4 text-danger"></i>
                        <h3 class="mt-2"><?php echo $stats['nouveau'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Nouveaux</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <i class="bi bi-eye display-4 text-warning"></i>
                        <h3 class="mt-2"><?php echo $stats['lu'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Lus</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="bi bi-check-circle display-4 text-success"></i>
                        <h3 class="mt-2"><?php echo $stats['traite'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Traités</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des messages -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Messages Reçus</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while ($msg = $messages->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="col-12 mb-3">
                        <div class="card message-card status-<?php echo $msg['statut']; ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-<?php echo $msg['statut'] === 'nouveau' ? 'danger' : ($msg['statut'] === 'lu' ? 'warning' : 'success'); ?> me-2">
                                                <?php echo ucfirst($msg['statut']); ?>
                                            </span>
                                            <small class="text-muted"><?php echo $msg['date_formatted']; ?></small>
                                        </div>
                                        
                                        <h6 class="card-title mb-1">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($msg['nom']); ?>
                                        </h6>
                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?>
                                        </p>
                                        <h6 class="text-primary mb-2">
                                            <i class="bi bi-chat-square-text"></i> <?php echo htmlspecialchars($msg['objet']); ?>
                                        </h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    </div>
                                    
                                    <div class="col-md-4 text-end">
                                        <div class="btn-group-vertical d-grid gap-2">
                                            <?php if ($msg['statut'] === 'nouveau'): ?>
                                                <a href="?action=mark_read&id=<?php echo $msg['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-eye"></i> Marquer comme lu
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($msg['statut'] !== 'traite'): ?>
                                                <a href="?action=mark_processed&id=<?php echo $msg['id']; ?>" 
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
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($stats['total'] === 0): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <h4 class="text-muted">Aucun message reçu</h4>
                                <p class="text-muted">Les messages envoyés via le formulaire de contact apparaîtront ici.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
