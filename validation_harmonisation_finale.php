<?php
/**
 * Script de validation finale complète de l'harmonisation
 * Vérifie que toutes les pages admin sont correctement harmonisées et responsives
 */

echo "🎯 VALIDATION FINALE DE L'HARMONISATION ADMIN\n";
echo "==============================================\n\n";

// Pages principales à vérifier
$pages_principales = [
    'admin/administrateurs.php',
    'admin/menus.php', 
    'admin/commandes.php',
    'admin/tables.php',
    'admin-messages.php',
    'dashboard-admin.php',
    'employes.php'
];

// Templates à vérifier
$templates = [
    'admin/header_template.php',
    'admin/footer_template.php',
    'admin/html_head_template.php',
    'admin/html_foot_template.php'
];

// CSS et assets à vérifier
$assets_critiques = [
    'assets/css/admin-responsive.css',
    'assets/js/admin-sidebar.js',
    'assets/js/harmonize-admin-styles.js'
];

$total_score = 0;
$max_score = 0;

// 1. VÉRIFICATION DES PAGES PRINCIPALES
echo "📋 1. VÉRIFICATION DES PAGES PRINCIPALES\n";
echo "----------------------------------------\n";

foreach ($pages_principales as $page) {
    $max_score += 5;
    $page_score = 0;
    
    if (!file_exists($page)) {
        echo "❌ $page - Fichier introuvable\n";
        continue;
    }
    
    $content = file_get_contents($page);
    echo "📄 $page :\n";
    
    // Vérifier l'inclusion du header template
    if (preg_match('/require.*header_template\.php/', $content)) {
        echo "  ✅ Header template inclus\n";
        $page_score++;
    } else {
        echo "  ❌ Header template manquant\n";
    }
    
    // Vérifier l'inclusion du footer template
    if (preg_match('/require.*footer_template\.php/', $content)) {
        echo "  ✅ Footer template inclus\n";
        $page_score++;
    } else {
        echo "  ❌ Footer template manquant\n";
    }
    
    // Vérifier la définition INCLUDED_IN_PAGE
    if (preg_match('/define.*INCLUDED_IN_PAGE/', $content)) {
        echo "  ✅ Constante INCLUDED_IN_PAGE définie\n";
        $page_score++;
    } else {
        echo "  ❌ Constante INCLUDED_IN_PAGE manquante\n";
    }
    
    // Vérifier l'absence de balises HTML redondantes
    $has_redundant_html = preg_match('/<html[^>]*>.*<html[^>]*>/s', $content) || 
                         preg_match('/<\/html>.*<\/html>/s', $content);
    if (!$has_redundant_html) {
        echo "  ✅ Pas de balises HTML redondantes\n";
        $page_score++;
    } else {
        echo "  ⚠️  Balises HTML redondantes détectées\n";
    }
    
    // Vérifier la structure responsive
    if (preg_match('/container-fluid|row|col-md/', $content)) {
        echo "  ✅ Structure Bootstrap responsive\n";
        $page_score++;
    } else {
        echo "  ⚠️  Structure responsive à vérifier\n";
    }
    
    $total_score += $page_score;
    echo "  📊 Score : $page_score/5\n\n";
}

// 2. VÉRIFICATION DES TEMPLATES
echo "🎨 2. VÉRIFICATION DES TEMPLATES\n";
echo "--------------------------------\n";

foreach ($templates as $template) {
    $max_score += 3;
    $template_score = 0;
    
    if (!file_exists($template)) {
        echo "❌ $template - Fichier introuvable\n";
        continue;
    }
    
    $content = file_get_contents($template);
    echo "📄 $template :\n";
    
    // Vérifier la protection contre l'inclusion directe
    if (preg_match('/INCLUDED_IN_PAGE/', $content)) {
        echo "  ✅ Protection contre inclusion directe\n";
        $template_score++;
    } else {
        echo "  ⚠️  Protection manquante\n";
    }
    
    // Vérifier la gestion des chemins relatifs
    if (preg_match('/asset_path|is_in_admin_folder/', $content)) {
        echo "  ✅ Gestion des chemins relatifs\n";
        $template_score++;
    } else {
        echo "  ⚠️  Gestion des chemins à vérifier\n";
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
    echo "  📊 Score : $template_score/3\n\n";
}

// 3. VÉRIFICATION DES ASSETS CRITIQUES  
echo "🎨 3. VÉRIFICATION DES ASSETS CRITIQUES\n";
echo "---------------------------------------\n";

foreach ($assets_critiques as $asset) {
    $max_score += 2;
    $asset_score = 0;
    
    if (!file_exists($asset)) {
        echo "❌ $asset - Fichier introuvable\n";
        continue;
    }
    
    echo "📄 $asset :\n";
    echo "  ✅ Fichier présent\n";
    $asset_score++;
    
    $filesize = filesize($asset);
    if ($filesize > 100) {
        echo "  ✅ Contenu substantiel (" . round($filesize/1024, 1) . " KB)\n";
        $asset_score++;
    } else {
        echo "  ⚠️  Fichier très petit\n";
    }
    
    $total_score += $asset_score;
    echo "  📊 Score : $asset_score/2\n\n";
}

// 4. VÉRIFICATION DE LA RESPONSIVITÉ
echo "📱 4. VÉRIFICATION DE LA RESPONSIVITÉ\n";
echo "------------------------------------\n";

$responsive_css = 'assets/css/admin-responsive.css';
if (file_exists($responsive_css)) {
    $css_content = file_get_contents($responsive_css);
    $max_score += 4;
    $responsive_score = 0;
    
    // Vérifier les media queries
    $media_queries = preg_match_all('/@media[^{]+{/', $css_content);
    if ($media_queries >= 15) {
        echo "  ✅ Media queries nombreuses ($media_queries détectées)\n";
        $responsive_score++;
    } else {
        echo "  ⚠️  Media queries insuffisantes ($media_queries)\n";
    }
    
    // Vérifier les breakpoints mobiles
    if (preg_match('/max-width:\s*768px/', $css_content)) {
        echo "  ✅ Breakpoint mobile (768px)\n";
        $responsive_score++;
    }
    
    // Vérifier les breakpoints tablette
    if (preg_match('/max-width:\s*992px/', $css_content)) {
        echo "  ✅ Breakpoint tablette (992px)\n";
        $responsive_score++;
    }
    
    // Vérifier les optimisations tactiles
    if (preg_match('/touch-action|user-select|tap-highlight/', $css_content)) {
        echo "  ✅ Optimisations tactiles\n";
        $responsive_score++;
    }
    
    $total_score += $responsive_score;
    echo "  📊 Score responsive : $responsive_score/4\n\n";
} else {
    echo "❌ CSS responsive introuvable\n\n";
}

// 5. CALCUL DU SCORE FINAL
echo "📊 SCORE FINAL DE L'HARMONISATION\n";
echo "==================================\n";

$percentage = round(($total_score / $max_score) * 100, 1);

echo "Score obtenu : $total_score / $max_score ($percentage%)\n\n";

if ($percentage >= 95) {
    echo "🎉 EXCELLENT ! L'harmonisation est parfaite !\n";
    echo "✨ Toutes les pages admin sont harmonisées et responsives.\n";
} elseif ($percentage >= 85) {
    echo "👍 TRÈS BIEN ! L'harmonisation est quasi-parfaite.\n";
    echo "🔧 Quelques ajustements mineurs peuvent être apportés.\n";
} elseif ($percentage >= 75) {
    echo "😊 BIEN ! L'harmonisation est fonctionnelle.\n";
    echo "🎯 Quelques améliorations sont recommandées.\n";
} else {
    echo "⚠️  À AMÉLIORER ! L'harmonisation nécessite plus de travail.\n";
    echo "🔧 Plusieurs éléments doivent être corrigés.\n";
}

echo "\n";
echo "🚀 RECOMMANDATIONS FINALES :\n";
echo "- Tester l'interface sur mobile et desktop\n";
echo "- Vérifier la navigation dans toutes les pages\n";
echo "- S'assurer que les cartes de stats restent alignées\n";
echo "- Valider le comportement du burger menu\n";
echo "- Contrôler qu'aucun débordement n'apparaît sur mobile\n";

echo "\n✅ Validation terminée !\n";
?>
