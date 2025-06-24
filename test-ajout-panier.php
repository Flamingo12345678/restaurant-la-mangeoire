<?php
/**
 * Test du système d'ajout au panier
 * Ce script simule l'ajout d'un article au panier et vérifie le fonctionnement
 */

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "<h1>Test du système d'ajout au panier</h1>";

// Test 1: Vérifier la connexion à la base
echo "<h2>1. Test de connexion à la base de données</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Connexion à la base OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Vérifier que la table Menus existe et contient des données
echo "<h2>2. Test de la table Menus</h2>";
try {
    $stmt = $pdo->query("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 5");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($menus)) {
        echo "⚠️ La table Menus est vide<br>";
    } else {
        echo "✅ Table Menus OK, " . count($menus) . " articles trouvés:<br>";
        foreach ($menus as $menu) {
            echo "- ID: {$menu['MenuID']}, Nom: {$menu['NomItem']}, Prix: {$menu['Prix']}€<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur lecture table Menus: " . $e->getMessage() . "<br>";
}

// Test 3: Vérifier que la table Panier existe
echo "<h2>3. Test de la table Panier</h2>";
try {
    $stmt = $pdo->query("DESCRIBE Panier");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Table Panier OK, colonnes disponibles:<br>";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur avec la table Panier: " . $e->getMessage() . "<br>";
}

// Test 4: Simuler l'ajout d'un article au panier (session)
echo "<h2>4. Test d'ajout au panier (session)</h2>";
session_start();

try {
    $cartManager = new CartManager($pdo);
    
    // Prendre le premier article du menu pour le test
    $stmt = $pdo->query("SELECT MenuID FROM Menus LIMIT 1");
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($menu) {
        $result = $cartManager->addItem($menu['MenuID'], 2);
        
        if ($result['success']) {
            echo "✅ Ajout au panier réussi: " . $result['message'] . "<br>";
            
            // Vérifier le contenu du panier
            $items = $cartManager->getItems();
            echo "📦 Contenu du panier:<br>";
            foreach ($items as $item) {
                echo "- {$item['name']} x{$item['quantity']} = {$item['price']}€<br>";
            }
            
            $summary = $cartManager->getSummary();
            echo "💰 Total: {$summary['total_amount']}€ ({$summary['total_items']} articles)<br>";
            
        } else {
            echo "❌ Erreur ajout au panier: " . $result['message'] . "<br>";
        }
    } else {
        echo "⚠️ Aucun article trouvé dans la table Menus pour le test<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "<br>";
}

// Test 5: Simuler une requête AJAX comme celle du menu.php
echo "<h2>5. Test de la requête AJAX simulate</h2>";

// Simuler les paramètres POST
$_POST['menu_id'] = 1;
$_POST['quantity'] = 1;
$_POST['ajax'] = true;

try {
    // Exécuter le code de ajouter-au-panier.php
    ob_start(); // Capturer la sortie
    
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $ajax = filter_input(INPUT_POST, 'ajax', FILTER_VALIDATE_BOOLEAN);
    
    $cartManager = new CartManager($pdo);
    $result = $cartManager->addItem($menu_id, $quantity);
    
    $response = [
        'success' => $result['success'],
        'message' => $result['message'] ?? ($result['success'] ? 'Article ajouté' : 'Erreur')
    ];
    
    if ($response['success']) {
        echo "✅ Simulation AJAX réussie: " . $response['message'] . "<br>";
    } else {
        echo "❌ Simulation AJAX échouée: " . $response['message'] . "<br>";
    }
    
    ob_end_clean(); // Nettoyer le buffer
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Erreur simulation AJAX: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Tests terminés</h2>";
echo "<p><a href='menu.php'>← Retour au menu</a></p>";
?>
