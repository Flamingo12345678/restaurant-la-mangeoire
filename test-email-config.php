<?php
/**
 * Script de test et configuration des notifications email
 * Ce script aide à diagnostiquer et configurer le système d'email
 */

require_once 'includes/email_notifications.php';

echo "🔧 CONFIGURATION ET TEST DES NOTIFICATIONS EMAIL\n";
echo "=" . str_repeat("=", 55) . "\n\n";

// Test 1: Vérifier PHPMailer
echo "1️⃣ VÉRIFICATION PHPMAILER\n";
echo "-" . str_repeat("-", 30) . "\n";

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer installé et disponible\n";
} else {
    echo "❌ PHPMailer non trouvé. Exécutez : composer require phpmailer/phpmailer\n";
    exit(1);
}

// Test 2: Vérifier la configuration
echo "\n2️⃣ CONFIGURATION EMAIL\n";
echo "-" . str_repeat("-", 25) . "\n";

$config_file = 'config/email_config.php';
if (file_exists($config_file)) {
    $config = include $config_file;
    echo "✅ Fichier de configuration trouvé\n";
    
    // Vérifier les paramètres essentiels
    if (isset($config['admin']['email']) && !empty($config['admin']['email'])) {
        echo "✅ Email administrateur configuré : " . $config['admin']['email'] . "\n";
    } else {
        echo "⚠️ Email administrateur manquant\n";
    }
    
    if (isset($config['smtp']) && !empty($config['smtp']['username'])) {
        echo "✅ SMTP configuré pour : " . $config['smtp']['username'] . "\n";
        if (empty($config['smtp']['password'])) {
            echo "⚠️ ATTENTION : Mot de passe SMTP vide\n";
            echo "   💡 Pour Gmail, générez un 'Mot de passe d'application'\n";
            echo "   🔗 https://myaccount.google.com/apppasswords\n";
        } else {
            echo "✅ Mot de passe SMTP configuré\n";
        }
    } else {
        echo "⚠️ Configuration SMTP incomplète\n";
    }
    
    if ($config['test_mode']) {
        echo "🧪 Mode test activé (Mailtrap)\n";
    } else {
        echo "🚀 Mode production (SMTP réel)\n";
    }
    
} else {
    echo "❌ Fichier de configuration manquant : $config_file\n";
    echo "   Créez ce fichier avec vos paramètres SMTP\n";
}

// Test 3: Test des fonctions PHP mail
echo "\n3️⃣ FONCTION MAIL() PHP\n";
echo "-" . str_repeat("-", 25) . "\n";

if (function_exists('mail')) {
    echo "✅ Fonction mail() disponible\n";
    
    // Test de configuration serveur mail
    $ini_sendmail = ini_get('sendmail_path');
    $ini_smtp = ini_get('SMTP');
    
    if (!empty($ini_sendmail)) {
        echo "✅ Sendmail configuré : $ini_sendmail\n";
    } elseif (!empty($ini_smtp)) {
        echo "✅ SMTP PHP configuré : $ini_smtp\n";
    } else {
        echo "⚠️ Aucune configuration mail détectée dans PHP\n";
        echo "   La fonction mail() peut ne pas fonctionner\n";
    }
} else {
    echo "❌ Fonction mail() non disponible\n";
}

// Test 4: Test d'envoi
echo "\n4️⃣ TEST D'ENVOI\n";
echo "-" . str_repeat("-", 20) . "\n";

if (isset($_GET['test']) && $_GET['test'] === 'email') {
    echo "🚀 Lancement du test d'envoi...\n";
    
    try {
        $emailNotification = new EmailNotification();
        $result = $emailNotification->testEmailConfiguration();
        
        if ($result) {
            echo "✅ EMAIL ENVOYÉ AVEC SUCCÈS !\n";
            echo "   Vérifiez votre boîte mail : la-mangeoire@gmail.com\n";
            echo "   (Pensez à vérifier le dossier spam)\n";
        } else {
            echo "❌ Échec de l'envoi d'email\n";
            echo "   Vérifiez les logs pour plus de détails\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur lors du test : " . $e->getMessage() . "\n";
    }
} else {
    echo "💡 Pour tester l'envoi d'email, ajoutez ?test=email à l'URL\n";
    echo "   Exemple : http://localhost/test-email-config.php?test=email\n";
}

// Test 5: Logs
echo "\n5️⃣ LOGS ET DIAGNOSTICS\n";
echo "-" . str_repeat("-", 25) . "\n";

$log_files = [
    'logs/email_notifications.log' => 'Logs notifications email',
    'logs/contact_messages.log' => 'Logs messages de contact'
];

foreach ($log_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ $description : $file ($size bytes)\n";
        
        if ($size > 0) {
            echo "   📄 Dernières lignes :\n";
            $lines = file($file);
            $last_lines = array_slice($lines, -3);
            foreach ($last_lines as $line) {
                echo "      " . trim($line) . "\n";
            }
        }
    } else {
        echo "📝 $description : $file (vide)\n";
    }
}

// Instructions finales
echo "\n🎯 INSTRUCTIONS DE CONFIGURATION\n";
echo "=" . str_repeat("=", 35) . "\n";
echo "1. 📧 GMAIL (recommandé) :\n";
echo "   - Activez l'authentification à 2 facteurs\n";
echo "   - Générez un mot de passe d'application\n";
echo "   - Mettez à jour config/email_config.php\n";
echo "\n";
echo "2. 🧪 MAILTRAP (pour tests) :\n";
echo "   - Créez un compte sur https://mailtrap.io\n";
echo "   - Configurez les identifiants Mailtrap\n";
echo "   - Activez 'test_mode' => true\n";
echo "\n";
echo "3. 🚀 TEST :\n";
echo "   - Exécutez ce script avec ?test=email\n";
echo "   - Vérifiez la réception dans votre boîte mail\n";
echo "\n";
echo "4. 🔧 PRODUCTION :\n";
echo "   - Configurez le SMTP de votre hébergeur\n";
echo "   - Testez régulièrement les notifications\n";
echo "\n";

if (!isset($_GET['test'])) {
    echo "🔗 TESTER MAINTENANT :\n";
    echo "   <a href='" . $_SERVER['PHP_SELF'] . "?test=email'>Cliquez ici pour tester l'envoi d'email</a>\n";
}
?>
