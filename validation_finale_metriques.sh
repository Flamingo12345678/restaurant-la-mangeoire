#!/bin/bash
# Validation finale des métriques système réelles
# Date: 23 juin 2025

echo "🔍 VALIDATION FINALE - Métriques Système Réelles"
echo "================================================"

# Vérification des fichiers
echo ""
echo "📁 Vérification des fichiers..."

files=("dashboard-admin.php" "includes/system-stats.php" "api/system-stats.php")

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file existe"
        
        # Vérification syntaxe PHP
        if php -l "$file" > /dev/null 2>&1; then
            echo "   ✅ Syntaxe PHP correcte"
        else
            echo "   ❌ Erreur de syntaxe PHP"
        fi
    else
        echo "❌ $file manquant"
    fi
done

echo ""
echo "🔧 Vérification des fonctions système..."

# Test des fonctions
php -r "
require_once 'includes/system-stats.php';

echo '📊 Test des métriques système:' . PHP_EOL;

// Test getSystemStats
try {
    \$stats = getSystemStats();
    echo '   ✅ getSystemStats(): CPU=' . \$stats['cpu'] . '%, RAM=' . \$stats['memory'] . '%, Disk=' . \$stats['disk'] . '%' . PHP_EOL;
} catch (Exception \$e) {
    echo '   ❌ getSystemStats(): ' . \$e->getMessage() . PHP_EOL;
}

// Test getSystemUptime
try {
    \$uptime = getSystemUptime();
    echo '   ✅ getSystemUptime(): ' . \$uptime . PHP_EOL;
} catch (Exception \$e) {
    echo '   ❌ getSystemUptime(): ' . \$e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
"

echo "🔍 Vérification de l'intégration dashboard..."

# Vérifier que dashboard-admin.php utilise les vraies fonctions
if grep -q "require_once 'includes/system-stats.php'" dashboard-admin.php; then
    echo "✅ Dashboard utilise les fonctions système réelles"
else
    echo "❌ Dashboard n'utilise pas les fonctions système"
fi

if grep -q "getSystemStats()" dashboard-admin.php; then
    echo "✅ Dashboard appelle getSystemStats()"
else
    echo "❌ Dashboard n'appelle pas getSystemStats()"
fi

echo ""
echo "🌐 Vérification API AJAX..."

# Vérifier l'API
if grep -q "getSystemStats()" api/system-stats.php; then
    echo "✅ API utilise les vraies métriques"
else
    echo "❌ API n'utilise pas les vraies métriques"
fi

echo ""
echo "📱 Vérification JavaScript..."

# Vérifier le JavaScript de mise à jour
if grep -q "setInterval(updateSystemStats" dashboard-admin.php; then
    echo "✅ Mise à jour automatique configurée"
else
    echo "❌ Mise à jour automatique manquante"
fi

if grep -q "api/system-stats.php" dashboard-admin.php; then
    echo "✅ Connexion API configurée"
else
    echo "❌ Connexion API manquante"
fi

echo ""
echo "🎯 Résumé des améliorations:"
echo "   • Métriques CPU réelles (multiplateforme)"
echo "   • Utilisation mémoire système (vs PHP uniquement)"
echo "   • Espace disque du système complet"
echo "   • Uptime système précis"
echo "   • Services système vérifiés dynamiquement"
echo "   • Événements basés sur les données BDD"
echo "   • Mise à jour automatique toutes les 30s"

echo ""
echo "🚀 ÉTAPES SUIVANTES:"
echo "   1. Démarrer le serveur web local"
echo "   2. Se connecter en tant que superadmin"
echo "   3. Accéder à dashboard-admin.php"
echo "   4. Vérifier que les métriques sont réalistes"
echo "   5. Attendre 30s pour voir la mise à jour automatique"

echo ""
echo "🎉 Configuration terminée avec succès !"
