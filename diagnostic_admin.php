<?php
// Script de diagnostic pour résoudre les problèmes de connexion administrateur
session_start();
require_once __DIR__ . '/db_connexion.php';

echo "<h1>Diagnostic de connexion administrateur</h1>";

// 1. Vérifier si une session administrateur est active
echo "<h2>1. Vérification de la session actuelle</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
    echo "<p style='color:green;'>✓ Une session administrateur est active.</p>";
    echo "<p>Vous êtes connecté en tant que : " . htmlspecialchars($_SESSION['admin_prenom'] . ' ' . $_SESSION['admin_nom']) . "</p>";
    echo "<p><a href='admin/index.php'>Accéder au tableau de bord admin</a></p>";
    echo "<p><a href='deconnexion.php'>Se déconnecter</a></p>";
} else {
    echo "<p style='color:red;'>✗ Aucune session administrateur n'est active.</p>";
}

// 2. Vérifier si le compte administrateur existe dans la base de données
echo "<h2>2. Vérification du compte administrateur dans la base de données</h2>";
try {
    $stmt = $conn->prepare("SELECT * FROM Administrateurs WHERE Email = ?");
    $email = "admin@lamangeoire.fr";
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        echo "<p style='color:green;'>✓ Le compte administrateur existe dans la base de données.</p>";
        echo "<p>ID: " . $admin['AdminID'] . "</p>";
        echo "<p>Nom: " . htmlspecialchars($admin['Nom']) . "</p>";
        echo "<p>Prénom: " . htmlspecialchars($admin['Prenom']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($admin['Email']) . "</p>";
        echo "<p>Rôle: " . htmlspecialchars($admin['Role']) . "</p>";
    } else {
        echo "<p style='color:red;'>✗ Aucun compte administrateur trouvé avec l'email: " . htmlspecialchars($email) . "</p>";
        echo "<p>Exécutez le script <code>create_admin.php</code> pour créer un compte administrateur.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur lors de la vérification du compte administrateur: " . $e->getMessage() . "</p>";
    
    // Vérifier si la table Administrateurs existe
    try {
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables disponibles dans la base de données:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        
        if (!in_array('Administrateurs', $tables) && !in_array('administrateurs', $tables)) {
            echo "<p style='color:red;'>La table Administrateurs n'existe pas. Exécutez le script <code>init_admin.sql</code> pour la créer.</p>";
        }
    } catch (PDOException $e2) {
        echo "<p style='color:red;'>Erreur lors de la vérification des tables: " . $e2->getMessage() . "</p>";
    }
}

// 3. Formulaire de connexion administrateur pour test
echo "<h2>3. Formulaire de test de connexion</h2>";
echo "<p>Utilisez ce formulaire pour tester la connexion administrateur:</p>";
?>

<form method="POST" action="">
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="admin@lamangeoire.fr" required>
    </div>
    <div style="margin-top: 10px;">
        <label for="password">Mot de passe:</label>
        <input type="password" id="password" name="password" value="D@@mso_237*" required>
    </div>
    <div style="margin-top: 15px;">
        <button type="submit" name="test_login">Tester la connexion</button>
    </div>
</form>

<?php
// Traiter le formulaire de test
if (isset($_POST['test_login'])) {
    echo "<h3>Résultat du test de connexion</h3>";
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Vérifier dans la table Administrateurs
        $stmt = $conn->prepare("SELECT * FROM Administrateurs WHERE Email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<p>Compte administrateur trouvé avec l'email: " . htmlspecialchars($email) . "</p>";
            
            if (password_verify($password, $admin['MotDePasse'])) {
                echo "<p style='color:green;'>✓ Le mot de passe est correct.</p>";
                
                // Enregistrer la session
                $_SESSION['admin_id'] = $admin['AdminID'];
                $_SESSION['admin_nom'] = $admin['Nom'];
                $_SESSION['admin_prenom'] = $admin['Prenom'];
                $_SESSION['admin_email'] = $admin['Email'];
                $_SESSION['user_type'] = 'admin';
                
                echo "<p style='color:green;'>✓ Session administrateur créée.</p>";
                echo "<p><a href='admin/index.php'>Accéder au tableau de bord admin</a></p>";
            } else {
                echo "<p style='color:red;'>✗ Le mot de passe est incorrect.</p>";
                echo "<p>Le mot de passe hashé dans la base de données est: " . $admin['MotDePasse'] . "</p>";
                echo "<p>Vous pouvez réinitialiser le mot de passe en exécutant <code>create_admin.php</code>.</p>";
            }
        } else {
            echo "<p style='color:red;'>✗ Aucun compte administrateur trouvé avec l'email: " . htmlspecialchars($email) . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Erreur lors du test de connexion: " . $e->getMessage() . "</p>";
    }
}
?>
