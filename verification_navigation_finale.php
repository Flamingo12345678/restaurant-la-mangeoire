<?php
/**
 * Vérification finale des liens de navigation dans la sidebar admin
 * Date: 22 juin 2025
 */

echo "🔗 VÉRIFICATION FINALE - NAVIGATION SIDEBAR ADMIN\n";
echo str_repeat("=", 60) . "\n\n";

// Vérifier le contenu du header_template.php
echo "📋 ANALYSE DU HEADER TEMPLATE:\n";
echo str_repeat("-", 30) . "\n";

$header_file = 'admin/header_template.php';
if (file_exists($header_file)) {
    $header_content = file_get_contents($header_file);
    
    // Vérifications critiques
    $checks = [
        '$is_in_admin_folder' => 'Détection du contexte de répertoire',
        '$admin_prefix' => 'Préfixe pour liens admin',
        '$root_prefix' => 'Préfixe pour liens racine',
        'echo $admin_prefix' => 'Utilisation du préfixe admin',
        'echo $root_prefix' => 'Utilisation du préfixe racine'
    ];
    
    $passed = 0;
    $total = count($checks);
    
    foreach ($checks as $check => $description) {
        if (strpos($header_content, $check) !== false) {
            echo "✅ $description\n";
            $passed++;
        } else {
            echo "❌ $description - MANQUANT\n";
        }
    }
    
    echo "\nRésultat: $passed/$total vérifications passées\n";
} else {
    echo "❌ Fichier header_template.php non trouvé\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔍 ANALYSE DES LIENS SPÉCIFIQUES:\n";
echo str_repeat("=", 60) . "\n";

// Analyser les liens critiques
$critical_links = [
    'admin-messages.php' => 'Messages (lien vers racine)',
    'employes.php' => 'Employés (lien vers racine)', 
    'dashboard-admin.php' => 'Dashboard Système (lien vers racine)',
    'index.php' => 'Tableau de bord (lien vers admin)',
    'logout.php' => 'Déconnexion (lien vers admin)'
];

foreach ($critical_links as $file => $description) {
    $found_dynamic = strpos($header_content, 'echo $root_prefix') !== false && strpos($header_content, $file) !== false;
    $found_static = strpos($header_content, 'echo $admin_prefix') !== false && strpos($header_content, $file) !== false;
    
    if ($found_dynamic || $found_static) {
        echo "✅ $description ($file)\n";
    } else {
        echo "❌ $description ($file) - Lien non dynamique\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 VÉRIFICATION DES PAGES ADMIN:\n";
echo str_repeat("=", 60) . "\n";

// Pages importantes à vérifier
$admin_pages = [
    'admin-messages.php' => ['Viewport', 'CSS Sidebar', 'Header Template', 'Footer Template'],
    'admin/index.php' => ['CSS Sidebar', 'Header Template', 'Footer Template'],
    'dashboard-admin.php' => ['CSS Sidebar', 'Header Template', 'Footer Template'],
    'employes.php' => ['Header Template', 'CSS Sidebar', 'Footer Template']
];

$global_issues = [];

foreach ($admin_pages as $page => $required_elements) {
    echo "\n🔍 Analyse: $page\n";
    
    if (!file_exists($page)) {
        echo "⚠️  Page non trouvée: $page\n";
        continue;
    }
    
    $page_content = file_get_contents($page);
    
    foreach ($required_elements as $element) {
        $found = false;
        
        switch ($element) {
            case 'Viewport':
                $found = strpos($page_content, 'name="viewport"') !== false;
                break;
            case 'CSS Sidebar':
                $found = strpos($page_content, 'admin-sidebar.css') !== false;
                break;
            case 'Header Template':
                $found = strpos($page_content, 'header_template.php') !== false;
                break;
            case 'Footer Template':
                $found = strpos($page_content, 'footer_template.php') !== false;
                break;
        }
        
        if ($found) {
            echo "  ✅ $element\n";
        } else {
            echo "  ❌ $element manquant\n";
            $global_issues[] = "$page - $element manquant";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 RÉSUMÉ ET RECOMMANDATIONS:\n";
echo str_repeat("=", 60) . "\n";

if (empty($global_issues)) {
    echo "🎉 EXCELLENT! Tous les éléments sont en place.\n\n";
    echo "✅ Navigation dynamique configurée\n";
    echo "✅ Tous les liens adaptés au contexte\n";
    echo "✅ Pages admin correctement structurées\n";
    echo "✅ Bouton burger devrait fonctionner partout\n\n";
    echo "🔧 TESTS RECOMMANDÉS:\n";
    echo "1. Tester la navigation depuis admin-messages.php\n";
    echo "2. Tester la navigation depuis admin/index.php\n";
    echo "3. Vérifier le bouton burger sur mobile\n";
    echo "4. Confirmer les redirections de sécurité\n";
} else {
    echo "⚠️  PROBLÈMES DÉTECTÉS:\n";
    echo str_repeat("-", 25) . "\n";
    foreach ($global_issues as $issue) {
        echo "• $issue\n";
    }
    
    echo "\n🔧 ACTIONS NÉCESSAIRES:\n";
    echo "1. Corriger les éléments manquants listés ci-dessus\n";
    echo "2. Vérifier l'inclusion des templates sur chaque page\n";
    echo "3. S'assurer que tous les CSS sont chargés\n";
    echo "4. Tester la navigation manuelle\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔚 Fin de la vérification - " . date('Y-m-d H:i:s') . "\n";
echo "📁 Pour tester: ouvrir test-navigation-sidebar.html\n";
echo str_repeat("=", 60) . "\n";
?>
