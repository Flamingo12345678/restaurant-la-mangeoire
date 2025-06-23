<?php
/**
 * Page de retour PayPal
 * Traite l'approbation du paiement PayPal
 */

// Supprimer les avertissements pour éviter les problèmes de headers
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);

session_start();
require_once '../includes/payment_manager.php';

try {
    $paymentManager = new PaymentManager();
    
    // Vérifier les paramètres PayPal
    $payment_id = $_GET['paymentId'] ?? '';
    $payer_id = $_GET['PayerID'] ?? '';
    
    if (!$payment_id || !$payer_id) {
        throw new Exception("Paramètres PayPal manquants");
    }
    
    // Exécuter le paiement PayPal
    $result = $paymentManager->executePayPalPayment($payment_id, $payer_id);
    
    if ($result['success']) {
        $_SESSION['message'] = "Paiement PayPal réussi ! Vous recevrez un email de confirmation.";
        $_SESSION['message_type'] = "success";
        $commande_id = $result['commande_id'] ?? '';
        $redirect_url = "../resultat-paiement.php?status=success&type=paypal&payment_id=" . $payment_id;
        if ($commande_id) {
            $redirect_url .= "&commande=" . $commande_id;
        }
        header('Location: ' . $redirect_url);
    } else {
        $_SESSION['message'] = "Erreur lors du paiement PayPal: " . $result['error'];
        $_SESSION['message_type'] = "error";
        $commande_id = $result['commande_id'] ?? '';
        $redirect_url = "../resultat-paiement.php?status=error&type=paypal";
        if ($commande_id) {
            $redirect_url .= "&commande=" . $commande_id;
        }
        header('Location: ' . $redirect_url);
    }
    
} catch (Exception $e) {
    error_log("PayPal Return Error: " . $e->getMessage());
    $_SESSION['message'] = "Erreur lors du traitement du paiement PayPal: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header('Location: ../resultat-paiement.php?status=error&type=paypal');
}
exit;
?>
