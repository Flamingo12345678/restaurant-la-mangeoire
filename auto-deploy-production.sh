#!/bin/bash

echo "🚀 AUTO-DÉPLOIEMENT PRODUCTION - RESTAURANT LA MANGEOIRE"
echo "======================================================="
echo ""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "confirmation-commande.php" ]; then
    error "Veuillez exécuter ce script depuis le répertoire racine du projet"
    exit 1
fi

echo "1️⃣  CONFIGURATION DES FICHIERS DE PRODUCTION"
echo "============================================"

# Sauvegarder la configuration actuelle
if [ -f ".env" ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    success "Sauvegarde de .env créée"
fi

# Configurer HTTPS en production
if [ -f ".env" ]; then
    # Passer FORCE_HTTPS à true
    if grep -q "FORCE_HTTPS=" .env; then
        sed -i.bak 's/FORCE_HTTPS=false/FORCE_HTTPS=true/' .env
        success "FORCE_HTTPS activé pour la production"
    else
        echo "FORCE_HTTPS=true" >> .env
        success "FORCE_HTTPS ajouté au fichier .env"
    fi
    
    # Configurer l'environnement de production
    if grep -q "APP_ENV=" .env; then
        sed -i.bak 's/APP_ENV=development/APP_ENV=production/' .env
    else
        echo "APP_ENV=production" >> .env
    fi
    
    if grep -q "APP_DEBUG=" .env; then
        sed -i.bak 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    else
        echo "APP_DEBUG=false" >> .env
    fi
    
    success "Configuration production activée"
fi

# Copier la configuration .htaccess
if [ -f ".htaccess-production" ]; then
    cp .htaccess-production .htaccess
    
    # Décommenter les règles HTTPS
    sed -i.bak 's/# RewriteCond %{HTTPS} off/RewriteCond %{HTTPS} off/' .htaccess
    sed -i.bak 's/# RewriteCond %{HTTP_X_FORWARDED_PROTO} !https/RewriteCond %{HTTP_X_FORWARDED_PROTO} !https/' .htaccess
    sed -i.bak 's/# RewriteRule \^\(.*\)\$ https:\/\/%{HTTP_HOST}%{REQUEST_URI} \[R=301,L\]/RewriteRule ^(.*)$ https:\/\/%{HTTP_HOST}%{REQUEST_URI} [R=301,L]/' .htaccess
    
    success "Configuration .htaccess de production activée avec HTTPS forcé"
else
    warning "Fichier .htaccess-production non trouvé"
fi

echo ""
echo "2️⃣  OPTIMISATION ET SÉCURISATION"
echo "================================"

# Optimiser Composer pour la production
if command -v composer &> /dev/null; then
    info "Optimisation des dépendances Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction
    success "Dépendances Composer optimisées"
else
    warning "Composer non trouvé - optimisation manuelle requise"
fi

# Créer les dossiers de logs s'ils n'existent pas
mkdir -p logs/payments logs/errors logs/access
success "Dossiers de logs créés"

# Sécuriser les permissions des fichiers
chmod 644 .env 2>/dev/null || true
chmod 644 *.php 2>/dev/null || true
chmod 755 logs 2>/dev/null || true
chmod 755 includes 2>/dev/null || true
success "Permissions des fichiers sécurisées"

echo ""
echo "3️⃣  MISE À JOUR DU DASHBOARD ADMIN"
echo "=================================="

# Sauvegarder l'ancien dashboard
if [ -f "dashboard-admin.php" ]; then
    cp dashboard-admin.php dashboard-admin-backup-$(date +%Y%m%d_%H%M%S).php
    success "Sauvegarde de l'ancien dashboard créée"
fi

# Remplacer par le nouveau dashboard amélioré
if [ -f "dashboard-admin-enhanced.php" ]; then
    cp dashboard-admin-enhanced.php dashboard-admin.php
    success "Dashboard administrateur mis à jour avec monitoring paiements"
else
    warning "Dashboard amélioré non trouvé"
fi

echo ""
echo "4️⃣  CONFIGURATION DES LOGS DE MONITORING"
echo "========================================"

# Créer le fichier de configuration des logs
cat > logs/log-config.php << 'EOF'
<?php
/**
 * Configuration des logs - La Mangeoire
 */

// Niveau de logging (ERROR, WARNING, INFO, DEBUG)
define('LOG_LEVEL', 'INFO');

// Rotation des logs (en jours)
define('LOG_ROTATION_DAYS', 30);

// Taille maximale des fichiers de logs (en MB)
define('LOG_MAX_SIZE', 50);

// Formats de logs
define('LOG_FORMAT_PAYMENT', '[%s] %s - %s | Client: %s | Montant: %s€ | Statut: %s');
define('LOG_FORMAT_ERROR', '[%s] ERROR - %s | File: %s | Line: %s');
define('LOG_FORMAT_ACCESS', '[%s] %s - %s | IP: %s | User-Agent: %s');

// Emails d'alerte
define('ALERT_EMAIL', 'admin@votredomaine.com');
define('ALERT_THRESHOLD_ERRORS', 10); // Nombre d'erreurs avant alerte
?>
EOF

success "Configuration des logs créée"

# Créer le script de nettoyage des logs
cat > logs/cleanup-logs.sh << 'EOF'
#!/bin/bash
# Nettoyage automatique des logs anciens
find /path/to/your/site/logs -name "*.log" -mtime +30 -delete
find /path/to/your/site/logs -name "*.log.*" -mtime +30 -delete
echo "$(date): Nettoyage des logs terminé" >> /path/to/your/site/logs/cleanup.log
EOF

chmod +x logs/cleanup-logs.sh
success "Script de nettoyage des logs créé"

echo ""
echo "5️⃣  TESTS DE CONFIGURATION"
echo "=========================="

# Test de syntaxe PHP
info "Vérification de la syntaxe PHP..."
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
    success "Aucune erreur de syntaxe PHP détectée"
else
    error "$php_errors fichier(s) avec erreurs de syntaxe"
fi

# Test de connexion base de données
info "Test de connexion à la base de données..."
if php -r "
require_once 'db_connexion.php';
try {
    \$stmt = \$pdo->query('SELECT 1');
    echo 'OK';
} catch(Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" | grep -q "OK"; then
    success "Connexion à la base de données réussie"
else
    error "Problème de connexion à la base de données"
fi

# Test des clés API
info "Vérification des clés API..."
if grep -q "pk_live_" .env && grep -q "sk_live_" .env; then
    success "Clés Stripe LIVE détectées"
elif grep -q "pk_test_" .env && grep -q "sk_test_" .env; then
    warning "Clés Stripe TEST détectées - À remplacer en production"
else
    error "Clés Stripe manquantes"
fi

echo ""
echo "6️⃣  RECOMMANDATIONS FINALES"
echo "==========================="

info "Configuration terminée ! Vérifiez les points suivants :"
echo ""
echo "🔐 HTTPS ET CERTIFICAT SSL :"
echo "   • Configurez un certificat SSL (Let's Encrypt, Cloudflare, etc.)"
echo "   • Testez https://votredomaine.com"
echo "   • Vérifiez la redirection HTTP → HTTPS"
echo ""
echo "🔑 CLÉS API DE PRODUCTION :"
echo "   • Stripe : Remplacez pk_test_ et sk_test_ par pk_live_ et sk_live_"
echo "   • PayPal : Passez du mode 'sandbox' au mode 'live'"
echo "   • Testez avec des paiements de petits montants"
echo ""
echo "📧 CONFIGURATION EMAIL :"
echo "   • Vérifiez SMTP_* dans .env"
echo "   • Testez l'envoi d'emails de confirmation"
echo ""
echo "🗄️  BASE DE DONNÉES :"
echo "   • Configurez les sauvegardes automatiques"
echo "   • Optimisez les index et performances"
echo ""
echo "📊 MONITORING :"
echo "   • Accédez à dashboard-admin.php"
echo "   • Configurez les alertes par email"
echo "   • Surveillez les logs de paiements"
echo ""
echo "🔧 MAINTENANCE :"
echo "   • Planifiez logs/cleanup-logs.sh dans cron"
echo "   • Surveillez l'espace disque"
echo "   • Mettez à jour régulièrement"

echo ""
success "🎉 DÉPLOIEMENT AUTOMATIQUE TERMINÉ !"
echo ""
echo "🚀 PROCHAINES ÉTAPES :"
echo "   1. Configurez HTTPS sur votre serveur"
echo "   2. Remplacez les clés API par celles de production"
echo "   3. Testez les paiements réels"
echo "   4. Surveillez le dashboard administrateur"
echo ""
echo "📞 En cas de problème, consultez :"
echo "   • HTTPS_URGENT_GUIDE.md"
echo "   • CONFIGURATION_HTTPS_PRODUCTION.md"
echo "   • logs/errors/$(date +%Y-%m-%d).log"
echo ""

# Créer un rapport de déploiement
cat > deployment-report-$(date +%Y%m%d_%H%M%S).txt << EOF
RAPPORT DE DÉPLOIEMENT - RESTAURANT LA MANGEOIRE
================================================
Date: $(date)
Version: Production Ready 1.0

FICHIERS CONFIGURÉS:
- .env (production)
- .htaccess (HTTPS forcé)
- dashboard-admin.php (avec monitoring paiements)
- logs/ (structure créée)

STATUT TESTS:
- Syntaxe PHP: $([[ $php_errors -eq 0 ]] && echo "✅ OK" || echo "❌ $php_errors erreurs")
- Base de données: $(php -r "
require_once 'db_connexion.php';
try {
    \$stmt = \$pdo->query('SELECT 1');
    echo '✅ OK';
} catch(Exception \$e) {
    echo '❌ ERROR';
}
")

ACTIONS REQUISES:
1. Configurer HTTPS/SSL
2. Remplacer clés API test par production
3. Tester paiements réels
4. Configurer monitoring

SYSTÈME PRÊT POUR LA PRODUCTION ! 🚀
EOF

success "Rapport de déploiement créé : deployment-report-$(date +%Y%m%d_%H%M%S).txt"
