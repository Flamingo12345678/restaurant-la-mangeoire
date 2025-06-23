<?php
/**
 * Diagnostic complet de la responsivité mobile
 * Vérifie viewport, CSS, layouts et responsive design
 */

echo "🔍 DIAGNOSTIC COMPLET - RESPONSIVITÉ MOBILE\n";
echo str_repeat("=", 60) . "\n\n";

$pages_to_check = [
    'admin/index.php',
    'admin/administrateurs.php', 
    'admin/menus.php',
    'admin/commandes.php',
    'admin/tables.php',
    'admin-messages.php',
    'dashboard-admin.php',
    'employes.php'
];

$css_files = [
    'assets/css/admin-messages.css',
    'assets/css/admin-sidebar.css',
    'assets/css/admin.css'
];

$issues_found = [];

echo "📱 1. VÉRIFICATION VIEWPORT META TAG\n";
echo str_repeat("-", 40) . "\n";

foreach ($pages_to_check as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Chercher viewport meta tag
        if (preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $content, $matches)) {
            $viewport_tag = $matches[0];
            
            // Vérifier si c'est le bon format
            if (strpos($viewport_tag, 'width=device-width') !== false && 
                strpos($viewport_tag, 'initial-scale=1') !== false) {
                echo "  ✅ $page - Viewport correct\n";
            } else {
                echo "  ⚠️  $page - Viewport incomplet: $viewport_tag\n";
                $issues_found[] = "$page - Viewport incomplet";
            }
        } else {
            echo "  ❌ $page - Viewport manquant\n";
            $issues_found[] = "$page - Viewport manquant";
        }
    }
}

echo "\n📐 2. VÉRIFICATION CSS RESPONSIVE\n";
echo str_repeat("-", 40) . "\n";

foreach ($css_files as $css_file) {
    if (file_exists($css_file)) {
        $content = file_get_contents($css_file);
        
        // Compter les media queries
        $media_query_count = preg_match_all('/@media\s*\([^)]*\)/', $content, $matches);
        
        echo "  📄 $css_file\n";
        echo "    • Media queries: $media_query_count\n";
        
        // Vérifier les breakpoints courants
        $breakpoints = [
            'max-width:\s*768px' => 'Tablette',
            'max-width:\s*480px' => 'Mobile',
            'max-width:\s*320px' => 'Petit mobile',
            'min-width:\s*992px' => 'Desktop'
        ];
        
        foreach ($breakpoints as $pattern => $name) {
            if (preg_match("/@media[^{]*$pattern/i", $content)) {
                echo "    ✅ $name breakpoint trouvé\n";
            } else {
                echo "    ❌ $name breakpoint manquant\n";
                $issues_found[] = "$css_file - Breakpoint $name manquant";
            }
        }
        
        // Vérifier les propriétés importantes pour mobile
        $mobile_properties = [
            'overflow-x\s*:\s*hidden' => 'Overflow horizontal',
            'box-sizing\s*:\s*border-box' => 'Box sizing',
            'width\s*:\s*100%' => 'Largeur 100%',
            'max-width\s*:\s*100%' => 'Max largeur 100%'
        ];
        
        foreach ($mobile_properties as $pattern => $name) {
            if (preg_match("/$pattern/i", $content)) {
                echo "    ✅ $name présent\n";
            }
        }
        
        echo "\n";
    }
}

echo "🔧 3. VÉRIFICATION BOOTSTRAP ET FRAMEWORKS\n";
echo str_repeat("-", 40) . "\n";

foreach ($pages_to_check as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Vérifier Bootstrap
        if (strpos($content, 'bootstrap') !== false) {
            echo "  ✅ $page - Bootstrap détecté\n";
        } else {
            echo "  ⚠️  $page - Bootstrap non détecté\n";
        }
        
        // Vérifier classes responsive Bootstrap
        $bootstrap_classes = ['col-', 'row', 'container', 'd-none', 'd-block', 'd-sm-', 'd-md-', 'd-lg-'];
        $found_classes = [];
        
        foreach ($bootstrap_classes as $class) {
            if (strpos($content, $class) !== false) {
                $found_classes[] = $class;
            }
        }
        
        if (!empty($found_classes)) {
            echo "    • Classes responsive: " . implode(', ', array_slice($found_classes, 0, 3)) . "...\n";
        }
    }
}

echo "\n📊 4. ANALYSE DES PROBLÈMES POTENTIELS\n";
echo str_repeat("-", 40) . "\n";

// Analyser les CSS pour des problèmes courants
foreach ($css_files as $css_file) {
    if (file_exists($css_file)) {
        $content = file_get_contents($css_file);
        
        echo "  📄 $css_file\n";
        
        // Largeurs fixes
        if (preg_match_all('/width\s*:\s*(\d+px)/', $content, $matches)) {
            $fixed_widths = array_unique($matches[1]);
            if (count($fixed_widths) > 3) {
                echo "    ⚠️  Nombreuses largeurs fixes: " . implode(', ', array_slice($fixed_widths, 0, 3)) . "...\n";
                $issues_found[] = "$css_file - Trop de largeurs fixes";
            }
        }
        
        // Texte trop petit
        if (preg_match('/font-size\s*:\s*([0-9.]+)(px|rem|em)/', $content, $matches)) {
            $size = floatval($matches[1]);
            $unit = $matches[2];
            
            if ($unit === 'px' && $size < 14) {
                echo "    ⚠️  Taille de texte très petite détectée: {$matches[0]}\n";
                $issues_found[] = "$css_file - Texte trop petit pour mobile";
            }
        }
        
        // Padding/margins trop grands
        if (preg_match_all('/(padding|margin)\s*:\s*(\d+px)/', $content, $matches)) {
            foreach ($matches[2] as $value) {
                if (intval($value) > 30) {
                    echo "    ⚠️  {$matches[1][0]} important détecté: {$value}\n";
                    break;
                }
            }
        }
        
        echo "\n";
    }
}

echo "🏆 5. RÉSUMÉ ET RECOMMANDATIONS\n";
echo str_repeat("-", 40) . "\n";

if (empty($issues_found)) {
    echo "✅ EXCELLENT: Aucun problème de responsivité majeur détecté!\n";
} else {
    echo "❌ PROBLÈMES DÉTECTÉS (" . count($issues_found) . "):\n";
    foreach ($issues_found as $issue) {
        echo "  • $issue\n";
    }
    
    echo "\n🔧 RECOMMANDATIONS:\n";
    echo "  1. Ajouter/corriger les meta viewport manquants\n";
    echo "  2. Améliorer les media queries pour tous les breakpoints\n";
    echo "  3. Utiliser des unités relatives (rem, %, vw) au lieu de px\n";
    echo "  4. Tester sur vrais appareils mobiles\n";
    echo "  5. Vérifier l'overflow horizontal\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 DIAGNOSTIC TERMINÉ\n";
?>
