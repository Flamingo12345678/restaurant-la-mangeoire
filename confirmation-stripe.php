<?php
/**
 * Page de confirmation de paiement Stripe - Restaurant La Mangeoire
 * Cette page traite les retours de Stripe après un paiement
 */

require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/stripe-config.php';
require_once 'db_connexion.php';

$message = '';
$error = '';
$status = $_GET['status'] ?? '';
$session_id = $_GET['session_id'] ?? '';

if ($status === 'success' && $session_id) {
    try {
        // Récupérer les détails de la session de paiement
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        
        if ($session->payment_status === 'paid') {
            // Récupérer les détails du paiement
            $payment_intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
            
            // Enregistrer le paiement dans la base de données
            $montant = $session->amount_total / 100; // Convertir de centimes en euros
            $method_paiement = 'Stripe';
            $transaction_id = $payment_intent->id;
            
            // Déterminer si c'est une commande ou une réservation
            $commande_id = $_SESSION['commande_id'] ?? null;
            $reservation_id = $_SESSION['reservation_id'] ?? null;
            
            if ($commande_id) {
                // Enregistrer le paiement pour une commande
                $sql = "INSERT INTO Paiements (CommandeID, Montant, MethodePaiement, NumeroTransaction, DatePaiement) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$commande_id, $montant, $method_paiement, $transaction_id]);
                
                // Mettre à jour le statut de la commande
                $sql_update = "UPDATE Commandes SET Statut = 'Payé', DatePaiement = NOW() WHERE CommandeID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([$commande_id]);
                
                $message = "✅ Paiement réussi ! Votre commande #$commande_id a été confirmée.";
                
                // Nettoyer la session
                unset($_SESSION['commande_id']);
                
            } elseif ($reservation_id) {
                // Enregistrer le paiement pour une réservation
                $sql = "INSERT INTO Paiements (ReservationID, Montant, MethodePaiement, NumeroTransaction, DatePaiement) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$reservation_id, $montant, $method_paiement, $transaction_id]);
                
                // Mettre à jour le statut de la réservation
                $sql_update = "UPDATE Reservations SET Statut = 'Confirmé' WHERE ReservationID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([$reservation_id]);
                
                $message = "✅ Paiement réussi ! Votre réservation #$reservation_id a été confirmée.";
                
                // Nettoyer la session
                unset($_SESSION['reservation_id']);
            } else {
                $message = "✅ Paiement réussi ! Montant: " . number_format($montant, 2) . " €";
            }
            
            // Détails du paiement pour affichage
            $payment_details = [
                'montant' => number_format($montant, 2) . ' €',
                'methode' => $method_paiement,
                'transaction_id' => $transaction_id,
                'date' => date('d/m/Y H:i:s'),
                'statut' => 'Confirmé'
            ];
            
        } else {
            $error = "❌ Le paiement n'a pas été finalisé. Statut: " . $session->payment_status;
        }
        
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        $error = "❌ Session de paiement invalide: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "❌ Erreur lors de la vérification du paiement: " . $e->getMessage();
        error_log("Erreur Stripe confirmation: " . $e->getMessage());
    }
    
} elseif ($status === 'cancel') {
    $error = "❌ Paiement annulé. Vous pouvez réessayer quand vous le souhaitez.";
} else {
    $error = "❌ Paramètres de confirmation invalides.";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Paiement - Restaurant La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Confirmation de Paiement</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <h4><?= $message ?></h4>
                            </div>
                            
                            <?php if (isset($payment_details)): ?>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5>Détails du paiement :</h5>
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Montant payé:</strong></td>
                                                <td><?= $payment_details['montant'] ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Méthode de paiement:</strong></td>
                                                <td><?= $payment_details['methode'] ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Numéro de transaction:</strong></td>
                                                <td><code><?= $payment_details['transaction_id'] ?></code></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Date et heure:</strong></td>
                                                <td><?= $payment_details['date'] ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Statut:</strong></td>
                                                <td><span class="badge bg-success"><?= $payment_details['statut'] ?></span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-primary me-2">Retour à l'accueil</a>
                                <a href="mon-compte.php" class="btn btn-outline-primary">Mon compte</a>
                            </div>
                            
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                <h4><?= $error ?></h4>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="panier.php" class="btn btn-warning me-2">Retour au panier</a>
                                <a href="index.php" class="btn btn-outline-primary">Retour à l'accueil</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
