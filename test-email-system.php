<?php
/**
 * Test du système d'emails SMTP
 */

echo "=== TEST SYSTÈME D'EMAILS SMTP ===\n\n";

require_once 'db_connexion.php';
require_once 'includes/email_manager.php';

// Test 1: Vérification de la configuration
echo "1. Configuration email:\n";
$emailManager = new EmailManager();
$config = $emailManager->testConfiguration();

foreach ($config as $key => $value) {
    echo "   $key: $value\n";
}

echo "\n2. Test d'envoi d'email:\n";

// Test d'envoi de notification
try {
    echo "   Envoi d'un email de test...\n";
    
    $result = $emailManager->sendContactNotification(
        'Test Système',
        'test@example.com',
        'Test automatique du système email',
        'Ceci est un test automatique du système d\'envoi d\'emails.\n\nSi vous recevez ce message, la configuration SMTP fonctionne correctement.\n\nTest effectué le: ' . date('Y-m-d H:i:s')
    );
    
    if ($result) {
        echo "   ✅ Email envoyé avec succès!\n";
        echo "   📧 Vérifiez votre boîte mail: " . getEnvVar('ADMIN_EMAIL') . "\n";
    } else {
        echo "   ❌ Erreur lors de l'envoi\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n3. Test d'email de confirmation:\n";

try {
    $result = $emailManager->sendContactConfirmation(
        'Client Test',
        getEnvVar('ADMIN_EMAIL'), // Envoyer à l'admin pour test
        'Test de confirmation'
    );
    
    if ($result) {
        echo "   ✅ Email de confirmation envoyé!\n";
    } else {
        echo "   ❌ Erreur lors de l'envoi de confirmation\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== VÉRIFICATIONS ===\n";

// Vérifier les variables critiques
$critical_vars = [
    'SMTP_HOST',
    'SMTP_USERNAME', 
    'SMTP_PASSWORD',
    'SMTP_PORT',
    'FROM_EMAIL',
    'ADMIN_EMAIL'
];

$missing = [];
foreach ($critical_vars as $var) {
    $value = getEnvVar($var);
    if (empty($value)) {
        $missing[] = $var;
    }
}

if (empty($missing)) {
    echo "✅ Toutes les variables email sont configurées\n";
} else {
    echo "❌ Variables manquantes: " . implode(', ', $missing) . "\n";
}

// Vérifier le mode de fonctionnement
$test_mode = getEnvVar('EMAIL_TEST_MODE', 'false');
$debug_mode = getEnvVar('EMAIL_DEBUG', 'false');

echo "\nModes de fonctionnement:\n";
echo "   Test mode: " . ($test_mode === 'true' ? '✅ Activé (emails simulés)' : '🔄 Désactivé (emails réels)') . "\n";
echo "   Debug mode: " . ($debug_mode === 'true' ? '✅ Activé' : '❌ Désactivé') . "\n";

echo "\n=== UTILISATION ===\n";
echo "• Pour activer le mode test: EMAIL_TEST_MODE=true dans .env\n";
echo "• Pour voir les logs: EMAIL_DEBUG=true dans .env\n";
echo "• Les emails sont envoyés automatiquement quand un message de contact est soumis\n";

echo "\n🎯 PROCHAINES ÉTAPES:\n";
echo "1. Testez le formulaire de contact sur http://localhost:8000/contact.php\n";
echo "2. Vérifiez votre boîte mail (" . getEnvVar('ADMIN_EMAIL') . ")\n";
echo "3. Consultez les logs d'erreur PHP si nécessaire\n";

?>
