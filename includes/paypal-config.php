<?php
/**
 * Configuration PayPal pour le Restaurant La Mangeoire
 * Ce fichier contient les paramètres et fonctions pour l'intégration PayPal
 */

// Chargement des variables d'environnement
if (file_exists(__DIR__ . '/../.env')) {
    if (class_exists('\\Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }
}

// Configuration PayPal
define('PAYPAL_CLIENT_ID', $_ENV['PAYPAL_CLIENT_ID'] ?? getenv('PAYPAL_CLIENT_ID') ?? 'AR7B2Pm1rhiX1ZiHHapFBxB9WjBNx6rEakKYj-BD6Hc8O8WY5dv5KKWpqxtbD1nxmIWc_nH-FfHZn5nb');
define('PAYPAL_SECRET_KEY', $_ENV['PAYPAL_SECRET_KEY'] ?? getenv('PAYPAL_SECRET_KEY') ?? 'EBFf91y4FdKvcsWEZ9zwu24Y5jk5s209Zr83juNV1vlqpZen1Dr7KTTFPvcXGkueTTC8WSrrOekJOrKP');
define('PAYPAL_MODE', $_ENV['PAYPAL_MODE'] ?? getenv('PAYPAL_MODE') ?? 'sandbox'); // 'sandbox' ou 'live'
define('PAYPAL_WEBHOOK_ID', $_ENV['PAYPAL_WEBHOOK_ID'] ?? getenv('PAYPAL_WEBHOOK_ID') ?? '');

// URLs PayPal selon le mode
if (PAYPAL_MODE === 'live') {
    define('PAYPAL_API_URL', 'https://api.paypal.com');
    define('PAYPAL_CHECKOUT_URL', 'https://www.paypal.com/checkoutnow?token=');
} else {
    define('PAYPAL_API_URL', 'https://api.sandbox.paypal.com');
    define('PAYPAL_CHECKOUT_URL', 'https://www.sandbox.paypal.com/checkoutnow?token=');
}

// Configuration générale
define('PAYPAL_CURRENCY', 'EUR');
define('PAYPAL_LOCALE', 'fr_FR');

// URLs de redirection
define('PAYPAL_SUCCESS_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/confirmation-paypal.php?status=success');
define('PAYPAL_CANCEL_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/confirmation-paypal.php?status=cancel');
define('PAYPAL_RETURN_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/confirmation-paypal.php');

/**
 * Obtenir un token d'accès PayPal
 */
function getPayPalAccessToken() {
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => PAYPAL_API_URL . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET_KEY,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Accept-Language: en_US',
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    return null;
}

/**
 * Créer une commande PayPal
 */
function createPayPalOrder($montant, $description = "Commande Restaurant La Mangeoire") {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) {
        return false;
    }
    
    $curl = curl_init();
    
    $data = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'amount' => [
                'currency_code' => PAYPAL_CURRENCY,
                'value' => number_format($montant, 2, '.', '')
            ],
            'description' => $description
        ]],
        'application_context' => [
            'return_url' => PAYPAL_SUCCESS_URL,
            'cancel_url' => PAYPAL_CANCEL_URL,
            'brand_name' => 'Restaurant La Mangeoire',
            'locale' => PAYPAL_LOCALE,
            'landing_page' => 'BILLING',
            'user_action' => 'PAY_NOW'
        ]
    ];
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => PAYPAL_API_URL . '/v2/checkout/orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Prefer: return=representation'
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 201) {
        return json_decode($response, true);
    }
    
    return false;
}

/**
 * Capturer un paiement PayPal
 */
function capturePayPalOrder($orderId) {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) {
        return false;
    }
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => PAYPAL_API_URL . '/v2/checkout/orders/' . $orderId . '/capture',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 201) {
        return json_decode($response, true);
    }
    
    return false;
}

/**
 * Obtenir les détails d'une commande PayPal
 */
function getPayPalOrderDetails($orderId) {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) {
        return false;
    }
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => PAYPAL_API_URL . '/v2/checkout/orders/' . $orderId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}
