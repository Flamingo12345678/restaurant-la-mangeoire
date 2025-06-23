<?php
/**
 * Script de vérification des permissions et configuration pour la production
 * À exécuter après déploiement pour s'assurer que tout fonctionne
 */

echo "=== VÉRIFICATION SYSTÈME - RESTAURANT LA MANGEOIRE ===\n\n";

// 1. Vérification du dossier logs
echo "1. Vérification du dossier logs...\n";
$logs_dir = __DIR__ . '/logs';

if (!is_dir($logs_dir)) {
    echo "❌ Dossier logs n'existe pas. Tentative de création...\n";
    if (mkdir($logs_dir, 0755, true)) {
        echo "✅ Dossier logs créé avec succès\n";
    } else {
        echo "❌ Impossible de créer le dossier logs\n";
        echo "⚠️  Fallback : Les logs utiliseront " . sys_get_temp_dir() . "\n";
    }
} else {
    echo "✅ Dossier logs existe\n";
}

// 2. Vérification des permissions d'écriture
echo "\n2. Vérification des permissions d'écriture...\n";
if (is_writable($logs_dir)) {
    echo "✅ Permissions d'écriture OK sur $logs_dir\n";
} else {
    echo "❌ Pas de permissions d'écriture sur $logs_dir\n";
    echo "💡 Solution : chmod 755 $logs_dir ou chown approprié\n";
}

// 3. Test d'écriture réel
echo "\n3. Test d'écriture dans les logs...\n";
try {
    $test_content = date('Y-m-d H:i:s') . " - Test de déploiement production\n";
    $test_file = $logs_dir . '/deployment_test.log';
    
    $result = file_put_contents($test_file, $test_content, FILE_APPEND | LOCK_EX);
    
    if ($result !== false) {
        echo "✅ Test d'écriture réussi\n";
        echo "📄 Fichier test créé : $test_file\n";
        
        // Nettoyage
        if (file_exists($test_file)) {
            unlink($test_file);
            echo "✅ Fichier test supprimé\n";
        }
    } else {
        echo "❌ Échec du test d'écriture\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors du test : " . $e->getMessage() . "\n";
}

// 4. Vérification de la configuration email
echo "\n4. Vérification de la configuration email...\n";
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    echo "✅ Fichier .env présent\n";
    
    // Vérifier les variables email importantes
    $required_vars = [
        'ADMIN_EMAIL',
        'SMTP_HOST',
        'SMTP_USERNAME',
        'EMAIL_TEST_MODE'
    ];
    
    $env_content = file_get_contents($env_file);
    foreach ($required_vars as $var) {
        if (strpos($env_content, $var . '=') !== false) {
            echo "✅ Variable $var configurée\n";
        } else {
            echo "❌ Variable $var manquante\n";
        }
    }
} else {
    echo "❌ Fichier .env manquant\n";
    echo "💡 Copiez .env.example vers .env et configurez\n";
}

// 5. Test du système de notification
echo "\n5. Test du système de notification...\n";
try {
    require_once __DIR__ . '/includes/email_notifications.php';
    
    $notifier = new EmailNotifications();
    echo "✅ Classe EmailNotifications chargée\n";
    
    // Test de log simple
    $test_data = [
        'nom' => 'Test Production',
        'email' => 'test@production.com',
        'objet' => 'Test déploiement',
        'message' => 'Test du système en production'
    ];
    
    $notifier->logNewMessage($test_data);
    echo "✅ Test de logging réussi\n";
    
} catch (Exception $e) {
    echo "❌ Erreur système de notification : " . $e->getMessage() . "\n";
}

// 6. Informations sur l'environnement
echo "\n6. Informations environnement...\n";
echo "📍 PHP Version: " . PHP_VERSION . "\n";
echo "📍 Dossier de travail: " . getcwd() . "\n";
echo "📍 Utilisateur web: " . get_current_user() . "\n";
echo "📍 Dossier temporaire: " . sys_get_temp_dir() . "\n";

// 7. Recommandations de sécurité
echo "\n7. Recommandations de sécurité...\n";
if (is_dir($logs_dir)) {
    $htaccess_file = $logs_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        echo "⚠️  Créer un fichier .htaccess dans logs/ pour sécuriser\n";
        echo "💡 Contenu suggéré :\n";
        echo "   Order deny,allow\n";
        echo "   Deny from all\n\n";
        
        // Créer automatiquement le .htaccess de sécurité
        $htaccess_content = "# Sécurité - Interdire l'accès web aux logs\nOrder deny,allow\nDeny from all\n";
        if (file_put_contents($htaccess_file, $htaccess_content)) {
            echo "✅ Fichier .htaccess de sécurité créé automatiquement\n";
        }
    } else {
        echo "✅ Fichier .htaccess de sécurité présent\n";
    }
}

echo "\n=== VÉRIFICATION TERMINÉE ===\n";
echo "Si tous les tests sont ✅, le système est prêt pour la production.\n";
echo "En cas de ❌, consultez la documentation ou contactez l'administrateur.\n\n";
?>
