<?php
/**
 * Gestionnaire de commandes moderne
 * Restaurant La Mangeoire - 21 juin 2025
 * 
 * Gère les commandes avec support localStorage, multi-devises, et paiements modernes
 */

class OrderManager {
    private $conn;
    private $currencyManager;
    
    // Constantes
    const STATUS_PENDING = 'en_attente';
    const STATUS_CONFIRMED = 'confirmee';
    const STATUS_PREPARING = 'en_preparation';
    const STATUS_READY = 'prete';
    const STATUS_DELIVERED = 'livree';
    const STATUS_CANCELLED = 'annulee';
    
    const TYPE_DELIVERY = 'livraison';
    const TYPE_TAKEAWAY = 'emporter';
    const TYPE_DINE_IN = 'sur_place';
    
    public function __construct($database_connection, $currency_manager = null) {
        $this->conn = $database_connection;
        $this->currencyManager = $currency_manager;
    }
    
    /**
     * Créer une nouvelle commande à partir du panier localStorage
     */
    public function createOrderFromCart($cartData, $customerData, $orderOptions = []) {
        try {
            $this->conn->beginTransaction();
            
            // Valider les données d'entrée
            $validatedCart = $this->validateCartData($cartData);
            $validatedCustomer = $this->validateCustomerData($customerData);
            $validatedOptions = $this->validateOrderOptions($orderOptions);
            
            // Générer un numéro de commande unique
            $orderNumber = $this->generateOrderNumber();
            
            // Calculer les totaux
            $totals = $this->calculateOrderTotals($validatedCart, $validatedOptions);
            
            // Créer la commande principale
            $orderId = $this->insertOrder($orderNumber, $validatedCustomer, $validatedOptions, $totals);
            
            // Ajouter les articles
            $this->insertOrderItems($orderId, $validatedCart);
            
            // Logger la création
            $this->logOrderAction($orderId, 'creation', null, [
                'order_number' => $orderNumber,
                'total' => $totals['total'],
                'currency' => $totals['currency']
            ]);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $totals['total_formatted'],
                'currency' => $totals['currency']
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur création commande: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la création de la commande',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Valider les données du panier localStorage
     */
    private function validateCartData($cartData) {
        if (empty($cartData) || !is_array($cartData)) {
            throw new InvalidArgumentException('Panier vide ou invalide');
        }
        
        $validatedItems = [];
        
        foreach ($cartData as $item) {
            // Vérifier les champs requis
            if (!isset($item['id'], $item['name'], $item['price'], $item['quantity'])) {
                throw new InvalidArgumentException('Article de panier invalide');
            }
            
            // Vérifier que l'article existe dans la base
            $stmt = $this->conn->prepare("SELECT MenuID, NomItem, Prix, Description FROM Menus WHERE MenuID = ? AND Prix = ?");
            $stmt->execute([$item['id'], $item['price']]);
            $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$menuItem) {
                throw new InvalidArgumentException("Article introuvable ou prix modifié: {$item['name']}");
            }
            
            $validatedItems[] = [
                'menu_id' => (int)$item['id'],
                'name' => $menuItem['NomItem'],
                'description' => $menuItem['Description'],
                'price' => (float)$menuItem['Prix'],
                'quantity' => max(1, (int)$item['quantity']),
                'customizations' => isset($item['customizations']) ? $item['customizations'] : null,
                'notes' => isset($item['notes']) ? trim($item['notes']) : null
            ];
        }
        
        return $validatedItems;
    }
    
    /**
     * Valider les données client
     */
    private function validateCustomerData($customerData) {
        $validated = [
            'client_id' => null,
            'is_guest' => true,
            'name' => '',
            'email' => '',
            'phone' => ''
        ];
        
        // Si l'utilisateur est connecté
        if (isset($_SESSION['client_id']) && !empty($_SESSION['client_id'])) {
            $validated['client_id'] = (int)$_SESSION['client_id'];
            $validated['is_guest'] = false;
            
            // Récupérer les infos depuis la base
            $stmt = $this->conn->prepare("SELECT Nom, Email, Telephone FROM Clients WHERE ClientID = ?");
            $stmt->execute([$validated['client_id']]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                $validated['name'] = $client['Nom'];
                $validated['email'] = $client['Email'];
                $validated['phone'] = $client['Telephone'] ?? '';
            }
        } else {
            // Commande invité - valider les données fournies
            if (empty($customerData['email']) || !filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Email requis et valide pour commande invité');
            }
            
            if (empty($customerData['name']) || strlen(trim($customerData['name'])) < 2) {
                throw new InvalidArgumentException('Nom requis pour commande invité');
            }
            
            $validated['name'] = trim($customerData['name']);
            $validated['email'] = trim(strtolower($customerData['email']));
            $validated['phone'] = isset($customerData['phone']) ? trim($customerData['phone']) : '';
        }
        
        return $validated;
    }
    
    /**
     * Valider les options de commande
     */
    private function validateOrderOptions($options) {
        $validated = [
            'type' => self::TYPE_TAKEAWAY,
            'delivery_address' => null,
            'delivery_city' => null,
            'delivery_postal' => null,
            'special_notes' => null,
            'currency_code' => 'EUR',
            'currency_rate' => 1.0
        ];
        
        // Type de commande
        if (isset($options['type']) && in_array($options['type'], [self::TYPE_DELIVERY, self::TYPE_TAKEAWAY, self::TYPE_DINE_IN])) {
            $validated['type'] = $options['type'];
        }
        
        // Adresse de livraison (obligatoire si livraison)
        if ($validated['type'] === self::TYPE_DELIVERY) {
            if (empty($options['delivery_address'])) {
                throw new InvalidArgumentException('Adresse de livraison requise');
            }
            $validated['delivery_address'] = trim($options['delivery_address']);
            $validated['delivery_city'] = trim($options['delivery_city'] ?? '');
            $validated['delivery_postal'] = trim($options['delivery_postal'] ?? '');
        }
        
        // Notes spéciales
        if (!empty($options['special_notes'])) {
            $validated['special_notes'] = trim($options['special_notes']);
        }
        
        // Devise (depuis CurrencyManager si disponible)
        if ($this->currencyManager) {
            $currentCurrency = $this->currencyManager->getCurrentCurrency();
            $validated['currency_code'] = $currentCurrency['code'];
            $validated['currency_rate'] = $currentCurrency['rate'];
        }
        
        return $validated;
    }
    
    /**
     * Calculer les totaux de la commande
     */
    private function calculateOrderTotals($cartItems, $options) {
        $subtotal = 0;
        
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Frais de livraison
        $deliveryFee = 0;
        if ($options['type'] === self::TYPE_DELIVERY) {
            $deliveryFee = 5.00; // Configuration à externaliser
        }
        
        // Taxes (exemple: 10%)
        $taxRate = 0.10; // Configuration à externaliser
        $taxes = $subtotal * $taxRate;
        
        $total = $subtotal + $deliveryFee + $taxes;
        
        // Conversion en centimes pour stockage
        return [
            'subtotal' => (int)round($subtotal * 100),
            'taxes' => (int)round($taxes * 100),
            'delivery_fee' => (int)round($deliveryFee * 100),
            'total' => (int)round($total * 100),
            'currency' => $options['currency_code'],
            'currency_rate' => $options['currency_rate'],
            'total_formatted' => number_format($total * $options['currency_rate'], 2) . ' ' . $options['currency_code']
        ];
    }
    
    /**
     * Insérer la commande principale
     */
    private function insertOrder($orderNumber, $customer, $options, $totals) {
        $sql = "INSERT INTO CommandesModernes (
            NumeroCommande, ClientID, NomClient, EmailClient, TelephoneClient,
            TypeCommande, StatutCommande, SousTotal, TaxesTotal, FraisLivraison, MontantTotal,
            DeviseCode, TauxConversion, AdresseLivraison, VilleLivraison, CodePostalLivraison,
            NotesSpeciales, IPClient, UserAgent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $orderNumber,
            $customer['client_id'],
            $customer['is_guest'] ? $customer['name'] : null,
            $customer['is_guest'] ? $customer['email'] : null,
            $customer['is_guest'] ? $customer['phone'] : null,
            $options['type'],
            self::STATUS_PENDING,
            $totals['subtotal'],
            $totals['taxes'],
            $totals['delivery_fee'],
            $totals['total'],
            $totals['currency'],
            $totals['currency_rate'],
            $options['delivery_address'],
            $options['delivery_city'],
            $options['delivery_postal'],
            $options['special_notes'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Insérer les articles de la commande
     */
    private function insertOrderItems($orderId, $cartItems) {
        $sql = "INSERT INTO ArticlesCommande (
            CommandeID, MenuID, NomArticle, DescriptionArticle, PrixUnitaire,
            Quantite, SousTotal, PersonnalisationsJSON, NotesSpeciales
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($cartItems as $item) {
            $unitPrice = (int)round($item['price'] * 100);
            $subtotal = $unitPrice * $item['quantity'];
            $customizationsJson = $item['customizations'] ? json_encode($item['customizations']) : null;
            
            $stmt->execute([
                $orderId,
                $item['menu_id'],
                $item['name'],
                $item['description'],
                $unitPrice,
                $item['quantity'],
                $subtotal,
                $customizationsJson,
                $item['notes']
            ]);
        }
    }
    
    /**
     * Logger une action sur une commande
     */
    private function logOrderAction($orderId, $action, $oldValue = null, $newValue = null) {
        $sql = "INSERT INTO LogsCommandes (CommandeID, TypeAction, AncienneValeur, NouvelleValeur, UtilisateurID, IPOrigine, UserAgent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $orderId,
            $action,
            $oldValue ? json_encode($oldValue) : null,
            $newValue ? json_encode($newValue) : null,
            $_SESSION['client_id'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Générer un numéro de commande unique
     */
    private function generateOrderNumber() {
        $prefix = 'CMD-' . date('Y') . '-';
        $attempts = 0;
        
        do {
            $number = $prefix . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM CommandesModernes WHERE NumeroCommande = ?");
            $stmt->execute([$number]);
            $exists = $stmt->fetchColumn() > 0;
            $attempts++;
        } while ($exists && $attempts < 10);
        
        if ($exists) {
            throw new Exception('Impossible de générer un numéro de commande unique');
        }
        
        return $number;
    }
    
    /**
     * Récupérer une commande par son ID
     */
    public function getOrder($orderId) {
        $sql = "SELECT * FROM vue_commandes_completes WHERE CommandeID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Récupérer les articles
        $sql = "SELECT * FROM ArticlesCommande WHERE CommandeID = ? ORDER BY DateAjout";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
    }
    
    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateOrderStatus($orderId, $newStatus, $notes = null) {
        // Vérifier que le statut est valide
        $validStatuses = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PREPARING, self::STATUS_READY, self::STATUS_DELIVERED, self::STATUS_CANCELLED];
        if (!in_array($newStatus, $validStatuses)) {
            throw new InvalidArgumentException('Statut de commande invalide');
        }
        
        // Récupérer l'ancien statut
        $stmt = $this->conn->prepare("SELECT StatutCommande FROM CommandesModernes WHERE CommandeID = ?");
        $stmt->execute([$orderId]);
        $oldStatus = $stmt->fetchColumn();
        
        if (!$oldStatus) {
            throw new InvalidArgumentException('Commande introuvable');
        }
        
        // Mettre à jour
        $sql = "UPDATE CommandesModernes SET StatutCommande = ?";
        $params = [$newStatus];
        
        if ($newStatus === self::STATUS_CONFIRMED && $oldStatus === self::STATUS_PENDING) {
            $sql .= ", DateConfirmation = NOW()";
        } elseif ($newStatus === self::STATUS_DELIVERED) {
            $sql .= ", DateLivraison = NOW()";
        }
        
        $sql .= " WHERE CommandeID = ?";
        $params[] = $orderId;
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($params);
        
        // Logger le changement
        if ($result) {
            $this->logOrderAction($orderId, 'changement_statut', $oldStatus, $newStatus);
        }
        
        return $result;
    }
    
    /**
     * Annuler une commande
     */
    public function cancelOrder($orderId, $reason = null) {
        return $this->updateOrderStatus($orderId, self::STATUS_CANCELLED, $reason);
    }
    
    /**
     * Rechercher des commandes
     */
    public function searchOrders($filters = []) {
        $sql = "SELECT * FROM vue_commandes_completes WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND StatutCommande = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['client_id'])) {
            $sql .= " AND ClientID = ?";
            $params[] = $filters['client_id'];
        }
        
        if (!empty($filters['email'])) {
            $sql .= " AND EmailClient = ?";
            $params[] = $filters['email'];
        }
        
        if (!empty($filters['order_number'])) {
            $sql .= " AND NumeroCommande LIKE ?";
            $params[] = '%' . $filters['order_number'] . '%';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DateCommande >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DateCommande <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $sql .= " ORDER BY DateCommande DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
