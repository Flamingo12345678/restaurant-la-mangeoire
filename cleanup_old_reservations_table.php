<?php
/**
 * Script de nettoyage : suppression de l'ancienne table 'reservations' (minuscule)
 * qui n'est plus utilisée. Toutes les données sont maintenant dans 'Reservations' (majuscule).
 */

require_once 'db_connexion.php';

echo "=== NETTOYAGE DE L'ANCIENNE TABLE RESERVATIONS ===" . PHP_EOL;

try {
    // Vérifier si la table 'reservations' (minuscule) existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'reservations'");
    
    if ($checkTable->rowCount() > 0) {
        // Afficher le contenu de l'ancienne table avant suppression
        echo "📋 Contenu de l'ancienne table 'reservations' (minuscule):" . PHP_EOL;
        $oldData = $conn->query("SELECT * FROM reservations")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($oldData) > 0) {
            foreach ($oldData as $row) {
                echo "  • " . $row['nom'] . " (" . $row['email'] . ") - " . $row['date_reservation'] . " " . $row['heure_reservation'] . PHP_EOL;
            }
            echo PHP_EOL;
            
            // Demander confirmation
            echo "⚠️ ATTENTION: Cette table contient " . count($oldData) . " enregistrements." . PHP_EOL;
            echo "Ces données devraient déjà être migrées dans la table 'Reservations' (majuscule)." . PHP_EOL;
            echo "Voulez-vous vraiment supprimer cette ancienne table ? (y/N): ";
            
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($confirmation) === 'y' || strtolower($confirmation) === 'yes') {
                // Supprimer la table
                $conn->exec("DROP TABLE reservations");
                echo "✅ Ancienne table 'reservations' supprimée avec succès." . PHP_EOL;
            } else {
                echo "❌ Suppression annulée." . PHP_EOL;
            }
        } else {
            // Table vide, on peut la supprimer directement
            $conn->exec("DROP TABLE reservations");
            echo "✅ Ancienne table 'reservations' (vide) supprimée avec succès." . PHP_EOL;
        }
    } else {
        echo "ℹ️ Aucune ancienne table 'reservations' trouvée. Rien à nettoyer." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du nettoyage: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "🔍 Tables actuelles dans la base de données:" . PHP_EOL;
$tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "  • " . $table . PHP_EOL;
}

echo PHP_EOL . "✅ Nettoyage terminé." . PHP_EOL;
?>
