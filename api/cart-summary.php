<?php
/**
 * API pour récupérer le résumé du panier
 * Retourne le nombre d'articles et le total en JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'includes/https-security.php';
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

try {
    // Initialiser le gestionnaire de panier
    $cartManager = new CartManager($pdo);
    
    // Récupérer le résumé
    $summary = $cartManager->getSummary();
    
    // Ajouter des informations supplémentaires
    $response = [
        'success' => true,
        'data' => [
            'total_items' => $summary['total_items'],
            'total_amount' => $summary['total_amount'],
            'items_count' => $summary['items_count'],
            'is_empty' => $summary['is_empty'],
            'formatted_total' => number_format($summary['total_amount'], 2, ',', ' ') . ' €'
        ],
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [
            'total_items' => 0,
            'total_amount' => 0,
            'items_count' => 0,
            'is_empty' => true,
            'formatted_total' => '0,00 €'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
