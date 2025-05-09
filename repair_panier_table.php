<?php
// filepath: /Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/repair_panier_table.php
/**
 * Script de réparation pour la table Panier
 * Ce script va modifier la structure de la table Panier pour ajouter une valeur par défaut
 * au champ Quantite afin de corriger l'erreur : SQLSTATE[HY000]: General error: 1364 Field 'Quantite' doesn't have a default value
 */

session_start();
require_once 'db_connexion.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Fonction pour vérifier si une colonne a une valeur par défaut
function checkColumnDefault($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Default'];
    } catch (PDOException $e) {
        return false;
    }
}

echo "<h1>Réparation de la structure de la table Panier</h1>";

// Vérifier si le champ Quantite existe et s'il a une valeur par défaut
$default = checkColumnDefault($conn, 'Panier', 'Quantite');

if ($default === null) {
    echo "<p>Le champ 'Quantite' dans la table Panier n'a pas de valeur par défaut. Tentative de correction...</p>";
    
    try {
        // Mettre à jour le champ pour ajouter une valeur par défaut de 1
        $conn->exec("ALTER TABLE Panier MODIFY COLUMN Quantite INT(11) NOT NULL DEFAULT 1");
        echo "<p style='color: green;'>✅ Modification réussie : le champ 'Quantite' a maintenant une valeur par défaut de 1.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de la modification : " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Le champ 'Quantite' de la table Panier a déjà une valeur par défaut : " . $default . "</p>";
}

// Mettre à jour tous les enregistrements existants qui ont une valeur nulle pour Quantite
try {
    $update = $conn->prepare("UPDATE Panier SET Quantite = 1 WHERE Quantite IS NULL OR Quantite = 0");
    $affectedRows = $update->execute();
    echo "<p>" . ($update->rowCount() > 0 ? $update->rowCount() . " enregistrements ont été mis à jour avec une quantité de 1." : "Aucun enregistrement avec quantité nulle n'a été trouvé.") . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur lors de la mise à jour des données : " . $e->getMessage() . "</p>";
}

// Nettoyer les entrées du panier qui pourraient être invalides
try {
    // Supprimer les entrées qui se référent à des menus inexistants
    $clean_stmt = $conn->prepare("
        DELETE FROM Panier 
        WHERE MenuID NOT IN (SELECT MenuID FROM Menus)
    ");
    $clean_stmt->execute();
    echo "<p>" . ($clean_stmt->rowCount() > 0 ? $clean_stmt->rowCount() . " entrées de panier faisant référence à des menus inexistants ont été supprimées." : "Aucune entrée de panier invalide trouvée.") . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur lors du nettoyage des entrées invalides : " . $e->getMessage() . "</p>";
}

echo "<p>Processus terminé. <a href='admin/index.php'>Retour à l'administration</a></p>";
?>
