<?php
// Ce script corrige les problèmes liés aux paiements sans clients identifiés
// et permet de gérer les paiements orphelins

require_once 'check_admin_access.php';
require_once __DIR__ . '/../includes/common.php';
require_admin();
require_once '../db_connexion.php';

$message = '';

// Action: nettoyer les paiements
if (isset($_POST['action']) && $_POST['action'] === 'fix_payments' && isset($_POST['csrf_token'])) {
    if (!check_csrf_token($_POST['csrf_token'])) {
        $message = 'Erreur de sécurité (CSRF).';
    } else {
        try {
            // 1. Marquer les paiements qui n'ont pas de réservation valide
            $paiements_problematiques = $pdo->query(
                "SELECT p.* 
                 FROM Paiements p 
                 LEFT JOIN Reservations r ON p.ReservationID = r.ReservationID 
                 WHERE r.ReservationID IS NULL OR p.ReservationID IS NULL OR p.ReservationID = ''"
            )->fetchAll(PDO::FETCH_ASSOC);
            
            $count_problematiques = count($paiements_problematiques);
            
            if ($count_problematiques > 0 && isset($_POST['fix_type'])) {
                if ($_POST['fix_type'] === 'fix_associate') {
                    // Option 1: Associer à une réservation générique
                    $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
                    
                    if ($reservation_id > 0) {
                        // Vérifier si la réservation existe
                        $check_res = $pdo->prepare("SELECT ReservationID FROM Reservations WHERE ReservationID = ?");
                        $check_res->execute([$reservation_id]);
                        
                        if ($check_res->fetchColumn()) {
                            // Mettre à jour tous les paiements problématiques
                            $update = $pdo->prepare("UPDATE Paiements SET ReservationID = ? WHERE PaiementID = ?");
                            $success_count = 0;
                            
                            foreach ($paiements_problematiques as $paiement) {
                                if ($update->execute([$reservation_id, $paiement['PaiementID']])) {
                                    $success_count++;
                                }
                            }
                            
                            $message = "Correction effectuée : $success_count paiements ont été associés à la réservation #$reservation_id.";
                        } else {
                            $message = "La réservation #$reservation_id n'existe pas.";
                        }
                    } else {
                        $message = "Veuillez spécifier un ID de réservation valide.";
                    }
                } elseif ($_POST['fix_type'] === 'delete') {
                    // Option 2: Supprimer les paiements problématiques
                    $delete = $pdo->prepare("DELETE FROM Paiements WHERE PaiementID = ?");
                    $success_count = 0;
                    
                    foreach ($paiements_problematiques as $paiement) {
                        if ($delete->execute([$paiement['PaiementID']])) {
                            $success_count++;
                        }
                    }
                    
                    $message = "Suppression effectuée : $success_count paiements problématiques ont été supprimés.";
                }
            } else {
                $message = "Aucun paiement problématique trouvé ou aucune action sélectionnée.";
            }
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupérer la liste des paiements problématiques
$paiements_problematiques = [];
try {
    $query = $pdo->query(
        "SELECT p.*, 
         r.nom_client, r.email_client 
         FROM Paiements p 
         LEFT JOIN Reservations r ON p.ReservationID = r.ReservationID 
         WHERE r.ReservationID IS NULL OR p.ReservationID IS NULL OR p.ReservationID = ''"
    );
    $paiements_problematiques = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des paiements problématiques : " . $e->getMessage();
}

// Récupérer les réservations récentes pour suggestion
$reservations_recentes = [];
try {
    $query = $pdo->query(
        "SELECT r.ReservationID, r.nom_client, r.DateReservation  
         FROM Reservations r 
         ORDER BY r.DateReservation DESC 
         LIMIT 10"
    );
    $reservations_recentes = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silencieux
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Correction des paiements - Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-animations.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .problem-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .problem-table th, 
        .problem-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        
        .problem-table th {
            background-color: #f5f5f5;
        }
        
        .problem-row:hover {
            background-color: #f9f9f9;
        }
        
        .fix-options {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #e1e1e1;
        }
        
        .option-group {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .reservation-suggestion {
            cursor: pointer;
            padding: 5px 10px;
            margin: 5px;
            display: inline-block;
            background-color: #f5f5f5;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .reservation-suggestion:hover {
            background-color: #e9e9e9;
        }
    </style>
</head>
<body>
    <?php
    // Définir le titre de la page
    $page_title = "Correction des paiements";
    
    // Indiquer que ce fichier est inclus dans une page
    define('INCLUDED_IN_PAGE', true);
    include 'header_template.php';
    ?>
    
    <div class="content-wrapper">
        <div style="background-color: #f9f9f9; border-radius: 5px; margin-bottom: 20px;">
            <h2 style="color: #222; font-size: 23px; margin-bottom: 20px; position: relative;">
                <i class="bi bi-tools"></i> Correction des paiements problématiques
            </h2>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos(strtolower($message), 'erreur') !== false ? 'alert-danger' : 'alert-success'; ?>">
                <i class="bi <?php echo strpos(strtolower($message), 'erreur') !== false ? 'bi-exclamation-triangle' : 'bi-check-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Paiements sans réservation valide</h3>
                <p>Cette page vous permet de corriger les paiements qui n'ont pas de réservation valide associée.</p>
            </div>
            
            <div class="admin-card-body">
                <?php if (empty($paiements_problematiques)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Aucun paiement problématique trouvé. Tout est en ordre !
                    </div>
                <?php else: ?>
                    <p>Les <?php echo count($paiements_problematiques); ?> paiements suivants n'ont pas de réservation valide associée :</p>
                    
                    <div class="table-responsive">
                        <table class="problem-table">
                            <thead>
                                <tr>
                                    <th>ID Paiement</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th>Transaction</th>
                                    <th>ID Réservation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paiements_problematiques as $p): ?>
                                    <tr class="problem-row">
                                        <td><?php echo htmlspecialchars($p['PaiementID'] ?? ''); ?></td>
                                        <td><strong><?php echo number_format((float)($p['Montant'] ?? 0), 2, ',', ' '); ?> €</strong></td>
                                        <td><?php echo isset($p['DatePaiement']) ? date('d/m/Y', strtotime($p['DatePaiement'])) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($p['ModePaiement'] ?? 'Non spécifié'); ?></td>
                                        <td><?php echo htmlspecialchars($p['TransactionID'] ?? '-'); ?></td>
                                        <td><?php echo empty($p['ReservationID']) ? '<span style="color:red;">-</span>' : '<span style="color:red; text-decoration:line-through;">' . htmlspecialchars($p['ReservationID']) . '</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="fix-options">
                        <h4><i class="bi bi-wrench"></i> Options de correction</h4>
                        
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="fix_payments">
                            
                            <div class="option-group">
                                <label>
                                    <input type="radio" name="fix_type" value="fix_associate" checked>
                                    Associer tous ces paiements à une réservation existante
                                </label>
                                <div style="margin-top: 10px; margin-left: 25px;">
                                    <label for="reservation_id">ID de la réservation :</label>
                                    <input type="number" id="reservation_id" name="reservation_id" required min="1" style="width: 100px;">
                                    
                                    <?php if (!empty($reservations_recentes)): ?>
                                        <div style="margin-top: 8px;">
                                            <label>Suggestions de réservations récentes :</label>
                                            <div style="margin-top: 5px;">
                                                <?php foreach ($reservations_recentes as $res): ?>
                                                    <span class="reservation-suggestion" onclick="document.getElementById('reservation_id').value='<?php echo $res['ReservationID']; ?>'">
                                                        #<?php echo $res['ReservationID']; ?> - 
                                                        <?php echo htmlspecialchars($res['nom_client'] ?? 'Client non identifié'); ?> 
                                                        (<?php echo date('d/m/Y', strtotime($res['DateReservation'])); ?>)
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="option-group">
                                <label>
                                    <input type="radio" name="fix_type" value="delete">
                                    Supprimer tous ces paiements problématiques
                                </label>
                                <div style="margin-top: 5px; margin-left: 25px; color: #e74c3c;">
                                    <i class="bi bi-exclamation-triangle"></i> Attention : Cette action est irréversible !
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">Appliquer la correction</button>
                                <a href="paiements.php" class="btn btn-secondary" style="margin-left: 10px;">Retour aux paiements</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'footer_template.php'; ?>
</body>
</html>
