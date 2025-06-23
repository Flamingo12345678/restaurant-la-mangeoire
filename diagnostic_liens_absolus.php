<?php
echo "🔍 Diagnostic des Liens Absolus - Restaurant La Mangeoire\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Fichiers à vérifier (pages principales, pas les tests)
$files_to_check = [
    // Pages principales
    'index.php',
    'contact.php',
    'reserver-table.php',
    'panier.php',
    'commande-moderne.php',
    'passer-commande.php',
    
    // Pages admin à la racine
    'admin-messages.php',
    'dashboard-admin.php',
    'employes.php',
    
    // Pages dans le dossier admin
    'admin/index.php',
    'admin/header_template.php',
    'admin/footer_template.php',
    'admin/menus.php',
    'admin/commandes.php',
    'admin/tables.php',
    'admin/administrateurs.php',
    'admin/activity_log.php',
    
    // Scripts JavaScript
    'assets/js/main.js',
    'assets/js/admin-sidebar.js'
];

$problematic_patterns = [
    'http://localhost' => 'URL localhost absolue',
    'href="http://' => 'Lien href absolu',
    'action="http://' => 'Action formulaire absolue',
    'src="http://localhost' => 'Source absolue localhost',
    'window.location = "http://' => 'Redirection JS absolue',
    'Location: http://' => 'Redirection PHP absolue'
];

$issues_found = [];
$files_checked = 0;

echo "📄 VÉRIFICATION DES FICHIERS:\n\n";

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        echo "⚠️  $file - Fichier non trouvé\n";
        continue;
    }
    
    $files_checked++;
    $content = file_get_contents($file);
    $file_issues = [];
    
    foreach ($problematic_patterns as $pattern => $description) {
        if (stripos($content, $pattern) !== false) {
            $file_issues[] = $description;
        }
    }
    
    if (empty($file_issues)) {
        echo "✅ $file - Aucun problème détecté\n";
    } else {
        echo "❌ $file - Problèmes trouvés:\n";
        foreach ($file_issues as $issue) {
            echo "   - $issue\n";
        }
        $issues_found[$file] = $file_issues;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ:\n";
echo "Fichiers vérifiés: $files_checked\n";
echo "Fichiers avec problèmes: " . count($issues_found) . "\n\n";

if (empty($issues_found)) {
    echo "🎉 EXCELLENT ! Aucun lien absolu problématique trouvé.\n";
    echo "Tous les liens utilisent des chemins relatifs.\n\n";
} else {
    echo "🔧 FICHIERS À CORRIGER:\n";
    foreach ($issues_found as $file => $issues) {
        echo "- $file\n";
    }
    echo "\n";
}

echo "🌐 CONFIGURATION POUR ACCÈS MOBILE:\n";
echo "=" . str_repeat("=", 40) . "\n";
echo "Pour accéder depuis votre téléphone :\n\n";

// Obtenir l'IP locale
$local_ip = trim(shell_exec("ifconfig | grep 'inet ' | grep -v 127.0.0.1 | awk '{print $2}' | head -1"));
if (empty($local_ip)) {
    // Méthode alternative pour macOS
    $local_ip = trim(shell_exec("ipconfig getifaddr en0"));
}
if (empty($local_ip)) {
    $local_ip = "[VOTRE_IP_LOCALE]";
}

echo "1. Démarrez votre serveur web :\n";
echo "   cd " . __DIR__ . "\n";
echo "   php -S 0.0.0.0:8000\n\n";

echo "2. Depuis votre téléphone, accédez à :\n";
echo "   http://$local_ip:8000\n\n";

echo "3. Vérifiez que votre téléphone est sur le même réseau WiFi\n\n";

echo "🔧 AVANTAGES DES CHEMINS RELATIFS:\n";
echo "✅ Fonctionnent avec n'importe quelle IP/domaine\n";
echo "✅ Compatibles mobile et desktop\n";
echo "✅ Fonctionnent en local et en production\n";
echo "✅ Pas besoin de configuration spécifique\n\n";

echo "📱 PAGES À TESTER SUR MOBILE:\n";
$test_pages = [
    '' => 'Page d\'accueil',
    'admin-messages.php' => 'Messages admin',
    'admin/index.php' => 'Dashboard admin',
    'admin/menus.php' => 'Gestion menus',
    'admin/commandes.php' => 'Gestion commandes',
    'employes.php' => 'Gestion employés'
];

foreach ($test_pages as $page => $description) {
    echo "- http://$local_ip:8000/$page ($description)\n";
}

echo "\n✅ RÉSULTAT ATTENDU:\n";
echo "Toutes les pages doivent s'afficher identiquement sur mobile et desktop,\n";
echo "avec le menu burger fonctionnel sur mobile.\n";
?>
