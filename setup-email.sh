#!/bin/bash

echo "🔧 CONFIGURATION EMAIL - RESTAURANT LA MANGEOIRE"
echo "=============================================="
echo ""

# Vérifier l'état actuel
echo "📋 DIAGNOSTIC ACTUEL :"
echo "----------------------"

if [ -f "config/email_config.php" ]; then
    echo "✅ Fichier de configuration trouvé"
else
    echo "❌ Fichier de configuration manquant"
    exit 1
fi

if [ -f "vendor/autoload.php" ]; then
    echo "✅ PHPMailer installé"
else
    echo "❌ PHPMailer manquant - Installation..."
    composer install
fi

echo ""
echo "🎯 PROCHAINES ÉTAPES :"
echo "====================="
echo ""
echo "OPTION 1 - TEST RAPIDE AVEC MAILTRAP (5 minutes) :"
echo "---------------------------------------------------"
echo "1. Créer un compte gratuit sur https://mailtrap.io"
echo "2. Copier username/password depuis votre inbox Mailtrap"
echo "3. Modifier config/email_config.php :"
echo "   - 'test_mode' => true"
echo "   - Remplir username/password dans la section 'mailtrap'"
echo "4. Tester : php test-email-config.php?test=email"
echo "5. Vérifier l'email dans l'interface Mailtrap"
echo ""
echo "OPTION 2 - PRODUCTION AVEC GMAIL :"
echo "-----------------------------------"
echo "1. Activer l'authentification à 2 facteurs sur Gmail"
echo "2. Générer un mot de passe d'app : https://myaccount.google.com/apppasswords"
echo "3. Modifier config/email_config.php :"
echo "   - 'test_mode' => false"
echo "   - Remplir le mot de passe dans 'smtp.password'"
echo "4. Tester : php test-email-config.php?test=email"
echo ""
echo "📞 SUPPORT : Consultez GUIDE_EMAIL_CONFIGURATION.md pour plus de détails"
echo ""

# Tester la configuration actuelle
echo "🧪 TEST DE CONFIGURATION :"
echo "--------------------------"
php -f test-email-config.php 2>/dev/null || echo "⚠️  Configuration incomplète - suivez les étapes ci-dessus"
echo ""

echo "✨ Une fois configuré, vos formulaires de contact enverront automatiquement des emails !"
