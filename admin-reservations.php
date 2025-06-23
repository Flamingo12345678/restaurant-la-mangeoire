<?php
// Gestion des réservations - Panel Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connexion.php';
require_once 'includes/common.php';
require_once 'includes/email_notifications.php';
require_once 'admin/check_admin_access.php';

// Vérifier l'accès admin ou employé (pas besoin d'être admin exclusivement)
check_admin_access(false);

// Obtenir les informations utilisateur
$current_user = get_current_admin_user();
$is_admin = $current_user['type'] === 'admin';

$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reservation_id = (int)($_POST['reservation_id'] ?? 0);
    
    if ($action === 'update_status' && $reservation_id > 0) {
        $nouveau_statut = $_POST['nouveau_statut'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
            $result = $stmt->execute([$nouveau_statut, $reservation_id]);
            
            if ($result) {
                $message = "Statut de la réservation mis à jour avec succès.";
                
                // Optionnel : Envoyer un email de confirmation au client
                if ($nouveau_statut === 'Confirmée') {
                    // Récupérer les détails de la réservation
                    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
                    $stmt->execute([$reservation_id]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($reservation) {
                        // Ici on pourrait envoyer un email de confirmation au client
                        // Pour l'instant on log juste l'action
                        error_log("Réservation #{$reservation_id} confirmée pour {$reservation['email']}");
                    }
                }
            } else {
                $error = "Erreur lors de la mise à jour du statut.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $reservation_id > 0 && $is_admin) {
        try {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            $result = $stmt->execute([$reservation_id]);
            
            if ($result) {
                $message = "Réservation supprimée avec succès.";
            } else {
                $error = "Erreur lors de la suppression.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupérer les réservations
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM reservations WHERE 1=1";
$params = [];

if ($filter_status !== 'all') {
    $sql .= " AND statut = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $sql .= " AND (nom LIKE ? OR email LIKE ? OR telephone LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY date_reservation DESC, heure_reservation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats_sql = "SELECT 
    statut, 
    COUNT(*) as count,
    DATE(date_reservation) as date_res
FROM reservations 
GROUP BY statut, DATE(date_reservation)
ORDER BY date_res DESC";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - Restaurant La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.8em;
        }
        .status-en-attente { background-color: #ffc107; }
        .status-confirmee { background-color: #198754; }
        .status-annulee { background-color: #dc3545; }
        .status-terminee { background-color: #6c757d; }
        
        .table-responsive {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .stats-card {
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Administration</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin-messages.php">
                                <i class="bi bi-envelope"></i> Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin-reservations.php">
                                <i class="bi bi-calendar-check"></i> Réservations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="commandes.php">
                                <i class="bi bi-cart"></i> Commandes
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-house"></i> Retour au site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="deconnexion.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-calendar-check"></i> Gestion des Réservations
                    </h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtres et recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Filtrer par statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Tous</option>
                                    <option value="En attente" <?php echo $filter_status === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="Confirmée" <?php echo $filter_status === 'Confirmée' ? 'selected' : ''; ?>>Confirmée</option>
                                    <option value="Annulée" <?php echo $filter_status === 'Annulée' ? 'selected' : ''; ?>>Annulée</option>
                                    <option value="Terminée" <?php echo $filter_status === 'Terminée' ? 'selected' : ''; ?>>Terminée</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Rechercher</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Nom, email ou téléphone...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bi bi-search"></i> Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total</h5>
                                <h3 class="text-primary"><?php echo count($reservations); ?></h3>
                            </div>
                        </div>
                    </div>
                    <!-- Ajoutez d'autres stats si nécessaire -->
                </div>

                <!-- Liste des réservations -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list"></i> Liste des Réservations
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reservations)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                <h4 class="text-muted">Aucune réservation trouvée</h4>
                                <p class="text-muted">Aucune réservation ne correspond à vos critères de recherche.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Contact</th>
                                            <th>Date/Heure</th>
                                            <th>Personnes</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservations as $reservation): ?>
                                            <tr>
                                                <td>#<?php echo $reservation['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($reservation['nom']); ?></strong>
                                                    <?php if (!empty($reservation['message'])): ?>
                                                        <br><small class="text-muted">
                                                            <i class="bi bi-chat-text"></i> 
                                                            <?php echo htmlspecialchars(substr($reservation['message'], 0, 50)); ?>
                                                            <?php echo strlen($reservation['message']) > 50 ? '...' : ''; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($reservation['email']); ?><br>
                                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($reservation['telephone']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($reservation['heure_reservation'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $reservation['nombre_personnes']; ?> pers.</span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($reservation['statut']) {
                                                        case 'En attente': $status_class = 'bg-warning'; break;
                                                        case 'Confirmée': $status_class = 'bg-success'; break;
                                                        case 'Annulée': $status_class = 'bg-danger'; break;
                                                        case 'Terminée': $status_class = 'bg-secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($reservation['statut']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- Changer statut -->
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#statusModal<?php echo $reservation['id']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        
                                                        <?php if ($is_admin): ?>
                                                            <!-- Supprimer -->
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')) { document.getElementById('deleteForm<?php echo $reservation['id']; ?>').submit(); }">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Modal pour changer le statut -->
                                                    <div class="modal fade" id="statusModal<?php echo $reservation['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Modifier le statut</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Réservation de <?php echo htmlspecialchars($reservation['nom']); ?></label>
                                                                            <p class="text-muted small">
                                                                                <?php echo date('d/m/Y à H:i', strtotime($reservation['date_reservation'] . ' ' . $reservation['heure_reservation'])); ?>
                                                                                - <?php echo $reservation['nombre_personnes']; ?> personne(s)
                                                                            </p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="nouveau_statut<?php echo $reservation['id']; ?>" class="form-label">Nouveau statut</label>
                                                                            <select class="form-select" name="nouveau_statut" id="nouveau_statut<?php echo $reservation['id']; ?>" required>
                                                                                <option value="En attente" <?php echo $reservation['statut'] === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                                                                                <option value="Confirmée" <?php echo $reservation['statut'] === 'Confirmée' ? 'selected' : ''; ?>>Confirmée</option>
                                                                                <option value="Annulée" <?php echo $reservation['statut'] === 'Annulée' ? 'selected' : ''; ?>>Annulée</option>
                                                                                <option value="Terminée" <?php echo $reservation['statut'] === 'Terminée' ? 'selected' : ''; ?>>Terminée</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php if ($is_admin): ?>
                                                        <!-- Formulaire de suppression caché -->
                                                        <form id="deleteForm<?php echo $reservation['id']; ?>" method="POST" style="display: none;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
