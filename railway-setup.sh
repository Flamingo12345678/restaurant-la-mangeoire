#!/bin/bash

# 🚂 CONFIGURATION VARIABLES RAILWAY - Restaurant La Mangeoire
# Ce script configure toutes les variables d'environnement nécessaires sur Railway

echo "🚂 Configuration des variables d'environnement Railway..."

# Base de données (déjà configurées par Railway)
echo "✅ Variables de base de données (configurées automatiquement par Railway)"

# Configuration HTTPS
railway variables set FORCE_HTTPS=true
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set SITE_URL=https://la-mangeoire.up.railway.app/

# Sécurité
railway variables set SESSION_SECURE=true
railway variables set COOKIE_SECURE=true
railway variables set CSRF_PROTECTION=true

# Stripe (REMPLACEZ PAR VOS VRAIES CLÉS DE PRODUCTION)
railway variables set STRIPE_PUBLISHABLE_KEY=pk_live_VOTRE_CLE_PUBLIQUE_ICI
railway variables set STRIPE_SECRET_KEY=sk_live_VOTRE_CLE_SECRETE_ICI
railway variables set STRIPE_WEBHOOK_SECRET=whsec_VOTRE_WEBHOOK_SECRET_ICI

# PayPal (REMPLACEZ PAR VOS VRAIES CLÉS DE PRODUCTION)
railway variables set PAYPAL_CLIENT_ID=VOTRE_PAYPAL_CLIENT_ID_ICI
railway variables set PAYPAL_SECRET_KEY=VOTRE_PAYPAL_SECRET_KEY_ICI
railway variables set PAYPAL_WEBHOOK_ID=your_paypal_webhook_id
railway variables set PAYPAL_MODE=sandbox

# Email
railway variables set ADMIN_EMAIL=votre-email@domaine.com
railway variables set ADMIN_NAME="Restaurant La Mangeoire"
railway variables set FROM_EMAIL=votre-email@domaine.com
railway variables set FROM_NAME="Restaurant La Mangeoire"
railway variables set SMTP_HOST=smtp.gmail.com
railway variables set SMTP_PORT=587
railway variables set SMTP_USER=votre-email@domaine.com
railway variables set SMTP_PASS=votre_mot_de_passe_application
railway variables set SMTP_FROM_EMAIL=votre-email@domaine.com
railway variables set SMTP_FROM_NAME="Restaurant La Mangeoire"
railway variables set EMAIL_TEST_MODE=false
railway variables set SMTP_USERNAME=votre-email@domaine.com
railway variables set SMTP_PASSWORD=votre_mot_de_passe_application
railway variables set SMTP_ENCRYPTION=tls

echo "✅ Variables configurées sur Railway"
echo ""
echo "🔧 PROCHAINES ÉTAPES:"
echo "1. Vérifiez que vos clés Stripe/PayPal sont correctes"
echo "2. Redéployez votre application : railway up"
echo "3. Testez le site : https://la-mangeoire.up.railway.app/"
echo ""
echo "⚠️  IMPORTANT: Remplacez les clés de test par vos vraies clés de production"
