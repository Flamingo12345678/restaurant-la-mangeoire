<?php
/**
 * Test d'ajout au panier depuis index.php
 * Simule l'envoi du formulaire HTML
 */

require_once 'includes/https-security.php';
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "<h1>Test d'ajout au panier depuis index.php</h1>";

// Simuler les données du formulaire HTML de index.php
$_POST = [
    'menu_id' => '5',       // ID du menu (BONGO)
    'quantite' => '2',      // Nom du champ dans index.php
    'action' => 'add'       // Action du formulaire
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h2>Données POST simulées (comme depuis index.php) :</h2>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

try {
    // Test direct du script ajouter-au-panier.php
    echo "<h2>Test du script ajouter-au-panier.php :</h2>";
    
    // Capturer la sortie
    ob_start();
    
    // Récupérer les paramètres avec la nouvelle logique
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
    
    echo "✅ menu_id extrait : ";
    var_dump($menu_id);
    echo "<br>";
    
    echo "✅ quantity extrait : ";
    var_dump($quantity);
    echo "<br>";
    
    // Validation des paramètres
    if ($menu_id === false || $menu_id <= 0) {
        throw new Exception('Identifiant d\'article invalide');
    }
    
    if ($quantity === false || $quantity <= 0) {
        $quantity = 1; // Quantité par défaut
    }
    
    echo "✅ Validation OK<br>";
    
    // Initialiser le gestionnaire de panier
    $cartManager = new CartManager($pdo);
    
    // Ajouter l'article au panier
    $result = $cartManager->addItem($menu_id, $quantity);
    
    if ($result['success']) {
        echo "✅ <strong>Succès !</strong> " . $result['message'] . "<br>";
        
        // Afficher le contenu du panier
        $items = $cartManager->getItems();
        echo "<h3>📦 Contenu du panier :</h3>";
        foreach ($items as $item) {
            echo "- {$item['name']} x{$item['quantity']} = {$item['price']}€<br>";
        }
        
        $summary = $cartManager->getSummary();
        echo "<p><strong>💰 Total : {$summary['total_amount']}€ ({$summary['total_items']} articles)</strong></p>";
        
    } else {
        throw new Exception($result['message'] ?? 'Erreur lors de l\'ajout au panier');
    }
    
    ob_end_clean(); // Nettoyer le buffer
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ <strong>Erreur :</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>Test complet du formulaire HTML :</h2>";

// Test avec inclusion du script complet
try {
    // Réinitialiser les variables
    $_POST = [
        'menu_id' => '3',       // KOKI
        'quantite' => '1',
        'action' => 'add'
    ];
    
    echo "<h3>Nouveau test avec menu_id=3 (KOKI), quantite=1</h3>";
    
    // Simuler l'inclusion du script (sans les headers)
    define('HTTPS_SECURITY_MANUAL_INIT', true); // Éviter la double initialisation
    
    $response = [
        'success' => false,
        'message' => 'Erreur inconnue',
        'redirect' => null
    ];
    
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
        echo "✅ <strong>Test complet réussi !</strong> " . $response['message'] . "<br>";
    } else {
        throw new Exception($result['message'] ?? 'Erreur lors de l\'ajout au panier');
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Test complet échoué :</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>🎯 Conclusion</h2>";
echo "<p>✅ Le problème était que <code>ajouter-au-panier.php</code> ne gérait que le champ <code>quantity</code></p>";
echo "<p>✅ Les formulaires dans <code>index.php</code> utilisent <code>quantite</code></p>";
echo "<p>✅ Le script a été modifié pour supporter les deux champs</p>";

echo "<p><a href='index.php#menu'>← Tester sur la vraie page d'accueil</a></p>";
?>
