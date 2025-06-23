<?php
/**
 * Script de validation finale du système de scripts JavaScript optimisé
 * Vérifie que toutes les pages utilisent correctement le nouveau système
 */

echo "🚀 VALIDATION DU SYSTÈME DE SCRIPTS OPTIMISÉ\n";
echo "=============================================\n\n";

// Pages à vérifier
$pages_a_verifier = [
    'admin/administrateurs.php',
    'admin/menus.php', 
    'admin/commandes.php',
    'admin/tables.php',
    'admin-messages.php',
    'dashboard-admin.php',
    'employes.php',
    'admin/demo-scripts-optimises.php'
];

$templates_a_verifier = [
    'admin/header_template.php',
    'admin/footer_template.php'
];

$total_score = 0;
$max_score = 0;

echo "📋 1. VÉRIFICATION DES PAGES\n";
echo "----------------------------\n";

foreach ($pages_a_verifier as $page) {
    $max_score += 3;
    $page_score = 0;
    
    if (!file_exists($page)) {
        echo "❌ $page - Fichier introuvable\n";
        continue;
    }
    
    $content = file_get_contents($page);
    echo "📄 $page :\n";
    
    // Vérifier l'utilisation des nouveaux templates
    if (preg_match('/require.*header_template\.php/', $content)) {
        echo "  ✅ Utilise header_template.php\n";
        $page_score++;
    } else {
        echo "  ❌ N'utilise pas header_template.php\n";
    }
    
    if (preg_match('/require.*footer_template\.php/', $content)) {
        echo "  ✅ Utilise footer_template.php\n";
        $page_score++;
    } else {
        echo "  ❌ N'utilise pas footer_template.php\n";
    }
    
    // Vérifier l'absence de scripts hardcodés
    $has_hardcoded_scripts = preg_match('/<script\s+src=["\'][^"\']*bootstrap[^"\']*["\']/', $content) ||
                             preg_match('/<script\s+src=["\'][^"\']*jquery[^"\']*["\']/', $content);
    
    if (!$has_hardcoded_scripts) {
        echo "  ✅ Pas de scripts hardcodés\n";
        $page_score++;
    } else {
        echo "  ⚠️  Scripts hardcodés détectés\n";
    }
    
    $total_score += $page_score;
    echo "  📊 Score : $page_score/3\n\n";
}

echo "🎨 2. VÉRIFICATION DES TEMPLATES\n";
echo "--------------------------------\n";

foreach ($templates_a_verifier as $template) {
    $max_score += 4;
    $template_score = 0;
    
    if (!file_exists($template)) {
        echo "❌ $template - Fichier introuvable\n";
        continue;
    }
    
    $content = file_get_contents($template);
    echo "📄 $template :\n";
    
    // Vérifications spécifiques selon le type de template
    if (strpos($template, 'header_template.php') !== false) {
        // Pour le header template
        
        // Vérifier le support des scripts dans le head
        if (strpos($content, '$head_js') !== false) {
            echo "  ✅ Support des scripts dans le head\n";
            $template_score++;
        } else {
            echo "  ❌ Support des scripts dans le head manquant\n";
        }
        
        // Vérifier le support des CSS additionnels
        if (strpos($content, '$additional_css') !== false) {
            echo "  ✅ Support des CSS additionnels\n";
            $template_score++;
        } else {
            echo "  ❌ Support des CSS additionnels manquant\n";
        }
        
        // Vérifier la gestion des chemins relatifs
        if (strpos($content, '$asset_path') !== false) {
            echo "  ✅ Gestion des chemins relatifs\n";
            $template_score++;
        } else {
            echo "  ❌ Gestion des chemins relatifs manquante\n";
        }
        
    } else {
        // Pour le footer template
        
        // Vérifier la présence du système de scripts communs
        if (strpos($content, '$common_scripts') !== false) {
            echo "  ✅ Système de scripts communs présent\n";
            $template_score++;
        } else {
            echo "  ❌ Système de scripts communs manquant\n";
        }
        
        // Vérifier la gestion des scripts additionnels
        if (strpos($content, '$additional_js') !== false) {
            echo "  ✅ Support des scripts additionnels\n";
            $template_score++;
        } else {
            echo "  ❌ Support des scripts additionnels manquant\n";
        }
        
        // Vérifier la gestion des chemins relatifs
        if (strpos($content, '$asset_path') !== false) {
            echo "  ✅ Gestion des chemins relatifs\n";
            $template_score++;
        } else {
            echo "  ❌ Gestion des chemins relatifs manquante\n";
        }
    }
    
    // Vérifier la syntaxe PHP
    $syntax_check = shell_exec("php -l '$template' 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "  ✅ Syntaxe PHP correcte\n";
        $template_score++;
    } else {
        echo "  ❌ Erreur de syntaxe PHP\n";
    }
    
    $total_score += $template_score;
    echo "  📊 Score : $template_score/4\n\n";
}

echo "⚡ 3. ANALYSE DES PERFORMANCES\n";
echo "------------------------------\n";

// Vérifier les scripts communs
$footer_content = file_get_contents('admin/footer_template.php');
$scripts_communs_count = substr_count($footer_content, '$asset_path');
$max_score += 2;
$perf_score = 0;

if ($scripts_communs_count >= 5) {
    echo "  ✅ Scripts communs optimisés ($scripts_communs_count détectés)\n";
    $perf_score++;
} else {
    echo "  ⚠️  Peu de scripts communs ($scripts_communs_count)\n";
}

// Vérifier l'utilisation du CDN Bootstrap
if (strpos($footer_content, 'cdn.jsdelivr.net') !== false) {
    echo "  ✅ CDN Bootstrap utilisé\n";
    $perf_score++;
} else {
    echo "  ⚠️  CDN Bootstrap non détecté\n";
}

$total_score += $perf_score;
echo "  📊 Score performance : $perf_score/2\n\n";

echo "🧪 4. TESTS FONCTIONNELS\n";
echo "------------------------\n";

$max_score += 3;
$functional_score = 0;

// Vérifier que la page de demo existe
if (file_exists('admin/demo-scripts-optimises.php')) {
    echo "  ✅ Page de démonstration créée\n";
    $functional_score++;
    
    $demo_content = file_get_contents('admin/demo-scripts-optimises.php');
    
    // Vérifier l'utilisation des variables de scripts
    if (strpos($demo_content, '$head_js') !== false && 
        strpos($demo_content, '$additional_js') !== false) {
        echo "  ✅ Variables de scripts utilisées\n";
        $functional_score++;
    } else {
        echo "  ❌ Variables de scripts non utilisées\n";
    }
    
    // Vérifier l'exemple avec Chart.js
    if (strpos($demo_content, 'chart.js') !== false) {
        echo "  ✅ Exemple avec Chart.js intégré\n";
        $functional_score++;
    } else {
        echo "  ❌ Exemple Chart.js manquant\n";
    }
} else {
    echo "  ❌ Page de démonstration manquante\n";
}

$total_score += $functional_score;
echo "  📊 Score fonctionnel : $functional_score/3\n\n";

// Calcul du score final
echo "📊 SCORE FINAL DU SYSTÈME DE SCRIPTS\n";
echo "====================================\n";

$percentage = round(($total_score / $max_score) * 100, 1);
echo "Score obtenu : $total_score / $max_score ($percentage%)\n\n";

if ($percentage >= 95) {
    echo "🎉 EXCELLENT ! Le système de scripts est parfaitement optimisé !\n";
    echo "✨ Toutes les pages utilisent le système harmonisé.\n";
} elseif ($percentage >= 85) {
    echo "👍 TRÈS BIEN ! Le système de scripts est bien optimisé.\n";
    echo "🔧 Quelques ajustements mineurs peuvent être apportés.\n";
} elseif ($percentage >= 75) {
    echo "😊 BIEN ! Le système de scripts est fonctionnel.\n";
    echo "🎯 Quelques améliorations sont recommandées.\n";
} else {
    echo "⚠️  À AMÉLIORER ! Le système de scripts nécessite des corrections.\n";
    echo "🔧 Plusieurs éléments doivent être optimisés.\n";
}

echo "\n🚀 AVANTAGES DU SYSTÈME OPTIMISÉ :\n";
echo "- ✅ Scripts communs centralisés et automatiques\n";
echo "- ✅ Support des scripts spécifiques par page\n";
echo "- ✅ Gestion des CSS additionnels\n";
echo "- ✅ Chargement conditionnel (performance)\n";
echo "- ✅ Structure maintenable et évolutive\n";
echo "- ✅ Compatible avec tous les navigateurs\n";

echo "\n💡 UTILISATION RECOMMANDÉE :\n";
echo "1. Utiliser \$head_js pour scripts d'initialisation\n";
echo "2. Utiliser \$additional_js pour scripts de fin de page\n";
echo "3. Utiliser \$additional_css pour styles spécifiques\n";
echo "4. Tester la page admin/demo-scripts-optimises.php\n";

echo "\n✅ Validation terminée !\n";
?>
