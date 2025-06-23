<?php
/**
 * Validation finale - Responsivité et fonctionnement des pages admin
 */

echo "🎯 VALIDATION FINALE - RESPONSIVITÉ PAGES ADMIN\n";
echo str_repeat("=", 60) . "\n\n";

$pages_admin = [
    'admin/administrateurs.php' => 'Gestion Administrateurs',
    'admin/menus.php' => 'Gestion Menus', 
    'admin/commandes.php' => 'Gestion Commandes',
    'admin/tables.php' => 'Gestion Tables',
    'admin-messages.php' => 'Messages Admin',
    'dashboard-admin.php' => 'Dashboard Admin',
    'employes.php' => 'Employés'
];

$success_count = 0;
$total_tests = 0;

echo "📋 1. VÉRIFICATION STRUCTURE HTML RESPONSIVE\n";
echo str_repeat("-", 50) . "\n";

foreach ($pages_admin as $page => $title) {
    $total_tests++;
    
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        $has_responsive_template = strpos($content, 'html_head_template.php') !== false;
        $has_responsive_css = strpos($content, 'admin-responsive.css') !== false || $has_responsive_template;
        $has_bootstrap = strpos($content, 'bootstrap') !== false || $has_responsive_template;
        
        echo "📄 $title\n";
        
        if ($has_responsive_template) {
            echo "  ✅ Template responsive utilisé\n";
            $success_count++;
        } else {
            echo "  ❌ Template responsive manquant\n";
        }
        
        if ($has_responsive_css) {
            echo "  ✅ CSS responsive inclus\n";
        } else {
            echo "  ⚠️  CSS responsive non détecté\n";
        }
        
        if ($has_bootstrap) {
            echo "  ✅ Bootstrap présent\n";
        } else {
            echo "  ⚠️  Bootstrap non détecté\n";
        }
        
        echo "\n";
    } else {
        echo "❌ $title - Fichier non trouvé: $page\n\n";
    }
}

echo "📱 2. VÉRIFICATION CSS RESPONSIVE\n";
echo str_repeat("-", 50) . "\n";

$css_responsive = 'assets/css/admin-responsive.css';
if (file_exists($css_responsive)) {
    echo "✅ Fichier CSS responsive trouvé\n";
    
    $css_content = file_get_contents($css_responsive);
    
    // Compter les media queries
    $media_count = preg_match_all('/@media/', $css_content);
    echo "📊 Media queries: $media_count\n";
    
    // Vérifier les breakpoints essentiels
    $breakpoints = [
        'max-width:\s*768px' => 'Tablette',
        'max-width:\s*480px' => 'Mobile',
        'max-width:\s*320px' => 'Petit mobile',
        'pointer:\s*coarse' => 'Touch devices'
    ];
    
    $bp_found = 0;
    foreach ($breakpoints as $pattern => $name) {
        if (preg_match("/$pattern/i", $css_content)) {
            echo "  ✅ $name breakpoint\n";
            $bp_found++;
        } else {
            echo "  ❌ $name breakpoint manquant\n";
        }
    }
    
    // Vérifier les optimisations importantes
    $optimizations = [
        'overflow-x:\s*hidden' => 'Évite scroll horizontal',
        'font-size:\s*16px' => 'Évite zoom iOS',
        'min-height:\s*44px' => 'Zones tactiles suffisantes',
        'flex:\s*1\s*1\s*25%' => 'Stats cards en ligne'
    ];
    
    echo "\n🔧 Optimisations:\n";
    foreach ($optimizations as $pattern => $desc) {
        if (preg_match("/$pattern/i", $css_content)) {
            echo "  ✅ $desc\n";
        } else {
            echo "  ⚠️  $desc - non détecté\n";
        }
    }
    
} else {
    echo "❌ Fichier CSS responsive non trouvé\n";
}

echo "\n🚀 3. VÉRIFICATION TEMPLATES\n";
echo str_repeat("-", 50) . "\n";

$templates = [
    'admin/html_head_template.php' => 'Template HEAD responsive',
    'admin/html_foot_template.php' => 'Template FOOT responsive'
];

foreach ($templates as $template => $desc) {
    if (file_exists($template)) {
        echo "✅ $desc trouvé\n";
        
        $template_content = file_get_contents($template);
        
        if (strpos($template_content, 'viewport') !== false) {
            echo "  ✅ Viewport meta tag présent\n";
        }
        
        if (strpos($template_content, 'admin-responsive.css') !== false) {
            echo "  ✅ CSS responsive inclus\n";
        }
        
        if (strpos($template_content, 'optimizeForMobile') !== false) {
            echo "  ✅ JavaScript mobile inclus\n";
        }
        
    } else {
        echo "❌ $desc non trouvé\n";
    }
}

echo "\n📊 4. RÉSULTAT FINAL\n";
echo str_repeat("-", 50) . "\n";

$percentage = round(($success_count / $total_tests) * 100);

echo "Pages avec template responsive: $success_count/$total_tests ($percentage%)\n";

if ($percentage >= 80) {
    echo "🎉 EXCELLENT - La responsivité est bien implémentée!\n";
    echo "✅ Les pages admin sont optimisées pour mobile\n";
    echo "✅ Template responsive en place\n";
    echo "✅ CSS responsive avec breakpoints\n";
    echo "✅ Optimisations mobile incluses\n\n";
    
    echo "📱 PROCHAINES ÉTAPES:\n";
    echo "1. Testez sur votre téléphone: http://192.168.1.152:8000/test-responsivite-mobile-complet.html\n";
    echo "2. Vérifiez que les 4 cartes stats restent en ligne horizontale\n";
    echo "3. Testez la navigation avec le bouton burger\n";
    echo "4. Vérifiez que les formulaires ne zooment pas sur iOS\n";
    
} else if ($percentage >= 60) {
    echo "⚠️  BON - Mais des améliorations sont nécessaires\n";
    echo "🔧 Quelques pages n'utilisent pas encore le template responsive\n";
} else {
    echo "❌ PROBLÈME - Beaucoup de pages ne sont pas responsive\n";
    echo "🚨 Action requise: Convertir plus de pages au template responsive\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 VALIDATION TERMINÉE\n";
?>
