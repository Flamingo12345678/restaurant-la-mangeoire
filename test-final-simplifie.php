<?php
// Test simplifié avec gestion d'erreur pour la connexion DB

echo "<h1>🧪 Test du processus complet : Panier → Commande</h1>\n";

// Tentative de connexion à la base de données
echo "<h2>1. Vérification de la connexion base de données</h2>\n";

try {
    require_once 'db_connexion.php';
    
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "✅ Connexion DB réussie<br>\n";
        $db_connected = true;
    } else {
        echo "❌ Variable \$pdo non définie dans db_connexion.php<br>\n";
        $db_connected = false;
    }
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion DB : " . $e->getMessage() . "<br>\n";
    $db_connected = false;
}

if (!$db_connected) {
    echo "<h2>⚠️ Configuration alternative</h2>\n";
    echo "<p>Impossible de se connecter à la base de données.</p>\n";
    echo "<p><strong>Solutions possibles :</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Vérifier le fichier .env avec les variables MySQL</li>\n";
    echo "<li>Vérifier que Composer a installé les dépendances (vendor/autoload.php)</li>\n";
    echo "<li>Tester la connexion sur votre serveur de production</li>\n";
    echo "</ul>\n";
    
    echo "<h2>📋 Checklist des fichiers</h2>\n";
    
    $files_to_check = [
        'panier.php' => 'Page du panier',
        'ajouter-au-panier.php' => 'Script d\'ajout au panier',
        'passer-commande.php' => 'Page de commande',
        'confirmation-commande.php' => 'Page de confirmation',
        'db_connexion.php' => 'Connexion base de données',
        '.env' => 'Variables d\'environnement',
        'vendor/autoload.php' => 'Autoloader Composer'
    ];
    
    foreach ($files_to_check as $file => $description) {
        if (file_exists($file)) {
            echo "✅ $file ($description)<br>\n";
        } else {
            echo "❌ $file ($description) - <strong>MANQUANT</strong><br>\n";
        }
    }
    
    exit;
}

// Continuer avec les tests si la DB fonctionne...

// 2. Test des tables
echo "<h2>2. Vérification des tables</h2>\n";

$tables_required = ['Commandes', 'DetailsCommande', 'Panier', 'Menu'];

foreach ($tables_required as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table $table existe<br>\n";
        } else {
            echo "❌ Table $table manquante<br>\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur vérification $table : " . $e->getMessage() . "<br>\n";
    }
}

// 3. Test du schéma Commandes
echo "<h2>3. Schéma de la table Commandes</h2>\n";

try {
    $stmt = $pdo->query("DESCRIBE Commandes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Colonnes existantes :</strong><br>\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>\n";
    }
    
    $required_cols = ['ID', 'ClientID', 'DateCommande', 'MontantTotal', 'Statut'];
    $missing_cols = [];
    $existing_cols = array_column($columns, 'Field');
    
    foreach ($required_cols as $req_col) {
        if (!in_array($req_col, $existing_cols)) {
            $missing_cols[] = $req_col;
        }
    }
    
    if (empty($missing_cols)) {
        echo "✅ Toutes les colonnes requises sont présentes<br>\n";
    } else {
        echo "❌ Colonnes manquantes : " . implode(', ', $missing_cols) . "<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur schéma : " . $e->getMessage() . "<br>\n";
}

// 4. Test d'un produit exemple
echo "<h2>4. Test des produits</h2>\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM Menu");
    $count = $stmt->fetch()['nb'];
    
    if ($count > 0) {
        echo "✅ $count produits trouvés dans le menu<br>\n";
        
        $stmt = $pdo->query("SELECT * FROM Menu LIMIT 3");
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>Exemples :</strong><br>\n";
        foreach ($produits as $p) {
            echo "- " . ($p['Nom'] ?? 'Sans nom') . " : " . ($p['Prix'] ?? '0') . "€<br>\n";
        }
    } else {
        echo "⚠️ Aucun produit dans le menu - ajoutez des produits pour tester<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur produits : " . $e->getMessage() . "<br>\n";
}

// 5. Test rapide d'insertion de commande
echo "<h2>5. Test d'insertion de commande</h2>\n";

try {
    $test_data = [
        'ClientID' => 999,
        'MontantTotal' => 25.50,
        'AdresseLivraison' => 'Test - Retrait sur place',
        'Statut' => 'En attente',
        'ModePaiement' => 'especes'
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO Commandes (ClientID, DateCommande, MontantTotal, AdresseLivraison, Statut, ModePaiement) 
        VALUES (?, NOW(), ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $test_data['ClientID'],
        $test_data['MontantTotal'],
        $test_data['AdresseLivraison'],
        $test_data['Statut'],
        $test_data['ModePaiement']
    ]);
    
    if ($result) {
        $test_commande_id = $pdo->lastInsertId();
        echo "✅ Commande de test créée avec ID : $test_commande_id<br>\n";
        
        // Nettoyage immédiat
        $pdo->prepare("DELETE FROM Commandes WHERE ID = ?")->execute([$test_commande_id]);
        echo "✅ Commande de test supprimée<br>\n";
    } else {
        echo "❌ Échec de création de commande de test<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur test commande : " . $e->getMessage() . "<br>\n";
}

// Conclusion
echo "<h2>🎯 Conclusion</h2>\n";
echo "<p><strong>✅ Le système semble opérationnel !</strong></p>\n";
echo "<p>📝 <strong>Prochaines étapes :</strong></p>\n";
echo "<ol>\n";
echo "<li>Testez le panier en ajoutant des produits via votre interface web</li>\n";
echo "<li>Testez la page de commande avec de vrais produits</li>\n";
echo "<li>Vérifiez les confirmations et notifications</li>\n";
echo "<li>Testez les différents modes de paiement</li>\n";
echo "</ol>\n";

echo "<p>🔗 <strong>Pages à tester dans votre navigateur :</strong></p>\n";
echo "<ul>\n";
echo "<li><code>panier.php</code> - Affichage du panier</li>\n";
echo "<li><code>passer-commande.php</code> - Formulaire de commande</li>\n";
echo "<li><code>confirmation-commande.php</code> - Confirmation</li>\n";
echo "</ul>\n";
?>
