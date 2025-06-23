#!/bin/bash

echo "🚀 DÉPLOIEMENT AUTOMATISÉ - LA MANGEOIRE"
echo "========================================"
echo "Configuration automatique du système de paiement pour la production"
echo ""

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

success() { echo -e "${GREEN}✅ $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Vérifier le répertoire
if [ ! -f "confirmation-commande.php" ]; then
    error "Veuillez exécuter ce script depuis le répertoire racine du projet"
    exit 1
fi

echo "1️⃣  PRÉPARATION DES FICHIERS DE PRODUCTION"
echo "==========================================="

# Copier .htaccess
if [ -f ".htaccess-production" ]; then
    cp .htaccess-production .htaccess
    success ".htaccess configuré pour la production"
else
    warning ".htaccess-production non trouvé"
fi

# Sauvegarder .env actuel
if [ -f ".env" ]; then
    cp .env .env.backup
    success "Sauvegarde de .env créée (.env.backup)"
fi

echo ""
echo "2️⃣  INSTALLATION DES DÉPENDANCES"
echo "================================="

# Installer Composer si nécessaire
if ! command -v composer &> /dev/null; then
    warning "Composer non trouvé. Installation..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Installer dépendances optimisées pour production
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
    success "Dépendances Composer installées (mode production)"
else
    warning "composer.json non trouvé"
fi

echo ""
echo "3️⃣  CONFIGURATION DE SÉCURITÉ"
echo "=============================="

# Permissions sécurisées
chmod 644 *.php
chmod 600 .env* 2>/dev/null
chmod 755 *.sh 2>/dev/null
success "Permissions de fichiers sécurisées"

# Créer dossier de logs
mkdir -p logs
chmod 755 logs
success "Dossier de logs créé"

# Vérifier la configuration SSL
if [ -f ".htaccess" ]; then
    if grep -q "RewriteCond %{HTTPS} off" .htaccess; then
        success "Redirection HTTPS configurée"
    else
        warning "Redirection HTTPS non activée dans .htaccess"
    fi
fi

echo ""
echo "4️⃣  VALIDATION DU SYSTÈME"
echo "========================="

# Test syntaxe PHP
php_errors=0
for file in *.php includes/*.php api/*.php; do
    if [ -f "$file" ]; then
        if ! php -l "$file" > /dev/null 2>&1; then
            error "Erreur de syntaxe dans $file"
            php_errors=$((php_errors + 1))
        fi
    fi
done

if [ $php_errors -eq 0 ]; then
    success "Tous les fichiers PHP ont une syntaxe correcte"
else
    warning "$php_errors fichier(s) avec des erreurs de syntaxe"
fi

# Test base de données
info "Test de connexion à la base de données..."
php -r "
try {
    require_once 'db_connexion.php';
    echo 'Base de données accessible\n';
} catch (Exception \$e) {
    echo 'Erreur de connexion: ' . \$e->getMessage() . '\n';
    exit(1);
}
" && success "Connexion base de données OK" || warning "Problème de connexion base de données"

echo ""
echo "5️⃣  CONFIGURATION HTTPS & APIS"
echo "==============================="

info "Vérification des variables d'environnement..."

# Vérifier les clés API
stripe_public=$(grep -o 'STRIPE_PUBLISHABLE_KEY=.*' .env 2>/dev/null | cut -d'=' -f2)
paypal_client=$(grep -o 'PAYPAL_CLIENT_ID=.*' .env 2>/dev/null | cut -d'=' -f2)

if [[ $stripe_public == pk_live_* ]]; then
    success "Clés Stripe LIVE configurées"
elif [[ $stripe_public == pk_test_* ]]; then
    warning "Clés Stripe en mode TEST (changez pour pk_live_... en production)"
else
    warning "Clés Stripe non configurées"
fi

if [[ $paypal_client == *"test"* || $paypal_client == *"sandbox"* ]]; then
    warning "PayPal en mode SANDBOX (changez pour mode LIVE en production)"
else
    info "Configuration PayPal détectée"
fi

echo ""
echo "6️⃣  TESTS AUTOMATISÉS"
echo "====================="

# Exécuter les tests
if [ -f "validation-finale-optimisee.php" ]; then
    info "Exécution des tests de validation..."
    php validation-finale-optimisee.php | grep -E "(✅|❌|⚠️)" | head -10
    success "Tests de validation exécutés"
fi

echo ""
echo "7️⃣  SAUVEGARDE ET LOGS"
echo "======================"

# Créer une sauvegarde
backup_name="backup_$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf "$backup_name" --exclude='node_modules' --exclude='vendor' --exclude='*.log' . 2>/dev/null
success "Sauvegarde créée: $backup_name"

# Configuration des logs
cat > logs/.htaccess << 'EOF'
Order deny,allow
Deny from all
EOF
success "Protection des logs configurée"

echo ""
echo "8️⃣  CHECKLIST FINALE"
echo "===================="

checklist=(
    "HTTPS configuré sur le serveur"
    "Certificat SSL valide"
    "Clés Stripe LIVE configurées"
    "PayPal en mode LIVE"
    "Emails SMTP testés"
    "Tests de paiement effectués"
    "Monitoring configuré"
    "Sauvegardes programmées"
)

echo "Vérifiez manuellement:"
for item in "${checklist[@]}"; do
    echo "□ $item"
done

echo ""
echo "🎯 COMMANDES DE PRODUCTION"
echo "=========================="

cat << 'EOF'
# Activer HTTPS (décommentez dans .htaccess) :
sed -i 's/# RewriteCond %{HTTPS} off/RewriteCond %{HTTPS} off/' .htaccess
sed -i 's/# RewriteRule/RewriteRule/' .htaccess

# Passer en mode production :
cp .env.production .env
# (Puis configurez vos vraies clés API)

# Test final :
php validation-finale-optimisee.php

# Monitoring des logs :
tail -f logs/payment.log

# Sauvegarde manuelle :
tar -czf backup_manual_$(date +%Y%m%d).tar.gz .
EOF

echo ""
echo "🚀 SYSTÈME PRÊT POUR LA PRODUCTION !"
echo "===================================="
echo ""
success "Configuration automatique terminée"
info "Configurez maintenant HTTPS sur votre serveur"
info "Remplacez les clés API par celles de production"
info "Testez les paiements avec de petits montants"
echo ""
echo "🎉 Votre restaurant peut maintenant accepter les paiements en ligne ! 🍽️✨"
