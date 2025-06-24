<?php
/**
 * Script de vérification finale du système d'administration
 */

echo "=== VÉRIFICATION DU SYSTÈME D'ADMINISTRATION ===\n\n";

// 1. Vérifier la base de données
require_once 'db_connexion.php';

echo "1. Vérification de la base de données:\n";
try {
    $superadmin_query = "SELECT AdminID, Email, Role FROM Administrateurs WHERE Role = 'superadmin'";
    $superadmins = $pdo->query($superadmin_query)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   ✓ Nombre de superadmins: " . count($superadmins) . "\n";
    foreach ($superadmins as $admin) {
        echo "     - ID: {$admin['AdminID']}, Email: {$admin['Email']}\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erreur DB: " . $e->getMessage() . "\n";
}

// 2. Vérifier les fichiers de sécurité
echo "\n2. Vérification des fichiers:\n";

$files_to_check = [
    'admin/includes/security_utils.php' => 'Utilitaires de sécurité',
    'admin/header_template.php' => 'Template header admin',
    'admin/administrateurs.php' => 'Page administrateurs',
    'admin/activity_log.php' => 'Page logs d\'activité',
    'connexion-unifiee.php' => 'Page de connexion unifiée'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description (fichier manquant: $file)\n";
    }
}

// 3. Vérifier les fonctions importantes
echo "\n3. Vérification des fonctions:\n";

require_once 'admin/includes/security_utils.php';

$functions_to_check = [
    'is_admin' => 'Vérification admin',
    'require_superadmin' => 'Exigence superadmin',
    'get_admin_login_url' => 'URL de connexion admin'
];

foreach ($functions_to_check as $func => $description) {
    if (function_exists($func)) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description (fonction manquante: $func)\n";
    }
}

echo "\n=== INSTRUCTIONS DE TEST ===\n";
echo "Pour tester le système:\n";
echo "1. Ouvrez votre navigateur\n";
echo "2. Allez sur: connexion-unifiee.php?admin=1\n";
echo "3. Connectez-vous avec: superadmin@lamangeoire.fr / SuperAdmin123!\n";
echo "4. Vérifiez que la sidebar contient les liens 'Administrateurs' et 'Logs d'activité'\n";
echo "5. Cliquez sur ces liens pour vérifier qu'ils fonctionnent correctement\n\n";

echo "✓ Vérification terminée!\n";
?>
