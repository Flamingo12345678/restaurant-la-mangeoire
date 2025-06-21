<?php
// Test spécifique pour la création de commande PayPal
require_once 'includes/paypal-config.php';

echo "<h1>Test de création de commande PayPal</h1>";

// Simuler les données d'une commande
$payment_amount = 25.00;
$payment_description = "Test Commande #123 - Restaurant La Mangeoire";
$customer_email = "test@example.com";

echo "<h2>Paramètres de test :</h2>";
echo "Montant: " . $payment_amount . " EUR<br>";
echo "Description: " . $payment_description . "<br>";
echo "Email client: " . $customer_email . "<br><br>";

echo "<h2>Configuration PayPal :</h2>";
echo "Mode: " . PAYPAL_MODE . "<br>";
echo "API Base: " . getPayPalAPIBase() . "<br>";
echo "Client ID: " . (defined('PAYPAL_CLIENT_ID') ? substr(PAYPAL_CLIENT_ID, 0, 20) . "..." : 'NON DÉFINI') . "<br><br>";

// Obtenir un token d'accès
echo "<h2>Étape 1: Obtention du token d'accès</h2>";
$access_token = getPayPalAccessToken();

if (!$access_token) {
    echo "❌ Impossible d'obtenir le token d'accès<br>";
    exit;
}

echo "✅ Token obtenu : " . substr($access_token, 0, 20) . "...<br><br>";

// Créer une commande PayPal
echo "<h2>Étape 2: Création de la commande PayPal</h2>";

$apiUrl = getPayPalAPIBase() . '/v2/checkout/orders';

$referenceId = "test-commande-123";

$payload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'reference_id' => $referenceId,
        'description' => $payment_description,
        'amount' => [
            'currency_code' => 'EUR',
            'value' => number_format($payment_amount, 2, '.', '')
        ]
    ]],
    'application_context' => [
        'brand_name' => 'Restaurant La Mangeoire',
        'landing_page' => 'BILLING',
        'user_action' => 'PAY_NOW',
        'return_url' => 'http://localhost/confirmation-paypal.php?status=success&type=order',
        'cancel_url' => 'http://localhost/confirmation-paypal.php?status=cancel'
    ]
];

echo "Payload envoyé :<br>";
echo "<pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code de réponse HTTP: " . $http_code . "<br>";

if ($error) {
    echo "❌ Erreur cURL: " . $error . "<br>";
    exit;
}

echo "Réponse PayPal :<br>";
echo "<pre>" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "</pre><br>";

$responseData = json_decode($response, true);

if (isset($responseData['id']) && isset($responseData['links'])) {
    $approvalUrl = null;
    
    foreach ($responseData['links'] as $link) {
        if ($link['rel'] === 'approve') {
            $approvalUrl = $link['href'];
            break;
        }
    }
    
    if ($approvalUrl) {
        echo "✅ Commande créée avec succès !<br>";
        echo "ID de commande: " . $responseData['id'] . "<br>";
        echo "URL d'approbation: <a href='" . $approvalUrl . "' target='_blank'>" . $approvalUrl . "</a><br>";
        echo "<br><strong>Cliquez sur le lien ci-dessus pour tester le paiement PayPal</strong>";
    } else {
        echo "❌ URL d'approbation non trouvée dans la réponse<br>";
    }
} else {
    echo "❌ Erreur lors de la création de la commande<br>";
    if (isset($responseData['error'])) {
        echo "Détails de l'erreur: " . $responseData['error'] . "<br>";
        if (isset($responseData['error_description'])) {
            echo "Description: " . $responseData['error_description'] . "<br>";
        }
    }
}
?>
