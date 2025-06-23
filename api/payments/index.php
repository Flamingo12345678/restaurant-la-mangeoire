<?php
/**
 * API Paiements - Endpoint REST
 * Restaurant La Mangeoire - 21 juin 2025
 * 
 * API pour créer et gérer les paiements
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../db_connexion.php';
require_once __DIR__ . '/../../includes/payment-manager.php';

// Configuration et initialisation
try {
    // Connexion à la base de données
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) {
        throw new Exception('Connexion base de données non disponible');
    }
    
    // Configuration Stripe
    $stripeConfig = [
        'secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? null,
        'publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? null,
        'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null
    ];
    
    // Configuration PayPal
    $paypalConfig = [
        'client_id' => $_ENV['PAYPAL_CLIENT_ID'] ?? null,
        'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? null,
        'sandbox' => ($_ENV['PAYPAL_SANDBOX'] ?? 'true') === 'true'
    ];
    
    // Payment Manager
    $paymentManager = new PaymentManager($conn, $stripeConfig, $paypalConfig);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de configuration du serveur'
    ]);
    exit();
}

// Router simple basé sur la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

switch ($method) {
    case 'GET':
        handleGetRequest($path, $paymentManager);
        break;
        
    case 'POST':
        handlePostRequest($path, $paymentManager);
        break;
        
    case 'PUT':
        handlePutRequest($path, $paymentManager);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Méthode non autorisée'
        ]);
        break;
}

/**
 * Gérer les requêtes GET
 */
function handleGetRequest($path, $paymentManager) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'payment':
            if (isset($segments[1])) {
                // GET /payment/{id}
                getPaymentById($segments[1], $paymentManager);
            } else {
                // GET /payment - Rechercher des paiements
                searchPayments($paymentManager);
            }
            break;
            
        case 'methods':
            // GET /methods - Liste des méthodes de paiement disponibles
            getPaymentMethods();
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint non trouvé'
            ]);
            break;
    }
}

/**
 * Gérer les requêtes POST
 */
function handlePostRequest($path, $paymentManager) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'create-intent':
            // POST /create-intent - Créer une intention de paiement
            createPaymentIntent($paymentManager);
            break;
            
        case 'webhook':
            if (isset($segments[1])) {
                // POST /webhook/{provider}
                handleWebhook($segments[1], $paymentManager);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Fournisseur webhook requis']);
            }
            break;
            
        case 'confirm':
            // POST /confirm - Confirmer un paiement manuellement
            confirmPayment($paymentManager);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint non trouvé'
            ]);
            break;
    }
}

/**
 * Gérer les requêtes PUT
 */
function handlePutRequest($path, $paymentManager) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'payment':
            if (isset($segments[1]) && isset($segments[2])) {
                // PUT /payment/{id}/status
                if ($segments[2] === 'status') {
                    updatePaymentStatus($segments[1], $paymentManager);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Action non trouvée']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID de paiement requis']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint non trouvé'
            ]);
            break;
    }
}

/**
 * Créer une intention de paiement
 */
function createPaymentIntent($paymentManager) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new InvalidArgumentException('Données JSON invalides');
        }
        
        // Valider les données requises
        if (empty($input['order_id'])) {
            throw new InvalidArgumentException('ID de commande requis');
        }
        
        if (empty($input['payment_method'])) {
            throw new InvalidArgumentException('Méthode de paiement requise');
        }
        
        // Extraire les données
        $orderId = (int)$input['order_id'];
        $paymentMethod = $input['payment_method'];
        $metadata = $input['metadata'] ?? [];
        
        // Créer l'intention de paiement
        $result = $paymentManager->createPaymentIntent($orderId, $paymentMethod, $metadata);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("Erreur API création intention paiement: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur interne du serveur'
        ]);
    }
}

/**
 * Récupérer un paiement par ID
 */
function getPaymentById($paymentId, $paymentManager) {
    try {
        if (!ctype_digit($paymentId)) {
            throw new InvalidArgumentException('ID de paiement invalide');
        }
        
        $payment = $paymentManager->getPayment($paymentId);
        
        if (!$payment) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Paiement non trouvé'
            ]);
            return;
        }
        
        // Vérifier les permissions
        if (!canAccessPayment($payment)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        // Nettoyer les données sensibles pour l'affichage client
        $cleanPayment = cleanPaymentData($payment);
        
        echo json_encode([
            'success' => true,
            'payment' => $cleanPayment
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la récupération du paiement'
        ]);
    }
}

/**
 * Rechercher des paiements
 */
function searchPayments($paymentManager) {
    try {
        $filters = [];
        
        // Construire les filtres depuis les paramètres GET
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['method'])) {
            $filters['method'] = $_GET['method'];
        }
        
        if (isset($_GET['order_id'])) {
            $filters['order_id'] = (int)$_GET['order_id'];
        }
        
        if (isset($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if (isset($_GET['limit'])) {
            $filters['limit'] = min(100, max(1, (int)$_GET['limit']));
        }
        
        // Vérifier les permissions
        if (!canSearchPayments($filters)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        $payments = $paymentManager->searchPayments($filters);
        
        // Nettoyer les données sensibles
        $cleanPayments = array_map('cleanPaymentData', $payments);
        
        echo json_encode([
            'success' => true,
            'payments' => $cleanPayments,
            'count' => count($cleanPayments)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la recherche'
        ]);
    }
}

/**
 * Gérer les webhooks
 */
function handleWebhook($provider, $paymentManager) {
    try {
        $payload = file_get_contents('php://input');
        
        switch ($provider) {
            case 'stripe':
                $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
                if (empty($signature)) {
                    throw new InvalidArgumentException('Signature Stripe manquante');
                }
                
                $result = $paymentManager->handleStripeWebhook($payload, $signature);
                echo json_encode($result);
                break;
                
            case 'paypal':
                // Implémenter la gestion des webhooks PayPal
                echo json_encode(['status' => 'received']);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Fournisseur webhook non supporté'
                ]);
                break;
        }
        
    } catch (Exception $e) {
        error_log("Erreur webhook $provider: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur traitement webhook'
        ]);
    }
}

/**
 * Confirmer un paiement manuellement
 */
function confirmPayment($paymentManager) {
    try {
        // Vérifier les permissions admin
        if (!canConfirmPayments()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['payment_id'])) {
            throw new InvalidArgumentException('ID de paiement requis');
        }
        
        $paymentId = (int)$input['payment_id'];
        $notes = $input['notes'] ?? 'Confirmation manuelle';
        
        $result = $paymentManager->updatePaymentStatus($paymentId, PaymentManager::STATUS_SUCCEEDED, [
            'manual_confirmation' => true,
            'confirmed_by' => $_SESSION['admin_id'] ?? $_SESSION['employe_id'] ?? 'system',
            'confirmation_notes' => $notes
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Paiement confirmé manuellement'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Impossible de confirmer le paiement'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur interne du serveur'
        ]);
    }
}

/**
 * Obtenir la liste des méthodes de paiement disponibles
 */
function getPaymentMethods() {
    $methods = [
        [
            'id' => PaymentManager::METHOD_STRIPE_CARD,
            'name' => 'Carte bancaire',
            'description' => 'Paiement sécurisé par carte (Visa, Mastercard, etc.)',
            'enabled' => !empty($_ENV['STRIPE_SECRET_KEY']),
            'fees' => '2.9% + 0.25€'
        ],
        [
            'id' => PaymentManager::METHOD_PAYPAL,
            'name' => 'PayPal',
            'description' => 'Paiement via votre compte PayPal',
            'enabled' => !empty($_ENV['PAYPAL_CLIENT_ID']),
            'fees' => '3.4% + 0.35€'
        ],
        [
            'id' => PaymentManager::METHOD_CASH,
            'name' => 'Espèces',
            'description' => 'Paiement en espèces au restaurant',
            'enabled' => true,
            'fees' => 'Gratuit'
        ],
        [
            'id' => PaymentManager::METHOD_BANK_TRANSFER,
            'name' => 'Virement bancaire',
            'description' => 'Virement sur le compte du restaurant',
            'enabled' => true,
            'fees' => 'Gratuit'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'methods' => $methods
    ]);
}

/**
 * Mettre à jour le statut d'un paiement
 */
function updatePaymentStatus($paymentId, $paymentManager) {
    try {
        // Vérifier les permissions admin
        if (!canUpdatePaymentStatus()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        if (!ctype_digit($paymentId)) {
            throw new InvalidArgumentException('ID de paiement invalide');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status'])) {
            throw new InvalidArgumentException('Nouveau statut requis');
        }
        
        $result = $paymentManager->updatePaymentStatus($paymentId, $input['status'], $input['details'] ?? []);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Statut mis à jour'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Impossible de mettre à jour le statut'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur interne du serveur'
        ]);
    }
}

/**
 * Nettoyer les données de paiement pour l'affichage client
 */
function cleanPaymentData($payment) {
    // Supprimer les données sensibles selon le contexte
    $isAdmin = isset($_SESSION['admin_id']) || isset($_SESSION['employe_id']);
    
    if (!$isAdmin) {
        // Pour les clients, masquer certaines informations
        unset($payment['IPClient']);
        unset($payment['UserAgent']);
        
        // Masquer certaines métadonnées sensibles
        if (isset($payment['MetadataJSON'])) {
            $metadata = json_decode($payment['MetadataJSON'], true);
            if ($metadata) {
                // Garder seulement les infos non sensibles
                $cleanMetadata = array_intersect_key($metadata, [
                    'client_secret' => '',
                    'confirmation_notes' => '',
                    'manual_confirmation' => '',
                    'bank_reference' => ''
                ]);
                $payment['MetadataJSON'] = json_encode($cleanMetadata);
            }
        }
    }
    
    return $payment;
}

/**
 * Vérifications de permissions
 */
function canAccessPayment($payment) {
    // Admin/employé peut tout voir
    if (isset($_SESSION['admin_id']) || isset($_SESSION['employe_id'])) {
        return true;
    }
    
    // TODO: Vérifier si le client peut accéder à ce paiement
    // via la commande associée
    return false;
}

function canSearchPayments($filters) {
    // Seuls admin/employés peuvent rechercher
    return isset($_SESSION['admin_id']) || isset($_SESSION['employe_id']);
}

function canConfirmPayments() {
    // Seuls admin/employés peuvent confirmer
    return isset($_SESSION['admin_id']) || isset($_SESSION['employe_id']);
}

function canUpdatePaymentStatus() {
    // Seuls admin/employés peuvent modifier les statuts
    return isset($_SESSION['admin_id']) || isset($_SESSION['employe_id']);
}
?>
