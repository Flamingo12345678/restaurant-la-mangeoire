<?php
/**
 * Configuration PayPal pour le Restaurant La Mangeoire
 * Ce fichier contient les clés d'API PayPal et la configuration de base pour l'intégration des paiements
 */

// Chargement des variables d'environnement si disponibles
if (file_exists(__DIR__ . '/../.env')) {
    $env_lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if they exist
            if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Clés API PayPal
// Toujours récupérer les clés depuis les variables d'environnement
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: ($_ENV['PAYPAL_CLIENT_ID'] ?? ''));
define('PAYPAL_SECRET_KEY', getenv('PAYPAL_SECRET_KEY') ?: ($_ENV['PAYPAL_SECRET_KEY'] ?? ''));

// Vérification que les clés API sont définies
if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_SECRET_KEY)) {
    error_log('Erreur: Les clés d\'API PayPal ne sont pas définies dans le fichier .env');
    // Ne pas arrêter l'exécution, mais enregistrer l'erreur
}

// Mode PayPal (sandbox ou live)
define('PAYPAL_MODE', getenv('PAYPAL_MODE') ?: ($_ENV['PAYPAL_MODE'] ?? 'sandbox'));

// Configuration de base
define('PAYPAL_CURRENCY', 'EUR'); // Euro
define('PAYPAL_LOCALE', 'fr_FR'); // Langue française

// URL de redirection après paiement
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
define('PAYPAL_SUCCESS_URL', 'http://' . $host . '/confirmation-paypal.php?status=success');
define('PAYPAL_CANCEL_URL', 'http://' . $host . '/confirmation-paypal.php?status=cancel');

// URL pour les webhooks PayPal (notifications de paiement)
define('PAYPAL_WEBHOOK_ID', getenv('PAYPAL_WEBHOOK_ID') ?: ($_ENV['PAYPAL_WEBHOOK_ID'] ?? ''));

// Obtenez l'URL de base de l'API PayPal en fonction du mode
function getPayPalAPIBase() {
    if (PAYPAL_MODE === 'live') {
        return 'https://api-m.paypal.com';
    }
    return 'https://api-m.sandbox.paypal.com';
}

// Fonction pour générer un token d'accès PayPal
function getPayPalAccessToken() {
    // Vérifier si les clés API sont définies
    if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_SECRET_KEY)) {
        error_log('Erreur PayPal (getPayPalAccessToken): Clés d\'API manquantes dans le fichier .env');
        error_log('PAYPAL_CLIENT_ID: ' . (empty(PAYPAL_CLIENT_ID) ? 'VIDE' : 'DÉFINI'));
        error_log('PAYPAL_SECRET_KEY: ' . (empty(PAYPAL_SECRET_KEY) ? 'VIDE' : 'DÉFINI'));
        return null;
    }
    
    $url = getPayPalAPIBase() . '/v1/oauth2/token';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET_KEY);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Pour les tests uniquement
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Erreur PayPal cURL (getPayPalAccessToken): ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    
    error_log('PayPal API Response Code: ' . $http_code);
    error_log('PayPal API Response: ' . $result);
    
    $json = json_decode($result);
    if (isset($json->access_token)) {
        return $json->access_token;
    }
    
    error_log('Erreur PayPal: Impossible d\'obtenir un token d\'accès');
    error_log('Réponse: ' . print_r($json, true));
    return null;
}
