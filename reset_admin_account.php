<?php
// Script pour tester et réparer la connexion administrateur
require_once __DIR__ . '/db_connexion.php';

// Recréer la table Administrateurs si nécessaire
try {
    $conn->exec("
    CREATE TABLE IF NOT EXISTS Administrateurs (
        AdminID INT AUTO_INCREMENT PRIMARY KEY,
        Nom VARCHAR(100) NOT NULL,
        Prenom VARCHAR(100) NOT NULL,
        Email VARCHAR(100) NOT NULL UNIQUE,
        MotDePasse VARCHAR(255) NOT NULL,
        Role ENUM('admin', 'superadmin') DEFAULT 'admin' NOT NULL,
        DateCreation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        DerniereConnexion TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "<p>Table Administrateurs vérifiée/créée avec succès.</p>";
} catch (PDOException $e) {
    echo "<p>Erreur lors de la création de la table Administrateurs: " . $e->getMessage() . "</p>";
}

// Créer un compte administrateur avec un mot de passe simple pour les tests
$email = 'admin@lamangeoire.fr';
$password = 'admin123'; // Mot de passe simple pour les tests
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$nom = 'Admin';
$prenom = 'Test';
$role = 'superadmin';

try {
    // Supprimer d'abord l'administrateur s'il existe
    $stmt = $conn->prepare("DELETE FROM Administrateurs WHERE Email = ?");
    $stmt->execute([$email]);
    
    // Puis le créer à nouveau
    $stmt = $conn->prepare("INSERT INTO Administrateurs (Email, MotDePasse, Nom, Prenom, Role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $hashed_password, $nom, $prenom, $role]);
    
    echo "<h3>Compte administrateur recréé avec succès</h3>";
    echo "<p>Email: " . htmlspecialchars($email) . "</p>";
    echo "<p>Mot de passe: " . htmlspecialchars($password) . "</p>";
    echo "<p><a href='connexion-unifiee.php'>Tester la connexion</a></p>";
} catch (PDOException $e) {
    echo "<p>Erreur lors de la recréation du compte administrateur: " . $e->getMessage() . "</p>";
}
?>
