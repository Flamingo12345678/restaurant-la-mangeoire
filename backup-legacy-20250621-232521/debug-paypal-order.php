<?php
/**
 * Test détaillé de création de commande PayPal
 */

require_once 'includes/paypal-config.php';

echo "<h1>Test détaillé PayPal</h1>\n";

// Test de création avec debug
function createPayPalOrderDebug($montant, $description = "Commande Restaurant La Mangeoire") {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) {
        echo "<p>❌ Pas de token d'accès</p>\n";
        return false;
    }
    
    echo "<p>✅ Token obtenu: " . substr($accessToken, 0, 20) . "...</p>\n";
    
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
    
    echo "<p><strong>Données envoyées:</strong><br><pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre></p>\n";
    
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
    $error = curl_error($curl);
    curl_close($curl);
    
    echo "<p><strong>Code HTTP:</strong> $httpCode</p>\n";
    echo "<p><strong>Erreur cURL:</strong> " . ($error ?: 'Aucune') . "</p>\n";
    echo "<p><strong>Réponse:</strong><br><pre>" . $response . "</pre></p>\n";
    
    if ($httpCode === 201) {
        return json_decode($response, true);
    }
    
    return false;
}

$result = createPayPalOrderDebug(25.50, "Test - Restaurant La Mangeoire");

if ($result) {
    echo "<h2>✅ Commande créée avec succès!</h2>\n";
    echo "<p><strong>ID:</strong> " . $result['id'] . "</p>\n";
} else {
    echo "<h2>❌ Échec de création de commande</h2>\n";
}
