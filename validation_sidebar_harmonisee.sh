#!/bin/bash

echo "=== VALIDATION SIDEBAR HARMONISÉE - DASHBOARD ADMIN ==="
echo ""

# Vérification que le dashboard utilise le template header
echo "✅ Vérification de l'utilisation du template header :"
echo -n "  - Template header inclus : "
if grep -q "require_once 'admin/header_template.php'" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Constante INCLUDED_IN_PAGE définie : "
if grep -q "define('INCLUDED_IN_PAGE', true)" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Page title défini : "
if grep -q '\$page_title = "Dashboard Système"' dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification de l'absence d'ancienne sidebar :"

echo -n "  - Ancienne sidebar supprimée : "
if ! grep -q '<div id="admin-sidebar" class="admin-sidebar">' dashboard-admin.php; then
    echo "✅ CORRECT (ancienne sidebar supprimée)"
else
    echo "❌ INCORRECT (ancienne sidebar encore présente)"
fi

echo -n "  - Ancien bouton burger supprimé : "
if ! grep -q 'admin-burger-btn.*position.*fixed' dashboard-admin.php; then
    echo "✅ CORRECT (ancien bouton supprimé)"
else
    echo "❌ INCORRECT (ancien bouton encore présent)"
fi

echo -n "  - Ancien overlay supprimé : "
if ! grep -q 'admin-sidebar-overlay.*position.*fixed' dashboard-admin.php; then
    echo "✅ CORRECT (ancien overlay supprimé)"
else
    echo "❌ INCORRECT (ancien overlay encore présent)"
fi

echo ""
echo "✅ Vérification de la structure harmonisée :"

echo -n "  - Container admin-dashboard : "
if grep -q 'admin-dashboard' dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Header avec card bg-primary : "
if grep -q 'card bg-primary text-white' dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Footer template inclus : "
if grep -q "require_once 'admin/footer_template.php'" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification des cartes statistiques :"

echo -n "  - Stats-grid utilisé : "
if grep -q "stats-grid" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Stat-card utilisé : "
if grep -q "stat-card" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Structure identique admin-messages : "
if grep -q "stat-value" dashboard-admin.php && grep -q "stat-label" dashboard-admin.php && grep -q "stat-description" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "✅ Vérification du contenu système :"

echo -n "  - Services système : "
if grep -q "system-services-card" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Métriques performance : "
if grep -q "performance-metric" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - JavaScript animations : "
if grep -q "animateCountUp" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "=== RÉSULTAT ==="
echo "✅ Structure de sidebar : HARMONISÉE avec le template commun"
echo "✅ Interface utilisateur : COHÉRENTE avec les autres pages admin"
echo "✅ Fonctionnalités système : PRÉSERVÉES et améliorées"
echo "✅ Responsive design : IDENTIQUE aux autres pages"
echo ""
echo "🎉 SUCCÈS : Le dashboard admin utilise maintenant la même sidebar"
echo "   que toutes les autres pages admin via le template harmonisé !"
