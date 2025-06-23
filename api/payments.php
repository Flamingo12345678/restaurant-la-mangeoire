<?php
/**
 * API pour traiter les paiements Stripe et PayPal
 * Restaurant La Mangeoire - Version APIs complètes
 */

// Supprimer les avertissements deprecated du SDK PayPal
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

session_start();
require_once '../includes/payment_manager.php';
require_once '../db_connexion.php';

header('Content-Type: application/json');

try {
    $paymentManager = new PaymentManager();
    
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST; // Fallback pour les formulaires classiques
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'stripe_payment':
            $result = $paymentManager->processStripePayment([
                'montant' => floatval($input['montant']),
                'payment_method_id' => $input['payment_method_id'],
                'commande_id' => $input['commande_id'] ?? null,
                'reservation_id' => $input['reservation_id'] ?? null,
                'client_id' => $input['client_id'] ?? null
            ]);
            break;
            
        case 'create_paypal_payment':
            $return_url = $input['return_url'] ?? (
                (isset($_SERVER['HTTPS']) ? 'https' : 'http') . 
                '://' . $_SERVER['HTTP_HOST'] . 
                '/api/payments/paypal_return.php'
            );
            $cancel_url = $input['cancel_url'] ?? (
                (isset($_SERVER['HTTPS']) ? 'https' : 'http') . 
                '://' . $_SERVER['HTTP_HOST'] . 
                '/paiement.php?status=cancelled'
            );
            
            $result = $paymentManager->createPayPalPayment([
                'montant' => floatval($input['montant']),
                'commande_id' => $input['commande_id'] ?? null,
                'reservation_id' => $input['reservation_id'] ?? null,
                'client_id' => $input['client_id'] ?? null
            ], $return_url, $cancel_url);
            break;
            
        case 'execute_paypal_payment':
            $result = $paymentManager->executePayPalPayment(
                $input['payment_id'],
                $input['payer_id']
            );
            break;
            
        case 'process_wire_transfer':
            $result = ['success' => false, 'error' => 'Méthode de paiement non supportée'];
            break;
            
        case 'get_public_keys':
            $result = [
                'success' => true,
                'keys' => $paymentManager->getPublicKeys()
            ];
            break;
            
        case 'get_api_status':
            $result = [
                'success' => true,
                'status' => $paymentManager->getApiStatus()
            ];
            break;
            
        default:
            throw new Exception('Action non reconnue: ' . $action);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Payment API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
