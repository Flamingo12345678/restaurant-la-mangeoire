<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'><head><meta charset='UTF-8'><title>Debug Commande</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .debug{background:#f5f5f5;padding:15px;margin:10px 0;border-left:4px solid #007cba;} .error{border-left-color:#dc3545;background:#f8d7da;} .success{border-left-color:#28a745;background:#d4edda;}</style>";
echo "</head><body>";

echo "<h1>🔧 Debug Création de Commande</h1>";

// Test 1: Vérifier la structure de la table Commandes
echo "<div class='debug'>";
echo "<h3>📋 Test 1: Structure de la table Commandes</h3>";
try {
    $stmt = $pdo->query("DESCRIBE Commandes");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:green'>✓ Table Commandes accessible</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur table Commandes: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Vérifier la structure de la table DetailsCommande
echo "<div class='debug'>";
echo "<h3>📋 Test 2: Structure de la table DetailsCommande</h3>";
try {
    $stmt = $pdo->query("DESCRIBE DetailsCommande");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:green'>✓ Table DetailsCommande accessible</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur table DetailsCommande: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Simuler l'insertion d'une commande
echo "<div class='debug'>";
echo "<h3>🧪 Test 3: Simulation d'insertion de commande</h3>";

// Données de test
$test_data = [
    'user_id' => null, // Utilisateur non connecté
    'nom' => 'Test',
    'prenom' => 'Debug',
    'telephone' => '0123456789',
    'email' => 'test@debug.com',
    'adresse' => '123 Rue Test',
    'instructions' => 'Test debug',
    'mode_livraison' => 'livraison',
    'mode_paiement' => 'especes',
    'total' => 1000
];

try {
    // Test d'insertion de commande
    $sql = "
        INSERT INTO Commandes (
            UtilisateurID, NomClient, PrenomClient, TelephoneClient, EmailClient, 
            AdresseLivraison, InstructionsSpeciales, ModeLivraison, ModePaiement, 
            MontantTotal, StatutCommande, DateCommande
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente', NOW())
    ";
    
    echo "<p><strong>SQL à exécuter:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p><strong>Données:</strong></p>";
    echo "<pre>" . print_r($test_data, true) . "</pre>";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $test_data['user_id'],
        $test_data['nom'],
        $test_data['prenom'],
        $test_data['telephone'],
        $test_data['email'],
        $test_data['adresse'],
        $test_data['instructions'],
        $test_data['mode_livraison'],
        $test_data['mode_paiement'],
        $test_data['total']
    ]);
    
    if ($result) {
        $commande_id = $pdo->lastInsertId();
        echo "<p style='color:green'>✓ Commande test créée avec ID: $commande_id</p>";
        
        // Test d'insertion des détails
        echo "<h4>Test d'insertion des détails:</h4>";
        
        // Récupérer un menu pour le test
        $menu_stmt = $pdo->query("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 1");
        $menu = $menu_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($menu) {
            $detail_sql = "
                INSERT INTO DetailsCommande (CommandeID, MenuID, Quantite, PrixUnitaire, SousTotal)
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $detail_stmt = $pdo->prepare($detail_sql);
            $sous_total = $menu['Prix'] * 1;
            $detail_result = $detail_stmt->execute([
                $commande_id,
                $menu['MenuID'],
                1,
                $menu['Prix'],
                $sous_total
            ]);
            
            if ($detail_result) {
                echo "<p style='color:green'>✓ Détail de commande créé pour: " . $menu['NomItem'] . "</p>";
            } else {
                echo "<p style='color:red'>✗ Erreur création détail de commande</p>";
            }
        }
        
        // Nettoyer le test
        $pdo->exec("DELETE FROM DetailsCommande WHERE CommandeID = $commande_id");
        $pdo->exec("DELETE FROM Commandes WHERE CommandeID = $commande_id");
        echo "<p style='color:blue'>🧹 Données de test nettoyées</p>";
        
    } else {
        echo "<p style='color:red'>✗ Échec de l'insertion de commande</p>";
        echo "<pre>Error Info: " . print_r($stmt->errorInfo(), true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception lors du test: " . $e->getMessage() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// Test 4: Vérifier les données du panier actuel
echo "<div class='debug'>";
echo "<h3>🛒 Test 4: État du panier actuel</h3>";

if (isset($_SESSION['client_id'])) {
    echo "<p>Utilisateur connecté ID: " . $_SESSION['client_id'] . "</p>";
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, m.NomItem, m.Prix 
            FROM Panier p 
            JOIN Menus m ON p.MenuID = m.MenuID 
            WHERE p.UtilisateurID = ?
        ");
        $stmt->execute([$_SESSION['client_id']]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($cart_items)) {
            echo "<p style='color:green'>✓ Panier contient " . count($cart_items) . " articles</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Article</th><th>Prix</th><th>Quantité</th><th>Sous-total</th></tr>";
            foreach ($cart_items as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['NomItem']) . "</td>";
                echo "<td>" . $item['Prix'] . " XAF</td>";
                echo "<td>" . $item['Quantite'] . "</td>";
                echo "<td>" . ($item['Prix'] * $item['Quantite']) . " XAF</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange'>⚠ Panier vide en base de données</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erreur récupération panier: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Utilisateur non connecté</p>";
    if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
        echo "<p style='color:green'>✓ Panier session contient " . count($_SESSION['panier']) . " articles</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Article</th><th>Prix</th><th>Quantité</th><th>Sous-total</th></tr>";
        foreach ($_SESSION['panier'] as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['NomItem']) . "</td>";
            echo "<td>" . $item['Prix'] . " XAF</td>";
            echo "<td>" . $item['Quantite'] . "</td>";
            echo "<td>" . ($item['Prix'] * $item['Quantite']) . " XAF</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Panier session vide</p>";
    }
}
echo "</div>";

// Test 5: Vérifier les contraintes de base de données
echo "<div class='debug'>";
echo "<h3>🔗 Test 5: Contraintes de base de données</h3>";
try {
    // Vérifier les clés étrangères
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME IN ('Commandes', 'DetailsCommande', 'Panier')
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Colonne</th><th>Référence Table</th><th>Référence Colonne</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['TABLE_NAME'] . "</td>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur vérification contraintes: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='debug'>";
echo "<h3>🔧 Actions recommandées</h3>";
echo "<p>Si les tests ci-dessus révèlent des problèmes :</p>";
echo "<ul>";
echo "<li>Vérifiez que toutes les tables existent avec les bonnes colonnes</li>";
echo "<li>Vérifiez que les clés étrangères sont correctes</li>";
echo "<li>Testez avec des données simples</li>";
echo "<li>Vérifiez les logs d'erreur PHP</li>";
echo "</ul>";
echo "<a href='passer-commande.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:3px'>Retourner à la commande</a>";
echo "</div>";

echo "<hr>";
echo "<p><em>Debug effectué le " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>
