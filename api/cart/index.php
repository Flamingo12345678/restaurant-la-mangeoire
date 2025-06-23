<?php
/**
 * API Panier - Endpoint REST
 * Restaurant La Mangeoire - 21 juin 2025
 * 
 * API pour synchroniser et gérer le panier
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../db_connexion.php';

// Configuration et initialisation
try {
    // Connexion à la base de données
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) {
        throw new Exception('Connexion base de données non disponible');
    }
    
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
        handleGetRequest($path, $conn);
        break;
        
    case 'POST':
        handlePostRequest($path, $conn);
        break;
        
    case 'PUT':
        handlePutRequest($path, $conn);
        break;
        
    case 'DELETE':
        handleDeleteRequest($path, $conn);
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
function handleGetRequest($path, $conn) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'validate':
            // GET /validate - Valider les articles du panier
            validateCartItems($conn);
            break;
            
        case 'totals':
            // GET /totals - Calculer les totaux
            calculateTotals($conn);
            break;
            
        case 'items':
            // GET /items - Récupérer les détails des articles du menu
            getMenuItems($conn);
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
function handlePostRequest($path, $conn) {
    $segments = explode('/', trim($path, '/'));
    
    switch ($segments[0]) {
        case 'sync':
            // POST /sync - Synchroniser le panier
            syncCart($conn);
            break;
            
        case 'validate':
            // POST /validate - Valider un panier complet
            validateFullCart($conn);
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
 * Gérer les requêtes PUT (non utilisées pour le moment)
 */
function handlePutRequest($path, $conn) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint non trouvé'
    ]);
}

/**
 * Gérer les requêtes DELETE (non utilisées pour le moment)
 */
function handleDeleteRequest($path, $conn) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint non trouvé'
    ]);
}

/**
 * Valider les articles du panier
 */
function validateCartItems($conn) {
    try {
        if (empty($_GET['items'])) {
            throw new InvalidArgumentException('Liste des articles requise (paramètre items)');
        }
        
        $itemIds = explode(',', $_GET['items']);
        $itemIds = array_filter(array_map('intval', $itemIds));
        
        if (empty($itemIds)) {
            throw new InvalidArgumentException('IDs d\'articles invalides');
        }
        
        // Construire la requête avec placeholders
        $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
        $sql = "SELECT MenuID, NomItem, Prix, Description, ImageURL, Disponible 
                FROM Menus 
                WHERE MenuID IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($itemIds);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les résultats
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'id' => (int)$item['MenuID'],
                'name' => $item['NomItem'],
                'price' => (float)$item['Prix'],
                'description' => $item['Description'],
                'image' => $item['ImageURL'],
                'available' => (bool)$item['Disponible']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $result
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Calculer les totaux du panier
 */
function calculateTotals($conn) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['items']) || !is_array($input['items'])) {
            $input = ['items' => []];
            
            // Tenter de récupérer depuis les paramètres GET
            if (!empty($_GET['cart'])) {
                $cartData = json_decode($_GET['cart'], true);
                if ($cartData && is_array($cartData)) {
                    $input['items'] = $cartData;
                }
            }
        }
        
        if (empty($input['items'])) {
            echo json_encode([
                'success' => true,
                'totals' => [
                    'subtotal' => 0,
                    'taxes' => 0,
                    'delivery_fee' => 0,
                    'total' => 0,
                    'formatted' => [
                        'subtotal' => '0.00 €',
                        'taxes' => '0.00 €',
                        'delivery_fee' => '0.00 €',
                        'total' => '0.00 €'
                    ]
                ]
            ]);
            return;
        }
        
        $subtotal = 0;
        $validItems = [];
        
        // Valider chaque article et calculer le sous-total
        foreach ($input['items'] as $item) {
            if (!isset($item['id'], $item['quantity'], $item['price'])) {
                continue;
            }
            
            // Vérifier que l'article existe et a le bon prix
            $stmt = $conn->prepare("SELECT Prix, Disponible FROM Menus WHERE MenuID = ?");
            $stmt->execute([$item['id']]);
            $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$menuItem || !$menuItem['Disponible']) {
                continue; // Article non disponible
            }
            
            $currentPrice = (float)$menuItem['Prix'];
            $itemTotal = $currentPrice * max(1, (int)$item['quantity']);
            
            $subtotal += $itemTotal;
            $validItems[] = [
                'id' => $item['id'],
                'quantity' => (int)$item['quantity'],
                'price' => $currentPrice,
                'total' => $itemTotal
            ];
        }
        
        // Calculer les frais supplémentaires
        $deliveryType = $input['delivery_type'] ?? 'takeaway';
        $deliveryFee = ($deliveryType === 'delivery') ? 5.00 : 0.00;
        
        // Calculer les taxes (exemple: 10%)
        $taxRate = 0.10;
        $taxes = $subtotal * $taxRate;
        
        $total = $subtotal + $taxes + $deliveryFee;
        
        // Gestion des devises (optionnel)
        $currency = $input['currency'] ?? 'EUR';
        $currencyRate = 1.0;
        
        if ($currency !== 'EUR' && file_exists(__DIR__ . '/../../includes/currency_manager.php')) {
            require_once __DIR__ . '/../../includes/currency_manager.php';
            $currencyManager = new CurrencyManager($conn);
            $currencyData = $currencyManager->getCurrencyByCode($currency);
            if ($currencyData) {
                $currencyRate = $currencyData['rate'];
            }
        }
        
        $result = [
            'success' => true,
            'totals' => [
                'subtotal' => $subtotal,
                'taxes' => $taxes,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'currency' => $currency,
                'currency_rate' => $currencyRate,
                'formatted' => [
                    'subtotal' => number_format($subtotal * $currencyRate, 2) . ' ' . $currency,
                    'taxes' => number_format($taxes * $currencyRate, 2) . ' ' . $currency,
                    'delivery_fee' => number_format($deliveryFee * $currencyRate, 2) . ' ' . $currency,
                    'total' => number_format($total * $currencyRate, 2) . ' ' . $currency
                ]
            ],
            'valid_items' => $validItems,
            'delivery_type' => $deliveryType
        ];
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors du calcul des totaux'
        ]);
    }
}

/**
 * Récupérer les détails des articles du menu
 */
function getMenuItems($conn) {
    try {
        $itemIds = [];
        
        if (!empty($_GET['ids'])) {
            $itemIds = explode(',', $_GET['ids']);
            $itemIds = array_filter(array_map('intval', $itemIds));
        }
        
        if (empty($itemIds)) {
            // Retourner tous les articles disponibles
            $sql = "SELECT MenuID, NomItem, Prix, Description, ImageURL, Disponible, DateModification
                    FROM Menus 
                    WHERE Disponible = 1 
                    ORDER BY NomItem";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        } else {
            // Retourner seulement les articles demandés
            $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
            $sql = "SELECT MenuID, NomItem, Prix, Description, ImageURL, Disponible, DateModification
                    FROM Menus 
                    WHERE MenuID IN ($placeholders)
                    ORDER BY NomItem";
            $stmt = $conn->prepare($sql);
            $stmt->execute($itemIds);
        }
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les résultats
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'id' => (int)$item['MenuID'],
                'name' => $item['NomItem'],
                'price' => (float)$item['Prix'],
                'description' => $item['Description'],
                'image' => $item['ImageURL'],
                'available' => (bool)$item['Disponible'],
                'last_modified' => $item['DateModification']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $result,
            'count' => count($result)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la récupération des articles'
        ]);
    }
}

/**
 * Synchroniser le panier (pour les clients connectés)
 */
function syncCart($conn) {
    try {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['client_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Utilisateur non connecté'
            ]);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new InvalidArgumentException('Données JSON invalides');
        }
        
        $clientId = $_SESSION['client_id'];
        $cartData = $input['cart'] ?? [];
        $action = $input['action'] ?? 'save'; // save, load, merge
        
        switch ($action) {
            case 'save':
                // Sauvegarder le panier en base
                saveCartToDatabase($conn, $clientId, $cartData);
                echo json_encode([
                    'success' => true,
                    'message' => 'Panier sauvegardé'
                ]);
                break;
                
            case 'load':
                // Charger le panier depuis la base
                $savedCart = loadCartFromDatabase($conn, $clientId);
                echo json_encode([
                    'success' => true,
                    'cart' => $savedCart
                ]);
                break;
                
            case 'merge':
                // Fusionner panier local + panier sauvegardé
                $savedCart = loadCartFromDatabase($conn, $clientId);
                $mergedCart = mergeCartData($cartData, $savedCart);
                
                // Sauvegarder le résultat
                saveCartToDatabase($conn, $clientId, $mergedCart);
                
                echo json_encode([
                    'success' => true,
                    'cart' => $mergedCart,
                    'message' => 'Paniers fusionnés'
                ]);
                break;
                
            default:
                throw new InvalidArgumentException('Action non supportée');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Valider un panier complet avant commande
 */
function validateFullCart($conn) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['cart']) || !is_array($input['cart'])) {
            throw new InvalidArgumentException('Panier requis');
        }
        
        $cart = $input['cart'];
        $errors = [];
        $warnings = [];
        $validItems = [];
        
        foreach ($cart as $index => $item) {
            if (!isset($item['id'], $item['quantity'], $item['price'])) {
                $errors[] = "Article #$index: données incomplètes";
                continue;
            }
            
            // Vérifier l'existence et la disponibilité
            $stmt = $conn->prepare("SELECT NomItem, Prix, Disponible FROM Menus WHERE MenuID = ?");
            $stmt->execute([$item['id']]);
            $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$menuItem) {
                $errors[] = "Article #$index: article introuvable";
                continue;
            }
            
            if (!$menuItem['Disponible']) {
                $errors[] = "Article '{$menuItem['NomItem']}': non disponible";
                continue;
            }
            
            // Vérifier le prix
            $currentPrice = (float)$menuItem['Prix'];
            $itemPrice = (float)$item['price'];
            
            if (abs($currentPrice - $itemPrice) > 0.01) {
                $warnings[] = "Prix modifié pour '{$menuItem['NomItem']}': {$itemPrice}€ → {$currentPrice}€";
                $item['price'] = $currentPrice; // Corriger le prix
            }
            
            // Vérifier la quantité
            $quantity = max(1, (int)$item['quantity']);
            if ($quantity > 20) { // Limite arbitraire
                $warnings[] = "Quantité limitée à 20 pour '{$menuItem['NomItem']}'";
                $quantity = 20;
            }
            
            $validItems[] = [
                'id' => (int)$item['id'],
                'name' => $menuItem['NomItem'],
                'price' => $currentPrice,
                'quantity' => $quantity,
                'total' => $currentPrice * $quantity,
                'customizations' => $item['customizations'] ?? null,
                'notes' => $item['notes'] ?? null
            ];
        }
        
        $isValid = empty($errors);
        
        $result = [
            'success' => true,
            'valid' => $isValid,
            'items' => $validItems,
            'errors' => $errors,
            'warnings' => $warnings
        ];
        
        // Calculer les totaux si le panier est valide
        if ($isValid && !empty($validItems)) {
            $subtotal = array_sum(array_column($validItems, 'total'));
            $deliveryFee = ($input['delivery_type'] ?? 'takeaway') === 'delivery' ? 5.00 : 0.00;
            $taxes = $subtotal * 0.10;
            $total = $subtotal + $taxes + $deliveryFee;
            
            $result['totals'] = [
                'subtotal' => $subtotal,
                'taxes' => $taxes,
                'delivery_fee' => $deliveryFee,
                'total' => $total
            ];
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Sauvegarder le panier en base de données
 */
function saveCartToDatabase($conn, $clientId, $cartData) {
    $sql = "INSERT INTO PaniersClients (ClientID, CartData, DateModification) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE CartData = VALUES(CartData), DateModification = NOW()";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$clientId, json_encode($cartData)]);
}

/**
 * Charger le panier depuis la base de données
 */
function loadCartFromDatabase($conn, $clientId) {
    $stmt = $conn->prepare("SELECT CartData FROM PaniersClients WHERE ClientID = ?");
    $stmt->execute([$clientId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return [];
    }
    
    $cartData = json_decode($result['CartData'], true);
    return is_array($cartData) ? $cartData : [];
}

/**
 * Fusionner deux paniers
 */
function mergeCartData($localCart, $savedCart) {
    $merged = [];
    $itemsById = [];
    
    // Ajouter les articles du panier local
    foreach ($localCart as $item) {
        if (isset($item['id'])) {
            $itemsById[$item['id']] = $item;
        }
    }
    
    // Fusionner avec le panier sauvegardé
    foreach ($savedCart as $item) {
        if (isset($item['id'])) {
            if (isset($itemsById[$item['id']])) {
                // Article déjà dans le panier local - garder la quantité la plus élevée
                $localQty = $itemsById[$item['id']]['quantity'] ?? 0;
                $savedQty = $item['quantity'] ?? 0;
                $itemsById[$item['id']]['quantity'] = max($localQty, $savedQty);
            } else {
                // Nouvel article du panier sauvegardé
                $itemsById[$item['id']] = $item;
            }
        }
    }
    
    return array_values($itemsById);
}
?>
