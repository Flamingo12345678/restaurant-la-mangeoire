#!/bin/bash

echo "🚀 DÉPLOIEMENT HTTPS PRODUCTION - La Mangeoire"
echo "=============================================="

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "confirmation-commande.php" ]; then
    echo "❌ Erreur: Veuillez exécuter ce script depuis le répertoire racine du projet"
    exit 1
fi

echo "1️⃣  Configuration des fichiers de production..."

# Copier le fichier .htaccess de production
if [ -f ".htaccess-production" ]; then
    cp .htaccess-production .htaccess
    echo "✅ .htaccess configuré pour la production"
else
    echo "⚠️  .htaccess-production non trouvé"
fi

# Copier la configuration d'environnement
if [ -f ".env.production" ]; then
    echo "⚠️  Pensez à configurer .env.production avec vos vraies clés API"
    echo "   - Clés Stripe LIVE (pk_live_... et sk_live_...)"
    echo "   - Clés PayPal LIVE"
    echo "   - Configuration base de données production"
    echo "   - Configuration SMTP"
else
    echo "⚠️  .env.production non trouvé"
fi

echo ""
echo "2️⃣  Vérification de la configuration..."

# Vérifier PHP
php_version=$(php -v | head -n1 | cut -d" " -f2)
echo "✅ PHP version: $php_version"

# Vérifier les extensions PHP requises
extensions=("pdo" "curl" "json" "openssl" "mbstring")
for ext in "${extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✅ Extension $ext: disponible"
    else
        echo "❌ Extension $ext: MANQUANTE (requis pour les paiements)"
    fi
done

# Vérifier Composer
if command -v composer &> /dev/null; then
    echo "✅ Composer: disponible"
    echo "   💡 Exécutez: composer install --no-dev --optimize-autoloader"
else
    echo "⚠️  Composer non trouvé"
fi

echo ""
echo "3️⃣  Actions requises pour la production:"
echo "========================================"
echo ""
echo "🔐 ÉTAPE 1: CONFIGURER HTTPS"
echo "----------------------------"
echo "Option A - Hébergeur avec SSL automatique:"
echo "  • OVH, Hostinger, SiteGround, etc."
echo "  • Activer SSL dans le panel d'administration"
echo ""
echo "Option B - Cloudflare (GRATUIT):"
echo "  • Créer compte sur cloudflare.com"
echo "  • Ajouter votre domaine"
echo "  • Changer les DNS"
echo "  • Activer 'Full (strict)' SSL"
echo ""
echo "Option C - Let's Encrypt (VPS):"
echo "  • sudo apt install certbot python3-certbot-apache"
echo "  • sudo certbot --apache -d votredomaine.com"
echo ""

echo "🔑 ÉTAPE 2: CONFIGURER LES CLÉS API"
echo "-----------------------------------"
echo "• Stripe Dashboard → Développeurs → Clés API"
echo "  - Récupérer pk_live_... et sk_live_..."
echo "• PayPal Developer → Applications"
echo "  - Passer en mode 'Live'"  
echo "  - Récupérer Client ID et Secret"
echo ""

echo "📧 ÉTAPE 3: CONFIGURER LES EMAILS"
echo "--------------------------------"
echo "• Configuration SMTP (Gmail, SendGrid, etc.)"
echo "• Tester l'envoi d'emails"
echo ""

echo "🗄️  ÉTAPE 4: BASE DE DONNÉES PRODUCTION"
echo "---------------------------------------"
echo "• Créer la base de données de production"
echo "• Importer la structure avec setup-database.php"
echo "• Configurer les accès dans .env"
echo ""

echo "🧪 ÉTAPE 5: TESTS DE PRODUCTION"
echo "-------------------------------"
echo "• Tester les paiements avec de petits montants"
echo "• Vérifier les emails automatiques"
echo "• Contrôler les logs d'erreur"
echo ""

echo "📋 CHECKLIST FINALE"
echo "==================="
echo "□ HTTPS activé et fonctionnel"
echo "□ Certificat SSL valide"
echo "□ Clés Stripe LIVE configurées"
echo "□ PayPal en mode LIVE"
echo "□ Emails SMTP configurés"
echo "□ Base de données de production"
echo "□ Tests de paiement effectués"
echo "□ Logs configurés"
echo "□ Sauvegardes automatiques"
echo ""

echo "🎯 COMMANDES UTILES:"
echo "==================="
echo "# Activer HTTPS dans .htaccess:"
echo "sed -i 's/# RewriteCond %{HTTPS} off/RewriteCond %{HTTPS} off/' .htaccess"
echo "sed -i 's/# RewriteRule/RewriteRule/' .htaccess"
echo ""
echo "# Installer dépendances optimisées:"
echo "composer install --no-dev --optimize-autoloader"
echo ""
echo "# Vérifier la configuration:"
echo "php -f validation-finale-optimisee.php"
echo ""

echo "🚀 VOTRE SYSTÈME EST PRÊT !"
echo "============================"
echo "Il ne manque que la configuration HTTPS et les clés de production."
echo "Une fois configuré, vos clients pourront payer en toute sécurité ! 🔒✨"
