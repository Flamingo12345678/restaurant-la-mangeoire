<?php
/**
 * Vérification du fonctionnement du bouton burger sur toutes les pages admin
 * Date: 22 juin 2025
 */

echo "🔧 VÉRIFICATION BOUTON BURGER - TOUTES PAGES ADMIN\n";
echo str_repeat("=", 60) . "\n\n";

// Liste des pages admin à vérifier
$admin_pages = [
    'admin-messages.php' => 'Messages de Contact',
    'admin/index.php' => 'Tableau de Bord Admin',
    'employes.php' => 'Gestion Employés',
    'dashboard-admin.php' => 'Dashboard Système'
];

$issues_found = [];
$total_checks = 0;
$passed_checks = 0;

echo "📊 ANALYSE DES FICHIERS:\n";
echo str_repeat("-", 30) . "\n";

foreach ($admin_pages as $file => $title) {
    echo "\n🔍 Vérification: $title ($file)\n";
    
    if (!file_exists($file)) {
        echo "⚠️  Fichier non trouvé: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $total_checks += 4; // 4 vérifications par fichier
    
    // 1. Vérifier l'inclusion du header_template
    if (strpos($content, 'header_template.php') !== false || 
        strpos($content, 'admin-burger-btn') !== false) {
        echo "✅ Header template inclus ou bouton burger présent\n";
        $passed_checks++;
    } else {
        echo "❌ Header template manquant\n";
        $issues_found[] = "$file - Header template non inclus";
    }
    
    // 2. Vérifier l'inclusion du CSS sidebar
    if (strpos($content, 'admin-sidebar.css') !== false) {
        echo "✅ CSS sidebar inclus\n";
        $passed_checks++;
    } else {
        echo "❌ CSS sidebar manquant\n";
        $issues_found[] = "$file - CSS sidebar non inclus";
    }
    
    // 3. Vérifier l'inclusion du JS sidebar
    if (strpos($content, 'admin-sidebar.js') !== false || 
        strpos($content, 'footer_template.php') !== false) {
        echo "✅ JavaScript sidebar inclus\n";
        $passed_checks++;
    } else {
        echo "❌ JavaScript sidebar manquant\n";
        $issues_found[] = "$file - JavaScript sidebar non inclus";
    }
    
    // 4. Vérifier la structure responsive
    if (strpos($content, 'viewport') !== false || 
        strpos($content, 'meta name="viewport"') !== false) {
        echo "✅ Viewport responsive configuré\n";
        $passed_checks++;
    } else {
        echo "❌ Viewport responsive manquant\n";
        $issues_found[] = "$file - Viewport responsive non configuré";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 VÉRIFICATION DES FICHIERS ASSETS:\n";
echo str_repeat("-", 35) . "\n";

// Vérifier les fichiers assets
$asset_files = [
    'assets/css/admin-sidebar.css' => 'CSS Sidebar',
    'assets/js/admin-sidebar.js' => 'JavaScript Sidebar',
    'admin/header_template.php' => 'Template Header',
    'admin/footer_template.php' => 'Template Footer'
];

foreach ($asset_files as $file => $name) {
    if (file_exists($file)) {
        echo "✅ $name ($file)\n";
        $passed_checks++;
    } else {
        echo "❌ $name manquant ($file)\n";
        $issues_found[] = "Fichier manquant: $file";
    }
    $total_checks++;
}

// Vérifier le contenu des fichiers critiques
echo "\n🔧 VÉRIFICATION DU CONTENU:\n";
echo str_repeat("-", 28) . "\n";

// Vérifier admin-sidebar.js
if (file_exists('assets/js/admin-sidebar.js')) {
    $js_content = file_get_contents('assets/js/admin-sidebar.js');
    
    $js_checks = [
        'document.getElementById(\'admin-burger-btn\')' => 'Sélection bouton burger',
        'classList.toggle(\'open\')' => 'Toggle sidebar',
        'addEventListener(\'click\'' => 'Event listeners',
        'console.log' => 'Debug activé'
    ];
    
    foreach ($js_checks as $check => $description) {
        if (strpos($js_content, $check) !== false) {
            echo "✅ $description\n";
            $passed_checks++;
        } else {
            echo "❌ $description manquant\n";
            $issues_found[] = "JavaScript - $description manquant";
        }
        $total_checks++;
    }
}

// Vérifier admin-sidebar.css
if (file_exists('assets/css/admin-sidebar.css')) {
    $css_content = file_get_contents('assets/css/admin-sidebar.css');
    
    $css_checks = [
        '.admin-burger-btn' => 'Styles bouton burger',
        'position: fixed' => 'Position fixe',
        '@media (max-width: 991.98px)' => 'Responsive mobile',
        '.admin-sidebar.open' => 'État ouvert sidebar'
    ];
    
    foreach ($css_checks as $check => $description) {
        if (strpos($css_content, $check) !== false) {
            echo "✅ $description\n";
            $passed_checks++;
        } else {
            echo "❌ $description manquant\n";
            $issues_found[] = "CSS - $description manquant";
        }
        $total_checks++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ DE LA VÉRIFICATION:\n";
echo str_repeat("=", 60) . "\n";

$success_rate = round(($passed_checks / $total_checks) * 100, 1);

echo "Tests réussis: $passed_checks/$total_checks\n";
echo "Taux de réussite: $success_rate%\n\n";

if (empty($issues_found)) {
    echo "🎉 EXCELLENT! Aucun problème détecté.\n";
    echo "✅ Le bouton burger devrait fonctionner sur toutes les pages.\n";
} else {
    echo "⚠️  PROBLÈMES DÉTECTÉS:\n";
    echo str_repeat("-", 22) . "\n";
    foreach ($issues_found as $issue) {
        echo "• $issue\n";
    }
    
    echo "\n🔧 ACTIONS RECOMMANDÉES:\n";
    echo str_repeat("-", 25) . "\n";
    echo "1. Vérifier les inclusions de templates sur chaque page\n";
    echo "2. S'assurer que les chemins vers les assets sont corrects\n";
    echo "3. Tester le fonctionnement sur mobile (< 992px)\n";
    echo "4. Vérifier la console JavaScript pour les erreurs\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔚 Fin de la vérification - " . date('Y-m-d H:i:s') . "\n";
?>
