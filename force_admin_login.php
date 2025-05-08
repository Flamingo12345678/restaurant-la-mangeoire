<?php
// Force la connexion en tant qu'administrateur pour débloquer l'accès
session_start();

// Récupérer un compte administrateur existant
require_once __DIR__ . '/db_connexion.php';

try {
    // Vérifier si un compte admin existe
    $stmt = $conn->query("SELECT * FROM Administrateurs LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Forcer la connexion avec ce compte
        $_SESSION['admin_id'] = $admin['AdminID'];
        $_SESSION['admin_nom'] = $admin['Nom'];
        $_SESSION['admin_prenom'] = $admin['Prenom'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        $_SESSION['user_type'] = 'admin';
        
        echo "<h2>Connexion forcée réussie</h2>";
        echo "<p>Vous êtes maintenant connecté en tant qu'administrateur.</p>";
        echo "<p>ID Admin: " . $admin['AdminID'] . "</p>";
        echo "<p>Nom: " . htmlspecialchars($admin['Nom'] . " " . $admin['Prenom']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($admin['Email']) . "</p>";
        echo "<p><a href='admin/index.php'>Accéder au tableau de bord</a></p>";
    } else {
        // Créer un compte administrateur et s'y connecter
        $email = "admin@lamangeoire.fr";
        $password = "admin123";
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $nom = "Admin";
        $prenom = "Principal";
        $role = "superadmin";
        
        $conn->exec("CREATE TABLE IF NOT EXISTS Administrateurs (
            AdminID INT AUTO_INCREMENT PRIMARY KEY,
            Nom VARCHAR(100) NOT NULL,
            Prenom VARCHAR(100) NOT NULL,
            Email VARCHAR(100) NOT NULL UNIQUE,
            MotDePasse VARCHAR(255) NOT NULL,
            Role ENUM('admin', 'superadmin') DEFAULT 'admin' NOT NULL,
            DateCreation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            DerniereConnexion TIMESTAMP NULL
        )");
        
        $stmt = $conn->prepare("INSERT INTO Administrateurs (Email, MotDePasse, Nom, Prenom, Role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed_password, $nom, $prenom, $role]);
        $admin_id = $conn->lastInsertId();
        
        // Forcer la connexion avec ce compte
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['admin_nom'] = $nom;
        $_SESSION['admin_prenom'] = $prenom;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_role'] = $role;
        $_SESSION['user_type'] = 'admin';
        
        echo "<h2>Compte administrateur créé et connexion forcée</h2>";
        echo "<p>Un nouveau compte administrateur a été créé et vous êtes maintenant connecté.</p>";
        echo "<p>Email: " . htmlspecialchars($email) . "</p>";
        echo "<p>Mot de passe: " . htmlspecialchars($password) . "</p>";
        echo "<p><a href='admin/index.php'>Accéder au tableau de bord</a></p>";
    }
} catch (PDOException $e) {
    echo "<h2>Erreur</h2>";
    echo "<p>Une erreur est survenue: " . $e->getMessage() . "</p>";
}
?>
