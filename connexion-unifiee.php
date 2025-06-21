<?php
// Activer la mise en tampon de sortie pour éviter les erreurs "headers already sent"
ob_start();

// Inclure la configuration de session avant toute sortie
require_once dirname(__FILE__) . '/includes/session_config.php';

// Démarrer la session si ce n'est pas déjà fait et si c'est possible
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
// Note: session.cookie_secure devrait être à 1 en production avec HTTPS

// Inclure le fichier de sécurité pour l'administration qui inclut config.php
require_once 'admin/includes/security_utils.php';

// Inclure la connexion à la base de données
require_once 'db_connexion.php';

// La session est déjà démarrée dans security_utils.php via config.php

// Gérer le paramètre de redirection
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

// Vérifier si l'utilisateur est déjà connecté, le rediriger vers la page appropriée
// Sauf si on force une nouvelle connexion avec force_new=1
$force_new = isset($_GET['force_new']) && $_GET['force_new'] == '1';

if (!$force_new && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        // Si une page de redirection est définie, l'utiliser, sinon aller au tableau de bord
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect_url");
        } else {
            header("Location: admin/index.php");
        }
        exit;
    } elseif ($_SESSION['user_type'] === 'client') {
        header("Location: mon-compte.php");
        exit;
    }
}

// Si on force une nouvelle connexion, détruire la session existante
if ($force_new && session_status() === PHP_SESSION_ACTIVE) {
    // Sauvegarder temporairement les informations pour le log
    $user_type = $_SESSION['user_type'] ?? 'inconnu';
    $user_id = $_SESSION['admin_id'] ?? ($_SESSION['client_id'] ?? 0);
    $user_email = $_SESSION['admin_email'] ?? ($_SESSION['client_email'] ?? 'inconnu');
    
    // Journaliser la déconnexion forcée
    log_unauthorized_access("Réinitialisation forcée de session ($user_type, ID: $user_id, Email: $user_email)", __FILE__);
    
    // Détruire la session
    $_SESSION = array();
    session_destroy();
    
    // Redémarrer une nouvelle session
    session_start();
    $_SESSION['last_regenerate'] = time();
}

// Vérifier si l'IP est sur liste noire
if (is_ip_blacklisted()) {
    // Afficher un message d'erreur approprié
    $error_message = "Votre adresse IP a été temporairement bloquée en raison de trop nombreuses tentatives de connexion échouées. Veuillez réessayer plus tard.";
} else {
    $error_message = "";
}

// Déterminer si la demande vient de l'interface admin
$is_admin_login = isset($_POST['admin_login']) || (isset($_GET['admin']) && $_GET['admin'] == '1');

if ($_SERVER["REQUEST_METHOD"] == "POST" && !is_ip_blacklisted()) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Déterminer si la demande vient de l'interface admin
    $is_admin_login = isset($_POST['admin_login']) || (isset($_GET['admin']) && $_GET['admin'] == '1');
    
    // Vérifier d'abord dans la table admin
    $admin_query = "SELECT * FROM Administrateurs WHERE Email = ?";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bindValue(1, $email, PDO::PARAM_STR);
    $admin_stmt->execute();
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // C'est un administrateur
        if (password_verify($password, $admin['MotDePasse'])) {
            // Réinitialiser les tentatives de connexion échouées
            reset_failed_login_attempts($email, null, 'admin');
            
            // Connexion réussie pour admin
            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_nom'] = $admin['Nom'];
            $_SESSION['admin_prenom'] = $admin['Prenom'];
            $_SESSION['admin_email'] = $admin['Email'];
            $_SESSION['admin_role'] = !empty($admin['Role']) ? $admin['Role'] : 'admin'; // Utiliser le rôle de la DB ou admin par défaut
            $_SESSION['user_type'] = 'admin';
            
            // Mettre à jour la date de dernière connexion
            try {
                // Vérifier d'abord si la colonne existe
                $check_column = $conn->query("SHOW COLUMNS FROM Administrateurs LIKE 'DerniereConnexion'");
                if ($check_column->rowCount() > 0) {
                    $update_query = "UPDATE Administrateurs SET DerniereConnexion = NOW() WHERE AdminID = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([$admin['AdminID']]);
                } else {
                    // Rediriger vers la page de mise à jour de la structure
                    header("Location: update_administrateurs_table.php");
                    exit;
                }
            } catch (PDOException $e) {
                // Si une erreur se produit, la journaliser mais continuer
                error_log("Erreur lors de la mise à jour de la dernière connexion: " . $e->getMessage());
            }
            
            // Enregistrer l'heure de la dernière activité et régénération
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regenerate'] = time();
            
            // Journal de sécurité
            log_unauthorized_access("Connexion d'administrateur réussie", __FILE__);
            
            // Rediriger vers la page d'origine si elle existe, sinon vers le tableau de bord
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect_url");
            } else {
                header("Location: admin/index.php");
            }
            exit;
        } else {
            // Enregistrer la tentative de connexion échouée
            check_failed_login_attempts($email, 'admin');
            
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
                // Réinitialiser les tentatives de connexion échouées
                reset_failed_login_attempts($email, null, 'client');
                
                // Connexion réussie pour client
                $_SESSION['user_id'] = $client['ClientID'];
                $_SESSION['user_nom'] = $client['Nom'];
                $_SESSION['user_prenom'] = $client['Prenom'];
                $_SESSION['user_email'] = $client['Email'];
                $_SESSION['user_type'] = 'client';
                
                // Compatibilité avec l'ancien système (à supprimer plus tard)
                $_SESSION['client_id'] = $client['ClientID'];
                $_SESSION['client_nom'] = $client['Nom'];
                $_SESSION['client_prenom'] = $client['Prenom'];
                $_SESSION['client_email'] = $client['Email'];
                
                // Gérer la redirection après connexion
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect_url = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirect_url);
                } else {
                    header("Location: mon-compte.php");
                }
                exit;
            } else {
                // Enregistrer la tentative de connexion échouée
                check_failed_login_attempts($email, 'client');
                
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
                    // Réinitialiser les tentatives de connexion échouées
                    reset_failed_login_attempts($email, null, 'client');
                    
                    // Connexion réussie pour utilisateur
                    $_SESSION['user_id'] = $user['UtilisateurID'];
                    $_SESSION['user_nom'] = $user['Nom'];
                    $_SESSION['user_prenom'] = $user['Prenom'];
                    $_SESSION['user_email'] = $user['Email'];
                    $_SESSION['user_type'] = 'client';
                    
                    // Compatibilité avec l'ancien système (à supprimer plus tard)
                    $_SESSION['client_id'] = $user['UtilisateurID'];
                    $_SESSION['client_nom'] = $user['Nom'];
                    $_SESSION['client_prenom'] = $user['Prenom'];
                    $_SESSION['client_email'] = $user['Email'];
                    
                    // Gérer la redirection après connexion
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: " . $redirect_url);
                    } else {
                        header("Location: mon-compte.php");
                    }
                    exit;
                } else {
                    // Enregistrer la tentative de connexion échouée
                    check_failed_login_attempts($email, 'client');
                    
                    $error_message = "Mot de passe incorrect";
                }
            } else {
                if ($is_admin_login) {
                    // Tentative d'accès à un compte administrateur inexistant
                    check_failed_login_attempts($email, 'admin');
                } else {
                    // Tentative d'accès à un compte client inexistant
                    check_failed_login_attempts($email, 'client');
                }
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
    <link rel="stylesheet" href="assets/css/auth-modern.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body class="auth-page">
    <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left-circle"></i> Retour au site public
    </a>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-container">
                <img src="assets/img/favcon.jpeg" alt="Logo La Mangeoire" class="auth-logo">
            </div>
            
            <h2 class="auth-title">Connexion</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="auth-error-message">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="auth-form-group">
                    <i class="bi bi-person auth-form-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="auth-form-group">
                    <i class="bi bi-lock auth-form-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Mot de passe" required>
                </div>
                
                <?php if (isset($_GET['admin']) && $_GET['admin'] == '1'): ?>
                    <input type="hidden" name="admin_login" value="1">
                    <div class="auth-notice">
                        <strong><i class="bi bi-shield-lock"></i> Connexion administrateur</strong>
                        <p style="margin: 8px 0 0 0; font-size: 0.95em;">Vous vous connectez à l'interface d'administration.</p>
                    </div>
                <?php endif; ?>

                <button type="submit" class="auth-btn">
                    <i class="bi bi-box-arrow-in-right auth-btn-icon"></i> Connexion
                </button>
            </form>
            
            <div class="auth-footer">
                <p><a href="mot-de-passe-oublie.php" class="forgotten-password">Mot de passe oublié ?</a></p>
                
                <p style="margin-top: 15px; color: #666;">
                    Pas encore de compte ? <a href="inscription.php" class="auth-link">S'inscrire</a>
                </p>
                
                <?php if (!isset($_GET['admin']) || $_GET['admin'] != '1'): ?>
                    <p style="margin-top: 15px; color: #666;">
                        <a href="connexion-employe.php" class="auth-link">
                            <i class="bi bi-person-badge"></i> Connexion employé
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
    <script src="assets/js/harmonize-auth-styles.js"></script>
</body>
</html>