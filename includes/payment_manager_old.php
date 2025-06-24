<?php
/**
 * Gestionnaire de paiements avec notifications emails automatiques
 * Intègre Stripe, PayPal et les emails de confirmation
 * Version complète avec vraies APIs
 */

require_once __DIR__ . '/email_manager.php';
require_once __DIR__ . '/../db_connexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;

class PaymentManager {
    private $emailManager;
    private $pdo;
    private $stripe_secret_key;
    private $stripe_publishable_key;
    private $paypal_client_id;
    private $paypal_secret_key;
    private $paypal_mode;
    private $paypal_api_context;
    
    public function __construct() {
        $this->emailManager = new EmailManager();
        global $pdo;
        $this->pdo = $pdo;
        
        // Charger les clés API depuis .env
        $this->loadPaymentConfig();
        $this->initializePaymentGateways();
    }
    
    /**
     * Charge la configuration des paiements depuis .env
     */
    private function loadPaymentConfig() {
        $this->stripe_secret_key = getEnvVar('STRIPE_SECRET_KEY');
        $this->stripe_publishable_key = getEnvVar('STRIPE_PUBLISHABLE_KEY');
        $this->paypal_client_id = getEnvVar('PAYPAL_CLIENT_ID');
        $this->paypal_secret_key = getEnvVar('PAYPAL_SECRET_KEY');
        $this->paypal_mode = getEnvVar('PAYPAL_MODE', 'sandbox');
    }
    
    /**
     * Initialise les SDK de paiement
     */
    private function initializePaymentGateways() {
        // Configuration Stripe
        if ($this->stripe_secret_key) {
            Stripe::setApiKey($this->stripe_secret_key);
        }
        
        // Configuration PayPal
        if ($this->paypal_client_id && $this->paypal_secret_key) {
            $this->paypal_api_context = new ApiContext(
                new OAuthTokenCredential(
                    $this->paypal_client_id,
                    $this->paypal_secret_key
                )
            );
            $this->paypal_api_context->setConfig([
                'mode' => $this->paypal_mode
            ]);
        }
    }
    
    /**
     * Traite un paiement et envoie les emails de confirmation
     */
    public function processPayment($payment_data) {
        try {
            // 1. Enregistrer le paiement en base
            $payment_id = $this->savePaymentToDatabase($payment_data);
            
            if (!$payment_id) {
                throw new Exception("Erreur lors de l'enregistrement du paiement");
            }
            
            // 2. Récupérer les informations complètes pour les emails
            $payment_info = $this->getPaymentInfo($payment_id);
            $client_info = $this->getClientInfo($payment_data['client_id']);
            
            // 3. Envoyer les emails automatiques
            $this->sendPaymentNotifications($payment_info, $client_info);
            
            return [
                'success' => true,
                'payment_id' => $payment_id,
                'message' => 'Paiement traité avec succès'
            ];
            
        } catch (Exception $e) {
            error_log("PaymentManager Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enregistre le paiement en base de données
     */
    private function savePaymentToDatabase($data) {
        try {
            $query = "INSERT INTO Paiements (CommandeID, ReservationID, Montant, ModePaiement, Statut, TransactionID, DatePaiement) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['commande_id'] ?? null,
                $data['reservation_id'] ?? null,
                $data['montant'],
                $data['mode_paiement'], // 'stripe', 'paypal', 'virement'
                $data['statut'] ?? 'Confirme',
                $data['transaction_id'] ?? null
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            throw new Exception("Erreur de sauvegarde du paiement");
        }
    }
    
    /**
     * Récupère les informations du paiement
     */
    private function getPaymentInfo($payment_id) {
        $query = "SELECT p.*, c.DateCommande, c.MontantTotal as CommandeMontant 
                 FROM Paiements p 
                 LEFT JOIN Commandes c ON p.CommandeID = c.CommandeID 
                 WHERE p.PaiementID = ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les informations du client
     */
    private function getClientInfo($client_id) {
        $query = "SELECT * FROM Clients WHERE ClientID = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$client_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Envoie les notifications emails automatiques
     */
    private function sendPaymentNotifications($payment_info, $client_info) {
        if (!$payment_info || !$client_info) {
            throw new Exception("Informations manquantes pour les notifications");
        }
        
        // Email à l'admin
        $admin_success = $this->emailManager->sendPaymentNotificationToAdmin(
            $payment_info, 
            $client_info
        );
        
        // Email de confirmation au client
        $client_success = $this->emailManager->sendPaymentConfirmationToClient(
            $payment_info, 
            $client_info
        );
        
        if (!$admin_success) {
            error_log("Failed to send admin payment notification");
        }
        
        if (!$client_success) {
            error_log("Failed to send client payment confirmation");
        }
        
        return $admin_success && $client_success;
    }
    
    /**
     * Traite un paiement Stripe
     */
    public function processStripePayment($payment_data) {
        try {
            if (!$this->stripe_secret_key) {
                throw new Exception("Clé API Stripe non configurée");
            }
            
            // Créer le PaymentIntent Stripe
            $payment_intent = PaymentIntent::create([
                'amount' => round($payment_data['montant'] * 100), // Stripe utilise les centimes
                'currency' => 'eur',
                'payment_method' => $payment_data['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
                'metadata' => [
                    'commande_id' => $payment_data['commande_id'] ?? '',
                    'reservation_id' => $payment_data['reservation_id'] ?? '',
                    'client_id' => $payment_data['client_id'] ?? ''
                ]
            ]);
            
            // Déterminer le statut en fonction de la réponse Stripe
            $statut = 'En attente';
            $transaction_id = $payment_intent->id;
            
            if ($payment_intent->status === 'succeeded') {
                $statut = 'Confirme';
            } elseif ($payment_intent->status === 'requires_action') {
                return [
                    'success' => false,
                    'requires_action' => true,
                    'client_secret' => $payment_intent->client_secret,
                    'payment_intent_id' => $payment_intent->id
                ];
            }
            
            // Enregistrer en base et envoyer emails
            $payment_record = [
                'commande_id' => $payment_data['commande_id'] ?? null,
                'reservation_id' => $payment_data['reservation_id'] ?? null,
                'montant' => $payment_data['montant'],
                'mode_paiement' => 'stripe',
                'statut' => $statut,
                'transaction_id' => $transaction_id,
                'client_id' => $payment_data['client_id'] ?? null
            ];
            
            return $this->processPayment($payment_record);
            
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error' => 'Carte refusée: ' . $e->getError()->message
            ];
        } catch (Exception $e) {
            error_log("Stripe Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur de paiement Stripe: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crée un paiement PayPal
     */
    public function createPayPalPayment($payment_data, $return_url, $cancel_url) {
        try {
            if (!$this->paypal_api_context) {
                throw new Exception("PayPal API non configurée");
            }
            
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');
            
            $amount = new Amount();
            $amount->setCurrency('EUR')
                   ->setTotal(number_format($payment_data['montant'], 2, '.', ''));
            
            $transaction = new Transaction();
            $transaction->setAmount($amount)
                       ->setDescription('Paiement Restaurant La Mangeoire')
                       ->setInvoiceNumber('CMD_' . ($payment_data['commande_id'] ?? time()));
            
            $redirect_urls = new RedirectUrls();
            $redirect_urls->setReturnUrl($return_url)
                         ->setCancelUrl($cancel_url);
            
            $payment = new Payment();
            $payment->setIntent('sale')
                   ->setPayer($payer)
                   ->setRedirectUrls($redirect_urls)
                   ->setTransactions([$transaction]);
            
            $payment->create($this->paypal_api_context);
            
            // Enregistrer le paiement en attente
            $payment_record = [
                'commande_id' => $payment_data['commande_id'] ?? null,
                'reservation_id' => $payment_data['reservation_id'] ?? null,
                'montant' => $payment_data['montant'],
                'mode_paiement' => 'paypal',
                'statut' => 'En attente',
                'transaction_id' => $payment->getId(),
                'client_id' => $payment_data['client_id'] ?? null
            ];
            
            $payment_id = $this->savePaymentToDatabase($payment_record);
            
            return [
                'success' => true,
                'payment_id' => $payment_id,
                'approval_url' => $payment->getApprovalLink(),
                'paypal_payment_id' => $payment->getId()
            ];
            
        } catch (Exception $e) {
            error_log("PayPal Payment Creation Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur création paiement PayPal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Exécute un paiement PayPal après approbation
     */
    public function executePayPalPayment($payment_id, $payer_id) {
        try {
            if (!$this->paypal_api_context) {
                throw new Exception("PayPal API non configurée");
            }
            
            $payment = Payment::get($payment_id, $this->paypal_api_context);
            
            $execution = new PaymentExecution();
            $execution->setPayerId($payer_id);
            
            $result = $payment->execute($execution, $this->paypal_api_context);
            
            // Mettre à jour le statut en base
            $this->updatePaymentStatus($payment_id, 'Confirme');
            
            // Récupérer les infos et envoyer les emails
            $payment_info = $this->getPaymentByTransactionId($payment_id);
            if ($payment_info) {
                $client_info = $this->getClientInfo($payment_info['client_id']);
                $this->sendPaymentNotifications($payment_info, $client_info);
            }
            
            return [
                'success' => true,
                'payment_id' => $payment_id,
                'status' => $result->getState()
            ];
            
        } catch (Exception $e) {
            error_log("PayPal Payment Execution Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur exécution paiement PayPal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère le token d'accès PayPal
     */
    private function getPayPalAccessToken() {
        $paypal_api_url = $this->paypal_mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypal_api_url . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->paypal_client_id . ':' . $this->paypal_secret_key);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($result, true);
        return $response['access_token'] ?? null;
    }
    
    /**
     * Exécute le paiement PayPal
     */
    private function executePayPalPayment($payment_id, $payer_id, $access_token) {
        $paypal_api_url = $this->paypal_mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypal_api_url . '/v1/payments/payment/' . $payment_id . '/execute');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['payer_id' => $payer_id]));
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($result, true);
        return ['success' => isset($response['state']) && $response['state'] === 'approved'];
    }
    
    /**
     * Obtient les clés publiques pour le frontend
     */
    public function getPublicKeys() {
        return [
            'stripe_publishable_key' => $this->stripe_publishable_key,
            'paypal_client_id' => $this->paypal_client_id,
            'paypal_mode' => $this->paypal_mode
        ];
    }
}
?>
