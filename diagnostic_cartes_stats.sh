#!/bin/bash

# Script de diagnostic pour vérifier l'affichage des cartes statistiques
# Test d'intégrité CSS et structure HTML

echo "🔍 Diagnostic des cartes statistiques - admin-messages.php"
echo "========================================================="

# Vérifier l'existence des fichiers
if [ -f "admin-messages.php" ]; then
    echo "✅ admin-messages.php existe"
else
    echo "❌ admin-messages.php introuvable"
fi

if [ -f "assets/css/admin-messages.css" ]; then
    echo "✅ admin-messages.css existe"
else
    echo "❌ admin-messages.css introuvable"
fi

if [ -f "admin/header_template.php" ]; then
    echo "✅ header_template.php existe"
else
    echo "❌ header_template.php introuvable"
fi

echo ""
echo "🔧 Vérification des classes CSS critiques..."

# Rechercher les classes importantes dans le CSS
grep -q "\.admin-messages \.row\.g-4" assets/css/admin-messages.css && echo "✅ Classes row.g-4 trouvées" || echo "❌ Classes row.g-4 manquantes"

grep -q "\.stats-card" assets/css/admin-messages.css && echo "✅ Classes stats-card trouvées" || echo "❌ Classes stats-card manquantes"

grep -q "flex.*nowrap" assets/css/admin-messages.css && echo "✅ Règles flexbox trouvées" || echo "❌ Règles flexbox manquantes"

echo ""
echo "📊 Structure HTML des cartes statistiques..."

# Vérifier la structure HTML
grep -q "col-md-3.*stats-card" admin-messages.php && echo "✅ Structure Bootstrap correcte" || echo "❌ Structure Bootstrap incorrecte"

echo ""
echo "🎨 Suggestions d'amélioration :"
echo "1. Vérifiez que Bootstrap 5.3.0 est bien chargé"
echo "2. Testez sur différentes tailles d'écran"
echo "3. Vérifiez la console navigateur pour d'éventuelles erreurs CSS"
echo "4. L'ordre d'inclusion des CSS doit être : Bootstrap > admin.css > admin-messages.css"

echo ""
echo "✨ Diagnostic terminé."
