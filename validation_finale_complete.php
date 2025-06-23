<?php
/**
 * VALIDATION FINALE COMPLÈTE
 * Vérifie que toutes les pages admin sont harmonisées et responsives
 * Teste l'affichage des cartes de statistiques
 */

echo "<!DOCTYPE html>\n";
echo "<html lang='fr'>\n";
echo "<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<title>Validation Finale Complète - Interface Admin</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }\n";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo ".header { text-align: center; margin-bottom: 30px; }\n";
echo ".section { margin-bottom: 30px; }\n";
echo ".section h3 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; }\n";
echo ".status { padding: 8px 12px; border-radius: 4px; font-weight: bold; }\n";
echo ".success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }\n";
echo ".error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }\n";
echo ".warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }\n";
echo ".info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }\n";
echo ".score { font-size: 24px; font-weight: bold; margin: 10px 0; }\n";
echo ".table { width: 100%; border-collapse: collapse; margin: 15px 0; }\n";
echo ".table th, .table td { padding: 8px; border: 1px solid #ddd; text-align: left; }\n";
echo ".table th { background: #f8f9fa; font-weight: bold; }\n";
echo ".result-item { margin: 5px 0; padding: 8px; border-left: 4px solid #3498db; background: #f8f9fa; }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<div class='container'>\n";
echo "<div class='header'>\n";
echo "<h1>🏆 VALIDATION FINALE COMPLÈTE</h1>\n";
echo "<p>Interface Admin Restaurant La Mangeoire</p>\n";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "</div>\n";

// Configuration
$pages_admin = [
    'dashboard-admin.php' => 'Dashboard Principal',
    'admin-messages.php' => 'Messages Admin',
    'admin/administrateurs.php' => 'Gestion Administrateurs',
    'admin/menus.php' => 'Gestion Menus',
    'admin/commandes.php' => 'Gestion Commandes',
    'admin/tables.php' => 'Gestion Tables',
    'employes.php' => 'Gestion Employés'
];

$fichiers_critiques = [
    'admin/header_template.php' => 'Template Header',
    'admin/footer_template.php' => 'Template Footer',
    'assets/css/admin-responsive.css' => 'CSS Responsive'
];

$total_tests = 0;
$tests_reussis = 0;

// Test 1: Existence des fichiers critiques
echo "<div class='section'>\n";
echo "<h3>📁 Test 1: Fichiers Critiques</h3>\n";

foreach ($fichiers_critiques as $fichier => $nom) {
    $total_tests++;
    if (file_exists($fichier)) {
        echo "<div class='result-item'>\n";
        echo "<span class='status success'>✓ TROUVÉ</span> $nom ($fichier)\n";
        echo "</div>\n";
        $tests_reussis++;
    } else {
        echo "<div class='result-item'>\n";
        echo "<span class='status error'>✗ MANQUANT</span> $nom ($fichier)\n";
        echo "</div>\n";
    }
}

// Test 2: Validation des templates dans les pages
echo "</div>\n";
echo "<div class='section'>\n";
echo "<h3>🔗 Test 2: Intégration des Templates</h3>\n";

foreach ($pages_admin as $page => $nom) {
    $total_tests++;
    if (file_exists($page)) {
        $contenu = file_get_contents($page);
        $utilise_header = (strpos($contenu, 'header_template.php') !== false || strpos($contenu, 'html_head_template.php') !== false);
        $utilise_footer = (strpos($contenu, 'footer_template.php') !== false || strpos($contenu, 'html_foot_template.php') !== false);
        
        if ($utilise_header && $utilise_footer) {
            echo "<div class='result-item'>\n";
            echo "<span class='status success'>✓ TEMPLATES OK</span> $nom\n";
            echo "</div>\n";
            $tests_reussis++;
        } else {
            echo "<div class='result-item'>\n";
            echo "<span class='status warning'>⚠ TEMPLATES PARTIELS</span> $nom (Header: " . ($utilise_header ? 'Oui' : 'Non') . ", Footer: " . ($utilise_footer ? 'Oui' : 'Non') . ")\n";
            echo "</div>\n";
        }
    } else {
        echo "<div class='result-item'>\n";
        echo "<span class='status error'>✗ FICHIER MANQUANT</span> $nom\n";
        echo "</div>\n";
    }
}

// Test 3: Vérification CSS responsive
echo "</div>\n";
echo "<div class='section'>\n";
echo "<h3>📱 Test 3: CSS Responsive</h3>\n";

$total_tests++;
if (file_exists('assets/css/admin-responsive.css')) {
    $css_content = file_get_contents('assets/css/admin-responsive.css');
    
    // Vérifier les media queries essentielles
    $media_queries = [
        '@media (max-width: 768px)' => 'Mobile',
        '@media (max-width: 992px)' => 'Tablette',
        '@media (min-width: 1200px)' => 'Desktop Large'
    ];
    
    $mq_trouvees = 0;
    foreach ($media_queries as $mq => $nom) {
        if (strpos($css_content, $mq) !== false) {
            $mq_trouvees++;
            echo "<div class='result-item'>\n";
            echo "<span class='status success'>✓ MQ TROUVÉE</span> $nom ($mq)\n";
            echo "</div>\n";
        }
    }
    
    // Vérifier les styles des cartes de stats
    $styles_cartes = [
        '.stats-card' => 'Cartes de statistiques',
        '.row.g-4' => 'Grille responsive',
        '.col-md-3' => 'Colonnes responsive'
    ];
    
    $styles_trouves = 0;
    foreach ($styles_cartes as $style => $nom) {
        if (strpos($css_content, $style) !== false) {
            $styles_trouves++;
            echo "<div class='result-item'>\n";
            echo "<span class='status success'>✓ STYLE OK</span> $nom ($style)\n";
            echo "</div>\n";
        }
    }
    
    if ($mq_trouvees >= 2 && $styles_trouves >= 2) {
        $tests_reussis++;
        echo "<div class='result-item'>\n";
        echo "<span class='status success'>✓ CSS RESPONSIVE VALIDÉ</span> Media queries: $mq_trouvees/3, Styles cartes: $styles_trouves/3\n";
        echo "</div>\n";
    }
} else {
    echo "<div class='result-item'>\n";
    echo "<span class='status error'>✗ CSS RESPONSIVE MANQUANT</span>\n";
    echo "</div>\n";
}

// Test 4: Vérification des cartes de statistiques
echo "</div>\n";
echo "<div class='section'>\n";
echo "<h3>📊 Test 4: Cartes de Statistiques</h3>\n";

$pages_avec_stats = ['dashboard-admin.php', 'admin-messages.php'];

foreach ($pages_avec_stats as $page) {
    $total_tests++;
    if (file_exists($page)) {
        $contenu = file_get_contents($page);
        
        // Chercher la structure des cartes
        $a_stats_card = (strpos($contenu, 'stats-card') !== false);
        $a_row_g4 = (strpos($contenu, 'row g-4') !== false || strpos($contenu, 'row.g-4') !== false);
        $a_col_md = (strpos($contenu, 'col-md-3') !== false || strpos($contenu, 'col-md-6') !== false);
        
        if ($a_stats_card && ($a_row_g4 || $a_col_md)) {
            echo "<div class='result-item'>\n";
            echo "<span class='status success'>✓ STRUCTURE OK</span> $page (stats-card: " . ($a_stats_card ? 'Oui' : 'Non') . ", grille: " . (($a_row_g4 || $a_col_md) ? 'Oui' : 'Non') . ")\n";
            echo "</div>\n";
            $tests_reussis++;
        } else {
            echo "<div class='result-item'>\n";
            echo "<span class='status warning'>⚠ STRUCTURE PARTIELLE</span> $page\n";
            echo "</div>\n";
        }
    }
}

// Test 5: Vérification des scripts JavaScript
echo "</div>\n";
echo "<div class='section'>\n";
echo "<h3>📜 Test 5: Scripts JavaScript</h3>\n";

$total_tests++;
if (file_exists('admin/footer_template.php')) {
    $footer_content = file_get_contents('admin/footer_template.php');
    
    // Vérifier les scripts essentiels
    $scripts_essentiels = [
        'bootstrap.bundle.min.js' => 'Bootstrap JS',
        'chart.js' => 'Chart.js',
        'sidebar-mobile.js' => 'Sidebar Mobile'
    ];
    
    $scripts_trouves = 0;
    foreach ($scripts_essentiels as $script => $nom) {
        if (strpos($footer_content, $script) !== false) {
            $scripts_trouves++;
            echo "<div class='result-item'>\n";
            echo "<span class='status success'>✓ SCRIPT OK</span> $nom\n";
            echo "</div>\n";
        }
    }
    
    // Vérifier le support des scripts additionnels
    $support_additional = (strpos($footer_content, '$additional_js') !== false);
    
    if ($scripts_trouves >= 2 && $support_additional) {
        $tests_reussis++;
        echo "<div class='result-item'>\n";
        echo "<span class='status success'>✓ SYSTÈME SCRIPTS VALIDÉ</span> Scripts: $scripts_trouves/3, Support additionnel: " . ($support_additional ? 'Oui' : 'Non') . "\n";
        echo "</div>\n";
    }
}

// Calcul du score final
echo "</div>\n";
echo "<div class='section'>\n";
echo "<h3>🎯 Résultat Final</h3>\n";

$pourcentage = ($total_tests > 0) ? round(($tests_reussis / $total_tests) * 100) : 0;

echo "<div class='score'>\n";
if ($pourcentage >= 90) {
    echo "<span class='status success'>🏆 EXCELLENT: $pourcentage% ($tests_reussis/$total_tests)</span>\n";
} elseif ($pourcentage >= 75) {
    echo "<span class='status info'>👍 BON: $pourcentage% ($tests_reussis/$total_tests)</span>\n";
} elseif ($pourcentage >= 50) {
    echo "<span class='status warning'>⚠ MOYEN: $pourcentage% ($tests_reussis/$total_tests)</span>\n";
} else {
    echo "<span class='status error'>❌ FAIBLE: $pourcentage% ($tests_reussis/$total_tests)</span>\n";
}
echo "</div>\n";

// Recommandations
echo "<div class='section'>\n";
echo "<h3>💡 Recommandations</h3>\n";

if ($pourcentage >= 90) {
    echo "<div class='result-item'>\n";
    echo "<span class='status success'>✅ FÉLICITATIONS</span> L'interface admin est harmonisée, responsive et optimisée !\n";
    echo "</div>\n";
    echo "<div class='result-item'>\n";
    echo "<span class='status info'>🔍 TESTS MANUELS</span> Testez l'affichage sur différents appareils (mobile, tablette, desktop)\n";
    echo "</div>\n";
    echo "<div class='result-item'>\n";
    echo "<span class='status info'>🧹 NETTOYAGE</span> Supprimez les anciens fichiers CSS/JS non utilisés si nécessaire\n";
    echo "</div>\n";
} else {
    echo "<div class='result-item'>\n";
    echo "<span class='status warning'>⚠ AMÉLIORATIONS NÉCESSAIRES</span> Vérifiez les éléments marqués comme manquants ou partiels\n";
    echo "</div>\n";
}

echo "</div>\n";

// Informations système
echo "<div class='section'>\n";
echo "<h3>🔧 Informations Système</h3>\n";
echo "<div class='result-item'>\n";
echo "<strong>PHP Version:</strong> " . phpversion() . "\n";
echo "</div>\n";
echo "<div class='result-item'>\n";
echo "<strong>Répertoire:</strong> " . getcwd() . "\n";
echo "</div>\n";
echo "<div class='result-item'>\n";
echo "<strong>Timestamp:</strong> " . time() . "\n";
echo "</div>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>
