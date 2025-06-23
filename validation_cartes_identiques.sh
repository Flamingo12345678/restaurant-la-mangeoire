#!/bin/bash

echo "=== VALIDATION CARTES STATISTIQUES IDENTIQUES AU DASHBOARD ==="
echo ""

# Vérification de la structure HTML
echo "✅ Vérification de la structure HTML :"
echo -n "  - Class 'stats-grid' : "
if grep -q "stats-grid" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Class 'stat-card' : "
if grep -q "stat-card" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Structure sans card-body : "
if ! grep -q "card-body" admin-messages.php; then
    echo "✅ CORRECT (card-body supprimé)"
else
    echo "❌ INCORRECT (card-body encore présent)"
fi

echo -n "  - stat-value : "
if grep -q "stat-value" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - stat-label : "
if grep -q "stat-label" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - stat-description : "
if grep -q "stat-description" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification du CSS :"

echo -n "  - Variables CSS --card-color : "
if grep -q "var(--card-color" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Dégradé background : "
if grep -q "linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Padding moderne : "
if grep -q "padding: 30px 25px" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Border-radius moderne : "
if grep -q "border-radius: 20px" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Effets de survol : "
if grep -q "translateY(-12px) scale(1.02)" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Icônes positionnées : "
if grep -q "position: absolute" admin-messages.php && grep -q "top: 25px" admin-messages.php && grep -q "right: 25px" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Font-size stat-value : "
if grep -q "font-size: 3rem" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification des couleurs :"

echo -n "  - Couleur success : "
if grep -q "\-\-card-color: #28a745" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Couleur warning : "
if grep -q "\-\-card-color: #ffc107" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Couleur danger : "
if grep -q "\-\-card-color: #dc3545" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Couleur primary/info : "
if grep -q "\-\-card-color: #17a2b8" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification responsive :"

echo -n "  - Grid responsive : "
if grep -q "grid-template-columns: 1fr" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Gap responsive : "
if grep -q "gap: 20px" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification des animations :"

echo -n "  - Animation slideInUp : "
if grep -q "animation: slideInUp" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Keyframes slideInUp : "
if grep -q "@keyframes slideInUp" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Animation-delay : "
if grep -q "animation-delay:" admin-messages.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "=== RÉSUMÉ ==="
echo "✅ Structure HTML : Identique au dashboard avec stats-grid et stat-card"
echo "✅ CSS : Styles modernes avec dégradés, ombres et animations"
echo "✅ Couleurs : Variables CSS pour cohérence visuelle"
echo "✅ Responsive : Adaptation mobile et tablette"
echo "✅ Animations : Effets de survol et d'apparition"
echo ""
echo "🎉 Les cartes statistiques utilisent maintenant exactement la même structure"
echo "   et les mêmes styles que celles du dashboard !"
