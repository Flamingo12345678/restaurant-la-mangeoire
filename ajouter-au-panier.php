<?php
/**
 * Ajouter au panier - Version moderne avec sécurité HTTPS
 * 
 * Script pour ajouter des articles au panier depuis les formulaires HTML
 * Supporte les redirections et les réponses AJAX
 * Fonctionne uniquement en HTTPS pour la sécurité
 */

// Inclure la configuration de sécurité HTTPS
require_once 'includes/https-security.php';
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

// Initialiser les variables
$response = [
    'success' => false,
    'message' => 'Erreur inconnue',
    'redirect' => null
];

try {
    // Vérifier que c'est une requête POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode de requête non autorisée');
    }
    
    // Récupérer les paramètres avec fallback sur $_POST
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    if ($menu_id === null || $menu_id === false) {
        $menu_id = isset($_POST['menu_id']) ? filter_var($_POST['menu_id'], FILTER_VALIDATE_INT) : false;
    }
    
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    if ($quantity === null || $quantity === false) {
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : false;
    }
    
    // Gérer aussi le champ 'quantite' utilisé dans index.php
    if ($quantity === false || $quantity === null) {
        $quantity = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
        if ($quantity === null || $quantity === false) {
            $quantity = isset($_POST['quantite']) ? filter_var($_POST['quantite'], FILTER_VALIDATE_INT) : false;
        }
    }
    
    $redirect_url = filter_input(INPUT_POST, 'redirect', FILTER_SANITIZE_URL);
    if ($redirect_url === null) {
        $redirect_url = isset($_POST['redirect']) ? filter_var($_POST['redirect'], FILTER_SANITIZE_URL) : null;
    }
    
    $ajax = filter_input(INPUT_POST, 'ajax', FILTER_VALIDATE_BOOLEAN);
    if ($ajax === null) {
        $ajax = isset($_POST['ajax']) ? filter_var($_POST['ajax'], FILTER_VALIDATE_BOOLEAN) : false;
    }
    
    // Validation des paramètres
    if ($menu_id === false || $menu_id <= 0) {
        throw new Exception('Identifiant d\'article invalide');
    }
    
    if ($quantity === false || $quantity <= 0) {
        $quantity = 1; // Quantité par défaut
    }
    
    // Initialiser le gestionnaire de panier
    $cartManager = new CartManager($pdo);
    
    // Ajouter l'article au panier
    $result = $cartManager->addItem($menu_id, $quantity);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = $result['message'];
        
        // Ajouter le résumé du panier pour les requêtes AJAX
        if ($ajax) {
            $response['cart_summary'] = $cartManager->getSummary();
        }
        
        // URL de redirection par défaut
        if (!$redirect_url) {
            $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        }
        
        $response['redirect'] = $redirect_url;
        
    } else {
        throw new Exception($result['message'] ?? 'Erreur lors de l\'ajout au panier');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // En cas d'erreur, rediriger vers la page précédente
    if (!$ajax) {
        $response['redirect'] = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    }
}

// Gestion de la réponse
if ($ajax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    // Réponse AJAX/JSON
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
    
} else {
    // Réponse HTML classique avec redirection
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($response['success']) {
        $_SESSION['cart_message'] = [
            'type' => 'success',
            'text' => $response['message']
        ];
    } else {
        $_SESSION['cart_message'] = [
            'type' => 'error',
            'text' => $response['message']
        ];
    }
    
    // Redirection
    $redirect_url = $response['redirect'] ?? 'index.php';
    header("Location: $redirect_url");
    exit;
}
?>