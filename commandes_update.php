<?php
// Script de mise à jour pour ajouter les colonnes manquantes à la table Commandes
require_once __DIR__ . '/db_connexion.php';

// Fonction pour vérifier si une colonne existe dans une table
function checkColumnExists($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SELECT $column FROM $table LIMIT 1");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column") !== false) {
            return false;
        }
        throw $e;
    }
}

echo "<h1>Mise à jour de la table Commandes</h1>";
echo "<p>Vérification des colonnes requises...</p>";

// Vérifier et ajouter la colonne PrixUnitaire si elle n'existe pas
if (!checkColumnExists($conn, 'Commandes', 'PrixUnitaire')) {
    try {
        $conn->exec("ALTER TABLE Commandes ADD COLUMN PrixUnitaire DECIMAL(10,2) DEFAULT NULL");
        echo "<p style='color: green;'>✅ Colonne 'PrixUnitaire' ajoutée avec succès.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de l'ajout de la colonne 'PrixUnitaire': " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>✓ La colonne 'PrixUnitaire' existe déjà.</p>";
}

// Vérifier et ajouter la colonne MontantTotal si elle n'existe pas
if (!checkColumnExists($conn, 'Commandes', 'MontantTotal')) {
    try {
        $conn->exec("ALTER TABLE Commandes ADD COLUMN MontantTotal DECIMAL(10,2) DEFAULT NULL");
        echo "<p style='color: green;'>✅ Colonne 'MontantTotal' ajoutée avec succès.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de l'ajout de la colonne 'MontantTotal': " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>✓ La colonne 'MontantTotal' existe déjà.</p>";
}

// Mettre à jour les valeurs existantes si les colonnes viennent d'être ajoutées
if (checkColumnExists($conn, 'Commandes', 'PrixUnitaire') && checkColumnExists($conn, 'Commandes', 'MontantTotal')) {
    try {
        // Mettre à jour PrixUnitaire et MontantTotal pour les commandes existantes
        $sql = "UPDATE Commandes c 
                JOIN Menus m ON c.MenuID = m.MenuID 
                SET c.PrixUnitaire = m.Prix, 
                    c.MontantTotal = m.Prix * c.Quantite 
                WHERE c.PrixUnitaire IS NULL OR c.MontantTotal IS NULL";
        
        $updated = $conn->exec($sql);
        echo "<p style='color: green;'>✅ Mise à jour des données: $updated commandes mises à jour.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de la mise à jour des données: " . $e->getMessage() . "</p>";
    }
}

echo "<p><a href='/admin/commandes.php'>Retour à la gestion des commandes</a></p>";
?>
