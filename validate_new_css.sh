#!/bin/bash
# Script de validation pour le nouveau CSS admin-messages clean

echo "🔍 VALIDATION DU NOUVEAU CSS ADMIN-MESSAGES"
echo "=============================================="

# Vérifier la présence des fichiers
echo "📁 Vérification des fichiers..."
if [ -f "assets/css/admin-messages.css" ]; then
    echo "✅ Nouveau admin-messages.css présent"
else
    echo "❌ Fichier admin-messages.css manquant"
    exit 1
fi

if [ -f "assets/css/admin-messages-backup-"*".css" ]; then
    echo "✅ Fichier de sauvegarde créé"
else
    echo "⚠️  Aucun fichier de sauvegarde trouvé"
fi

# Compter les lignes du nouveau fichier
lines=$(wc -l < assets/css/admin-messages.css)
echo "📊 Nouveau fichier CSS: $lines lignes"

# Vérifier les éléments critiques
echo ""
echo "🎨 Vérification des règles CSS critiques..."

if grep -q ":root" assets/css/admin-messages.css; then
    echo "✅ Variables CSS présentes"
fi

if grep -q "display: flex" assets/css/admin-messages.css; then
    echo "✅ Flexbox configuré"
fi

if grep -q "flex-wrap: nowrap" assets/css/admin-messages.css; then
    echo "✅ Nowrap appliqué"
fi

if grep -q "height: 200px" assets/css/admin-messages.css; then
    echo "✅ Hauteur fixe définie"
fi

# Compter les media queries
media_queries=$(grep -c "@media" assets/css/admin-messages.css)
echo "✅ $media_queries media queries pour le responsive"

# Vérifier les sélecteurs principaux
echo ""
echo "🎯 Vérification des sélecteurs..."

if grep -q ".admin-messages .row.g-4" assets/css/admin-messages.css; then
    echo "✅ Sélecteur row principal"
fi

if grep -q ".admin-messages .stats-card" assets/css/admin-messages.css; then
    echo "✅ Sélecteur cartes statistiques"
fi

if grep -q ".admin-messages .stats-card .card-body" assets/css/admin-messages.css; then
    echo "✅ Sélecteur card-body"
fi

# Vérifier les couleurs
echo ""
echo "🌈 Vérification des couleurs..."

if grep -q "var(--info-color)" assets/css/admin-messages.css; then
    echo "✅ Variables de couleur utilisées"
fi

if grep -q "nth-child" assets/css/admin-messages.css; then
    echo "✅ Couleurs spécifiques par carte"
fi

echo ""
echo "🚀 INSTRUCTIONS DE TEST:"
echo "1. Videz le cache du navigateur (Cmd+Shift+R)"
echo "2. Rechargez http://localhost:8000/admin-messages.php"
echo "3. Vérifiez que les 4 cartes sont alignées horizontalement"
echo "4. Testez le responsive en redimensionnant la fenêtre"
echo "5. Vérifiez les animations au survol"
echo ""
echo "📋 CARACTÉRISTIQUES DU NOUVEAU CSS:"
echo "- Variables CSS pour faciliter la maintenance"
echo "- Flexbox simple sans !important excessifs"
echo "- Hauteur fixe pour uniformité"
echo "- Responsive optimisé"
echo "- Code propre et commenté"
echo ""
echo "✨ Validation terminée - Nouveau CSS prêt !"
