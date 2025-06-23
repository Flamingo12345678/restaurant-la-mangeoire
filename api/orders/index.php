<?php
/**
 * API Commandes - Endpoint REST
 * Restaurant La Mangeoire - 21 juin 2025
 * 
 * API pour créer et gérer les commandes
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

require_once __DIR__ . '/../../includes/common.php';
require_once __DIR__ . '/../../includes/order-manager.php';

// Configuration et initialisation
try {
    // Connexion à la base de données
    $pdo = getDBConnection();
    
    // Currency Manager (si disponible)
    $currencyManager = null;
    if (file_exists(__DIR__ . '/../../includes/currency_manager.php')) {
        require_once __DIR__ . '/../../includes/currency_manager.php';
        $currencyManager = new CurrencyManager($pdo);
    }
    
    // Order Manager
    $orderManager = new OrderManager($pdo, $currencyManager);
    
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
        handleGetRequest($path, $orderManager);
        break;
        
    case 'POST':
        handlePostRequest($path, $orderManager);
        break;
        
    case 'PUT':
        handlePutRequest($path, $orderManager);
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
function handleGetRequest($path, $orderManager) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'order':
            if (isset($segments[1])) {
                // GET /order/{id}
                getOrderById($segments[1], $orderManager);
            } else {
                // GET /order - Rechercher des commandes
                searchOrders($orderManager);
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
 * Gérer les requêtes POST
 */
function handlePostRequest($path, $orderManager) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'order':
            // POST /order - Créer une commande
            createOrder($orderManager);
            break;
            
        case 'validate-cart':
            // POST /validate-cart - Valider un panier
            validateCart($orderManager);
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
function handlePutRequest($path, $orderManager) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'order':
            if (isset($segments[1]) && isset($segments[2])) {
                // PUT /order/{id}/status
                if ($segments[2] === 'status') {
                    updateOrderStatus($segments[1], $orderManager);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Action non trouvée']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID de commande requis']);
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
 * Créer une nouvelle commande
 */
function createOrder($orderManager) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new InvalidArgumentException('Données JSON invalides');
        }
        
        // Valider les données requises
        if (empty($input['cart']) || !is_array($input['cart'])) {
            throw new InvalidArgumentException('Panier requis et doit être un tableau');
        }
        
        if (empty($input['customer'])) {
            throw new InvalidArgumentException('Informations client requises');
        }
        
        // Extraire les données
        $cartData = $input['cart'];
        $customerData = $input['customer'];
        $orderOptions = $input['options'] ?? [];
        
        // Créer la commande
        $result = $orderManager->createOrderFromCart($cartData, $customerData, $orderOptions);
        
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
        error_log("Erreur API création commande: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur interne du serveur'
        ]);
    }
}

/**
 * Récupérer une commande par ID
 */
function getOrderById($orderId, $orderManager) {
    try {
        if (!ctype_digit($orderId)) {
            throw new InvalidArgumentException('ID de commande invalide');
        }
        
        $order = $orderManager->getOrder($orderId);
        
        if (!$order) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Commande non trouvée'
            ]);
            return;
        }
        
        // Vérifier les permissions (optionnel - à adapter selon vos besoins)
        if (!canAccessOrder($order)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la récupération de la commande'
        ]);
    }
}

/**
 * Rechercher des commandes
 */
function searchOrders($orderManager) {
    try {
        $filters = [];
        
        // Construire les filtres depuis les paramètres GET
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['client_id'])) {
            $filters['client_id'] = (int)$_GET['client_id'];
        }
        
        if (isset($_GET['email'])) {
            $filters['email'] = $_GET['email'];
        }
        
        if (isset($_GET['order_number'])) {
            $filters['order_number'] = $_GET['order_number'];
        }
        
        if (isset($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if (isset($_GET['limit'])) {
            $filters['limit'] = min(100, max(1, (int)$_GET['limit'])); // Limiter entre 1 et 100
        }
        
        // Vérifier les permissions
        if (!canSearchOrders($filters)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        $orders = $orderManager->searchOrders($filters);
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'count' => count($orders)
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
 * Mettre à jour le statut d'une commande
 */
function updateOrderStatus($orderId, $orderManager) {
    try {
        if (!ctype_digit($orderId)) {
            throw new InvalidArgumentException('ID de commande invalide');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status'])) {
            throw new InvalidArgumentException('Nouveau statut requis');
        }
        
        // Vérifier les permissions
        if (!canUpdateOrderStatus($orderId)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé'
            ]);
            return;
        }
        
        $result = $orderManager->updateOrderStatus($orderId, $input['status'], $input['notes'] ?? null);
        
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
        
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur interne du serveur'
        ]);
    }
}

/**
 * Valider un panier avant commande
 */
function validateCart($orderManager) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['cart']) || !is_array($input['cart'])) {
            throw new InvalidArgumentException('Panier requis et doit être un tableau');
        }
        
        // Utiliser la méthode de validation du OrderManager
        $reflection = new ReflectionClass($orderManager);
        $method = $reflection->getMethod('validateCartData');
        $method->setAccessible(true);
        
        $validatedCart = $method->invokeArgs($orderManager, [$input['cart']]);
        
        // Calculer les totaux si des options sont fournies
        $totals = null;
        if (!empty($input['options'])) {
            $optionsMethod = $reflection->getMethod('validateOrderOptions');
            $optionsMethod->setAccessible(true);
            $validatedOptions = $optionsMethod->invokeArgs($orderManager, [$input['options']]);
            
            $totalsMethod = $reflection->getMethod('calculateOrderTotals');
            $totalsMethod->setAccessible(true);
            $totals = $totalsMethod->invokeArgs($orderManager, [$validatedCart, $validatedOptions]);
        }
        
        echo json_encode([
            'success' => true,
            'valid' => true,
            'cart' => $validatedCart,
            'totals' => $totals
        ]);
        
    } catch (InvalidArgumentException $e) {
        echo json_encode([
            'success' => true,
            'valid' => false,
            'error' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la validation'
        ]);
    }
}

/**
 * Vérifier si l'utilisateur peut accéder à une commande
 */
function canAccessOrder($order) {
    // Si utilisateur connecté et c'est sa commande
    if (isset($_SESSION['client_id']) && $order['ClientID'] == $_SESSION['client_id']) {
        return true;
    }
    
    // Si admin/employé connecté
    if (isset($_SESSION['admin_id']) || isset($_SESSION['employe_id'])) {
        return true;
    }
    
    // Si commande invité et même session/email (à implémenter selon vos besoins)
    // Par sécurité, on refuse par défaut
    return false;
}

/**
 * Vérifier si l'utilisateur peut rechercher des commandes
 */
function canSearchOrders($filters) {
    // Seuls les admins/employés peuvent rechercher
    if (isset($_SESSION['admin_id']) || isset($_SESSION['employe_id'])) {
        return true;
    }
    
    // Les clients peuvent uniquement rechercher leurs propres commandes
    if (isset($_SESSION['client_id'])) {
        return isset($filters['client_id']) && $filters['client_id'] == $_SESSION['client_id'];
    }
    
    return false;
}

/**
 * Vérifier si l'utilisateur peut mettre à jour le statut d'une commande
 */
function canUpdateOrderStatus($orderId) {
    // Seuls les admins/employés peuvent mettre à jour les statuts
    return isset($_SESSION['admin_id']) || isset($_SESSION['employe_id']);
}
?>
