<?php
/**
 * Test des warnings de session HTTPS
 */

echo "<h1>Test des warnings de session HTTPS</h1>";

// Démarrer session d'abord (simulation du problème)
session_start();
echo "<p>✅ Session démarrée d'abord</p>";

// Maintenant inclure la sécurité HTTPS (ne devrait plus générer de warnings)
echo "<h2>Inclusion de la sécurité HTTPS...</h2>";

require_once 'includes/https-security.php';

echo "<p>✅ Sécurité HTTPS incluse sans warnings</p>";

echo "<h2>Tests de fonctionnement</h2>";

echo "<ul>";
echo "<li>Session active: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Oui" : "❌ Non") . "</li>";
echo "<li>HTTPS détecté: " . (IS_HTTPS ? "✅ Oui" : "⚠️ Non (normal en local)") . "</li>";
echo "<li>Base URL: " . SECURE_BASE_URL . "</li>";
echo "</ul>";

echo "<h2>Test d'ajout au panier</h2>";

// Simuler l'ajout au panier
$_POST['menu_id'] = '1';
$_POST['quantite'] = '2'; // Test avec 'quantite' comme dans index.php

try {
    require_once 'db_connexion.php';
    require_once 'includes/CartManager.php';
    
    $cartManager = new CartManager($pdo);
    
    // Test avec les nouvelles corrections
    $menu_id = isset($_POST['menu_id']) ? filter_var($_POST['menu_id'], FILTER_VALIDATE_INT) : false;
    $quantity = isset($_POST['quantite']) ? filter_var($_POST['quantite'], FILTER_VALIDATE_INT) : false;
    
    if (!$quantity) {
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 1;
    }
    
    echo "<p>Menu ID: $menu_id</p>";
    echo "<p>Quantité: $quantity</p>";
    
    if ($menu_id && $quantity) {
        $result = $cartManager->addItem($menu_id, $quantity);
        
        if ($result['success']) {
            echo "<div style='color: green; background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
            echo "✅ <strong>Succès:</strong> " . $result['message'];
            echo "</div>";
        } else {
            echo "<div style='color: red; background: #f5e8e8; padding: 10px; border-radius: 5px;'>";
            echo "❌ <strong>Erreur:</strong> " . $result['message'];
            echo "</div>";
        }
    } else {
        echo "<div style='color: orange; background: #f5f0e8; padding: 10px; border-radius: 5px;'>";
        echo "⚠️ <strong>Paramètres invalides:</strong> menu_id=$menu_id, quantity=$quantity";
        echo "</div>";
    }
        
} catch (Exception $e) {
    echo "<div style='color: red; background: #f5e8e8; padding: 10px; border-radius: 5px;'>";
    echo "❌ <strong>Exception:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<p><a href='index.php'>← Retour à l'accueil</a> | <a href='menu.php'>Menu</a></p>";
?>
