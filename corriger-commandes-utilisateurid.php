<?php
/**
 * Script de migration pour corriger la table Commandes
 * Change UtilisateurID en ClientID et met à jour les références
 */

echo "=== MIGRATION TABLE COMMANDES ===\n";

require_once 'db_connexion.php';

try {
    echo "1. Vérification de la structure actuelle...\n";
    $stmt = $pdo->query("DESCRIBE Commandes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasUtilisateurID = false;
    $hasClientID = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] == 'UtilisateurID') {
            $hasUtilisateurID = true;
            echo "   ✅ Colonne UtilisateurID trouvée\n";
        }
        if ($col['Field'] == 'ClientID') {
            $hasClientID = true;
            echo "   ✅ Colonne ClientID trouvée\n"; 
        }
    }
    
    if (!$hasUtilisateurID) {
        echo "   ⚠️  Colonne UtilisateurID non trouvée - migration déjà effectuée ?\n";
        exit;
    }
    
    if ($hasClientID) {
        echo "   ⚠️  Colonne ClientID déjà présente - suppression de l'ancienne...\n";
        $pdo->exec("ALTER TABLE Commandes DROP COLUMN ClientID");
    }
    
    echo "\n2. Vérification des données existantes...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Commandes WHERE UtilisateurID IS NOT NULL AND UtilisateurID != ''");
    $nonNullCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Commandes avec UtilisateurID non-null: $nonNullCount\n";
    
    echo "\n3. Ajout de la colonne ClientID...\n";
    $pdo->exec("ALTER TABLE Commandes ADD COLUMN ClientID INT NULL AFTER CommandeID");
    echo "   ✅ Colonne ClientID ajoutée\n";
    
    echo "\n4. Migration des données...\n";
    // Tenter de mapper les commandes existantes par email
    $stmt = $pdo->query("
        UPDATE Commandes c 
        SET ClientID = (
            SELECT cl.ClientID 
            FROM Clients cl 
            WHERE cl.Email = c.EmailClient 
            LIMIT 1
        )
        WHERE c.EmailClient IS NOT NULL AND c.EmailClient != ''
    ");
    $updated = $stmt->rowCount();
    echo "   ✅ $updated commandes mises à jour via email\n";
    
    echo "\n5. Suppression de l'ancienne colonne UtilisateurID...\n";
    $pdo->exec("ALTER TABLE Commandes DROP COLUMN UtilisateurID");
    echo "   ✅ Colonne UtilisateurID supprimée\n";
    
    echo "\n6. Ajout de la contrainte de clé étrangère...\n";
    try {
        $pdo->exec("ALTER TABLE Commandes ADD CONSTRAINT fk_commandes_client FOREIGN KEY (ClientID) REFERENCES Clients(ClientID) ON DELETE SET NULL");
        echo "   ✅ Contrainte de clé étrangère ajoutée\n";
    } catch (PDOException $e) {
        echo "   ⚠️  Contrainte non ajoutée (normal si des ClientID sont NULL): " . $e->getMessage() . "\n";
    }
    
    echo "\n7. Vérification finale...\n";
    $stmt = $pdo->query("DESCRIBE Commandes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasClientIDFinal = false;
    foreach ($columns as $col) {
        if ($col['Field'] == 'ClientID') {
            $hasClientIDFinal = true;
            echo "   ✅ Structure finale: ClientID présent (" . $col['Type'] . ")\n";
        }
    }
    
    if (!$hasClientIDFinal) {
        throw new Exception("Erreur: ClientID non trouvé après migration!");
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(ClientID) as with_client FROM Commandes");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total commandes: " . $stats['total'] . "\n";
    echo "   Avec ClientID: " . $stats['with_client'] . "\n";
    
    echo "\n🎉 MIGRATION TERMINÉE AVEC SUCCÈS!\n";
    echo "La table Commandes utilise maintenant ClientID au lieu de UtilisateurID.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
