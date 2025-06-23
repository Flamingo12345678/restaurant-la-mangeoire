<?php
/**
 * API Cart - Interface REST pour le système de panier
 * 
 * Endpoints disponibles:
 * - POST add: Ajouter un article
 * - POST update: Modifier quantité
 * - DELETE remove: Supprimer un article
 * - GET items: Lister les articles
 * - GET summary: Résumé du panier
 * - DELETE clear: Vider le panier
 * 
 * Format de réponse JSON standardisé
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

try {
    // Initialiser le gestionnaire de panier
    $cartManager = new CartManager($pdo);
    
    // Déterminer l'action à effectuer
    $action = $_GET['action'] ?? $_POST['action'] ?? 'unknown';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Router les requêtes
    switch ($action) {
        case 'add':
            if ($method !== 'POST') {
                throw new Exception('Méthode non autorisée pour cette action');
            }
            handleAdd($cartManager);
            break;
            
        case 'update':
            if ($method !== 'POST') {
                throw new Exception('Méthode non autorisée pour cette action');
            }
            handleUpdate($cartManager);
            break;
            
        case 'remove':
            handleRemove($cartManager);
            break;
            
        case 'items':
            if ($method !== 'GET') {
                throw new Exception('Méthode non autorisée pour cette action');
            }
            handleGetItems($cartManager);
            break;
            
        case 'summary':
            if ($method !== 'GET') {
                throw new Exception('Méthode non autorisée pour cette action');
            }
            handleGetSummary($cartManager);
            break;
            
        case 'clear':
            handleClear($cartManager);
            break;
            
        default:
            throw new Exception('Action non reconnue: ' . $action);
    }
    
} catch (Exception $e) {
    respondError($e->getMessage(), 400);
}

// === HANDLERS ===

function handleAdd($cartManager) {
    $menu_id = getRequiredParam('menu_id');
    $quantity = getOptionalParam('quantity', 1);
    
    if (!is_numeric($menu_id) || $menu_id <= 0) {
        throw new Exception('ID menu invalide');
    }
    
    if (!is_numeric($quantity) || $quantity <= 0) {
        throw new Exception('Quantité invalide');
    }
    
    $result = $cartManager->addItem($menu_id, $quantity);
    
    if ($result['success']) {
        $summary = $cartManager->getSummary();
        respondSuccess($result['message'], [
            'item_added' => true,
            'cart_summary' => $summary
        ]);
    } else {
        throw new Exception($result['message']);
    }
}

function handleUpdate($cartManager) {
    $menu_id = getRequiredParam('menu_id');
    $quantity = getRequiredParam('quantity');
    
    if (!is_numeric($menu_id) || $menu_id <= 0) {
        throw new Exception('ID menu invalide');
    }
    
    if (!is_numeric($quantity) || $quantity < 0) {
        throw new Exception('Quantité invalide');
    }
    
    $result = $cartManager->updateItem($menu_id, $quantity);
    
    if ($result['success']) {
        $summary = $cartManager->getSummary();
        respondSuccess($result['message'], [
            'item_updated' => true,
            'cart_summary' => $summary
        ]);
    } else {
        throw new Exception($result['message']);
    }
}

function handleRemove($cartManager) {
    $menu_id = getRequiredParam('menu_id');
    
    if (!is_numeric($menu_id) || $menu_id <= 0) {
        throw new Exception('ID menu invalide');
    }
    
    $result = $cartManager->removeItem($menu_id);
    
    if ($result['success']) {
        $summary = $cartManager->getSummary();
        respondSuccess($result['message'], [
            'item_removed' => true,
            'cart_summary' => $summary
        ]);
    } else {
        throw new Exception($result['message']);
    }
}

function handleGetItems($cartManager) {
    $items = $cartManager->getItems();
    $summary = $cartManager->getSummary();
    
    respondSuccess('Articles du panier récupérés', [
        'items' => $items,
        'summary' => $summary
    ]);
}

function handleGetSummary($cartManager) {
    $summary = $cartManager->getSummary();
    
    respondSuccess('Résumé du panier', [
        'summary' => $summary
    ]);
}

function handleClear($cartManager) {
    $result = $cartManager->clear();
    
    if ($result['success']) {
        respondSuccess($result['message'], [
            'cart_cleared' => true,
            'cart_summary' => [
                'total_amount' => 0,
                'total_items' => 0,
                'items_count' => 0,
                'is_empty' => true
            ]
        ]);
    } else {
        throw new Exception($result['message']);
    }
}

// === UTILITAIRES ===

function getRequiredParam($name) {
    $value = $_POST[$name] ?? $_GET[$name] ?? null;
    
    if ($value === null || $value === '') {
        throw new Exception("Paramètre requis manquant: $name");
    }
    
    return $value;
}

function getOptionalParam($name, $default = null) {
    return $_POST[$name] ?? $_GET[$name] ?? $default;
}

function respondSuccess($message, $data = []) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function respondError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
