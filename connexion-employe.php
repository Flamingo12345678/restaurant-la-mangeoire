<?php
/**
 * Interface de connexion pour les employés
 * Permet aux employés de se connecter à leur espace dédié
 */

// Inclure le fichier de sécurité pour l'administration qui inclut config.php
require_once 'admin/includes/security_utils.php';

// La session est déjà démarrée dans security_utils.php via config.php

// Vérifier si l'utilisateur est déjà connecté, le rediriger vers la page appropriée
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($_SESSION['user_type'] === 'client') {
        header("Location: mon-compte.php");
        exit;
    } elseif ($_SESSION['user_type'] === 'employe') {
        header("Location: employe/index.php");
        exit;
    }
}

// Vérifier si l'IP est sur liste noire
if (is_ip_blacklisted()) {
    // Afficher un message d'erreur approprié
    $error_message = "Votre adresse IP a été temporairement bloquée en raison de trop nombreuses tentatives de connexion échouées. Veuillez réessayer plus tard.";
} else {
    $error_message = "";
}

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST" && !is_ip_blacklisted()) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Vérifier dans la table Employes
        $query = "SELECT * FROM Employes WHERE Email = ? AND Status = 'Actif'";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $employe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employe && password_verify($password, $employe['MotDePasse'])) {
            // Réinitialiser les tentatives de connexion échouées
            reset_failed_login_attempts($email, null, 'employe');
            
            // Mettre à jour la date de dernière connexion
            try {
                // Vérifier d'abord si la colonne existe
                $check_column = $pdo->query("SHOW COLUMNS FROM Employes LIKE 'DerniereConnexion'");
                if ($check_column->rowCount() > 0) {
                    $update_query = "UPDATE Employes SET DerniereConnexion = NOW() WHERE EmployeID = ?";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->execute([$employe['EmployeID']]);
                } else {
                    // On ne peut pas rediriger ici car on doit continuer le processus de connexion
                    // On journalise juste l'erreur
                    error_log("Colonne DerniereConnexion manquante dans la table Employes");
                }
            } catch (PDOException $e) {
                // Si une erreur se produit, la journaliser mais continuer
                error_log("Erreur lors de la mise à jour de la dernière connexion: " . $e->getMessage());
            }
            
            // Connexion réussie
            $_SESSION['employe_id'] = $employe['EmployeID'];
            $_SESSION['employe_nom'] = $employe['Nom'];
            $_SESSION['employe_prenom'] = $employe['Prenom'];
            $_SESSION['employe_email'] = $employe['Email'];
            $_SESSION['employe_poste'] = $employe['Poste'];
            $_SESSION['user_type'] = 'employe';
            
            // Enregistrer l'heure de la dernière activité et régénération
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regenerate'] = time();
            
            // Journal de sécurité
            log_unauthorized_access("Connexion d'employé réussie: {$employe['Prenom']} {$employe['Nom']} ({$employe['Poste']})", __FILE__);
            
            // Rediriger vers l'espace employé
            header("Location: employe/index.php");
            exit;
        } else {
            if ($employe) {
                // Mot de passe incorrect
                check_failed_login_attempts($email, 'employe');
                $error_message = "Mot de passe incorrect";
            } else {
                // Vérifier si l'email existe dans la table mais est inactif
                $query_inactif = "SELECT * FROM Employes WHERE Email = ? AND Status = 'Inactif'";
                $stmt_inactif = $pdo->prepare($query_inactif);
                $stmt_inactif->bindValue(1, $email, PDO::PARAM_STR);
                $stmt_inactif->execute();
                $employe_inactif = $stmt_inactif->fetch(PDO::FETCH_ASSOC);
                
                if ($employe_inactif) {
                    check_failed_login_attempts($email, 'employe');
                    $error_message = "Votre compte a été désactivé. Veuillez contacter votre administrateur.";
                } else {
                    // Email inconnu
                    check_failed_login_attempts($email, 'employe');
                    $error_message = "Email inconnu ou mot de passe incorrect";
                }
            }
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de connexion: " . $e->getMessage();
        error_log("Erreur de connexion employé: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Employé - Restaurant La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth-modern.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
    </style>
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
            
            <h2 class="auth-title">Espace Employé</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="auth-error-message">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="employe-notice">
                <strong><i class="bi bi-info-circle"></i> Information</strong>
                <p style="margin: 8px 0 0 0; font-size: 0.95em;">Cette page est réservée aux employés du restaurant. Si vous êtes client, veuillez utiliser la <a href="connexion-unifiee.php" class="auth-link">page de connexion client</a>.</p>
            </div>
            
            <form method="POST" action="">
                <div class="auth-form-group">
                    <i class="bi bi-person auth-form-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="auth-form-group">
                    <i class="bi bi-lock auth-form-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Mot de passe" required>
                </div>
                
                <button type="submit" class="auth-btn">
                    <i class="bi bi-box-arrow-in-right auth-btn-icon"></i> Connexion
                </button>
            </form>
            
            <div class="auth-footer">
                <p><a href="mot-de-passe-oublie.php" class="forgotten-password">Mot de passe oublié ?</a></p>
                
                <p style="margin-top: 15px; color: #666;">
                    Vous êtes administrateur ? 
                    <a href="connexion-unifiee.php?admin=1" class="auth-link">
                        <i class="bi bi-shield-lock"></i> Connexion administrateur
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
    <script src="assets/js/harmonize-auth-styles.js"></script>
</body>
</html>
