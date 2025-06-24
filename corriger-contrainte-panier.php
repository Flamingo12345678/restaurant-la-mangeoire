<?php
/**
 * Script de correction de la contrainte de clé étrangère pour le panier
 * 
 * Problème : La table Panier pointe vers Utilisateurs mais le système utilise Clients
 * Solution : Modifier la contrainte pour pointer vers la table Clients
 */

require_once 'db_connexion.php';

echo "=== CORRECTION CONTRAINTE PANIER ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Vérifier la contrainte actuelle
    echo "1. Vérification de la contrainte actuelle...\n";
    $result = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'Panier' 
        AND TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($constraints as $constraint) {
        echo "   - {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
    }
    
    // 2. Vider la table Panier pour éviter les conflits
    echo "\n2. Vidage de la table Panier...\n";
    $count = $pdo->query("SELECT COUNT(*) as count FROM Panier")->fetch()['count'];
    if ($count > 0) {
        echo "   Suppression de $count enregistrements...\n";
        $pdo->exec("DELETE FROM Panier");
        echo "   ✅ Table Panier vidée\n";
    } else {
        echo "   ✅ Table Panier déjà vide\n";
    }
    
    // 3. Supprimer la contrainte actuelle
    echo "\n3. Suppression de la contrainte FK_Panier_Utilisateurs...\n";
    $pdo->exec("ALTER TABLE Panier DROP FOREIGN KEY FK_Panier_Utilisateurs");
    echo "   ✅ Contrainte supprimée\n";
    
    // 4. Modifier la colonne pour correspondre au type de ClientID
    echo "\n4. Modification de la colonne UtilisateurID...\n";
    
    // Vérifier le type de ClientID dans la table Clients
    $result = $pdo->query("SHOW COLUMNS FROM Clients LIKE 'ClientID'");
    $clientIdType = $result->fetch()['Type'];
    echo "   Type ClientID: $clientIdType\n";
    
    // Renommer la colonne
    $pdo->exec("ALTER TABLE Panier CHANGE UtilisateurID ClientID $clientIdType NOT NULL");
    echo "   ✅ Colonne renommée: UtilisateurID -> ClientID\n";
    
    // 5. Créer la nouvelle contrainte vers Clients
    echo "\n5. Création de la nouvelle contrainte...\n";
    $pdo->exec("
        ALTER TABLE Panier 
        ADD CONSTRAINT FK_Panier_Clients 
        FOREIGN KEY (ClientID) REFERENCES Clients(ClientID) ON DELETE CASCADE
    ");
    echo "   ✅ Nouvelle contrainte créée: FK_Panier_Clients\n";
    
    // 6. Vérification finale
    echo "\n6. Vérification finale...\n";
    $result = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'Panier' 
        AND TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $newConstraints = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($newConstraints as $constraint) {
        echo "   ✅ {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
    }
    
    echo "\n🎉 Correction terminée avec succès!\n";
    echo "La table Panier pointe maintenant vers la table Clients.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Détails: " . $e->getTraceAsString() . "\n";
}
?>
