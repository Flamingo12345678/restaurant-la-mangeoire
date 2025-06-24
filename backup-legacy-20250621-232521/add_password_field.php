<?php
// Script pour ajouter le champ MotDePasse à la table Clients
require_once __DIR__ . '/db_connexion.php';

try {
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->prepare("SHOW COLUMNS FROM Clients LIKE 'MotDePasse'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // La colonne n'existe pas, on l'ajoute
        $pdo->exec("ALTER TABLE Clients ADD COLUMN MotDePasse VARCHAR(255) NOT NULL DEFAULT ''");
        echo "Le champ MotDePasse a été ajouté à la table Clients avec succès.";
    } else {
        echo "Le champ MotDePasse existe déjà dans la table Clients.";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
