<?php
/**
 * Gestionnaire de paiements moderne
 * Restaurant La Mangeoire - 21 juin 2025
 * 
 * Gère les paiements avec support Stripe, PayPal, webhooks et SCA
 */

class PaymentManager {
    private $conn;
    private $stripeConfig;
    private $paypalConfig;
    
    // Constantes statuts
    const STATUS_PENDING = 'en_attente';
    const STATUS_PROCESSING = 'en_cours';
    const STATUS_SUCCEEDED = 'reussi';
    const STATUS_FAILED = 'echec';
    const STATUS_CANCELLED = 'annule';
    const STATUS_REFUNDED = 'rembourse';
    
    // Constantes méthodes
    const METHOD_STRIPE_CARD = 'stripe_card';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_CASH = 'especes';
    const METHOD_BANK_TRANSFER = 'virement';
    
    public function __construct($database_connection, $stripe_config = null, $paypal_config = null) {
        $this->conn = $database_connection;
        $this->stripeConfig = $stripe_config;
        $this->paypalConfig = $paypal_config;
    }
    
    /**
     * Créer une intention de paiement
     */
    public function createPaymentIntent($orderId, $paymentMethod, $metadata = []) {
        try {
            // Vérifier que la commande existe
            $order = $this->getOrderForPayment($orderId);
            if (!$order) {
                throw new InvalidArgumentException('Commande introuvable');
            }
            
            // Vérifier qu'il n'y a pas déjà un paiement réussi
            if ($this->hasSuccessfulPayment($orderId)) {
                throw new InvalidArgumentException('Cette commande a déjà été payée');
            }
            
            $this->conn->beginTransaction();
            
            // Créer l'enregistrement de paiement
            $paymentId = $this->insertPaymentRecord($order, $paymentMethod, $metadata);
            
            // Traiter selon la méthode
            $result = $this->processPaymentMethod($paymentId, $order, $paymentMethod, $metadata);
            
            $this->conn->commit();
            
            return array_merge($result, [
                'payment_id' => $paymentId,
                'order_id' => $orderId
            ]);
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur création paiement: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la création du paiement',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupérer une commande pour paiement
     */
    private function getOrderForPayment($orderId) {
        $sql = "SELECT CommandeID, NumeroCommande, MontantTotal, DeviseCode, StatutCommande, 
                       EmailClient, NomClient
                FROM CommandesModernes 
                WHERE CommandeID = ? AND StatutCommande IN ('en_attente', 'confirmee')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifier si la commande a déjà un paiement réussi
     */
    private function hasSuccessfulPayment($orderId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM PaiementsModernes WHERE CommandeID = ? AND StatutPaiement = 'reussi'");
        $stmt->execute([$orderId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Insérer l'enregistrement de paiement
     */
    private function insertPaymentRecord($order, $paymentMethod, $metadata) {
        $sql = "INSERT INTO PaiementsModernes (
            CommandeID, MethodePaiement, MontantCentimes, DeviseCode, StatutPaiement,
            IPClient, UserAgent, MetadataJSON
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $order['CommandeID'],
            $paymentMethod,
            $order['MontantTotal'],
            $order['DeviseCode'],
            self::STATUS_PENDING,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode($metadata)
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Traiter selon la méthode de paiement
     */
    private function processPaymentMethod($paymentId, $order, $method, $metadata) {
        switch ($method) {
            case self::METHOD_STRIPE_CARD:
                return $this->processStripePayment($paymentId, $order, $metadata);
                
            case self::METHOD_PAYPAL:
                return $this->processPayPalPayment($paymentId, $order, $metadata);
                
            case self::METHOD_CASH:
                return $this->processCashPayment($paymentId, $order);
                
            case self::METHOD_BANK_TRANSFER:
                return $this->processBankTransferPayment($paymentId, $order);
                
            default:
                throw new InvalidArgumentException('Méthode de paiement non supportée');
        }
    }
    
    /**
     * Traiter un paiement Stripe
     */
    private function processStripePayment($paymentId, $order, $metadata) {
        if (!$this->stripeConfig || !$this->stripeConfig['secret_key']) {
            throw new Exception('Configuration Stripe manquante');
        }
        
        try {
            // Initialiser Stripe (assumant que la librairie est installée)
            \Stripe\Stripe::setApiKey($this->stripeConfig['secret_key']);
            
            // Créer le PaymentIntent
            $intentData = [
                'amount' => $order['MontantTotal'],
                'currency' => strtolower($order['DeviseCode']),
                'metadata' => [
                    'payment_id' => $paymentId,
                    'order_id' => $order['CommandeID'],
                    'order_number' => $order['NumeroCommande']
                ],
                'automatic_payment_methods' => [
                    'enabled' => true
                ]
            ];
            
            // Ajouter l'email si disponible
            if (!empty($order['EmailClient'])) {
                $intentData['receipt_email'] = $order['EmailClient'];
            }
            
            $intent = \Stripe\PaymentIntent::create($intentData);
            
            // Mettre à jour l'enregistrement
            $this->updatePaymentStatus($paymentId, self::STATUS_PROCESSING, [
                'stripe_payment_intent_id' => $intent->id,
                'client_secret' => $intent->client_secret
            ]);
            
            return [
                'success' => true,
                'provider' => 'stripe',
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $order['MontantTotal'],
                'currency' => $order['DeviseCode']
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->updatePaymentStatus($paymentId, self::STATUS_FAILED, [
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode()
            ]);
            
            throw new Exception('Erreur Stripe: ' . $e->getMessage());
        }
    }
    
    /**
     * Traiter un paiement PayPal
     */
    private function processPayPalPayment($paymentId, $order, $metadata) {
        if (!$this->paypalConfig || !$this->paypalConfig['client_id']) {
            throw new Exception('Configuration PayPal manquante');
        }
        
        try {
            // Préparer les données pour PayPal
            $amount = number_format($order['MontantTotal'] / 100, 2, '.', '');
            
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order['NumeroCommande'],
                    'amount' => [
                        'currency_code' => $order['DeviseCode'],
                        'value' => $amount
                    ],
                    'description' => 'Commande ' . $order['NumeroCommande'] . ' - Restaurant La Mangeoire'
                ]],
                'application_context' => [
                    'brand_name' => 'Restaurant La Mangeoire',
                    'locale' => 'fr-FR',
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $this->getPayPalReturnUrl($paymentId),
                    'cancel_url' => $this->getPayPalCancelUrl($paymentId)
                ]
            ];
            
            // Créer la commande PayPal (à implémenter avec l'API PayPal)
            $paypalOrder = $this->createPayPalOrder($orderData);
            
            // Mettre à jour l'enregistrement
            $this->updatePaymentStatus($paymentId, self::STATUS_PROCESSING, [
                'paypal_order_id' => $paypalOrder['id'],
                'approval_link' => $paypalOrder['approval_link']
            ]);
            
            return [
                'success' => true,
                'provider' => 'paypal',
                'order_id' => $paypalOrder['id'],
                'approval_url' => $paypalOrder['approval_link'],
                'amount' => $amount,
                'currency' => $order['DeviseCode']
            ];
            
        } catch (Exception $e) {
            $this->updatePaymentStatus($paymentId, self::STATUS_FAILED, [
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Erreur PayPal: ' . $e->getMessage());
        }
    }
    
    /**
     * Traiter un paiement en espèces
     */
    private function processCashPayment($paymentId, $order) {
        // Les paiements en espèces sont marqués comme en attente
        // Ils seront confirmés manuellement par le personnel
        
        $this->updatePaymentStatus($paymentId, self::STATUS_PENDING, [
            'requires_manual_confirmation' => true,
            'payment_location' => 'restaurant'
        ]);
        
        return [
            'success' => true,
            'provider' => 'cash',
            'requires_confirmation' => true,
            'message' => 'Paiement en espèces - confirmation requise au restaurant'
        ];
    }
    
    /**
     * Traiter un paiement par virement
     */
    private function processBankTransferPayment($paymentId, $order) {
        // Générer les instructions de virement
        $reference = 'CMD-' . $order['NumeroCommande'] . '-' . $paymentId;
        
        $this->updatePaymentStatus($paymentId, self::STATUS_PENDING, [
            'bank_reference' => $reference,
            'requires_manual_confirmation' => true
        ]);
        
        return [
            'success' => true,
            'provider' => 'bank_transfer',
            'requires_confirmation' => true,
            'bank_details' => $this->getBankDetails(),
            'reference' => $reference,
            'amount' => number_format($order['MontantTotal'] / 100, 2),
            'currency' => $order['DeviseCode']
        ];
    }
    
    /**
     * Mettre à jour le statut d'un paiement
     */
    public function updatePaymentStatus($paymentId, $status, $details = []) {
        $updateData = [
            'StatutPaiement' => $status,
            'DerniereModification' => date('Y-m-d H:i:s')
        ];
        
        // Ajouter les détails spécifiques selon le statut
        if ($status === self::STATUS_SUCCEEDED) {
            $updateData['DateReussite'] = date('Y-m-d H:i:s');
        } elseif ($status === self::STATUS_FAILED) {
            $updateData['DateEchec'] = date('Y-m-d H:i:s');
        }
        
        // Mettre à jour les métadonnées
        if (!empty($details)) {
            $stmt = $this->conn->prepare("SELECT MetadataJSON FROM PaiementsModernes WHERE PaiementID = ?");
            $stmt->execute([$paymentId]);
            $currentMetadata = json_decode($stmt->fetchColumn() ?: '{}', true);
            
            $updatedMetadata = array_merge($currentMetadata, $details);
            $updateData['MetadataJSON'] = json_encode($updatedMetadata);
        }
        
        // Construire la requête UPDATE
        $setParts = [];
        $params = [];
        
        foreach ($updateData as $field => $value) {
            $setParts[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $paymentId;
        
        $sql = "UPDATE PaiementsModernes SET " . implode(', ', $setParts) . " WHERE PaiementID = ?";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($params);
        
        // Logger le changement
        if ($result) {
            $this->logPaymentAction($paymentId, 'status_update', ['new_status' => $status, 'details' => $details]);
        }
        
        return $result;
    }
    
    /**
     * Traiter un webhook Stripe
     */
    public function handleStripeWebhook($payload, $signature) {
        if (!$this->stripeConfig || !$this->stripeConfig['webhook_secret']) {
            throw new Exception('Configuration webhook Stripe manquante');
        }
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->stripeConfig['webhook_secret']
            );
            
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handleStripePaymentSuccess($event->data->object);
                    
                case 'payment_intent.payment_failed':
                    return $this->handleStripePaymentFailed($event->data->object);
                    
                case 'payment_intent.canceled':
                    return $this->handleStripePaymentCanceled($event->data->object);
                    
                default:
                    error_log("Type d'événement Stripe non géré: " . $event->type);
                    return ['status' => 'ignored'];
            }
            
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new Exception('Signature webhook invalide');
        }
    }
    
    /**
     * Gérer un paiement Stripe réussi
     */
    private function handleStripePaymentSuccess($paymentIntent) {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        $orderId = $paymentIntent->metadata->order_id ?? null;
        
        if (!$paymentId) {
            throw new Exception('Payment ID manquant dans les métadonnées Stripe');
        }
        
        $this->conn->beginTransaction();
        
        try {
            // Mettre à jour le paiement
            $this->updatePaymentStatus($paymentId, self::STATUS_SUCCEEDED, [
                'stripe_charge_id' => $paymentIntent->latest_charge,
                'stripe_payment_method' => $paymentIntent->payment_method,
                'amount_received' => $paymentIntent->amount_received
            ]);
            
            // Mettre à jour la commande
            if ($orderId) {
                $stmt = $this->conn->prepare("UPDATE CommandesModernes SET StatutCommande = 'confirmee', DateConfirmation = NOW() WHERE CommandeID = ?");
                $stmt->execute([$orderId]);
            }
            
            $this->conn->commit();
            
            // Envoyer email de confirmation (optionnel)
            $this->sendPaymentConfirmationEmail($paymentId);
            
            return ['status' => 'success', 'payment_id' => $paymentId];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * Gérer un paiement Stripe échoué
     */
    private function handleStripePaymentFailed($paymentIntent) {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        
        if (!$paymentId) {
            return ['status' => 'ignored'];
        }
        
        $this->updatePaymentStatus($paymentId, self::STATUS_FAILED, [
            'failure_code' => $paymentIntent->last_payment_error->code ?? null,
            'failure_message' => $paymentIntent->last_payment_error->message ?? null
        ]);
        
        return ['status' => 'processed', 'payment_id' => $paymentId];
    }
    
    /**
     * Gérer un paiement Stripe annulé
     */
    private function handleStripePaymentCanceled($paymentIntent) {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        
        if (!$paymentId) {
            return ['status' => 'ignored'];
        }
        
        $this->updatePaymentStatus($paymentId, self::STATUS_CANCELLED, [
            'cancellation_reason' => $paymentIntent->cancellation_reason ?? 'user_canceled'
        ]);
        
        return ['status' => 'processed', 'payment_id' => $paymentId];
    }
    
    /**
     * Récupérer un paiement par ID
     */
    public function getPayment($paymentId) {
        $sql = "SELECT p.*, c.NumeroCommande, c.NomClient, c.EmailClient
                FROM PaiementsModernes p
                LEFT JOIN CommandesModernes c ON p.CommandeID = c.CommandeID
                WHERE p.PaiementID = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paymentId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Rechercher des paiements
     */
    public function searchPayments($filters = []) {
        $sql = "SELECT p.*, c.NumeroCommande, c.NomClient, c.EmailClient
                FROM PaiementsModernes p
                LEFT JOIN CommandesModernes c ON p.CommandeID = c.CommandeID
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.StatutPaiement = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['method'])) {
            $sql .= " AND p.MethodePaiement = ?";
            $params[] = $filters['method'];
        }
        
        if (!empty($filters['order_id'])) {
            $sql .= " AND p.CommandeID = ?";
            $params[] = $filters['order_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND p.DateCreation >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND p.DateCreation <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $sql .= " ORDER BY p.DateCreation DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Logger une action de paiement
     */
    private function logPaymentAction($paymentId, $action, $details = []) {
        $sql = "INSERT INTO LogsPaiements (PaiementID, TypeAction, DetailsJSON, IPOrigine, UserAgent) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $paymentId,
            $action,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Obtenir les détails bancaires (à configurer)
     */
    private function getBankDetails() {
        return [
            'bank_name' => 'Banque Example',
            'iban' => 'FR76 1234 5678 9012 3456 7890 123',
            'bic' => 'EXAMPLEFR',
            'account_holder' => 'Restaurant La Mangeoire SARL'
        ];
    }
    
    /**
     * URLs de retour PayPal
     */
    private function getPayPalReturnUrl($paymentId) {
        return $_SERVER['HTTP_HOST'] . "/confirmation-paypal.php?payment_id=" . $paymentId;
    }
    
    private function getPayPalCancelUrl($paymentId) {
        return $_SERVER['HTTP_HOST'] . "/paiement-annule.php?payment_id=" . $paymentId;
    }
    
    /**
     * Créer une commande PayPal (placeholder - à implémenter)
     */
    private function createPayPalOrder($orderData) {
        // Ici, intégrer l'API PayPal réelle
        // Retourner un tableau avec 'id' et 'approval_link'
        throw new Exception('API PayPal non encore implémentée');
    }
    
    /**
     * Envoyer email de confirmation de paiement
     */
    private function sendPaymentConfirmationEmail($paymentId) {
        // À implémenter avec le système d'email existant
        error_log("TODO: Envoyer email confirmation paiement $paymentId");
    }
}
?>
