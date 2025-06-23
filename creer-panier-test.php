<?php
session_start();
require_once 'db_connexion.php';

echo "<h1>🛒 Création d'un Panier de Test</h1>\n";

// Récupérer quelques produits du menu
try {
    $stmt = $pdo->query("SELECT MenuID, NomItem, Prix, Description FROM Menus LIMIT 3");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($menus)) {
        echo "❌ Aucun menu trouvé dans la base<br>\n";
        exit;
    }
    
    echo "<h2>Menus disponibles:</h2>\n";
    foreach ($menus as $menu) {
        echo "- " . $menu['NomItem'] . " (" . $menu['MenuID'] . ") - " . $menu['Prix'] . "€<br>\n";
    }
    
    // Créer un panier de session
    $_SESSION['panier'] = [];
    foreach ($menus as $i => $menu) {
        $_SESSION['panier'][$menu['MenuID']] = [
            'MenuID' => $menu['MenuID'],
            'NomItem' => $menu['NomItem'],
            'Prix' => floatval($menu['Prix']),
            'Quantite' => $i + 1,
            'Description' => $menu['Description'] ?? 'Délicieux plat'
        ];
    }
    
    echo "<h2>✅ Panier créé avec " . count($_SESSION['panier']) . " articles</h2>\n";
    
    // Afficher le contenu du panier
    $total = 0;
    foreach ($_SESSION['panier'] as $id => $item) {
        $sous_total = $item['Prix'] * $item['Quantite'];
        $total += $sous_total;
        echo "- " . $item['NomItem'] . " x" . $item['Quantite'] . " = " . number_format($sous_total, 2) . "€<br>\n";
    }
    
    echo "<strong>Total: " . number_format($total, 2) . "€</strong><br>\n";
    
    echo "<h2>🔗 Prochaines étapes</h2>\n";
    echo "<p>1. Ouvrez <strong>panier.php</strong> dans votre navigateur</p>\n";
    echo "<p>2. Vous devriez voir les articles de test</p>\n";
    echo "<p>3. Testez le processus de commande</p>\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>\n";
}
?>
