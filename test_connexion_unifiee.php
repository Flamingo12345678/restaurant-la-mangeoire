<?php
/**
 * Script de test final pour la connexion unifiée
 * Ce script simule le processus de connexion réel sans accéder à la base de données
 */

// Activer la mise en tampon de sortie
ob_start();

// Inclure la configuration de session
require_once dirname(__FILE__) . '/includes/session_config.php';

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Simuler une tentative de connexion si le formulaire est soumis
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_test') {
    $test_email = $_POST['email'] ?? '';
    $test_password = $_POST['password'] ?? '';
    
    // Simple test - accepte admin@test.com avec mot de passe 'admin123'
    if ($test_email === 'admin@test.com' && $test_password === 'admin123') {
        // Simuler une connexion admin réussie
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_email'] = $test_email;
        $_SESSION['admin_nom'] = 'Admin';
        $_SESSION['admin_prenom'] = 'Test';
        $_SESSION['user_type'] = 'admin';
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regenerate'] = time();
        
        $success_message = "Connexion admin simulée réussie !";
    }
    // Simple test - accepte client@test.com avec mot de passe 'client123'
    elseif ($test_email === 'client@test.com' && $test_password === 'client123') {
        // Simuler une connexion client réussie
        $_SESSION['client_id'] = 1;
        $_SESSION['client_email'] = $test_email;
        $_SESSION['client_nom'] = 'Client';
        $_SESSION['client_prenom'] = 'Test';
        $_SESSION['user_type'] = 'client';
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regenerate'] = time();
        
        $success_message = "Connexion client simulée réussie !";
    }
    else {
        $error_message = "Email ou mot de passe incorrect.";
    }
}

// Simuler une déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Sauvegarder le type d'utilisateur pour le message
    $user_type = $_SESSION['user_type'] ?? 'inconnu';
    
    // Détruire la session
    session_unset();
    session_destroy();
    
    // Démarrer une nouvelle session
    session_start();
    
    $success_message = "Déconnexion ($user_type) réussie !";
}

// Créer le HTML de réponse
$html_output = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Connexion - La Mangeoire</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        form { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #ce1212; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #b31010; }
        .session-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px; }
        a { color: #ce1212; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test de Connexion Unifiée</h1>';

// Afficher les messages
if (!empty($error_message)) {
    $html_output .= '<div class="card"><p class="error">' . htmlspecialchars($error_message) . '</p></div>';
}

if (!empty($success_message)) {
    $html_output .= '<div class="card"><p class="success">' . htmlspecialchars($success_message) . '</p></div>';
}

// Afficher l'état de connexion actuel
$html_output .= '
        <div class="card">
            <h2>État de connexion</h2>';

if (isset($_SESSION['user_type'])) {
    $html_output .= '
            <p>Vous êtes connecté en tant que <strong>' . htmlspecialchars($_SESSION['user_type']) . '</strong>.</p>
            <p>Email: ' . htmlspecialchars($_SESSION['admin_email'] ?? $_SESSION['client_email'] ?? 'Non défini') . '</p>
            <p>Nom: ' . htmlspecialchars(($_SESSION['admin_prenom'] ?? $_SESSION['client_prenom'] ?? '') . ' ' . ($_SESSION['admin_nom'] ?? $_SESSION['client_nom'] ?? '')) . '</p>
            <p>ID: ' . htmlspecialchars($_SESSION['admin_id'] ?? $_SESSION['client_id'] ?? 'Non défini') . '</p>
            <p>Dernière activité: ' . date('H:i:s', $_SESSION['last_activity'] ?? time()) . '</p>
            <p><a href="?action=logout">Se déconnecter</a></p>';
} else {
    $html_output .= '
            <p>Vous n\'êtes pas connecté.</p>';
}

// Formulaire de connexion de test
$html_output .= '
        </div>
        
        <div class="card">
            <h2>Tester la connexion</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="login_test">
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required placeholder="email@example.com">
                </div>
                <div>
                    <label for="password">Mot de passe:</label>
                    <input type="password" id="password" name="password" required placeholder="Votre mot de passe">
                </div>
                <div>
                    <button type="submit">Se connecter</button>
                </div>
            </form>
            
            <p>Comptes de test:</p>
            <ul>
                <li>Admin: <code>admin@test.com</code> / <code>admin123</code></li>
                <li>Client: <code>client@test.com</code> / <code>client123</code></li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Informations de session</h2>
            <div class="session-info">
                <p>ID de session: <code>' . session_id() . '</code></p>
                <p>Session démarrée: <code>' . (session_status() === PHP_SESSION_ACTIVE ? 'Oui' : 'Non') . '</code></p>
                <p>Headers déjà envoyés: <code>' . (headers_sent() ? 'Oui' : 'Non') . '</code></p>
                <p>Output buffering: <code>' . ob_get_level() . '</code></p>
                <p>Nombre de variables de session: <code>' . count($_SESSION ?? []) . '</code></p>
            </div>
        </div>
        
        <div class="card">
            <h2>Navigation</h2>
            <p><a href="connexion-unifiee.php">Page de connexion réelle</a> | 
               <a href="session_diagnostic.php">Diagnostic de session</a> | 
               <a href="check_session_settings.php">Vérifier les paramètres</a> | 
               <a href="index.php">Accueil</a></p>
        </div>
    </div>
</body>
</html>';

// Envoyer la réponse
ob_end_clean();
echo $html_output;
