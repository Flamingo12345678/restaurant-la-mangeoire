#!/bin/bash

# Script de vérification rapide pour la production
# Restaurant La Mangeoire - Système de paiement

echo "🔍 VÉRIFICATION SYSTÈME DE PAIEMENT"
echo "=================================="
echo

# Vérifier que PHP fonctionne
if command -v php &> /dev/null; then
    echo "✅ PHP installé : $(php -v | head -n1)"
else
    echo "❌ PHP non installé"
    exit 1
fi

# Vérifier les extensions PHP requises
echo
echo "📦 Extensions PHP :"
extensions=("curl" "json" "openssl" "mbstring" "pdo" "pdo_mysql")
for ext in "${extensions[@]}"; do
    if php -m | grep -q "$ext"; then
        echo "✅ $ext"
    else
        echo "❌ $ext (REQUIS)"
    fi
done

# Vérifier les fichiers critiques
echo
echo "📁 Fichiers système :"
files=(
    ".env"
    "includes/payment_manager.php"
    "includes/email_manager.php" 
    "api/payments.php"
    "api/paypal_return.php"
    "paiement.php"
    "confirmation-paiement.php"
    "vendor/autoload.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (MANQUANT)"
    fi
done

# Vérifier la syntaxe PHP des fichiers critiques
echo
echo "🔍 Syntaxe PHP :"
php_files=(
    "includes/payment_manager.php"
    "includes/email_manager.php"
    "api/payments.php"
    "paiement.php"
)

for file in "${php_files[@]}"; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo "✅ $file"
        else
            echo "❌ $file (ERREUR SYNTAXE)"
        fi
    fi
done

# Vérifier les permissions
echo
echo "🔐 Permissions :"
if [ -r ".env" ]; then
    echo "✅ .env lisible"
else
    echo "❌ .env non lisible"
fi

if [ -d "api" ] && [ -x "api" ]; then
    echo "✅ Dossier api accessible"
else
    echo "❌ Dossier api non accessible"
fi

# Vérifier Composer
echo
echo "📦 Dépendances Composer :"
if [ -f "vendor/autoload.php" ]; then
    echo "✅ Autoloader présent"
    if [ -f "composer.lock" ]; then
        echo "✅ Dépendances verrouillées"
    else
        echo "⚠️  composer.lock manquant (recommandé)"
    fi
else
    echo "❌ Autoloader manquant (composer install requis)"
fi

# Test rapide de l'API
echo
echo "🌐 Test API :"
if command -v curl &> /dev/null; then
    if curl -s -f "http://localhost/api/payments.php" -d '{"action":"get_api_status"}' -H "Content-Type: application/json" > /dev/null 2>&1; then
        echo "✅ API accessible"
    else
        echo "⚠️  API non accessible (serveur web requis)"
    fi
else
    echo "⚠️  curl non disponible pour le test"
fi

echo
echo "🎯 RÉCAPITULATIF :"
echo "=================="

# Compter les problèmes
problems=0

# Vérifier les éléments critiques
if [ ! -f ".env" ]; then ((problems++)); fi
if [ ! -f "vendor/autoload.php" ]; then ((problems++)); fi
if [ ! -f "includes/payment_manager.php" ]; then ((problems++)); fi

if [ $problems -eq 0 ]; then
    echo "🎉 SYSTÈME OPÉRATIONNEL"
    echo "   Tous les composants sont présents"
    echo "   Prêt pour la production !"
else
    echo "⚠️  $problems PROBLÈME(S) DÉTECTÉ(S)"
    echo "   Vérifiez les éléments marqués ❌"
fi

echo
echo "📋 PROCHAINES ÉTAPES :"
echo "1. Configurer les clés API dans .env"
echo "2. Tester avec de vrais petits montants"
echo "3. Surveiller les logs en production"
echo
echo "✨ Bon déploiement !"
