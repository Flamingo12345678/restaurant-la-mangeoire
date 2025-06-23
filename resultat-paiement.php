<?php
session_start();
require_once 'db_connexion.php';

// Récupérer les paramètres de l'URL
$payment_status = $_GET['status'] ?? 'success';
$payment_type = $_GET['type'] ?? 'card';
$commande_id = $_GET['commande'] ?? null;
$payment_id = $_GET['payment_id'] ?? null;

// Informations par défaut
$commande = null;
$paiement = null;
$message_title = "Confirmation de Paiement";
$message_text = "Votre paiement a été traité.";
$message_type = "info";
$message_icon = "bi-info-circle";

// Récupérer les informations de commande si disponible
if ($commande_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cl.Nom, cl.Prenom, cl.Email, cl.Telephone
            FROM Commandes c
            LEFT JOIN Clients cl ON c.ClientID = cl.ClientID
            WHERE c.CommandeID = ?
        ");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Récupérer les informations de paiement
        if ($commande) {
            $stmt = $pdo->prepare("
                SELECT * FROM Paiements 
                WHERE CommandeID = ? 
                ORDER BY DatePaiement DESC 
                LIMIT 1
            ");
            $stmt->execute([$commande_id]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Erreur récupération commande: " . $e->getMessage());
    }
}

// Définir le message selon le statut
switch ($payment_status) {
    case 'success':
        $message_title = "🎉 Paiement Réussi !";
        $message_text = "Votre paiement a été confirmé avec succès. Vous recevrez un email de confirmation sous peu.";
        $message_type = "success";
        $message_icon = "bi-check-circle";
        break;
        
    case 'pending':
        $message_title = "⏳ Paiement en Attente";
        $message_text = "Votre paiement est en cours de traitement. Vous recevrez une confirmation dès validation.";
        $message_type = "warning";
        $message_icon = "bi-clock";
        break;
        
    case 'cancelled':
        $message_title = "❌ Paiement Annulé";
        $message_text = "Votre paiement a été annulé. Vous pouvez réessayer ou choisir un autre mode de paiement.";
        $message_type = "info";
        $message_icon = "bi-x-circle";
        break;
        
    case 'error':
    default:
        $message_title = "❌ Erreur de Paiement";
        $message_text = "Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer ou contacter le support.";
        $message_type = "danger";
        $message_icon = "bi-exclamation-triangle";
        break;
}

// Type de paiement pour l'affichage
$payment_method = match($payment_type) {
    'stripe' => 'Carte Bancaire (Stripe)',
    'paypal' => 'PayPal',
    'virement' => 'Virement Bancaire',
    default => 'Paiement en ligne'
};
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Paiement - La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .confirmation-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .confirmation-header {
            padding: 40px 30px 20px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .confirmation-body {
            padding: 30px;
        }
        
        .info-group {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 1.1rem;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .status-success { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-danger { color: #dc3545; }
        .status-info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <!-- Header -->
            <div class="confirmation-header">
                <div class="confirmation-icon status-<?php echo $message_type; ?>">
                    <i class="<?php echo $message_icon; ?>"></i>
                </div>
                <h2 class="mb-0"><?php echo $message_title; ?></h2>
            </div>
            
            <!-- Body -->
            <div class="confirmation-body">
                <div class="alert alert-<?php echo $message_type; ?> border-0 shadow-sm">
                    <p class="mb-0 fw-medium"><?php echo $message_text; ?></p>
                </div>
                
                <?php if ($commande): ?>
                <div class="info-group">
                    <h5 class="mb-3"><i class="bi bi-receipt"></i> Détails de la Commande</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Numéro de Commande</div>
                            <div class="info-value">#<?php echo $commande['CommandeID']; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Mode de Paiement</div>
                            <div class="info-value"><?php echo $payment_method; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Montant Total</div>
                            <div class="info-value fw-bold text-primary"><?php echo number_format($commande['MontantTotal'], 2); ?> €</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Date</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($commande['DateCommande'])); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($commande['Nom']): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="info-label">Client</div>
                        <div class="info-value"><?php echo htmlspecialchars($commande['Prenom'] . ' ' . $commande['Nom']); ?></div>
                        <?php if ($commande['Email']): ?>
                        <div class="info-label mt-2">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($commande['Email']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($paiement): ?>
                <div class="info-group">
                    <h5 class="mb-3"><i class="bi bi-credit-card"></i> Informations de Paiement</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">ID de Transaction</div>
                            <div class="info-value font-monospace"><?php echo $paiement['TransactionID'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Statut</div>
                            <div class="info-value">
                                <?php 
                                $statut_badge = match($paiement['Statut']) {
                                    'Confirme' => '<span class="badge bg-success">Confirmé</span>',
                                    'En_attente' => '<span class="badge bg-warning">En attente</span>',
                                    'Echoue' => '<span class="badge bg-danger">Échoué</span>',
                                    default => '<span class="badge bg-secondary">' . $paiement['Statut'] . '</span>'
                                };
                                echo $statut_badge;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="text-center mt-4">
                    <?php if ($payment_status === 'success'): ?>
                        <a href="index.php" class="btn btn-home btn-lg me-3">
                            <i class="bi bi-house"></i> Retour à l'Accueil
                        </a>
                        <?php if ($commande): ?>
                        <a href="mon-compte.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-person"></i> Mes Commandes
                        </a>
                        <?php endif; ?>
                    <?php elseif ($payment_status === 'cancelled' || $payment_status === 'error'): ?>
                        <?php if ($commande_id): ?>
                        <a href="confirmation-commande.php?id=<?php echo $commande_id; ?>" class="btn btn-primary btn-lg me-3">
                            <i class="bi bi-arrow-clockwise"></i> Réessayer le Paiement
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-house"></i> Retour à l'Accueil
                        </a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-home btn-lg">
                            <i class="bi bi-house"></i> Retour à l'Accueil
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($payment_status === 'success'): ?>
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="text-center mb-2"><i class="bi bi-info-circle"></i> Prochaines Étapes</h6>
                    <ul class="list-unstyled mb-0 small text-center">
                        <li>📧 Un email de confirmation va vous être envoyé</li>
                        <li>🍽️ Votre commande sera préparée dans les meilleurs délais</li>
                        <li>🚚 Vous serez notifié lors de la livraison/préparation</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-redirection après succès (optionnel) -->
    <?php if ($payment_status === 'success' && !$commande_id): ?>
    <script>
        setTimeout(function() {
            if (confirm('Souhaitez-vous être redirigé vers l\'accueil ?')) {
                window.location.href = 'index.php';
            }
        }, 10000); // 10 secondes
    </script>
    <?php endif; ?>
</body>
</html>
