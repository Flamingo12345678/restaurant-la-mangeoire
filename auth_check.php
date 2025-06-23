<?php
// Ce script vérifie l'état de la connexion après authentification
// Il affiche des informations détaillées sur la session et le processus de connexion
session_start();

echo "<h1>État de la connexion</h1>";

// Ajouter des journaux de débogage à la connexion
if (!file_exists('debug_log.txt')) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Fichier de débogage créé\n");
}

function debug_log($message) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    echo "<p>Log: " . htmlspecialchars($message) . "</p>";
}

debug_log("Ouverture de la page de connexion");

// Informations de session
echo "<h2>Informations de session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_type'])) {
    debug_log("Utilisateur connecté en tant que " . $_SESSION['user_type']);
    
    if ($_SESSION['user_type'] === 'admin') {
        echo "<p style='color:green;'>Vous êtes connecté en tant qu'administrateur.</p>";
        echo "<p>ID Admin: " . $_SESSION['admin_id'] . "</p>";
        echo "<p>Nom: " . $_SESSION['admin_nom'] . " " . $_SESSION['admin_prenom'] . "</p>";
        echo "<p>Email: " . $_SESSION['admin_email'] . "</p>";
        echo "<p><a href='admin/index.php'>Accéder au tableau de bord administrateur</a></p>";
    } else if ($_SESSION['user_type'] === 'client') {
        echo "<p style='color:blue;'>Vous êtes connecté en tant que client.</p>";
        echo "<p>ID Client: " . $_SESSION['client_id'] . "</p>";
        echo "<p>Nom: " . $_SESSION['client_nom'] . " " . $_SESSION['client_prenom'] . "</p>";
        echo "<p>Email: " . $_SESSION['client_email'] . "</p>";
        echo "<p><a href='mon-compte.php'>Accéder à votre compte client</a></p>";
    }
    
    echo "<p><a href='deconnexion.php'>Se déconnecter</a></p>";
} else {
    debug_log("Aucun utilisateur connecté");
    echo "<p style='color:red;'>Vous n'êtes pas connecté.</p>";
    
    // Formulaire de connexion simplifié pour test direct
    echo "<h2>Connexion rapide</h2>";
    echo "<form method='post' action='auth_process.php'>";
    echo "<div style='margin-bottom: 10px;'>";
    echo "<label for='email'>Email:</label>";
    echo "<input type='email' id='email' name='email' value='admin@lamangeoire.fr'>";
    echo "</div>";
    echo "<div style='margin-bottom: 10px;'>";
    echo "<label for='password'>Mot de passe:</label>";
    echo "<input type='password' id='password' name='password' value='admin123'>";
    echo "</div>";
    echo "<button type='submit'>Se connecter</button>";
    echo "</form>";
}

// Vérification de la base de données
require_once 'db_connexion.php';

echo "<h2>Vérification du compte administrateur</h2>";
try {
    $query = "SELECT * FROM Administrateurs WHERE Email = ?";
    $stmt = $pdo->prepare($query);
    $email = "admin@lamangeoire.fr";
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        debug_log("Compte administrateur trouvé dans la base de données");
        echo "<p style='color:green;'>✓ Le compte administrateur existe dans la base de données.</p>";
        echo "<p>ID: " . $admin['AdminID'] . "</p>";
        echo "<p>Nom: " . htmlspecialchars($admin['Nom'] . " " . $admin['Prenom']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($admin['Email']) . "</p>";
        echo "<p>Rôle: " . htmlspecialchars($admin['Role']) . "</p>";
        
        // Vérifier si le mot de passe par défaut fonctionne
        $test_password = "admin123";
        if (password_verify($test_password, $admin['MotDePasse'])) {
            debug_log("Le mot de passe de test est valide");
            echo "<p style='color:green;'>✓ Le mot de passe de test 'admin123' est valide pour ce compte.</p>";
        } else {
            debug_log("Le mot de passe de test est invalide");
            echo "<p style='color:red;'>✗ Le mot de passe de test 'admin123' est invalide pour ce compte.</p>";
            
            // Mise à jour du mot de passe
            echo "<h3>Réinitialisation du mot de passe</h3>";
            echo "<form method='post' action='auth_process.php'>";
            echo "<input type='hidden' name='reset_password' value='1'>";
            echo "<input type='hidden' name='admin_id' value='" . $admin['AdminID'] . "'>";
            echo "<button type='submit'>Réinitialiser le mot de passe à 'admin123'</button>";
            echo "</form>";
        }
    } else {
        debug_log("Aucun compte administrateur trouvé");
        echo "<p style='color:red;'>✗ Aucun compte administrateur trouvé avec l'email: " . htmlspecialchars($email) . "</p>";
        
        // Création du compte administrateur
        echo "<h3>Création du compte administrateur</h3>";
        echo "<form method='post' action='auth_process.php'>";
        echo "<input type='hidden' name='create_admin' value='1'>";
        echo "<button type='submit'>Créer un compte administrateur</button>";
        echo "</form>";
    }
} catch (PDOException $e) {
    debug_log("Erreur lors de la vérification de la base de données: " . $e->getMessage());
    echo "<p style='color:red;'>Erreur lors de la vérification de la base de données: " . $e->getMessage() . "</p>";
}

// Informations de debugging
echo "<h2>Informations de débogage</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session save path: " . session_save_path() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";
echo "<p>Cookies: " . (isset($_COOKIE) ? count($_COOKIE) : 0) . " cookies définis</p>";
if (isset($_COOKIE)) {
    echo "<pre>";
    print_r($_COOKIE);
    echo "</pre>";
}

// Vérification du chemin de redirection
echo "<h2>Vérification des fichiers de redirection</h2>";
echo "<p>admin/index.php - Vérification de session:</p>";
echo "<pre>";
$admin_index_code = file_get_contents('admin/index.php');
$session_check = preg_match('/if\s*\(\s*!isset\s*\(\s*\$_SESSION\s*\[\s*[\'"]admin_id[\'"]\s*\]\s*\)\s*\|\|\s*\$_SESSION\s*\[\s*[\'"]user_type[\'"]\s*\]\s*!==\s*[\'"]admin[\'"]\s*\)\s*{/m', $admin_index_code);
echo "Code de vérification trouvé: " . ($session_check ? "Oui" : "Non") . "\n";
echo "</pre>";

debug_log("Fin de la vérification");
?>
