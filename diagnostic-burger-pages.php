<?php
echo "🔧 DIAGNOSTIC MENU BURGER - VÉRIFICATION PAGES ADMIN\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Liste des pages admin à vérifier
$pages_admin = [
    'admin-messages.php',
    'admin/index.php', 
    'dashboard-admin.php',
    'employes.php'
];

$erreurs_trouvees = [];
$succès = 0;

foreach ($pages_admin as $page) {
    echo "📄 Vérification de $page...\n";
    
    if (!file_exists($page)) {
        echo "   ❌ Fichier non trouvé: $page\n";
        $erreurs_trouvees[] = "$page - Fichier manquant";
        continue;
    }
    
    $contenu = file_get_contents($page);
    
    // Vérifications essentielles
    $checks = [
        'header_template' => strpos($contenu, 'header_template.php') !== false,
        'admin_sidebar_js' => strpos($contenu, 'admin-sidebar.js') !== false || strpos($contenu, 'header_template.php') !== false,
        'viewport_meta' => strpos($contenu, 'viewport') !== false,
        'bootstrap_icons' => strpos($contenu, 'bootstrap-icons') !== false || strpos($contenu, 'header_template.php') !== false,
        'burger_button' => strpos($contenu, 'admin-burger-btn') !== false || strpos($contenu, 'header_template.php') !== false
    ];
    
    $page_ok = true;
    foreach ($checks as $check => $result) {
        if ($result) {
            echo "   ✅ $check\n";
        } else {
            echo "   ❌ $check\n";
            $erreurs_trouvees[] = "$page - Manque: $check";
            $page_ok = false;
        }
    }
    
    if ($page_ok) {
        $succès++;
        echo "   🎉 Page OK\n";
    } else {
        echo "   ⚠️  Page avec problèmes\n";
    }
    
    echo "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ\n";
echo "   ✅ Pages OK: $succès/" . count($pages_admin) . "\n";
echo "   ❌ Erreurs trouvées: " . count($erreurs_trouvees) . "\n\n";

if (!empty($erreurs_trouvees)) {
    echo "🔍 DÉTAIL DES ERREURS:\n";
    foreach ($erreurs_trouvees as $erreur) {
        echo "   • $erreur\n";
    }
    echo "\n";
}

// Test spécifique du JavaScript dans header_template.php
echo "🔧 VÉRIFICATION DU JAVASCRIPT DANS HEADER_TEMPLATE.PHP\n";
echo str_repeat("-", 60) . "\n";

$header_template = 'admin/header_template.php';
if (file_exists($header_template)) {
    $contenu_header = file_get_contents($header_template);
    
    // Vérifier la cohérence des classes CSS
    $js_checks = [
        'sidebar_toggle_open' => strpos($contenu_header, "classList.toggle('open')") !== false,
        'overlay_toggle_active' => strpos($contenu_header, "overlay.classList.toggle('active')") !== false,
        'sidebar_remove_open' => strpos($contenu_header, "sidebar.classList.remove('open')") !== false,
        'icon_change' => strpos($contenu_header, 'bi-x-lg') !== false && strpos($contenu_header, 'bi-list') !== false
    ];
    
    foreach ($js_checks as $check => $result) {
        echo ($result ? "   ✅" : "   ❌") . " $check\n";
    }
    
    echo "\n";
} else {
    echo "   ❌ header_template.php non trouvé\n\n";
}

// Vérification du CSS
echo "🎨 VÉRIFICATION DU CSS ADMIN-SIDEBAR\n";
echo str_repeat("-", 60) . "\n";

$css_file = 'assets/css/admin-sidebar.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    
    $css_checks = [
        'sidebar_open_class' => strpos($css_content, '.admin-sidebar.open') !== false,
        'burger_mobile_only' => strpos($css_content, '@media') !== false && strpos($css_content, 'admin-burger-btn') !== false,
        'overlay_active' => strpos($css_content, 'overlay') !== false
    ];
    
    foreach ($css_checks as $check => $result) {
        echo ($result ? "   ✅" : "   ❌") . " $check\n";
    }
    
    echo "\n";
} else {
    echo "   ❌ admin-sidebar.css non trouvé\n\n";
}

// Recommandations
echo "💡 RECOMMANDATIONS\n";
echo str_repeat("-", 60) . "\n";

if (count($erreurs_trouvees) == 0) {
    echo "🎉 Toutes les pages semblent correctement configurées !\n";
    echo "   • Le menu burger devrait fonctionner sur mobile\n";
    echo "   • Les classes CSS sont cohérentes\n";
    echo "   • Les scripts JavaScript sont inclus\n\n";
    
    echo "🧪 ÉTAPES DE TEST RECOMMANDÉES:\n";
    echo "   1. Ouvrir chaque page sur un appareil mobile\n";
    echo "   2. Vérifier que le bouton burger est visible\n";
    echo "   3. Tester l'ouverture/fermeture de la sidebar\n";
    echo "   4. Vérifier la fermeture par overlay\n";
    echo "   5. Tester la navigation entre les pages\n";
} else {
    echo "⚠️  Des corrections sont nécessaires:\n";
    echo "   1. Corriger les erreurs listées ci-dessus\n";
    echo "   2. S'assurer que toutes les pages incluent header_template.php\n";
    echo "   3. Vérifier l'inclusion des scripts JavaScript\n";
    echo "   4. Tester sur mobile après corrections\n";
}

echo "\n🔚 Fin du diagnostic\n";
?>
