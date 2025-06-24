#!/bin/bash

# 🚀 RAILWAY SETUP - Configuration des variables d'environnement
# Script pour configurer les variables d'environnement sur Railway
# Date: 24 juin 2025

echo "🚀 Configuration Railway - Restaurant La Mangeoire"
echo "================================================="

# Vérifier si on est sur Railway
if [ -n "$RAILWAY_ENVIRONMENT" ]; then
    echo "✅ Environnement Railway détecté: $RAILWAY_ENVIRONMENT"
else
    echo "⚠️  Attention: Variables Railway non détectées"
fi

# Afficher les variables MySQL disponibles
echo ""
echo "📋 Variables Base de Données disponibles:"
echo "----------------------------------------"

# Fonction pour afficher une variable en masquant les mots de passe
display_var() {
    local var_name=$1
    local var_value=$(printenv "$var_name")
    
    if [ -n "$var_value" ]; then
        if [[ "$var_name" == *"PASS"* ]] || [[ "$var_name" == *"SECRET"* ]] || [[ "$var_name" == *"KEY"* ]]; then
            echo "✅ $var_name = ********"
        else
            echo "✅ $var_name = $var_value"
        fi
    else
        echo "❌ $var_name = (non définie)"
    fi
}

# Variables Railway MySQL
display_var "MYSQLHOST"
display_var "MYSQLDATABASE"
display_var "MYSQLUSER"
display_var "MYSQLPASSWORD"
display_var "MYSQLPORT"

# Variables Railway générales
echo ""
echo "🔧 Variables Railway:"
echo "--------------------"
display_var "RAILWAY_ENVIRONMENT"
display_var "RAILWAY_PROJECT_NAME"
display_var "RAILWAY_SERVICE_NAME"

# Variables application
echo ""
echo "🌐 Variables Application:"
echo "------------------------"
display_var "FORCE_HTTPS"
display_var "SITE_URL"
display_var "STRIPE_PUBLISHABLE_KEY"
display_var "PAYPAL_CLIENT_ID"
display_var "SMTP_HOST"

# Créer un .env.railway temporaire si nécessaire (pour debug)
if [ "$1" = "--create-env-file" ]; then
    echo ""
    echo "📝 Création du fichier .env.railway pour debug..."
    
    cat > .env.railway << EOF
# Variables Railway - Généré automatiquement
# Date: $(date)

# Base de données
MYSQLHOST=${MYSQLHOST:-}
MYSQLDATABASE=${MYSQLDATABASE:-}
MYSQLUSER=${MYSQLUSER:-}
MYSQLPASSWORD=${MYSQLPASSWORD:-}
MYSQLPORT=${MYSQLPORT:-3306}

# Railway
RAILWAY_ENVIRONMENT=${RAILWAY_ENVIRONMENT:-}
RAILWAY_PROJECT_NAME=${RAILWAY_PROJECT_NAME:-}
RAILWAY_SERVICE_NAME=${RAILWAY_SERVICE_NAME:-}

# Application
FORCE_HTTPS=${FORCE_HTTPS:-true}
SITE_URL=${SITE_URL:-}
STRIPE_PUBLISHABLE_KEY=${STRIPE_PUBLISHABLE_KEY:-}
PAYPAL_CLIENT_ID=${PAYPAL_CLIENT_ID:-}
SMTP_HOST=${SMTP_HOST:-}
SMTP_PORT=${SMTP_PORT:-587}
SMTP_USERNAME=${SMTP_USERNAME:-}
SMTP_PASSWORD=${SMTP_PASSWORD:-}
EOF

    echo "✅ Fichier .env.railway créé"
fi

# Test de connexion PHP
echo ""
echo "🔍 Test de connexion PHP..."
echo "-------------------------"

if command -v php &> /dev/null; then
    php -r "
    \$host = getenv('MYSQLHOST');
    \$db = getenv('MYSQLDATABASE');
    \$user = getenv('MYSQLUSER');
    \$pass = getenv('MYSQLPASSWORD');
    \$port = getenv('MYSQLPORT');
    
    if (\$host && \$db && \$user && \$pass && \$port) {
        echo '✅ Toutes les variables MySQL sont présentes\n';
        try {
            \$dsn = \"mysql:host=\$host;port=\$port;dbname=\$db;charset=utf8mb4\";
            \$pdo = new PDO(\$dsn, \$user, \$pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo '✅ Connexion à la base de données réussie!\n';
        } catch (PDOException \$e) {
            echo '❌ Erreur de connexion: ' . \$e->getMessage() . \"\n\";
        }
    } else {
        echo '❌ Variables MySQL manquantes\n';
        echo 'Host: ' . (\$host ?: 'MANQUANT') . \"\n\";
        echo 'Database: ' . (\$db ?: 'MANQUANT') . \"\n\";
        echo 'User: ' . (\$user ?: 'MANQUANT') . \"\n\";
        echo 'Password: ' . (\$pass ? 'PRÉSENT' : 'MANQUANT') . \"\n\";
        echo 'Port: ' . (\$port ?: 'MANQUANT') . \"\n\";
    }
    "
else
    echo "⚠️  PHP non disponible pour le test"
fi

echo ""
echo "🎯 Configuration terminée!"
echo ""
echo "💡 Commandes utiles:"
echo "   - Voir ce script: ./railway-setup.sh"
echo "   - Créer .env debug: ./railway-setup.sh --create-env-file"
echo "   - Diagnostic web: https://[votre-url]/diagnostic-env.php"
echo ""
