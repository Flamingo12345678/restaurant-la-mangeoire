<?php
/**
 * Debug AJAX pour l'ajout au panier
 * Simule exactement ce que fait le JavaScript
 */

session_start();

// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug AJAX - Ajout au panier</h1>";

// Simuler exactement la requête AJAX du menu.php
echo "<h2>1. Simulation de la requête AJAX</h2>";

$postData = [
    'menu_id' => '1',
    'quantity' => '1', 
    'ajax' => 'true'
];

echo "<h3>Données POST simulées:</h3>";
echo "<pre>" . print_r($postData, true) . "</pre>";

// Créer le contexte de requête
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($postData)
    ]
]);

try {
    // Faire l'appel à notre script
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/ajouter-au-panier.php';
    echo "<h3>URL appelée: $url</h3>";
    
    $response = file_get_contents($url, false, $context);
    
    echo "<h3>Réponse brute:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Tenter de décoder en JSON
    $jsonResponse = json_decode($response, true);
    
    if ($jsonResponse) {
        echo "<h3>Réponse JSON décodée:</h3>";
        echo "<pre>" . print_r($jsonResponse, true) . "</pre>";
        
        if ($jsonResponse['success']) {
            echo "<p style='color: green;'>✅ Ajout au panier réussi!</p>";
        } else {
            echo "<p style='color: red;'>❌ Erreur: " . $jsonResponse['message'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ La réponse n'est pas du JSON valide</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors de l'appel: " . $e->getMessage() . "</p>";
}

// Test direct du script ajouter-au-panier.php
echo "<h2>2. Test direct (inclusion)</h2>";

try {
    // Simuler les variables POST
    $_POST = $postData;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    
    echo "<h3>Variables POST définies:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Capturer la sortie
    ob_start();
    
    // Inclure le script (attention aux headers)
    include 'ajouter-au-panier.php';
    
    $output = ob_get_clean();
    
    echo "<h3>Sortie directe:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    $jsonDirect = json_decode($output, true);
    if ($jsonDirect) {
        echo "<h3>JSON direct décodé:</h3>";
        echo "<pre>" . print_r($jsonDirect, true) . "</pre>";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Erreur test direct: " . $e->getMessage() . "</p>";
}

echo "<h2>✅ Debug terminé</h2>";
echo "<p><a href='menu.php'>← Retour au menu</a></p>";
?>
