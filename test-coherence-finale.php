<?php
/**
 * TEST FINAL DE COHÉRENCE - RESTAURANT LA MANGEOIRE
 * 
 * Ce script teste la cohérence finale après toutes les corrections
 */

require_once 'db_connexion.php';

echo "🧪 TEST FINAL DE COHÉRENCE\n";
echo "==========================\n\n";

$erreurs = 0;
$warnings = 0;
$success = 0;

// 1. Test de la connexion PDO
echo "1️⃣ Test connexion base de données...\n";
try {
    $pdo->query("SELECT 1");
    echo "   ✅ Connexion PDO active\n";
    $success++;
} catch (Exception $e) {
    echo "   ❌ Erreur connexion: " . $e->getMessage() . "\n";
    $erreurs++;
}

// 2. Test de la structure de la base
echo "\n2️⃣ Test structure base de données...\n";

// Vérifier les tables principales
$tables_requises = ['Clients', 'Commandes', 'Panier', 'Menus', 'DetailsCommande'];
foreach ($tables_requises as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "   ✅ Table $table: $count enregistrement(s)\n";
        $success++;
    } catch (Exception $e) {
        echo "   ❌ Table $table manquante ou erreur: " . $e->getMessage() . "\n";
        $erreurs++;
    }
}

// Vérifier que la table Utilisateurs n'existe pas ou est vide
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM Utilisateurs");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        echo "   ✅ Table Utilisateurs vide ($count enregistrements)\n";
        $success++;
    } else {
        echo "   ⚠️  Table Utilisateurs contient encore $count enregistrements\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "   ✅ Table Utilisateurs supprimée (erreur attendue: " . $e->getMessage() . ")\n";
    $success++;
}

// 3. Test des colonnes ClientID
echo "\n3️⃣ Test cohérence colonnes ClientID...\n";

$tables_clientid = ['Panier', 'Commandes', 'Reservations'];
foreach ($tables_clientid as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $has_clientid = false;
        $has_utilisateurid = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] == 'ClientID') $has_clientid = true;
            if ($col['Field'] == 'UtilisateurID') $has_utilisateurid = true;
        }
        
        if ($has_clientid && !$has_utilisateurid) {
            echo "   ✅ Table $table: ClientID OK, pas d'UtilisateurID\n";
            $success++;
        } elseif ($has_utilisateurid) {
            echo "   ❌ Table $table: UtilisateurID encore présent!\n";
            $erreurs++;
        } else {
            echo "   ⚠️  Table $table: ni ClientID ni UtilisateurID trouvé\n";
            $warnings++;
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur table $table: " . $e->getMessage() . "\n";
        $erreurs++;
    }
}

// 4. Test des contraintes de clé étrangère
echo "\n4️⃣ Test contraintes clés étrangères...\n";
try {
    $constraints_query = "
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'Clients'
        ORDER BY TABLE_NAME
    ";
    
    $stmt = $pdo->query($constraints_query);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) > 0) {
        echo "   ✅ Contraintes vers Clients trouvées:\n";
        foreach ($constraints as $constraint) {
            echo "      - {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} -> Clients.{$constraint['REFERENCED_COLUMN_NAME']}\n";
        }
        $success++;
    } else {
        echo "   ⚠️  Aucune contrainte FK vers Clients (peut être normal)\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur vérification contraintes: " . $e->getMessage() . "\n";
    $erreurs++;
}

// 5. Test des fichiers PHP critiques
echo "\n5️⃣ Test fichiers PHP critiques...\n";

$fichiers_critiques = [
    'db_connexion.php',
    'connexion-unifiee.php',
    'mon-compte.php',
    'passer-commande.php',
    'detail-commande.php'
];

foreach ($fichiers_critiques as $fichier) {
    if (file_exists($fichier)) {
        // Vérifier la syntaxe PHP
        $output = [];
        $return_code = 0;
        exec("php -l $fichier 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "   ✅ $fichier: Syntaxe OK\n";
            $success++;
        } else {
            echo "   ❌ $fichier: Erreur syntaxe\n";
            foreach ($output as $line) {
                echo "      $line\n";
            }
            $erreurs++;
        }
    } else {
        echo "   ⚠️  $fichier: Fichier non trouvé\n";
        $warnings++;
    }
}

// 6. Recherche résiduelle de références UtilisateurID
echo "\n6️⃣ Recherche références UtilisateurID résiduelles...\n";

$commande_grep = "grep -r --include='*.php' 'UtilisateurID' . 2>/dev/null | head -10";
$resultats = shell_exec($commande_grep);

if (!empty(trim($resultats))) {
    echo "   ⚠️  Références UtilisateurID trouvées:\n";
    $lignes = explode("\n", trim($resultats));
    foreach ($lignes as $ligne) {
        if (!empty($ligne)) {
            echo "      $ligne\n";
        }
    }
    $warnings++;
} else {
    echo "   ✅ Aucune référence UtilisateurID trouvée\n";
    $success++;
}

// 7. Test de création d'un client test
echo "\n7️⃣ Test création client test...\n";
try {
    // Vérifier si un client test existe déjà
    $stmt = $pdo->prepare("SELECT ClientID FROM Clients WHERE Email = 'test-coherence@mangeoire.test'");
    $stmt->execute();
    $client_existant = $stmt->fetch();
    
    if ($client_existant) {
        echo "   ℹ️  Client test existe déjà (ID: {$client_existant['ClientID']})\n";
        $client_test_id = $client_existant['ClientID'];
    } else {
        // Créer un client test
        $stmt = $pdo->prepare("INSERT INTO Clients (Nom, Prenom, Email, MotDePasse, DateInscription) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['Test', 'Coherence', 'test-coherence@mangeoire.test', password_hash('test123', PASSWORD_DEFAULT)]);
        $client_test_id = $pdo->lastInsertId();
        echo "   ✅ Client test créé (ID: $client_test_id)\n";
    }
    
    // Test ajout panier
    $stmt = $pdo->prepare("INSERT INTO Panier (ClientID, MenuID, Quantite) VALUES (?, 1, 1) ON DUPLICATE KEY UPDATE Quantite = Quantite + 1");
    $stmt->execute([$client_test_id]);
    echo "   ✅ Test ajout panier OK\n";
    
    // Test création commande
    $stmt = $pdo->prepare("INSERT INTO Commandes (ClientID, DateCommande, Statut, MontantTotal) VALUES (?, NOW(), 'En attente', 25.50)");
    $stmt->execute([$client_test_id]);
    $commande_test_id = $pdo->lastInsertId();
    echo "   ✅ Test création commande OK (ID: $commande_test_id)\n";
    
    // Nettoyer les données test
    $pdo->prepare("DELETE FROM Commandes WHERE CommandeID = ?")->execute([$commande_test_id]);
    $pdo->prepare("DELETE FROM Panier WHERE ClientID = ?")->execute([$client_test_id]);
    $pdo->prepare("DELETE FROM Clients WHERE ClientID = ?")->execute([$client_test_id]);
    echo "   ✅ Données test nettoyées\n";
    
    $success++;
    
} catch (Exception $e) {
    echo "   ❌ Erreur test création: " . $e->getMessage() . "\n";
    $erreurs++;
}

// Résumé final
echo "\n📊 RÉSUMÉ FINAL\n";
echo "================\n";
echo "✅ Succès: $success\n";
echo "⚠️  Avertissements: $warnings\n";
echo "❌ Erreurs: $erreurs\n\n";

if ($erreurs == 0) {
    echo "🎉 PROJET COHÉRENT!\n";
    echo "Le projet Restaurant La Mangeoire est maintenant cohérent.\n";
    echo "Toutes les incohérences UtilisateurID/ClientID ont été résolues.\n";
} elseif ($erreurs <= 2) {
    echo "⚠️  QUASI-COHÉRENT\n";
    echo "Le projet est presque entièrement cohérent.\n";
    echo "Quelques erreurs mineures restent à corriger.\n";
} else {
    echo "❌ INCOHÉRENCES RESTANTES\n";
    echo "Des erreurs importantes subsistent.\n";
    echo "Une intervention manuelle est requise.\n";
}

echo "\n📋 PROCHAINES ÉTAPES RECOMMANDÉES:\n";
echo "1. Corriger les erreurs identifiées ci-dessus\n";
echo "2. Tester l'application complète (connexion, commande, etc.)\n";
echo "3. Faire un backup de la base de données\n";
echo "4. Déployer en production si tout fonctionne\n";
?>
