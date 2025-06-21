<?php
session_start();

echo "<h1>Diagnostic PayPal</h1>";

echo "<h2>Paramètres GET :</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>Variables de session PayPal :</h2>";
echo "<pre>";
echo "paypal_order_id: " . (isset($_SESSION['paypal_order_id']) ? $_SESSION['paypal_order_id'] : 'NON DÉFINI') . "\n";
echo "paypal_reservation_id: " . (isset($_SESSION['paypal_reservation_id']) ? $_SESSION['paypal_reservation_id'] : 'NON DÉFINI') . "\n";
echo "paypal_amount: " . (isset($_SESSION['paypal_amount']) ? $_SESSION['paypal_amount'] : 'NON DÉFINI') . "\n";
echo "</pre>";

echo "<h2>Toutes les variables de session :</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test de la configuration PayPal
require_once 'includes/paypal-config.php';

echo "<h2>Configuration PayPal :</h2>";
echo "<pre>";
echo "PAYPAL_CLIENT_ID: " . (defined('PAYPAL_CLIENT_ID') ? (strlen(PAYPAL_CLIENT_ID) > 10 ? substr(PAYPAL_CLIENT_ID, 0, 10) . '...' : PAYPAL_CLIENT_ID) : 'NON DÉFINI') . "\n";
echo "PAYPAL_SECRET_KEY: " . (defined('PAYPAL_SECRET_KEY') ? (strlen(PAYPAL_SECRET_KEY) > 10 ? substr(PAYPAL_SECRET_KEY, 0, 10) . '...' : PAYPAL_SECRET_KEY) : 'NON DÉFINI') . "\n";
echo "PAYPAL_MODE: " . (defined('PAYPAL_MODE') ? PAYPAL_MODE : 'NON DÉFINI') . "\n";
echo "</pre>";

// Test du token d'accès
echo "<h2>Test du token d'accès PayPal :</h2>";
$token = getPayPalAccessToken();
echo "<pre>";
if ($token) {
    echo "Token obtenu avec succès : " . substr($token, 0, 20) . "...\n";
} else {
    echo "Erreur : Impossible d'obtenir le token d'accès PayPal\n";
}
echo "</pre>";
?>
