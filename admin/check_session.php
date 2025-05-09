<?php
// Debug admin - fichier pour vérifier l'état de la session dans le dossier admin
session_start();

echo "<h1>Vérification de session admin</h1>";

if (!file_exists('../debug_log.txt')) {
    file_put_contents('../debug_log.txt', date('Y-m-d H:i:s') . " - Fichier de débogage créé\n");
}

function debug_log($message) {
    file_put_contents('../debug_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    echo "<p>Log: " . htmlspecialchars($message) . "</p>";
}

debug_log("Page de vérification admin ouverte");

// Informations de session
echo "<h2>Informations de session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Vérification de session
if (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
    debug_log("Session admin valide");
    echo "<p style='color:green;'>✓ Session administrateur valide.</p>";
    echo "<p>ID Admin: " . $_SESSION['admin_id'] . "</p>";
    echo "<p>Nom: " . $_SESSION['admin_nom'] . " " . $_SESSION['admin_prenom'] . "</p>";
    echo "<p>Email: " . $_SESSION['admin_email'] . "</p>";
    echo "<p>Type: " . $_SESSION['user_type'] . "</p>";
    
    echo "<p><a href='index.php'>Accéder au tableau de bord</a></p>";
} else {
    debug_log("Session admin invalide");
    
    if (!isset($_SESSION['admin_id'])) {
        debug_log("admin_id n'est pas défini");
        echo "<p style='color:red;'>✗ admin_id n'est pas défini dans la session.</p>";
    }
    
    if (!isset($_SESSION['user_type'])) {
        debug_log("user_type n'est pas défini");
        echo "<p style='color:red;'>✗ user_type n'est pas défini dans la session.</p>";
    } else if ($_SESSION['user_type'] !== 'admin') {
        debug_log("user_type n'est pas 'admin', mais: " . $_SESSION['user_type']);
        echo "<p style='color:red;'>✗ user_type n'est pas 'admin', mais: " . htmlspecialchars($_SESSION['user_type']) . "</p>";
    }
    
    echo "<p><a href='../auth_check.php'>Revenir à la page de vérification</a></p>";
}

// Informations de débogage
echo "<h2>Informations de débogage</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session save path: " . session_save_path() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";

debug_log("Fin de la vérification admin");
?>
