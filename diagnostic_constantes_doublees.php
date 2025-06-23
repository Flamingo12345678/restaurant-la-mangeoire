<?php
/**
 * Script de diagnostic pour détecter les constantes définies plusieurs fois
 * dans le même fichier PHP
 */

$files_to_check = [
    'admin/administrateurs.php',
    'admin/index.php', 
    'admin/menus.php',
    'admin/commandes.php',
    'admin/tables.php',
    'admin/activity_log.php',
    'admin-messages.php',
    'dashboard-admin.php',
    'employes.php'
];

echo "🔍 DIAGNOSTIC - Détection des constantes doublées\n";
echo str_repeat("=", 60) . "\n\n";

$errors_found = false;

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "📄 Vérification de $file...\n";
        
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        $constants_found = [];
        $line_number = 0;
        
        foreach ($lines as $line) {
            $line_number++;
            // Recherche des définitions de constantes
            if (preg_match("/define\s*\(\s*['\"]([^'\"]+)['\"]/", $line, $matches)) {
                $constant_name = $matches[1];
                
                if (!isset($constants_found[$constant_name])) {
                    $constants_found[$constant_name] = [];
                }
                $constants_found[$constant_name][] = $line_number;
            }
        }
        
        // Vérifier les doublons
        $file_has_errors = false;
        foreach ($constants_found as $constant => $line_numbers) {
            if (count($line_numbers) > 1) {
                echo "  ❌ ERREUR: Constante '$constant' définie " . count($line_numbers) . " fois aux lignes: " . implode(', ', $line_numbers) . "\n";
                $file_has_errors = true;
                $errors_found = true;
            }
        }
        
        if (!$file_has_errors) {
            echo "  ✅ OK - Aucune constante doublée\n";
        }
        
        echo "\n";
    } else {
        echo "⚠️  ATTENTION: Fichier $file non trouvé\n\n";
    }
}

echo str_repeat("=", 60) . "\n";
if ($errors_found) {
    echo "🚨 RÉSULTAT: Des erreurs de constantes doublées ont été détectées!\n";
} else {
    echo "✅ RÉSULTAT: Aucune constante doublée détectée\n";
}
echo str_repeat("=", 60) . "\n";
?>
