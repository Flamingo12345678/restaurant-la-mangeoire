<?php
/**
 * Script pour ajouter la colonne "message" à la table Reservations
 * et mettre à jour le système pour l'utiliser
 */

require_once 'db_connexion.php';

echo "=== AJOUT DE LA COLONNE MESSAGE ===" . PHP_EOL;

try {
    // Vérifier si la colonne message existe déjà
    $stmt = $conn->query("SHOW COLUMNS FROM Reservations LIKE 'message'");
    $hasMessage = $stmt->rowCount() > 0;
    
    if (!$hasMessage) {
        echo "📝 Ajout de la colonne 'message' à la table Reservations..." . PHP_EOL;
        
        // Ajouter la colonne message
        $sql = "ALTER TABLE Reservations ADD COLUMN message TEXT DEFAULT NULL COMMENT 'Message ou demande spéciale du client'";
        $conn->exec($sql);
        
        echo "✅ Colonne 'message' ajoutée avec succès!" . PHP_EOL;
    } else {
        echo "ℹ️ La colonne 'message' existe déjà." . PHP_EOL;
    }
    
    // Afficher la structure finale
    echo PHP_EOL . "📋 Structure finale de la table:" . PHP_EOL;
    $columns = $conn->query("DESCRIBE Reservations")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']})" . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Table mise à jour avec succès!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . PHP_EOL;
}
?>
