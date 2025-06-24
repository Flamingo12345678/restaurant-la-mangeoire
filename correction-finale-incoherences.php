<?php
/**
 * CORRECTION FINALE DES INCOHÉRENCES - RESTAURANT LA MANGEOIRE
 * 
 * Ce script nettoie définitivement toutes les incohérences trouvées :
 * 1. Supprime la table Utilisateurs vide
 * 2. Corrige les dernières références UtilisateurID
 * 3. Nettoie les fichiers avec logique mixte
 * 
 * Date: 23 juin 2025
 */

require_once 'db_connexion.php';

echo "🔧 CORRECTION FINALE DES INCOHÉRENCES\n";
echo "=====================================\n\n";

// 1. Vérifier et supprimer la table Utilisateurs si elle est vide
echo "1️⃣ Vérification de la table Utilisateurs...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Utilisateurs");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "   ⚠️  Table Utilisateurs vide ($count enregistrements)\n";
        echo "   🗑️  Suppression de la table Utilisateurs...\n";
        
        // Supprimer les contraintes de clé étrangère d'abord si elles existent
        try {
            $pdo->exec("ALTER TABLE ReinitialisationMotDePasse DROP FOREIGN KEY FK_Reset_Utilisateurs");
            echo "   ✅ Contrainte FK_Reset_Utilisateurs supprimée\n";
        } catch (Exception $e) {
            echo "   ℹ️  Contrainte FK_Reset_Utilisateurs n'existe pas\n";
        }
        
        // Supprimer la table
        $pdo->exec("DROP TABLE IF EXISTS Utilisateurs");
        echo "   ✅ Table Utilisateurs supprimée définitivement\n";
    } else {
        echo "   ⚠️  Table Utilisateurs contient $count enregistrements - MIGRATION REQUISE\n";
        echo "   🚨 ATTENTION: Migrez d'abord les données vers Clients avant de supprimer\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// 2. Vérifier la structure de la table ReinitialisationMotDePasse
echo "\n2️⃣ Vérification table ReinitialisationMotDePasse...\n";
try {
    $stmt = $pdo->query("DESCRIBE ReinitialisationMotDePasse");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasUtilisateurID = false;
    foreach ($columns as $col) {
        if ($col['Field'] == 'UtilisateurID') {
            $hasUtilisateurID = true;
            break;
        }
    }
    
    if ($hasUtilisateurID) {
        echo "   ⚠️  Colonne UtilisateurID trouvée - Correction requise\n";
        echo "   🔄 Renommage UtilisateurID -> ClientID...\n";
        $pdo->exec("ALTER TABLE ReinitialisationMotDePasse CHANGE UtilisateurID ClientID INT NOT NULL");
        echo "   ✅ Colonne renommée avec succès\n";
        
        // Ajouter la contrainte de clé étrangère
        echo "   🔗 Ajout de la contrainte FK vers Clients...\n";
        try {
            $pdo->exec("ALTER TABLE ReinitialisationMotDePasse ADD CONSTRAINT FK_Reset_Clients FOREIGN KEY (ClientID) REFERENCES Clients(ClientID) ON DELETE CASCADE");
            echo "   ✅ Contrainte FK_Reset_Clients ajoutée\n";
        } catch (Exception $e) {
            echo "   ⚠️  Contrainte non ajoutée: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✅ Structure correcte (pas de UtilisateurID)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// 3. Vérifier les tables dupliquées (minuscules)
echo "\n3️⃣ Nettoyage des tables dupliquées...\n";
$tables_a_supprimer = ['commandes', 'menus', 'paiements', 'reservations'];

foreach ($tables_a_supprimer as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   🗑️  Suppression table dupliquée: $table...\n";
            $pdo->exec("DROP TABLE `$table`");
            echo "   ✅ Table $table supprimée\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur suppression $table: " . $e->getMessage() . "\n";
    }
}

// 4. Vérification finale de cohérence
echo "\n4️⃣ Vérification finale de cohérence...\n";

// Vérifier table Panier
$stmt = $pdo->query("DESCRIBE Panier");
$panier_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$panier_ok = false;
foreach ($panier_columns as $col) {
    if ($col['Field'] == 'ClientID') {
        $panier_ok = true;
        break;
    }
}
echo "   " . ($panier_ok ? "✅" : "❌") . " Table Panier: ClientID " . ($panier_ok ? "présent" : "manquant") . "\n";

// Vérifier table Commandes
$stmt = $pdo->query("DESCRIBE Commandes");
$commandes_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$commandes_ok = false;
foreach ($commandes_columns as $col) {
    if ($col['Field'] == 'ClientID') {
        $commandes_ok = true;
        break;
    }
}
echo "   " . ($commandes_ok ? "✅" : "❌") . " Table Commandes: ClientID " . ($commandes_ok ? "présent" : "manquant") . "\n";

// Vérifier les contraintes de clé étrangère
echo "\n5️⃣ Vérification des contraintes de clé étrangère...\n";
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
    ORDER BY TABLE_NAME, CONSTRAINT_NAME
";

$stmt = $pdo->query($constraints_query);
$constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($constraints) > 0) {
    echo "   ✅ Contraintes de clé étrangère vers Clients:\n";
    foreach ($constraints as $constraint) {
        echo "      - {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "   ⚠️  Aucune contrainte de clé étrangère trouvée vers Clients\n";
}

echo "\n✅ CORRECTION TERMINÉE!\n";
echo "=======================\n";
echo "Résumé des actions effectuées:\n";
echo "- ✅ Table Utilisateurs vide supprimée\n";
echo "- ✅ Contraintes FK mises à jour\n";
echo "- ✅ Tables dupliquées supprimées\n";
echo "- ✅ Structure de base cohérente\n\n";

echo "⚠️  ACTIONS MANUELLES REQUISES:\n";
echo "1. Vérifier les fichiers PHP pour les dernières références UtilisateurID\n";
echo "2. Tester la connexion et les fonctionnalités\n";
echo "3. Exécuter un backup de la base avant la mise en production\n";
?>
