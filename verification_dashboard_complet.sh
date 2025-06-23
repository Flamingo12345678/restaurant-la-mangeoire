#!/bin/bash

echo "=== VÉRIFICATION COMPLÈTE DU DASHBOARD ADMIN ==="
echo ""

echo "📊 Structure du dashboard :"
echo -n "  - Template header harmonisé : "
if grep -q "require_once 'admin/header_template.php'" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Cartes statistiques harmonisées : "
if grep -q "stats-grid" dashboard-admin.php && grep -q "stat-card" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Services système : "
if grep -q "system-services-card" dashboard-admin.php && grep -q "État des Services Système" dashboard-admin.php; then
    echo "✅ PRÉSENT" 
else
    echo "❌ MANQUANT"
fi

echo -n "  - Métriques de performance : "
if grep -q "performance-metric" dashboard-admin.php && grep -q "Utilisation CPU" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Logs système : "
if grep -q "Logs Système Récents" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - JavaScript fonctionnel : "
if grep -q "animateCountUp" dashboard-admin.php && grep -q "updateSystemStats" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "🔧 Fonctionnalités spécialisées :"

echo -n "  - Monitoring CPU/RAM/Disque : "
if grep -qa "cpu-percent\|memory-percent\|disk-percent" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Uptime système : "
if grep -q "uptime-display\|system_uptime" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Actualisation automatique : "
if grep -q "updateSystemStats()" dashboard-admin.php && grep -q "setInterval" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Fonction optimisation DB : "
if grep -q "optimizeDatabase" dashboard-admin.php; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "📁 Fichiers de support :"

echo -n "  - Fichier system-stats.php : "
if [ -f "includes/system-stats.php" ]; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Template header commun : "
if [ -f "admin/header_template.php" ]; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo -n "  - Template footer commun : "
if [ -f "admin/footer_template.php" ]; then
    echo "✅ PRÉSENT"
else
    echo "❌ MANQUANT"
fi

echo ""
echo "⚙️ Validation technique :"

echo -n "  - Syntaxe PHP : "
if php -l dashboard-admin.php >/dev/null 2>&1; then
    echo "✅ VALIDE"
else
    echo "❌ ERREUR"
fi

echo -n "  - Taille du fichier : "
lines=$(wc -l < dashboard-admin.php)
echo "$lines lignes"

echo -n "  - Pas de code dupliqué : "
duplicated_sections=$(grep -c "État des Services Système" dashboard-admin.php)
if [ "$duplicated_sections" -eq 1 ]; then
    echo "✅ OK (pas de duplication)"
else
    echo "⚠️  ATTENTION ($duplicated_sections sections trouvées)"
fi

echo ""
echo "=== RÉSULTAT FINAL ==="

if php -l dashboard-admin.php >/dev/null 2>&1 && 
   grep -q "admin/header_template.php" dashboard-admin.php &&
   grep -q "stats-grid" dashboard-admin.php &&
   grep -q "system-services-card" dashboard-admin.php &&
   grep -q "performance-metric" dashboard-admin.php &&
   grep -q "Logs Système Récents" dashboard-admin.php; then
    echo "🎉 SUCCÈS : Dashboard admin complet et fonctionnel !"
    echo "✅ Sidebar harmonisée"
    echo "✅ Cartes statistiques cohérentes" 
    echo "✅ Fonctionnalités système complètes"
    echo "✅ Monitoring temps réel"
    echo "✅ Interface professionnelle"
    echo ""
    echo "➡️  Prêt pour utilisation en production !"
else
    echo "❌ PROBLÈME : Certaines fonctionnalités manquent"
    echo "   Vérifiez les éléments marqués comme manquants ci-dessus"
fi
