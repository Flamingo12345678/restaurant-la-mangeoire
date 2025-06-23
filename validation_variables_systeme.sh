#!/bin/bash
# Script de validation des variables système - Dashboard Admin
# Date: 23 juin 2025

echo "🔍 Validation des variables système dans dashboard-admin.php"
echo "============================================================"

FILE="dashboard-admin.php"

if [ ! -f "$FILE" ]; then
    echo "❌ Fichier $FILE non trouvé"
    exit 1
fi

echo "✅ Fichier $FILE trouvé"

# Vérification de la syntaxe PHP
echo "🔍 Vérification syntaxe PHP..."
if php -l "$FILE" > /dev/null 2>&1; then
    echo "✅ Syntaxe PHP correcte"
else
    echo "❌ Erreur de syntaxe PHP"
    php -l "$FILE"
    exit 1
fi

# Vérification des variables système définies
echo "🔍 Vérification des variables système..."

# Vérification $system_services
if grep -q '\$system_services.*=' "$FILE"; then
    echo "✅ Variable \$system_services définie"
else
    echo "❌ Variable \$system_services manquante"
fi

# Vérification $system_stats
if grep -q '\$system_stats.*=' "$FILE"; then
    echo "✅ Variable \$system_stats définie"
else
    echo "❌ Variable \$system_stats manquante"
fi

# Vérification $system_uptime
if grep -q '\$system_uptime.*=' "$FILE"; then
    echo "✅ Variable \$system_uptime définie"
else
    echo "❌ Variable \$system_uptime manquante"
fi

# Vérification $recent_events
if grep -q '\$recent_events.*=' "$FILE"; then
    echo "✅ Variable \$recent_events définie"
else
    echo "❌ Variable \$recent_events manquante"
fi

# Vérification utilisation des variables
echo "🔍 Vérification utilisation des variables..."

if grep -q 'foreach.*\$system_services' "$FILE"; then
    echo "✅ Variable \$system_services utilisée correctement"
else
    echo "⚠️  Variable \$system_services non utilisée"
fi

if grep -q '\$system_stats\[' "$FILE"; then
    echo "✅ Variable \$system_stats utilisée correctement"
else
    echo "⚠️  Variable \$system_stats non utilisée"
fi

echo ""
echo "🎉 Validation terminée !"
echo "💡 Pour tester : rechargez la page dashboard-admin.php dans votre navigateur"
