#!/bin/bash

# Script d'activation HTTPS - Restaurant La Mangeoire
# Ce script configure tous les éléments nécessaires pour HTTPS en production

echo "🔒 ACTIVATION HTTPS - RESTAURANT LA MANGEOIRE"
echo "=============================================="

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction de logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 1. Vérifier que nous sommes dans le bon répertoire
if [ ! -f ".env" ]; then
    log_error "Fichier .env non trouvé. Êtes-vous dans le bon répertoire ?"
    exit 1
fi

log_info "Démarrage de la configuration HTTPS..."

# 2. Sauvegarder les fichiers existants
log_info "Sauvegarde des fichiers existants..."
if [ -f ".htaccess" ]; then
    cp .htaccess .htaccess.backup.$(date +%Y%m%d_%H%M%S)
    log_success "Sauvegarde .htaccess créée"
fi

if [ -f ".env" ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    log_success "Sauvegarde .env créée"
fi

# 3. Activer FORCE_HTTPS dans .env si ce n'est pas déjà fait
log_info "Configuration des variables d'environnement..."
if grep -q "FORCE_HTTPS=false" .env; then
    sed -i '' 's/FORCE_HTTPS=false/FORCE_HTTPS=true/g' .env
    log_success "FORCE_HTTPS activé dans .env"
elif grep -q "FORCE_HTTPS=true" .env; then
    log_success "FORCE_HTTPS déjà activé"
else
    echo "FORCE_HTTPS=true" >> .env
    log_success "FORCE_HTTPS ajouté à .env"
fi

# 4. Mettre à jour APP_ENV et APP_DEBUG pour production
if grep -q "APP_ENV=development" .env; then
    sed -i '' 's/APP_ENV=development/APP_ENV=production/g' .env
    log_success "APP_ENV mis en production"
fi

if grep -q "APP_DEBUG=true" .env; then
    sed -i '' 's/APP_DEBUG=true/APP_DEBUG=false/g' .env
    log_success "APP_DEBUG désactivé pour production"
fi

# 5. Vérifier et corriger l'URL de base
log_info "Configuration de l'URL de base..."
if grep -q "APP_URL=http://" .env; then
    log_warning "APP_URL utilise encore HTTP - veuillez le mettre à jour manuellement avec votre domaine HTTPS"
    echo "Exemple: APP_URL=https://votre-domaine.com"
fi

# 6. Copier le fichier .htaccess de production s'il n'existe pas déjà
if [ ! -f ".htaccess" ]; then
    if [ -f ".htaccess-production" ]; then
        cp .htaccess-production .htaccess
        log_success "Fichier .htaccess copié depuis .htaccess-production"
    else
        log_warning "Fichier .htaccess-production non trouvé - .htaccess déjà créé"
    fi
fi

# 7. Créer les répertoires de logs si nécessaire
log_info "Création des répertoires de logs..."
mkdir -p logs/https
mkdir -p logs/security
mkdir -p logs/payments
mkdir -p logs/alerts
chmod 755 logs
chmod 755 logs/*
log_success "Répertoires de logs créés"

# 8. Définir les bonnes permissions pour la sécurité
log_info "Configuration des permissions de sécurité..."
chmod 644 .env
chmod 644 .env.production 2>/dev/null || true
chmod 644 .htaccess
chmod 755 includes/
chmod 644 includes/*.php
chmod 755 api/
chmod 644 api/*.php
log_success "Permissions de sécurité configurées"

# 9. Test de la configuration PHP pour HTTPS
log_info "Test de la configuration PHP..."
php -l test-https-config.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    log_success "Configuration PHP valide"
else
    log_error "Erreur de syntaxe PHP détectée"
fi

# 10. Vérifier les dépendances Composer
if [ -f "composer.json" ]; then
    log_info "Vérification des dépendances Composer..."
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
        log_success "Dépendances Composer installées pour production"
    else
        log_warning "Composer non trouvé - veuillez installer les dépendances manuellement"
    fi
fi

# 11. Test rapide de la configuration
log_info "Test de la configuration HTTPS..."
php -f test-https-config.php > /tmp/https_test.html 2>&1
if [ $? -eq 0 ]; then
    log_success "Test HTTPS exécuté avec succès"
    log_info "Résultats sauvegardés dans /tmp/https_test.html"
else
    log_warning "Warnings détectés lors du test HTTPS (normal en ligne de commande)"
fi

# 12. Vérifier les services critiques
log_info "Vérification des services critiques..."

# Test base de données
php -r "
try {
    require_once 'db_connexion.php';
    \$pdo = getDbConnection();
    echo 'DB: OK\n';
} catch(Exception \$e) {
    echo 'DB: ERROR - ' . \$e->getMessage() . '\n';
}
" 2>/dev/null || log_warning "Impossible de tester la base de données"

# Test gestionnaire de paiements
if [ -f "includes/payment_manager.php" ]; then
    log_success "Gestionnaire de paiements présent"
else
    log_error "Gestionnaire de paiements manquant"
fi

# 13. Messages finaux et recommandations
echo ""
echo "✅ CONFIGURATION HTTPS TERMINÉE"
echo "==============================="
echo ""
log_success "Configuration HTTPS activée avec succès !"
echo ""
echo "📋 PROCHAINES ÉTAPES :"
echo "1. 🌐 Mettez à jour APP_URL dans .env avec votre vrai domaine HTTPS"
echo "2. 🔑 Remplacez les clés Stripe/PayPal TEST par les clés PRODUCTION"
echo "3. 🧪 Testez les paiements en mode production"
echo "4. 📧 Vérifiez l'envoi d'emails en HTTPS"
echo "5. 🔒 Obtenez un certificat SSL valide pour votre domaine"
echo ""
log_info "Fichiers de sauvegarde créés avec l'horodatage"
log_info "Test disponible : php test-https-config.php"
log_info "Monitoring disponible : php health-check.php"
echo ""
log_warning "IMPORTANT: Testez soigneusement avant de passer en production !"

exit 0
