<?php
require_once 'db_connexion.php';

echo "<h1>🧪 Test du processus complet : Panier → Commande</h1>\n";

// 1. Test de la base de données
echo "<h2>1. Vérification de la base de données</h2>\n";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'Commandes'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table Commandes existe<br>\n";
    } else {
        echo "❌ Table Commandes manquante<br>\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'DetailsCommande'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table DetailsCommande existe<br>\n";
    } else {
        echo "❌ Table DetailsCommande manquante<br>\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'Panier'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table Panier existe<br>\n";
    } else {
        echo "❌ Table Panier manquante<br>\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'Menu'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table Menu existe<br>\n";
    } else {
        echo "❌ Table Menu manquante<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur BDD : " . $e->getMessage() . "<br>\n";
}

// 2. Test du schéma de la table Commandes
echo "<h2>2. Vérification du schéma Commandes</h2>\n";

try {
    $stmt = $pdo->query("DESCRIBE Commandes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['ID', 'ClientID', 'DateCommande', 'MontantTotal', 'AdresseLivraison', 'Statut'];
    
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Colonne $col existe<br>\n";
        } else {
            echo "❌ Colonne $col manquante<br>\n";
        }
    }
    
    echo "<strong>Colonnes existantes :</strong> " . implode(', ', $columns) . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ Erreur schéma : " . $e->getMessage() . "<br>\n";
}

// 3. Test d'un produit du menu
echo "<h2>3. Test des produits du menu</h2>\n";

try {
    $stmt = $pdo->query("SELECT * FROM Menu LIMIT 1");
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produit) {
        echo "✅ Produit trouvé : <strong>" . htmlspecialchars($produit['Nom'] ?? 'Sans nom') . "</strong><br>\n";
        echo "Prix : " . ($produit['Prix'] ?? 'N/A') . "€<br>\n";
        echo "ID : " . ($produit['ID'] ?? 'N/A') . "<br>\n";
    } else {
        echo "❌ Aucun produit dans le menu<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur produits : " . $e->getMessage() . "<br>\n";
}

// 4. Simulation d'ajout au panier
echo "<h2>4. Simulation d'ajout au panier</h2>\n";

session_start();

try {
    // Créer un panier fictif
    $_SESSION['panier'] = [
        1 => [
            'nom' => 'Burger Test',
            'prix' => 12.50,
            'quantite' => 2,
            'description' => 'Burger de test',
            'image' => 'test.jpg'
        ],
        2 => [
            'nom' => 'Frites Test',
            'prix' => 4.50,
            'quantite' => 1,
            'description' => 'Frites de test',
            'image' => 'frites.jpg'
        ]
    ];
    
    $total = 0;
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['prix'] * $item['quantite'];
    }
    
    echo "✅ Panier créé avec " . count($_SESSION['panier']) . " articles<br>\n";
    echo "💰 Total panier : " . number_format($total, 2) . "€<br>\n";
    
} catch (Exception $e) {
    echo "❌ Erreur panier : " . $e->getMessage() . "<br>\n";
}

// 5. Test de création de commande
echo "<h2>5. Test de création de commande</h2>\n";

try {
    // Données de test
    $client_id = 999; // ID fictif
    $nom = "Test Client";
    $email = "test@example.com";
    $telephone = "0123456789";
    $mode_livraison = "retrait";
    $adresse = "Restaurant - Retrait sur place";
    $mode_paiement = "especes";
    $instructions = "Commande de test";
    $montant_total = $total;
    
    // Insertion de la commande
    $stmt = $pdo->prepare("
        INSERT INTO Commandes (ClientID, DateCommande, MontantTotal, AdresseLivraison, Statut, ModePaiement) 
        VALUES (?, NOW(), ?, ?, 'En attente', ?)
    ");
    
    $result = $stmt->execute([$client_id, $montant_total, $adresse, $mode_paiement]);
    
    if ($result) {
        $commande_id = $pdo->lastInsertId();
        echo "✅ Commande créée avec ID : <strong>$commande_id</strong><br>\n";
        
        // Insertion des détails
        $details_ok = 0;
        foreach ($_SESSION['panier'] as $produit_id => $item) {
            $stmt_detail = $pdo->prepare("
                INSERT INTO DetailsCommande (CommandeID, ProduitID, Quantite, PrixUnitaire) 
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt_detail->execute([$commande_id, $produit_id, $item['quantite'], $item['prix']])) {
                $details_ok++;
            }
        }
        
        echo "✅ Détails ajoutés : $details_ok/" . count($_SESSION['panier']) . " articles<br>\n";
        
        // Vérification de la commande créée
        $stmt_check = $pdo->prepare("
            SELECT c.*, COUNT(d.ID) as nb_details 
            FROM Commandes c 
            LEFT JOIN DetailsCommande d ON c.ID = d.CommandeID 
            WHERE c.ID = ? 
            GROUP BY c.ID
        ");
        $stmt_check->execute([$commande_id]);
        $commande = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($commande) {
            echo "<strong>📋 Résumé de la commande :</strong><br>\n";
            echo "ID : " . $commande['ID'] . "<br>\n";
            echo "Date : " . $commande['DateCommande'] . "<br>\n";
            echo "Montant : " . $commande['MontantTotal'] . "€<br>\n";
            echo "Statut : " . $commande['Statut'] . "<br>\n";
            echo "Articles : " . $commande['nb_details'] . "<br>\n";
        }
        
    } else {
        echo "❌ Échec de création de commande<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur création commande : " . $e->getMessage() . "<br>\n";
}

// 6. Nettoyage (suppression de la commande de test)
echo "<h2>6. Nettoyage</h2>\n";

try {
    if (isset($commande_id)) {
        $pdo->prepare("DELETE FROM DetailsCommande WHERE CommandeID = ?")->execute([$commande_id]);
        $pdo->prepare("DELETE FROM Commandes WHERE ID = ?")->execute([$commande_id]);
        echo "✅ Commande de test supprimée<br>\n";
    }
    
    // Vider le panier de test
    unset($_SESSION['panier']);
    echo "✅ Panier de test vidé<br>\n";
    
} catch (Exception $e) {
    echo "❌ Erreur nettoyage : " . $e->getMessage() . "<br>\n";
}

echo "<h2>🎯 Conclusion</h2>\n";
echo "<p><strong>Le système de commande semble " . (isset($commande_id) ? "✅ FONCTIONNEL" : "❌ PROBLÉMATIQUE") . "</strong></p>\n";

if (isset($commande_id)) {
    echo "<p>🎉 Votre système panier → commande fonctionne correctement !</p>\n";
    echo "<p>📝 Prochaines étapes recommandées :</p>\n";
    echo "<ul>\n";
    echo "<li>Tester avec de vrais produits sur votre site</li>\n";
    echo "<li>Vérifier l'interface utilisateur</li>\n";
    echo "<li>Tester les notifications et confirmations</li>\n";
    echo "<li>Valider les différents modes de paiement</li>\n";
    echo "</ul>\n";
} else {
    echo "<p>⚠️ Il y a encore des problèmes à résoudre.</p>\n";
}
?>
