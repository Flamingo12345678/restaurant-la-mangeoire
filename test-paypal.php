<?php
// Test de connexion PayPal
require_once 'includes/paypal-config.php';

echo "<h1>Test de connexion PayPal</h1>";

echo "<h2>Configuration actuelle :</h2>";
echo "PAYPAL_CLIENT_ID: " . (defined('PAYPAL_CLIENT_ID') ? (empty(PAYPAL_CLIENT_ID) ? 'DÉFINI MAIS VIDE' : 'DÉFINI') : 'NON DÉFINI') . "<br>";
echo "PAYPAL_SECRET_KEY: " . (defined('PAYPAL_SECRET_KEY') ? (empty(PAYPAL_SECRET_KEY) ? 'DÉFINI MAIS VIDE' : 'DÉFINI') : 'NON DÉFINI') . "<br>";
echo "PAYPAL_MODE: " . (defined('PAYPAL_MODE') ? PAYPAL_MODE : 'NON DÉFINI') . "<br>";

echo "<h2>Variables d'environnement :</h2>";
echo "PAYPAL_CLIENT_ID (getenv): " . (getenv('PAYPAL_CLIENT_ID') ?: 'NON TROUVÉ') . "<br>";
echo "PAYPAL_SECRET_KEY (getenv): " . (getenv('PAYPAL_SECRET_KEY') ?: 'NON TROUVÉ') . "<br>";
echo "PAYPAL_MODE (getenv): " . (getenv('PAYPAL_MODE') ?: 'NON TROUVÉ') . "<br>";

echo "<h2>Test d'obtention du token d'accès :</h2>";
$token = getPayPalAccessToken();

if ($token) {
    echo "✅ Token obtenu avec succès : " . substr($token, 0, 20) . "...<br>";
} else {
    echo "❌ Impossible d'obtenir le token<br>";
}

echo "<h2>Vérification de la fonction getPayPalAPIBase :</h2>";
if (function_exists('getPayPalAPIBase')) {
    echo "URL API Base: " . getPayPalAPIBase() . "<br>";
} else {
    echo "❌ Fonction getPayPalAPIBase non trouvée<br>";
}

echo "<h2>Test cURL basique :</h2>";
if (function_exists('curl_init')) {
    echo "✅ cURL est disponible<br>";
    
    // Test de connexion basique à PayPal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api-m.sandbox.paypal.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Code de réponse PayPal: " . $http_code . "<br>";
    if ($error) {
        echo "Erreur cURL: " . $error . "<br>";
    } else {
        echo "✅ Connexion à PayPal réussie<br>";
    }
} else {
    echo "❌ cURL n'est pas disponible<br>";
}
?>
