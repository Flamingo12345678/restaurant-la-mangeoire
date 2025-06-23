<?php
session_start();
require_once 'db_connexion.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'><head><meta charset='UTF-8'><title>Test Système de Commandes</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
    .success { border-left-color: #28a745; background: #d4edda; }
    .error { border-left-color: #dc3545; background: #f8d7da; }
    .warning { border-left-color: #ffc107; background: #fff3cd; }
    .btn { padding: 8px 16px; margin: 5px; text-decoration: none; background: #007bff; color: white; border-radius: 3px; display: inline-block; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f2f2f2; }
</style></head><body>";

echo "<h1>🧪 Test du Système de Commandes - La Mangeoire</h1>";

// Test 1: Vérification des tables
echo "<div class='test-section'>";
echo "<h3>📋 Test 1: Vérification des tables</h3>";
$tables = ['Commandes', 'DetailsCommande', 'Panier', 'Menus'];
$all_tables_ok = true;

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "<p style='color:green'>✓ Table $table: OK</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Table $table: ERREUR - " . $e->getMessage() . "</p>";
        $all_tables_ok = false;
    }
}

if ($all_tables_ok) {
    echo "<p><strong>✅ Toutes les tables sont présentes</strong></p>";
} else {
    echo "<p><strong>❌ Certaines tables sont manquantes</strong></p>";
}
echo "</div>";

// Test 2: Vérification du panier
echo "<div class='test-section'>";
echo "<h3>🛒 Test 2: État du panier</h3>";

$cart_items = [];
$total = 0;

if (isset($_SESSION['client_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, m.NomItem, m.Prix 
            FROM Panier p 
            JOIN Menus m ON p.MenuID = m.MenuID 
            WHERE p.UtilisateurID = ?
        ");
        $stmt->execute([$_SESSION['client_id']]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color:green'>✓ Utilisateur connecté (ID: " . $_SESSION['client_id'] . ")</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erreur panier DB: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠ Utilisateur non connecté</p>";
    if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
        $cart_items = $_SESSION['panier'];
        echo "<p>Panier en session</p>";
    }
}

if (!empty($cart_items)) {
    echo "<p style='color:green'>✓ Panier contient " . count($cart_items) . " article(s)</p>";
    echo "<table>";
    echo "<tr><th>Article</th><th>Quantité</th><th>Prix unitaire</th><th>Sous-total</th></tr>";
    foreach ($cart_items as $item) {
        $sous_total = $item['Prix'] * $item['Quantite'];
        $total += $sous_total;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['NomItem']) . "</td>";
        echo "<td>" . $item['Quantite'] . "</td>";
        echo "<td>" . number_format($item['Prix'], 2, ',', ' ') . " €</td>";
        echo "<td>" . number_format($sous_total, 2, ',', ' ') . " €</td>";
        echo "</tr>";
    }
    echo "<tr style='font-weight:bold'>";
    echo "<td colspan='3'>TOTAL</td>";
    echo "<td>" . number_format($total, 2, ',', ' ') . " €</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠ Panier vide</p>";
    echo "<p>Pour tester les commandes, ajoutez d'abord des articles au panier depuis le menu.</p>";
}
echo "</div>";

// Test 3: Articles disponibles dans le menu
echo "<div class='test-section'>";
echo "<h3>🍽️ Test 3: Articles du menu</h3>";
try {
    $stmt = $pdo->query("SELECT MenuID, NomItem, Prix FROM Menus ORDER BY NomItem");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($menus)) {
        echo "<p style='color:green'>✓ " . count($menus) . " articles disponibles</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prix</th><th>Action</th></tr>";
        foreach ($menus as $menu) {
            echo "<tr>";
            echo "<td>" . $menu['MenuID'] . "</td>";
            echo "<td>" . htmlspecialchars($menu['NomItem']) . "</td>";
            echo "<td>" . number_format($menu['Prix'], 2, ',', ' ') . " €</td>";
            echo "<td>";
            echo "<form method='post' action='ajouter-au-panier.php' style='display:inline'>";
            echo "<input type='hidden' name='menu_id' value='" . $menu['MenuID'] . "'>";
            echo "<input type='hidden' name='action' value='add'>";
            echo "<input type='hidden' name='quantite' value='1'>";
            echo "<button type='submit' style='background:#28a745;color:white;border:none;padding:5px 10px;border-radius:3px'>Ajouter</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>✗ Aucun article dans le menu</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur menu: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Historique des commandes
echo "<div class='test-section'>";
echo "<h3>📋 Test 4: Historique des commandes</h3>";
try {
    $stmt = $pdo->query("
        SELECT c.CommandeID, c.NomClient, c.PrenomClient, c.MontantTotal, 
               c.StatutCommande, c.DateCommande, c.NumeroSuivi,
               COUNT(d.DetailID) as NbArticles
        FROM Commandes c
        LEFT JOIN DetailsCommande d ON c.CommandeID = d.CommandeID
        GROUP BY c.CommandeID
        ORDER BY c.DateCommande DESC
        LIMIT 10
    ");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($commandes)) {
        echo "<p style='color:green'>✓ " . count($commandes) . " commandes récentes trouvées</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Client</th><th>Articles</th><th>Total</th><th>Statut</th><th>Date</th><th>N° Suivi</th></tr>";
        foreach ($commandes as $cmd) {
            echo "<tr>";
            echo "<td>" . $cmd['CommandeID'] . "</td>";
            echo "<td>" . htmlspecialchars($cmd['PrenomClient'] . ' ' . $cmd['NomClient']) . "</td>";
            echo "<td>" . $cmd['NbArticles'] . "</td>";
            echo "<td>" . number_format($cmd['MontantTotal'], 2, ',', ' ') . " €</td>";
            echo "<td>" . $cmd['StatutCommande'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($cmd['DateCommande'])) . "</td>";
            echo "<td>" . ($cmd['NumeroSuivi'] ?: 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Aucune commande trouvée</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur commandes: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Links et actions
echo "<div class='test-section'>";
echo "<h3>🔗 Test 5: Actions disponibles</h3>";
echo "<p>Testez les différentes pages :</p>";
echo "<a href='panier.php' class='btn'>📋 Voir le panier</a>";
echo "<a href='passer-commande.php' class='btn'>🛒 Passer commande</a>";
echo "<a href='index.php#menu' class='btn'>🍽️ Voir le menu</a>";
echo "<a href='diagnostic-panier-complet.php' class='btn'>🔍 Diagnostic panier</a>";
echo "<br><br>";

if (empty($cart_items)) {
    echo "<div style='background:#fff3cd;padding:10px;border-radius:5px;border-left:4px solid #ffc107'>";
    echo "<strong>💡 Pour tester les commandes :</strong>";
    echo "<ol>";
    echo "<li>Ajoutez des articles au panier depuis le tableau ci-dessus</li>";
    echo "<li>Allez sur la page 'Passer commande'</li>";
    echo "<li>Remplissez le formulaire de commande</li>";
    echo "<li>Confirmez la commande</li>";
    echo "</ol>";
    echo "</div>";
}
echo "</div>";

// Test 6: Vérification des permissions
echo "<div class='test-section'>";
echo "<h3>🔒 Test 6: État de la session</h3>";
echo "<ul>";
foreach ($_SESSION as $key => $value) {
    if (is_string($value)) {
        echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
    } else if (is_array($value)) {
        echo "<li><strong>$key:</strong> Array (" . count($value) . " éléments)</li>";
    } else {
        echo "<li><strong>$key:</strong> " . gettype($value) . "</li>";
    }
}
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Test effectué le " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>
