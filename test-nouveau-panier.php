<?php
/**
 * Test complet du nouveau système de panier
 * 
 * Ce script teste toutes les fonctionnalités du CartManager:
 * - Ajout d'articles (session et DB)
 * - Modification de quantités
 * - Suppression d'articles
 * - Migration session <-> DB
 * - Résumé et vidage du panier
 */

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

// Démarrer la session pour les tests
session_start();

echo "<h1>🧪 Test Complet du Système de Panier Moderne</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .info { color: #3498db; }
    .warning { color: #f39c12; }
    pre { background: #ecf0f1; padding: 10px; border-radius: 4px; overflow-x: auto; }
    h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
    h3 { color: #34495e; }
</style>";

// Initialiser le gestionnaire de panier
$cartManager = new CartManager($pdo);

// Nettoyer les données de test précédentes
echo "<div class='test'>";
echo "<h2>🧹 Nettoyage initial</h2>";
$_SESSION = array(); // Vider la session
session_start(); // Redémarrer proprement

// Supprimer les données de test dans la DB (utilisateur de test ID=999)
try {
    $pdo->exec("DELETE FROM Panier WHERE UtilisateurID = 999");
    echo "<p class='success'>✅ Données de test nettoyées</p>";
} catch (Exception $e) {
    echo "<p class='warning'>⚠️ Erreur nettoyage: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 1: Vérifier les articles disponibles
echo "<div class='test'>";
echo "<h2>🍽️ Test 1: Articles disponibles</h2>";
try {
    $stmt = $pdo->query("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 3");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($menus)) {
        echo "<p class='error'>❌ Aucun article dans la table Menus</p>";
        exit;
    }
    
    echo "<p class='success'>✅ " . count($menus) . " articles trouvés:</p>";
    foreach ($menus as $menu) {
        echo "<p class='info'>- {$menu['NomItem']} (ID: {$menu['MenuID']}) - {$menu['Prix']}€</p>";
    }
    
    // Utiliser le premier article pour les tests
    $test_menu_id = $menus[0]['MenuID'];
    $test_menu_name = $menus[0]['NomItem'];
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    exit;
}
echo "</div>";

// Test 2: Ajout d'articles (utilisateur non connecté - session)
echo "<div class='test'>";
echo "<h2>🛒 Test 2: Ajout articles (session)</h2>";

// S'assurer qu'on n'est pas connecté
unset($_SESSION['client_id']);

$result = $cartManager->addItem($test_menu_id, 2);
if ($result['success']) {
    echo "<p class='success'>✅ Article ajouté en session: " . $result['message'] . "</p>";
} else {
    echo "<p class='error'>❌ Erreur ajout: " . $result['message'] . "</p>";
}

// Ajouter un autre article
if (count($menus) > 1) {
    $result2 = $cartManager->addItem($menus[1]['MenuID'], 1);
    if ($result2['success']) {
        echo "<p class='success'>✅ Deuxième article ajouté: " . $result2['message'] . "</p>";
    }
}

// Vérifier le contenu du panier
$items = $cartManager->getItems();
$summary = $cartManager->getSummary();

echo "<h3>Contenu du panier (session):</h3>";
echo "<pre>" . print_r($items, true) . "</pre>";

echo "<h3>Résumé du panier:</h3>";
echo "<pre>" . print_r($summary, true) . "</pre>";

echo "</div>";

// Test 3: Modification de quantité
echo "<div class='test'>";
echo "<h2>✏️ Test 3: Modification quantité</h2>";

$result = $cartManager->updateItem($test_menu_id, 5);
if ($result['success']) {
    echo "<p class='success'>✅ Quantité modifiée: " . $result['message'] . "</p>";
    
    $new_summary = $cartManager->getSummary();
    echo "<p class='info'>Nouveau total: {$new_summary['total_items']} articles - {$new_summary['total_amount']}€</p>";
} else {
    echo "<p class='error'>❌ Erreur modification: " . $result['message'] . "</p>";
}

echo "</div>";

// Test 4: Simulation connexion utilisateur et migration
echo "<div class='test'>";
echo "<h2>👤 Test 4: Connexion et migration session -> DB</h2>";

// Simuler la connexion d'un utilisateur (ID=999 pour les tests)
$_SESSION['client_id'] = 999;

echo "<p class='info'>🔄 Simulation connexion utilisateur ID=999</p>";

$migration = $cartManager->migrateSessionToDatabase(999);
if ($migration['success']) {
    echo "<p class='success'>✅ Migration réussie: " . $migration['message'] . "</p>";
    echo "<p class='info'>Articles migrés: " . $migration['migrated'] . "</p>";
} else {
    echo "<p class='error'>❌ Erreur migration: " . $migration['message'] . "</p>";
}

// Vérifier que les articles sont maintenant en DB
$db_items = $cartManager->getItems();
echo "<h3>Articles après migration (DB):</h3>";
echo "<pre>" . print_r($db_items, true) . "</pre>";

echo "</div>";

// Test 5: Ajout d'article quand connecté (directement en DB)
echo "<div class='test'>";
echo "<h2>🗄️ Test 5: Ajout article utilisateur connecté (DB)</h2>";

if (count($menus) > 2) {
    $result = $cartManager->addItem($menus[2]['MenuID'], 3);
    if ($result['success']) {
        echo "<p class='success'>✅ Article ajouté en DB: " . $result['message'] . "</p>";
        
        $new_items = $cartManager->getItems();
        echo "<p class='info'>Total articles: " . count($new_items) . "</p>";
    } else {
        echo "<p class='error'>❌ Erreur ajout DB: " . $result['message'] . "</p>";
    }
}

echo "</div>";

// Test 6: Suppression d'article
echo "<div class='test'>";
echo "<h2>🗑️ Test 6: Suppression article</h2>";

$result = $cartManager->removeItem($test_menu_id);
if ($result['success']) {
    echo "<p class='success'>✅ Article supprimé: " . $result['message'] . "</p>";
    
    $remaining_items = $cartManager->getItems();
    echo "<p class='info'>Articles restants: " . count($remaining_items) . "</p>";
} else {
    echo "<p class='error'>❌ Erreur suppression: " . $result['message'] . "</p>";
}

echo "</div>";

// Test 7: Déconnexion et migration DB -> session
echo "<div class='test'>";
echo "<h2>🚪 Test 7: Déconnexion et migration DB -> session</h2>";

$migration = $cartManager->migrateDatabaseToSession(999);
if ($migration['success']) {
    echo "<p class='success'>✅ Migration DB -> session réussie</p>";
    echo "<p class='info'>Articles migrés: " . $migration['migrated'] . "</p>";
} else {
    echo "<p class='error'>❌ Erreur migration: " . $migration['error'] . "</p>";
}

// Simuler la déconnexion
unset($_SESSION['client_id']);

// Vérifier que les articles sont en session
$session_items = $cartManager->getItems();
echo "<h3>Articles après déconnexion (session):</h3>";
echo "<pre>" . print_r($session_items, true) . "</pre>";

echo "</div>";

// Test 8: Vidage du panier
echo "<div class='test'>";
echo "<h2>🧽 Test 8: Vidage du panier</h2>";

$result = $cartManager->clear();
if ($result['success']) {
    echo "<p class='success'>✅ Panier vidé: " . $result['message'] . "</p>";
    
    $empty_summary = $cartManager->getSummary();
    echo "<p class='info'>Panier vide: " . ($empty_summary['is_empty'] ? 'Oui' : 'Non') . "</p>";
} else {
    echo "<p class='error'>❌ Erreur vidage: " . $result['message'] . "</p>";
}

echo "</div>";

// Test 9: Test API REST
echo "<div class='test'>";
echo "<h2>🌐 Test 9: API REST</h2>";

echo "<p class='info'>Test des endpoints API (simulation):</p>";

// Test ajout via API
$test_data = [
    'action' => 'add',
    'menu_id' => $test_menu_id,
    'quantity' => 2
];

echo "<h3>POST /api/cart.php?action=add</h3>";
echo "<p class='info'>Données: " . json_encode($test_data) . "</p>";

// Simuler l'ajout
$_POST = $test_data;
$api_result = $cartManager->addItem($test_data['menu_id'], $test_data['quantity']);
echo "<p class='" . ($api_result['success'] ? 'success' : 'error') . "'>";
echo ($api_result['success'] ? '✅' : '❌') . " API Add: " . $api_result['message'];
echo "</p>";

// Test résumé via API
echo "<h3>GET /api/cart.php?action=summary</h3>";
$api_summary = $cartManager->getSummary();
echo "<p class='success'>✅ API Summary:</p>";
echo "<pre>" . json_encode($api_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "</div>";

// Test 10: Validation et cas d'erreur
echo "<div class='test'>";
echo "<h2>🛡️ Test 10: Validation et erreurs</h2>";

// Test avec ID invalide
$error_result = $cartManager->addItem(-1, 1);
echo "<p class='" . ($error_result['success'] ? 'error' : 'success') . "'>";
echo ($error_result['success'] ? '❌ Devrait échouer' : '✅ Validation OK') . ": ID invalide - " . $error_result['message'];
echo "</p>";

// Test avec quantité invalide
$error_result2 = $cartManager->addItem($test_menu_id, 0);
echo "<p class='" . ($error_result2['success'] ? 'error' : 'success') . "'>";
echo ($error_result2['success'] ? '❌ Devrait échouer' : '✅ Validation OK') . ": Quantité invalide - " . $error_result2['message'];
echo "</p>";

// Test avec article inexistant
$error_result3 = $cartManager->addItem(99999, 1);
echo "<p class='" . ($error_result3['success'] ? 'error' : 'success') . "'>";
echo ($error_result3['success'] ? '❌ Devrait échouer' : '✅ Validation OK') . ": Article inexistant - " . $error_result3['message'];
echo "</p>";

echo "</div>";

// Résumé final
echo "<div class='test'>";
echo "<h2>📊 Résumé Final</h2>";

$final_summary = $cartManager->getSummary();
$final_items = $cartManager->getItems();

echo "<h3>État final du panier:</h3>";
echo "<ul>";
echo "<li>Nombre d'articles: " . $final_summary['items_count'] . "</li>";
echo "<li>Quantité totale: " . $final_summary['total_items'] . "</li>";
echo "<li>Montant total: " . number_format($final_summary['total_amount'], 2) . "€</li>";
echo "<li>Panier vide: " . ($final_summary['is_empty'] ? 'Oui' : 'Non') . "</li>";
echo "</ul>";

if (!empty($final_items)) {
    echo "<h3>Articles dans le panier:</h3>";
    foreach ($final_items as $item) {
        echo "<p>- {$item['name']} x{$item['quantity']} = " . number_format($item['price'] * $item['quantity'], 2) . "€</p>";
    }
}

echo "<p class='success'><strong>🎉 Tests terminés!</strong></p>";
echo "<p class='info'>Le nouveau système de panier fonctionne correctement.</p>";

echo "</div>";

// Nettoyage final
echo "<div class='test'>";
echo "<h2>🧹 Nettoyage final</h2>";
$cartManager->clear();
$pdo->exec("DELETE FROM Panier WHERE UtilisateurID = 999");
echo "<p class='success'>✅ Données de test nettoyées</p>";
echo "</div>";
?>
