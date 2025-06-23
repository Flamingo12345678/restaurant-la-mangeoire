<?php
echo "🔍 Diagnostic des pages admin avec problèmes de structure\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$pages_to_check = [
    'admin/menus.php' => 'Menus',
    'admin/commandes.php' => 'Commandes',
    'admin/tables.php' => 'Tables',
    'admin/administrateurs.php' => 'Administrateurs',
    'admin/activity_log.php' => 'Logs d\'activité'
];

foreach ($pages_to_check as $file => $name) {
    echo "📄 Vérification de $name ($file)\n";
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Vérifier si la page a sa propre structure HTML
        $has_own_doctype = strpos($content, '<!DOCTYPE html>') !== false;
        $has_own_head = strpos($content, '<head>') !== false;
        $has_own_body = strpos($content, '<body>') !== false;
        $includes_header_template = strpos($content, 'header_template.php') !== false;
        
        echo "  - Structure HTML propre: " . ($has_own_doctype ? "❌ OUI (problème)" : "✅ NON") . "\n";
        echo "  - Balise <head> propre: " . ($has_own_head ? "❌ OUI (problème)" : "✅ NON") . "\n";
        echo "  - Balise <body> propre: " . ($has_own_body ? "❌ OUI (problème)" : "✅ NON") . "\n";
        echo "  - Inclut header_template: " . ($includes_header_template ? "✅ OUI" : "❌ NON") . "\n";
        
        if ($has_own_doctype || $has_own_head || $has_own_body) {
            echo "  🚨 PROBLÈME DÉTECTÉ: Cette page doit être corrigée !\n";
        } else {
            echo "  ✅ Structure correcte\n";
        }
    } else {
        echo "  ❌ Fichier non trouvé\n";
    }
    
    echo "\n";
}

echo "\n🔧 Correction requise pour les pages avec problèmes de structure:\n";
echo "1. Supprimer la structure HTML propre (<!DOCTYPE>, <head>, <body>)\n";
echo "2. Utiliser uniquement le système de templates\n";
echo "3. Définir \$page_title avant d'inclure header_template.php\n";
echo "4. Utiliser require_once au lieu d'include\n\n";

echo "🧪 Test du menu burger après corrections:\n";
echo "- Ouvrir une page admin en mode mobile\n";
echo "- Cliquer sur le bouton burger ☰\n";
echo "- Vérifier que la sidebar s'ouvre\n";
echo "- Vérifier que les liens fonctionnent\n";
?>
