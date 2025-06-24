#!/bin/bash

# 🚀 RAILWAY SETUP REAL - Configuration des VRAIES variables Railway
# Script pour configurer toutes les variables d'environnement Railway
# ATTENTION: Remplacez les valeurs par vos vraies clés de production !
# Date: 24 juin 2025

echo "🚀 Configuration RÉELLE Railway - Restaurant La Mangeoire"
echo "========================================================"
echo ""
echo "⚠️  ATTENTION: Ce script configure les VRAIES variables de production"
echo "    Assurez-vous d'avoir les bonnes valeurs avant de l'exécuter !"
echo ""

# Fonction pour définir une variable Railway
set_railway_var() {
    local var_name=$1
    local var_value=$2
    local description=$3
    
    if [ -n "$var_value" ] && [ "$var_value" != "YOUR_VALUE_HERE" ]; then
        echo "✅ Configuration $var_name - $description"
        # Commande Railway pour définir la variable
        railway variables set "$var_name=$var_value"
    else
        echo "⚠️  Ignorer $var_name - Valeur non définie (YOUR_VALUE_HERE)"
    fi
}

# Vérifier si Railway CLI est installé
if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI non installé !"
    echo ""
    echo "Installation:"
    echo "  npm install -g @railway/cli"
    echo "  ou"
    echo "  curl -fsSL https://railway.app/install.sh | sh"
    echo ""
    exit 1
fi

# Vérifier si on est connecté à Railway
if ! railway auth &> /dev/null; then
    echo "❌ Non connecté à Railway !"
    echo ""
    echo "Connexion:"
    echo "  railway login"
    echo ""
    exit 1
fi

echo "🔧 Configuration des variables Railway..."
echo "=========================================="

# =========================
# VARIABLES BASE DE DONNÉES
# =========================
echo ""
echo "📊 Base de Données MySQL:"

# ⚠️ REMPLACEZ CES VALEURS PAR VOS VRAIES VALEURS RAILWAY MYSQL
DB_HOST="YOUR_VALUE_HERE"           # Ex: viaduct.proxy.rlwy.net
DB_DATABASE="YOUR_VALUE_HERE"       # Ex: railway  
DB_USER="YOUR_VALUE_HERE"           # Ex: root
DB_PASSWORD="YOUR_VALUE_HERE"       # Ex: mdp-très-sécurisé
DB_PORT="3306"

set_railway_var "MYSQLHOST" "$DB_HOST" "Host MySQL"
set_railway_var "MYSQLDATABASE" "$DB_DATABASE" "Base de données"
set_railway_var "MYSQLUSER" "$DB_USER" "Utilisateur MySQL"
set_railway_var "MYSQLPASSWORD" "$DB_PASSWORD" "Mot de passe MySQL"
set_railway_var "MYSQLPORT" "$DB_PORT" "Port MySQL"

# =========================
# VARIABLES APPLICATION
# =========================
echo ""
echo "🌐 Application:"

# ⚠️ REMPLACEZ CES VALEURS PAR VOS VRAIES VALEURS
SITE_URL="YOUR_VALUE_HERE"          # Ex: https://restaurant-la-mangeoire.railway.app
FORCE_HTTPS="true"

set_railway_var "SITE_URL" "$SITE_URL" "URL du site"  
set_railway_var "FORCE_HTTPS" "$FORCE_HTTPS" "Forcer HTTPS"

# =========================
# PAIEMENTS STRIPE
# =========================
echo ""
echo "💳 Stripe:"

# ⚠️ REMPLACEZ PAR VOS VRAIES CLÉS STRIPE
STRIPE_PUBLISHABLE_KEY="YOUR_VALUE_HERE"    # Ex: pk_live_xxxxx ou pk_test_xxxxx
STRIPE_SECRET_KEY="YOUR_VALUE_HERE"         # Ex: sk_live_xxxxx ou sk_test_xxxxx

set_railway_var "STRIPE_PUBLISHABLE_KEY" "$STRIPE_PUBLISHABLE_KEY" "Clé publique Stripe"
set_railway_var "STRIPE_SECRET_KEY" "$STRIPE_SECRET_KEY" "Clé secrète Stripe"

# =========================
# PAIEMENTS PAYPAL
# =========================
echo ""
echo "🏦 PayPal:"

# ⚠️ REMPLACEZ PAR VOS VRAIES CLÉS PAYPAL
PAYPAL_CLIENT_ID="YOUR_VALUE_HERE"          # Ex: AZabc123...
PAYPAL_CLIENT_SECRET="YOUR_VALUE_HERE"      # Ex: EBdef456...
PAYPAL_MODE="sandbox"                       # ou "production"

set_railway_var "PAYPAL_CLIENT_ID" "$PAYPAL_CLIENT_ID" "Client ID PayPal"
set_railway_var "PAYPAL_CLIENT_SECRET" "$PAYPAL_CLIENT_SECRET" "Secret PayPal"
set_railway_var "PAYPAL_MODE" "$PAYPAL_MODE" "Mode PayPal"

# =========================
# EMAIL / SMTP
# =========================
echo ""
echo "📧 SMTP:"

# ⚠️ REMPLACEZ PAR VOS VRAIES VALEURS SMTP
SMTP_HOST="YOUR_VALUE_HERE"                 # Ex: smtp.gmail.com
SMTP_PORT="587"
SMTP_USERNAME="YOUR_VALUE_HERE"             # Ex: votre-email@gmail.com
SMTP_PASSWORD="YOUR_VALUE_HERE"             # Ex: mot-de-passe-app

set_railway_var "SMTP_HOST" "$SMTP_HOST" "Serveur SMTP"
set_railway_var "SMTP_PORT" "$SMTP_PORT" "Port SMTP"
set_railway_var "SMTP_USERNAME" "$SMTP_USERNAME" "Utilisateur SMTP"
set_railway_var "SMTP_PASSWORD" "$SMTP_PASSWORD" "Mot de passe SMTP"

# =========================
# FINALISATION
# =========================
echo ""
echo "🎯 Configuration terminée !"
echo ""
echo "📋 Vérifications recommandées :"
echo "  1. railway variables - Voir toutes les variables"
echo "  2. railway logs --tail - Voir les logs en temps réel"
echo "  3. Tester votre site : $SITE_URL"
echo "  4. Tester diagnostic : $SITE_URL/diagnostic-env.php"
echo ""
echo "🚀 Redéploiement automatique en cours..."

# =========================
# INSTRUCTIONS PERSONNALISÉES
# =========================
cat << 'EOF'

💡 INSTRUCTIONS POUR PERSONNALISER CE SCRIPT :

1. 📝 Éditer les variables :
   - Ouvrir ce fichier dans un éditeur
   - Remplacer toutes les valeurs "YOUR_VALUE_HERE"
   - Sauvegarder

2. 🔑 Obtenir vos clés :
   - Railway MySQL : Dashboard Railway → Database → Connect
   - Stripe : https://dashboard.stripe.com/apikeys  
   - PayPal : https://developer.paypal.com/developer/applications/
   - SMTP : Paramètres de votre fournisseur email

3. 🚀 Exécuter :
   ./railway-setup-real.sh

4. ✅ Vérifier :
   railway variables
   railway logs --tail

EOF

echo ""
echo "📌 N'oubliez pas de supprimer ce script après usage pour la sécurité !"
echo ""
