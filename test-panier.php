<?php
session_start();
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Panier</title></head><body>";
echo "<h2>Test d'ajout au panier</h2>";

$cartManager = new CartManager($pdo);

// Ajouter un article de test
if (isset($_GET['add_test'])) {
    try {
        // Récupérer un article du menu
        $stmt = $pdo->query("SELECT * FROM Menu LIMIT 1");
        $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($menuItem) {
            $cartManager->addItem($menuItem['MenuID'], 1);
            echo "<p style='color: green;'>✅ Article ajouté au panier : " . $menuItem['NomPlat'] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Aucun article trouvé dans le menu</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
}

// Afficher l'état du panier
$cartSummary = $cartManager->getSummary();
$cart_items = $cartManager->getItems();

echo "<h3>État actuel du panier :</h3>";
echo "<p><strong>Vide :</strong> " . ($cartSummary['is_empty'] ? 'OUI' : 'NON') . "</p>";
echo "<p><strong>Nombre d'articles :</strong> " . $cartSummary['total_items'] . "</p>";
echo "<p><strong>Total :</strong> " . $cartSummary['total_amount'] . " €</p>";

if (!empty($cart_items)) {
    echo "<h4>Articles dans le panier :</h4>";
    echo "<ul>";
    foreach ($cart_items as $item) {
        echo "<li>{$item['name']} - Quantité: {$item['quantity']} - Prix: {$item['price']} €</li>";
    }
    echo "</ul>";
}

echo "<h3>Actions :</h3>";
echo "<p><a href='?add_test=1' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Ajouter un article de test</a></p>";
echo "<p><a href='passer-commande.php' style='background: green; color: white; padding: 10px; text-decoration: none;'>Aller à la commande</a></p>";

// Afficher quelques articles du menu
echo "<h3>Articles disponibles dans le menu :</h3>";
try {
    $stmt = $pdo->query("SELECT MenuID, NomPlat, Prix FROM Menu LIMIT 5");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Prix</th><th>Action</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['MenuID'] . "</td>";
        echo "<td>" . $row['NomPlat'] . "</td>";
        echo "<td>" . $row['Prix'] . " €</td>";
        echo "<td><a href='?add_item=" . $row['MenuID'] . "'>Ajouter</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur menu : " . $e->getMessage() . "</p>";
}

// Ajouter un article spécifique
if (isset($_GET['add_item'])) {
    $menuId = (int)$_GET['add_item'];
    try {
        $cartManager->addItem($menuId, 1);
        echo "<script>alert('Article ajouté !'); window.location.href='test-panier.php';</script>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur ajout : " . $e->getMessage() . "</p>";
    }
}

echo "</body></html>";
?>
