<?php
session_start();
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Commande</title></head><body>";
echo "<h2>Diagnostic du problème de commande</h2>";

// Vérifier CartManager
try {
    $cartManager = new CartManager($pdo);
    $cartSummary = $cartManager->getSummary();
    $cart_items = $cartManager->getItems();
    
    echo "<h3>État du panier :</h3>";
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
    } else {
        echo "<p style='color: red;'>⚠️ Le panier est vide ! Vous devez d'abord ajouter des articles.</p>";
        echo "<p><a href='index.php#menu'>Aller au menu pour ajouter des articles</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur CartManager :</strong> " . $e->getMessage() . "</p>";
}

// Vérifier la méthode POST
echo "<h3>Données POST :</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<p>Aucune données POST (méthode: " . $_SERVER['REQUEST_METHOD'] . ")</p>";
}

// Formulaire de test simple
echo "<h3>Test de soumission :</h3>";
echo '<form method="POST" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <input type="hidden" name="passer_commande" value="1">
    <p><label>Nom: <input type="text" name="nom" value="Test" required></label></p>
    <p><label>Prénom: <input type="text" name="prenom" value="User" required></label></p>
    <p><label>Téléphone: <input type="tel" name="telephone" value="0123456789" required></label></p>
    <p><label>Email: <input type="email" name="email" value="test@example.com" required></label></p>
    <p><label>Mode livraison: 
        <select name="mode_livraison">
            <option value="retrait">Retrait</option>
            <option value="livraison">Livraison</option>
        </select>
    </label></p>
    <p><label>Mode paiement: 
        <select name="mode_paiement">
            <option value="especes">Espèces</option>
            <option value="carte_bancaire">Carte bancaire</option>
        </select>
    </label></p>
    <p><input type="submit" value="Test de commande" style="background: green; color: white; padding: 10px 20px; border: none; cursor: pointer;"></p>
</form>';

echo "</body></html>";
?>
