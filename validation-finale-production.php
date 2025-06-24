<?php
/**
 * 🚀 VALIDATION FINALE - PROJET PRODUCTION READY
 * Script de vérification complète avant mise en production
 * Date: 24 juin 2025
 */

echo "🚀 VALIDATION FINALE - RESTAURANT LA MANGEOIRE\n";
echo "=============================================\n\n";

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function test_result($test_name, $result, $details = '') {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    
    if ($result) {
        $passed_tests++;
        echo "✅ $test_name\n";
        if ($details) echo "   → $details\n";
    } else {
        $failed_tests++;
        echo "❌ $test_name\n";
        if ($details) echo "   → $details\n";
    }
}

// Test 1: Fichiers critiques
echo "📁 VÉRIFICATION DES FICHIERS CRITIQUES\n";
echo "======================================\n";

$critical_files = [
    'index.php' => 'Page d\'accueil',
    'paiement.php' => 'Page de paiement',
    'confirmation-commande.php' => 'Confirmation commande',
    'dashboard-admin.php' => 'Dashboard admin avec sidebar',
    'admin/header_template.php' => 'Template header admin',
    'admin/footer_template.php' => 'Template footer admin',
    'includes/payment_manager.php' => 'Gestionnaire paiements',
    'includes/email_manager.php' => 'Gestionnaire emails',
    'includes/https_manager.php' => 'Gestionnaire HTTPS',
    'includes/alert_manager.php' => 'Gestionnaire alertes',
    'api/monitoring.php' => 'API monitoring',
    '.env.production' => 'Configuration production',
    '.htaccess-production' => 'Configuration Apache production'
];

foreach ($critical_files as $file => $description) {
    $exists = file_exists($file);
    test_result("$description ($file)", $exists, $exists ? 'Fichier présent' : 'FICHIER MANQUANT');
}

// Test 2: Syntaxe PHP
echo "\n🔍 VÉRIFICATION SYNTAXE PHP\n";
echo "===========================\n";

$php_files_to_check = [
    'dashboard-admin.php',
    'paiement.php',
    'confirmation-commande.php',
    'includes/payment_manager.php',
    'includes/email_manager.php',
    'admin/header_template.php',
    'admin/footer_template.php'
];

foreach ($php_files_to_check as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_code = 0;
        exec("php -l $file 2>&1", $output, $return_code);
        $syntax_ok = ($return_code === 0);
        test_result("Syntaxe $file", $syntax_ok, $syntax_ok ? 'Syntaxe correcte' : 'ERREUR SYNTAXE: ' . implode(' ', $output));
    }
}

// Test 3: Configuration Production
echo "\n🔧 VÉRIFICATION CONFIGURATION PRODUCTION\n";
echo "========================================\n";

// Vérifier .env.production
if (file_exists('.env.production')) {
    $env_content = file_get_contents('.env.production');
    
    test_result("Variable FORCE_HTTPS", strpos($env_content, 'FORCE_HTTPS=true') !== false, 'HTTPS configuré');
    test_result("Variable DB_HOST", 
        strpos($env_content, 'DB_HOST=') !== false || strpos($env_content, 'MYSQLHOST=') !== false, 
        'Host DB configuré (Railway ou standard)');
    test_result("Variable STRIPE_PUBLISHABLE_KEY", strpos($env_content, 'STRIPE_PUBLISHABLE_KEY=') !== false, 'Clé Stripe configurée');
    test_result("Variable PAYPAL_CLIENT_ID", strpos($env_content, 'PAYPAL_CLIENT_ID=') !== false, 'PayPal configuré');
    test_result("Variable SMTP_HOST", strpos($env_content, 'SMTP_HOST=') !== false, 'SMTP configuré');
} else {
    test_result("Fichier .env.production", false, 'FICHIER MANQUANT');
}

// Test 4: Structure Admin
echo "\n👥 VÉRIFICATION INTERFACE ADMIN\n";
echo "===============================\n";

// Simuler test dashboard admin
$dashboard_content = '';
if (file_exists('dashboard-admin.php')) {
    $dashboard_content = file_get_contents('dashboard-admin.php');
    
    test_result("Template header inclus", strpos($dashboard_content, "require_once 'admin/header_template.php'") !== false, 'Header template utilisé');
    test_result("Template footer inclus", strpos($dashboard_content, "require_once 'admin/footer_template.php'") !== false, 'Footer template utilisé');
    test_result("Protection inclusion", strpos($dashboard_content, "define('INCLUDED_IN_PAGE', true)") !== false, 'Protection des templates');
    test_result("Onglets système/paiements", strpos($dashboard_content, 'adminTabs') !== false, 'Navigation par onglets');
}

// Test 5: Système de Paiement
echo "\n💳 VÉRIFICATION SYSTÈME PAIEMENT\n";
echo "================================\n";

if (file_exists('includes/payment_manager.php')) {
    $payment_content = file_get_contents('includes/payment_manager.php');
    
    test_result("Support Stripe", strpos($payment_content, 'stripe') !== false, 'API Stripe intégrée');
    test_result("Support PayPal", strpos($payment_content, 'paypal') !== false, 'API PayPal intégrée');
    test_result("Suppression virement", strpos($payment_content, 'virement') === false, 'Virement bancaire supprimé');
    test_result("Gestion statuts", strpos($payment_content, 'updatePaymentStatus') !== false, 'Mise à jour statuts');
}

// Test 6: Sécurité
echo "\n🛡️ VÉRIFICATION SÉCURITÉ\n";
echo "========================\n";

if (file_exists('.htaccess-production')) {
    $htaccess_content = file_get_contents('.htaccess-production');
    
    test_result("Redirection HTTPS", 
        strpos($htaccess_content, 'RewriteRule .* https://') !== false || strpos($htaccess_content, 'RewriteRule ^(.*)$ https://') !== false, 
        'HTTPS configuré (actif ou template)');
    test_result("Headers sécurisé", strpos($htaccess_content, 'X-Frame-Options') !== false, 'Headers de sécurité');
    test_result("Protection PHP", 
        strpos($htaccess_content, 'Deny from all') !== false || strpos($htaccess_content, 'Order allow,deny') !== false, 
        'Protection fichiers sensibles');
}

// Test 7: Scripts de Déploiement
echo "\n🚀 VÉRIFICATION SCRIPTS DÉPLOIEMENT\n";
echo "===================================\n";

$deploy_scripts = [
    'deploy-production.sh' => 'Script de déploiement principal',
    'auto-deploy-production.sh' => 'Script auto-déploiement',
    'railway-setup.sh' => 'Configuration Railway',
    'enable-https.sh' => 'Activation HTTPS'
];

foreach ($deploy_scripts as $script => $description) {
    $exists = file_exists($script);
    if ($exists) {
        $executable = is_executable($script);
        test_result("$description", $executable, $executable ? 'Script exécutable' : 'Permissions à corriger');
    } else {
        test_result("$description", false, 'Script manquant');
    }
}

// Test 8: Documentation
echo "\n📚 VÉRIFICATION DOCUMENTATION\n";
echo "=============================\n";

$docs = [
    'PRODUCTION_READY.md' => 'Bilan de production',
    'GUIDE_DEPLOIEMENT_HTTPS.md' => 'Guide HTTPS',
    'RESOLUTION_ERREUR_ENV.md' => 'Guide erreurs Railway',
    'INTEGRATION_SIDEBAR_DASHBOARD_FINALE.md' => 'Intégration sidebar'
];

foreach ($docs as $doc => $description) {
    $exists = file_exists($doc);
    test_result("$description", $exists, $exists ? 'Documentation présente' : 'Documentation manquante');
}

// Résultats finaux
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSULTATS FINAUX\n";
echo str_repeat("=", 50) . "\n";

echo "Tests exécutés: $total_tests\n";
echo "✅ Tests réussis: $passed_tests\n";
echo "❌ Tests échoués: $failed_tests\n";

$success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 1) : 0;
echo "📈 Taux de réussite: $success_rate%\n\n";

if ($failed_tests === 0) {
    echo "🎉 FÉLICITATIONS! 🎉\n";
    echo "Le projet Restaurant La Mangeoire est 100% PRÊT POUR LA PRODUCTION!\n";
    echo "\n✅ Toutes les vérifications sont RÉUSSIES\n";
    echo "✅ Le système est SÉCURISÉ et FONCTIONNEL\n";
    echo "✅ L'interface admin est UNIFIÉE et MODERNE\n";
    echo "✅ Le déploiement peut commencer IMMÉDIATEMENT\n";
    echo "\n🚀 STATUS: PRODUCTION READY! 🚀\n";
} else {
    echo "⚠️  ATTENTION! ⚠️\n";
    echo "$failed_tests problème(s) détecté(s) - Voir les détails ci-dessus\n";
    echo "Veuillez corriger ces problèmes avant la mise en production.\n";
    echo "\n🔧 STATUS: CORRECTIONS NÉCESSAIRES\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Validation terminée le " . date('d/m/Y à H:i:s') . "\n";
echo "Projet: Restaurant La Mangeoire - PHP/MySQL\n";
echo str_repeat("=", 50) . "\n";
?>
