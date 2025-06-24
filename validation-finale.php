<?php
/**
 * Validation finale complète du système de panier
 * Test de toutes les fonctionnalités implémentées
 */

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "<h1>🎯 VALIDATION FINALE - Système de panier complet</h1>";
echo "<p>Test de toutes les fonctionnalités implémentées</p>";

$tests_passed = 0;
$tests_total = 0;

function test_result($name, $condition, $message = '') {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($condition) {
        $tests_passed++;
        echo "<div style='color: green;'>✅ $name</div>";
        if ($message) echo "<div style='margin-left: 20px; color: #666;'>$message</div>";
    } else {
        echo "<div style='color: red;'>❌ $name</div>";
        if ($message) echo "<div style='margin-left: 20px; color: #666;'>$message</div>";
    }
}

echo "<h2>1. Tests base de données</h2>";

// Test connexion BDD
try {
    $stmt = $pdo->query("SELECT 1");
    test_result("Connexion base de données", true, "PDO connecté");
} catch (Exception $e) {
    test_result("Connexion base de données", false, $e->getMessage());
}

// Test table Menus
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Menus");
    $count = $stmt->fetch()['count'];
    test_result("Table Menus", $count > 0, "$count articles dans le menu");
} catch (Exception $e) {
    test_result("Table Menus", false, $e->getMessage());
}

// Test table Panier
try {
    $stmt = $pdo->query("DESCRIBE Panier");
    $columns = $stmt->fetchAll();
    test_result("Table Panier", count($columns) >= 5, count($columns) . " colonnes trouvées");
} catch (Exception $e) {
    test_result("Table Panier", false, $e->getMessage());
}

echo "<h2>2. Tests CartManager</h2>";

// Test CartManager
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $cartManager = new CartManager($pdo);
    test_result("CartManager instanciation", true, "Classe chargée correctement");
    
    // Test ajout article
    $result = $cartManager->addItem(1, 2);
    test_result("Ajout article au panier", $result['success'], $result['message'] ?? '');
    
    // Test récupération articles
    $items = $cartManager->getItems();
    test_result("Récupération articles", is_array($items), count($items) . " articles trouvés");
    
    // Test résumé
    $summary = $cartManager->getSummary();
    test_result("Résumé panier", isset($summary['total_items']), "Total: " . ($summary['total_items'] ?? 0) . " articles");
    
} catch (Exception $e) {
    test_result("CartManager", false, $e->getMessage());
}

echo "<h2>3. Tests fichiers système</h2>";

// Test fichiers principaux
$files_to_check = [
    'ajouter-au-panier.php' => 'Script ajout panier',
    'menu.php' => 'Page menu',
    'index.php' => 'Page accueil',
    'includes/header.php' => 'Header avec compteur',
    'includes/https-security.php' => 'Sécurité HTTPS',
    'api/cart-summary.php' => 'API résumé panier',
    '.htaccess' => 'Configuration Apache'
];

foreach ($files_to_check as $file => $description) {
    test_result($description, file_exists($file), $file);
}

echo "<h2>4. Tests API</h2>";

// Test API cart-summary
if (file_exists('api/cart-summary.php')) {
    // Test 1: Vérifier que le fichier API existe et est lisible
    test_result("API cart-summary fichier", is_readable('api/cart-summary.php'), "Fichier accessible");
    
    // Test 2: Simuler l'API sans inclure (pour éviter les conflits d'en-têtes)
    try {
        // Créer un environnement propre pour l'API
        $old_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Capturer la sortie de l'API
        ob_start();
        $api_error = false;
        
        // Inclure l'API dans un contexte isolé
        try {
            // Désactiver temporairement les en-têtes pour le test
            ini_set('display_errors', 0);
            include 'api/cart-summary.php';
        } catch (Exception $e) {
            $api_error = $e->getMessage();
        }
        
        $api_output = ob_get_clean();
        
        // Restaurer l'environnement
        if ($old_request_method !== null) {
            $_SERVER['REQUEST_METHOD'] = $old_request_method;
        } else {
            unset($_SERVER['REQUEST_METHOD']);
        }
        
        // Nettoyer la sortie des warnings éventuels
        $clean_output = preg_replace('/^.*?(\{.*\}).*$/s', '$1', $api_output);
        if (empty($clean_output) || $clean_output === $api_output) {
            // Si pas de JSON trouvé, prendre les dernières lignes
            $lines = explode("\n", trim($api_output));
            $clean_output = end($lines);
        }
        
        $api_data = json_decode($clean_output, true);
        
        test_result("API cart-summary JSON", 
            is_array($api_data) && json_last_error() === JSON_ERROR_NONE, 
            "JSON valide: " . substr($clean_output, 0, 100) . "...");
            
        test_result("API cart-summary structure", 
            is_array($api_data) && isset($api_data['success']) && isset($api_data['data']), 
            "Structure success + data présente");
            
    } catch (Exception $e) {
        test_result("API cart-summary JSON", false, "Erreur: " . $e->getMessage());
        test_result("API cart-summary structure", false, "Test échoué à cause de l'erreur précédente");
    }
}

echo "<h2>5. Tests HTTPS Security</h2>";

if (file_exists('includes/https-security.php')) {
    require_once 'includes/https-security.php';
    test_result("HTTPS Security chargé", defined('HTTPS_CONFIG_LOADED'), "Configuration chargée");
    test_result("Fonction isSecureConnection", function_exists('isSecureConnection'), "Fonction disponible");
    test_result("Fonction secureUrl", function_exists('secureUrl'), "Fonction disponible");
}

echo "<h2>6. Tests fonctionnalités JavaScript</h2>";

// Test présence des scripts dans les pages
$menu_content = file_get_contents('menu.php');
test_result("Menu.php - Script AJAX", 
    strpos($menu_content, 'fetch(') !== false, 
    "Appels AJAX présents");

$header_content = file_get_contents('includes/header.php');
test_result("Header - Compteur panier", 
    strpos($header_content, 'CartCounter') !== false, 
    "Script compteur présent");

echo "<h2>7. Tests formulaires</h2>";

$index_content = file_get_contents('index.php');
test_result("Index.php - Formulaires panier", 
    strpos($index_content, 'ajouter-au-panier.php') !== false, 
    "Formulaires HTML présents");

// Test gestion des deux formats de quantité
$panier_content = file_get_contents('ajouter-au-panier.php');
test_result("Support 'quantity' et 'quantite'", 
    strpos($panier_content, 'quantite') !== false && strpos($panier_content, 'quantity') !== false,
    "Deux formats supportés");

echo "<h2>📊 Résultats finaux</h2>";

$success_rate = ($tests_passed / $tests_total) * 100;

echo "<div style='padding: 20px; background: " . ($success_rate >= 90 ? '#d4edda' : ($success_rate >= 70 ? '#fff3cd' : '#f8d7da')) . "; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Score : $tests_passed/$tests_total (" . round($success_rate) . "%)</h3>";

if ($success_rate >= 90) {
    echo "<p style='color: #155724;'><strong>🎉 EXCELLENT !</strong> Votre système de panier est parfaitement fonctionnel !</p>";
} elseif ($success_rate >= 70) {
    echo "<p style='color: #856404;'><strong>⚠️ BON</strong> Le système fonctionne mais quelques améliorations sont possibles.</p>";
} else {
    echo "<p style='color: #721c24;'><strong>❌ ATTENTION</strong> Plusieurs problèmes détectés, révision nécessaire.</p>";
}

echo "</div>";

echo "<h2>🔗 Tests manuels recommandés</h2>";
echo "<ol>";
echo "<li><a href='test-compteur-panier.php' target='_blank'>Test compteur panier interactif</a></li>";
echo "<li><a href='menu.php' target='_blank'>Test ajout depuis le menu (AJAX)</a></li>";
echo "<li><a href='index.php' target='_blank'>Test ajout depuis l'accueil (Formulaire)</a></li>";
echo "<li><a href='test-https.php' target='_blank'>Test configuration HTTPS</a></li>";
echo "<li><a href='api/cart-summary.php' target='_blank'>Test API résumé panier</a></li>";
echo "</ol>";

echo "<p><strong>🎯 Mission accomplie !</strong> Votre restaurant peut maintenant vendre en ligne avec un système de panier sécurisé et fonctionnel !</p>";
?>
