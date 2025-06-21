<?php
/**
 * Configuration SMTP pour les notifications email
 * Utilise les variables d'environnement du fichier .env
 * SÉCURISÉ : Les mots de passe ne sont plus dans le code source
 */

// Charger les variables d'environnement depuis .env
if (!function_exists('loadEmailEnvVariables')) {
    function loadEmailEnvVariables($envFile) {
        if (!file_exists($envFile)) {
            return false;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Ignorer les commentaires
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, ' "\''); // Enlever les guillemets
            
            if (!empty($name) && !empty($value)) {
                $_ENV[$name] = $value;
                // Aussi dans $_SERVER pour compatibilité
                $_SERVER[$name] = $value;
            }
        }
        return true;
    }
}

// Charger le fichier .env
$envFile = __DIR__ . '/../.env';
loadEmailEnvVariables($envFile);

return [
    // Email de l'administrateur qui recevra les notifications
    'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com',
    'admin_name' => $_ENV['ADMIN_NAME'] ?? 'Restaurant La Mangeoire',
    
    // Email d'expédition (FROM)
    'from_email' => $_ENV['FROM_EMAIL'] ?? $_ENV['ADMIN_EMAIL'] ?? 'noreply@example.com',
    'from_name' => $_ENV['FROM_NAME'] ?? $_ENV['ADMIN_NAME'] ?? 'Restaurant La Mangeoire',
    
    // Mode de test (true = utilise Mailtrap, false = utilise SMTP réel)
    'test_mode' => filter_var($_ENV['EMAIL_TEST_MODE'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    // Configuration Mailtrap (pour les tests)
    'mailtrap' => [
        'host' => $_ENV['MAILTRAP_HOST'] ?? 'sandbox.smtp.mailtrap.io',
        'username' => $_ENV['MAILTRAP_USERNAME'] ?? '',
        'password' => $_ENV['MAILTRAP_PASSWORD'] ?? '',
        'port' => (int)($_ENV['MAILTRAP_PORT'] ?? 2525),
        'encryption' => 'tls',
        'auth' => true
    ],
    
    // Configuration SMTP pour la production
    'smtp' => [
        'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'username' => $_ENV['SMTP_USERNAME'] ?? '',
        'password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'port' => (int)($_ENV['SMTP_PORT'] ?? 587),
        'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
        'auth' => true
    ],
    
    // Paramètres de fallback (si SMTP échoue)
    'fallback_to_mail' => filter_var($_ENV['EMAIL_FALLBACK_TO_MAIL'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    // Activation des logs de debug
    'debug' => filter_var($_ENV['EMAIL_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    
    // Templates d'emails
    'templates' => [
        'new_contact' => [
            'subject' => '[La Mangeoire] Nouveau message de contact',
            'template' => 'new_contact_notification'
        ]
    ]
];

/*
=== NOUVELLE CONFIGURATION SÉCURISÉE ===

Les identifiants sensibles sont maintenant stockés dans le fichier .env
Ce fichier ne doit JAMAIS être commité dans le dépôt Git.

Pour configurer :
1. Éditez le fichier .env à la racine du projet
2. Modifiez les variables EMAIL_* selon vos besoins
3. Pour Gmail : utilisez un mot de passe d'application dans SMTP_PASSWORD
4. Pour Mailtrap : remplissez MAILTRAP_USERNAME et MAILTRAP_PASSWORD

Variables disponibles dans .env :
- ADMIN_EMAIL : Email qui recevra les notifications
- FROM_EMAIL : Email d'expédition
- EMAIL_TEST_MODE : true/false pour activer Mailtrap
- SMTP_* : Configuration Gmail/SMTP production
- MAILTRAP_* : Configuration Mailtrap pour tests

=== SÉCURITÉ ===
✅ Mots de passe dans .env (non versionné)
✅ Valeurs par défaut sécurisées
✅ Validation des booléens et entiers
*/
?>
