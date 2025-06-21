<?php
/**
 * Script pour vider la liste noire et réinitialiser les tentatives échouées
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier de configuration
require_once __DIR__ . '/includes/config.php';
// Inclure la connexion à la base de données
require_once __DIR__ . '/db_connexion.php';

$messages = [];

// Vider la table IPBlacklist
try {
    // Vérifier si la table existe
    $check_table = $conn->query("SHOW TABLES LIKE 'IPBlacklist'");
    
    if ($check_table->rowCount() > 0) {
        // Vider la table
        $conn->exec("TRUNCATE TABLE IPBlacklist");
        $messages[] = [
            'type' => 'success',
            'text' => 'La liste noire a été vidée avec succès.'
        ];
    } else {
        $messages[] = [
            'type' => 'info',
            'text' => 'La table IPBlacklist n\'existe pas.'
        ];
    }
} catch (PDOException $e) {
    $messages[] = [
        'type' => 'error',
        'text' => 'Erreur lors de la vidange de la liste noire: ' . $e->getMessage()
    ];
}

// Vider la table FailedLoginAttempts
try {
    // Vérifier si la table existe
    $check_table = $conn->query("SHOW TABLES LIKE 'FailedLoginAttempts'");
    
    if ($check_table->rowCount() > 0) {
        // Vider la table
        $conn->exec("TRUNCATE TABLE FailedLoginAttempts");
        $messages[] = [
            'type' => 'success',
            'text' => 'Les tentatives de connexion échouées ont été réinitialisées avec succès.'
        ];
    } else {
        $messages[] = [
            'type' => 'info',
            'text' => 'La table FailedLoginAttempts n\'existe pas.'
        ];
    }
} catch (PDOException $e) {
    $messages[] = [
        'type' => 'error',
        'text' => 'Erreur lors de la réinitialisation des tentatives de connexion: ' . $e->getMessage()
    ];
}

// Vérifier l'état de configuration
$is_local_env = defined('IS_LOCAL_ENV') ? (IS_LOCAL_ENV ? 'TRUE' : 'FALSE') : 'Non définie';
$is_local_detected = function_exists('is_local_environment') ? (is_local_environment() ? 'TRUE' : 'FALSE') : 'Fonction non définie';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du système de blocage</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .message { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .button-container { margin-top: 20px; }
        .button { display: inline-block; background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Réinitialisation du système de blocage</h1>
        
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['type']; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endforeach; ?>
        
        <h2>État actuel du système</h2>
        <table>
            <tr>
                <th>Paramètre</th>
                <th>Valeur</th>
            </tr>
            <tr>
                <td>IS_LOCAL_ENV (config.php)</td>
                <td><?php echo $is_local_env; ?></td>
            </tr>
            <tr>
                <td>is_local_environment() retourne</td>
                <td><?php echo $is_local_detected; ?></td>
            </tr>
            <tr>
                <td>Blocage d'IP actif</td>
                <td><?php echo ($is_local_env === 'TRUE' || $is_local_detected === 'TRUE') ? 'NON' : 'OUI'; ?></td>
            </tr>
        </table>
        
        <h2>Instructions pour réactiver le blocage</h2>
        <ol>
            <li>Ouvrez le fichier <code>includes/config.php</code></li>
            <li>Changez la valeur de <code>IS_LOCAL_ENV</code> de <code>true</code> à <code>false</code></li>
            <li>Ouvrez le fichier <code>admin/includes/security_utils.php</code></li>
            <li>Modifiez la fonction <code>is_local_environment()</code> pour qu'elle retourne <code>false</code> au lieu de <code>true</code></li>
            <li>Modifiez la fonction <code>is_ip_blacklisted()</code> pour supprimer le <code>return false;</code> au début de la fonction</li>
        </ol>
        
        <div class="button-container">
            <a href="index.php" class="button">Retour à l'accueil</a>
            <a href="diagnostic_blocage.php" class="button">Diagnostic complet</a>
        </div>
    </div>
</body>
</html>
