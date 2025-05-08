<?php
// Ne pas démarrer une session si elle existe déjà
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connexion.php';

// Vérifier si l'utilisateur est déjà connecté, le rediriger vers la page appropriée
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($_SESSION['user_type'] === 'client') {
        header("Location: mon-compte.php");
        exit;
    }
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Vérifier d'abord dans la table admin
    $admin_query = "SELECT * FROM Administrateurs WHERE Email = ?";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bindValue(1, $email, PDO::PARAM_STR);
    $admin_stmt->execute();
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // C'est un administrateur
        if (password_verify($password, $admin['MotDePasse'])) {
            // Connexion réussie pour admin
            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_nom'] = $admin['Nom'];
            $_SESSION['admin_prenom'] = $admin['Prenom'];
            $_SESSION['admin_email'] = $admin['Email'];
            $_SESSION['user_type'] = 'admin';
            
            header("Location: admin/index.php");
            exit;
        } else {
            $error_message = "Mot de passe incorrect";
        }
    } else {
        // Vérifier dans la table clients
        $client_query = "SELECT * FROM Clients WHERE Email = ?";
        $client_stmt = $conn->prepare($client_query);
        $client_stmt->bindValue(1, $email, PDO::PARAM_STR);
        $client_stmt->execute();
        $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client && isset($client['MotDePasse'])) {
            // C'est un client avec mot de passe dans la table Clients
            if (password_verify($password, $client['MotDePasse'])) {
                // Connexion réussie pour client
                $_SESSION['client_id'] = $client['ClientID'];
                $_SESSION['client_nom'] = $client['Nom'];
                $_SESSION['client_prenom'] = $client['Prenom'];
                $_SESSION['client_email'] = $client['Email'];
                $_SESSION['user_type'] = 'client';
                
                header("Location: mon-compte.php");
                exit;
            } else {
                $error_message = "Mot de passe incorrect";
            }
        } else {
            // Vérifier dans la table Utilisateurs
            $user_query = "SELECT * FROM Utilisateurs WHERE Email = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bindValue(1, $email, PDO::PARAM_STR);
            $user_stmt->execute();
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // C'est un utilisateur
                if (password_verify($password, $user['MotDePasse'])) {
                    // Connexion réussie pour utilisateur
                    $_SESSION['client_id'] = $user['UtilisateurID'];
                    $_SESSION['client_nom'] = $user['Nom'];
                    $_SESSION['client_prenom'] = $user['Prenom'];
                    $_SESSION['client_email'] = $user['Email'];
                    $_SESSION['user_type'] = 'client';
                    
                    header("Location: mon-compte.php");
                    exit;
                } else {
                    $error_message = "Mot de passe incorrect";
                }
            } else {
                $error_message = "Utilisateur non trouvé";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Restaurant La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        
        .back-link {
            padding: 15px;
            color: #ce1212;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px 20px;
            text-align: center;
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            padding: 5px;
            background-color: #fff1f1;
            box-shadow: 0 0 0 5px rgba(206, 18, 18, 0.1);
        }
        
        h2 {
            color: #ce1212;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            font-size: 1.2rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            background-color: #f8f8f8;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ce1212;
            background-color: #fff;
        }
        
        .btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 15px;
            background-color: #ce1212;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            background-color: #b01010;
        }
        
        .btn-icon {
            margin-right: 10px;
        }
        
        .error-message {
            color: #ce1212;
            background-color: #ffeaea;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: left;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left-circle"></i> Retour au site public
    </a>
    
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <img src="assets/img/favcon.jpeg" alt="Logo La Mangeoire" class="logo">
            </div>
            
            <h2>Connexion</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <i class="bi bi-person form-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                    <i class="bi bi-lock form-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Mot de passe" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="bi bi-box-arrow-in-right btn-icon"></i> Connexion
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <p><a href="mot-de-passe-oublie.php" style="color: #ce1212; text-decoration: none;">Mot de passe oublié ?</a></p>
                <p>Pas encore de compte ? <a href="inscription.php" style="color: #ce1212; text-decoration: none;">S'inscrire</a></p>
            </div>
        </div>
    </div>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
</body>
</html>