<?php
/**
 * Configuration SMTP pour les notifications email
 * IMPORTANT : Configurez ces paramètres selon votre fournisseur email
 */

return [
    // Email de l'administrateur qui recevra les notifications
    'admin_email' => 'ernestyombi20@gmail.com',
    'admin_name' => 'Restaurant La Mangeoire',
    
    // Email d'expédition (FROM)
    'from_email' => 'ernestyombi20@gmail.com',
    'from_name' => 'Restaurant La Mangeoire',
    
    // Mode de test (true = utilise Mailtrap, false = utilise SMTP réel)
    'test_mode' => false,  // ← CHANGÉ À FALSE POUR UTILISER GMAIL
    
    // Configuration Mailtrap (pour les tests)
    'mailtrap' => [
        'host' => 'sandbox.smtp.mailtrap.io',
        'username' => 'VOTRE_USERNAME_MAILTRAP',  // ← À REMPLACER
        'password' => 'VOTRE_PASSWORD_MAILTRAP',  // ← À REMPLACER
        'port' => 2525,
        'encryption' => 'tls',
        'auth' => true
    ],
    
    // Configuration SMTP pour la production (Gmail, Outlook, etc.)
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'username' => 'ernestyombi20@gmail.com',
        'password' => 'ptihyioqshfdqykb',  // ← MOT DE PASSE D'APPLICATION GOOGLE (16 caractères)
        'port' => 587,
        'encryption' => 'tls',
        'auth' => true
    ],
    
    // Paramètres de fallback (si SMTP échoue)
    'fallback_to_mail' => true,
    
    // Activation des logs de debug
    'debug' => true,
    
    // Templates d'emails
    'templates' => [
        'new_contact' => [
            'subject' => '[La Mangeoire] Nouveau message de contact',
            'template' => 'new_contact_notification'
        ]
    ]
];

/*
=== INSTRUCTIONS DE CONFIGURATION ===

1. GMAIL (recommandé) :
   - Activez l'authentification à 2 facteurs sur votre compte Gmail
   - Générez un "Mot de passe d'application" : https://myaccount.google.com/apppasswords
   - Remplacez 'password' => '' par 'password' => 'votre_mot_de_passe_application'

2. MAILTRAP (pour tests) :
   - Créez un compte gratuit sur https://mailtrap.io
   - Récupérez username/password dans votre inbox Mailtrap
   - Mettez 'test_mode' => true

3. AUTRES FOURNISSEURS :
   - Adaptez host, port, encryption selon votre fournisseur
   - Ex: Outlook/Hotmail, Yahoo, serveur SMTP personnalisé

=== SÉCURITÉ ===
- Ne jamais commiter ce fichier avec de vrais mots de passe
- Utilisez des variables d'environnement en production
- Alternativement, créez un .env file
*/
?>
