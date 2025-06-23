<?php
/**
 * Script de nettoyage des balises HTML redondantes
 * Recherche et supprime les balises HTML dupliquées dans les pages admin
 */

$files_to_check = [
    'admin/administrateurs.php',
    'admin/menus.php',
    'admin/commandes.php',
    'admin/tables.php',
    'admin-messages.php',
    'dashboard-admin.php',
    'employes.php'
];

$problematic_patterns = [
    // Balises HTML dupliquées
    '/<html[^>]*>[\s\n]*<html[^>]*>/i',
    '/<\/html>[\s\n]*<\/html>/i',
    '/<head[^>]*>[\s\n]*<head[^>]*>/i',
    '/<\/head>[\s\n]*<\/head>/i',
    '/<body[^>]*>[\s\n]*<body[^>]*>/i',
    '/<\/body>[\s\n]*<\/body>/i',
    
    // Meta viewport dupliqués
    '/(<meta\s+name=["\']viewport["\'][^>]*>)[\s\n]*(<meta\s+name=["\']viewport["\'][^>]*>)/i',
    
    // Scripts bootstrap dupliqués
    '/(bootstrap\.bundle\.min\.js["\'][^>]*>)[\s\n]*[^<]*(<script[^>]*bootstrap\.bundle\.min\.js)/i',
    
    // CSS dupliqués
    '/(admin-responsive\.css["\'][^>]*>)[\s\n]*[^<]*(<link[^>]*admin-responsive\.css)/i',
];

$fixes_applied = 0;
$files_processed = 0;

echo "🧹 NETTOYAGE DES BALISES HTML REDONDANTES\n";
echo "==========================================\n\n";

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        echo "⚠️  Fichier non trouvé : $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $original_content = $content;
    $file_fixes = 0;
    
    echo "Analyse de : $file\n";
    
    // Recherche des balises dupliquées
    foreach ($problematic_patterns as $pattern) {
        $matches = preg_match_all($pattern, $content);
        if ($matches > 0) {
            echo "  🔍 Trouvé " . $matches . " occurrences de balises dupliquées\n";
            
            // Nettoyer selon le type de pattern
            if (strpos($pattern, 'html') !== false) {
                $content = preg_replace('/<html[^>]*>[\s\n]*<html[^>]*>/i', '<html lang="fr">', $content);
                $content = preg_replace('/<\/html>[\s\n]*<\/html>/i', '</html>', $content);
            } elseif (strpos($pattern, 'head') !== false) {
                $content = preg_replace('/<head[^>]*>[\s\n]*<head[^>]*>/i', '<head>', $content);
                $content = preg_replace('/<\/head>[\s\n]*<\/head>/i', '</head>', $content);
            } elseif (strpos($pattern, 'body') !== false) {
                $content = preg_replace('/<body[^>]*>[\s\n]*<body[^>]*>/i', '<body>', $content);
                $content = preg_replace('/<\/body>[\s\n]*<\/body>/i', '</body>', $content);
            } elseif (strpos($pattern, 'viewport') !== false) {
                // Garder seulement le premier meta viewport
                $content = preg_replace('/(<meta\s+name=["\']viewport["\'][^>]*>)[\s\n]*(<meta\s+name=["\']viewport["\'][^>]*>)/i', '$1', $content);
            } elseif (strpos($pattern, 'bootstrap') !== false) {
                // Supprimer les scripts bootstrap dupliqués
                $content = preg_replace('/(bootstrap\.bundle\.min\.js["\'][^>]*>)[\s\n]*[^<]*(<script[^>]*bootstrap\.bundle\.min\.js[^>]*>)/i', '$1', $content);
            } elseif (strpos($pattern, 'admin-responsive') !== false) {
                // Supprimer les CSS admin-responsive dupliqués
                $content = preg_replace('/(admin-responsive\.css["\'][^>]*>)[\s\n]*[^<]*(<link[^>]*admin-responsive\.css[^>]*>)/i', '$1', $content);
            }
            
            $file_fixes++;
        }
    }
    
    // Vérifications spécifiques
    $specific_issues = [
        // Balises script vides
        '/<script[^>]*><\/script>/' => '',
        // Espaces multiples entre balises
        '/>\s{3,}</' => '>' . "\n" . '<',
        // Lignes vides multiples
        '/\n{4,}/' => "\n\n\n",
    ];
    
    foreach ($specific_issues as $pattern => $replacement) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            $file_fixes++;
            echo "  🔧 Nettoyage d'espaces/balises vides\n";
        }
    }
    
    // Sauvegarder si des modifications ont été apportées
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        echo "  ✅ $file_fixes corrections appliquées et sauvegardées\n";
        $fixes_applied += $file_fixes;
    } else {
        echo "  ✨ Aucune correction nécessaire\n";
    }
    
    $files_processed++;
    echo "\n";
}

echo "==========================================\n";
echo "📊 RÉSUMÉ DU NETTOYAGE :\n";
echo "  - Fichiers traités : $files_processed\n";
echo "  - Corrections appliquées : $fixes_applied\n";

if ($fixes_applied > 0) {
    echo "  ✅ Nettoyage terminé avec succès !\n";
} else {
    echo "  ✨ Tous les fichiers étaient déjà propres !\n";
}

echo "\n🎯 Le système de templates est maintenant parfaitement harmonisé.\n";
?>
