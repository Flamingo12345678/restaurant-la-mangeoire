<?php
/**
 * Script de diagnostic pour l'affichage des cartes de statistiques
 * Vérifie la structure HTML et les styles appliqués
 */

echo "🔍 DIAGNOSTIC CARTES DE STATISTIQUES\n";
echo "====================================\n\n";

// Pages avec cartes de stats
$pages_avec_stats = [
    'admin-messages.php' => 'Messages Admin',
    'dashboard-admin.php' => 'Dashboard Principal',
    'admin/administrateurs.php' => 'Administrateurs',
    'admin/menus.php' => 'Menus',
    'admin/commandes.php' => 'Commandes',
    'admin/tables.php' => 'Tables',
    'employes.php' => 'Employés'
];

echo "📋 1. VÉRIFICATION STRUCTURE HTML\n";
echo "---------------------------------\n";

foreach ($pages_avec_stats as $page => $nom) {
    if (!file_exists($page)) {
        echo "❌ $nom ($page) - Fichier introuvable\n";
        continue;
    }
    
    $content = file_get_contents($page);
    echo "📄 $nom ($page) :\n";
    
    // Vérifier la structure des cartes
    $row_g4_count = preg_match_all('/class=["\'][^"\']*row[^"\']*g-4[^"\']*["\']/', $content);
    $col_md3_count = preg_match_all('/class=["\'][^"\']*col-md-3[^"\']*["\']/', $content);
    $stats_card_count = preg_match_all('/class=["\'][^"\']*stats-card[^"\']*["\']/', $content);
    
    echo "  🏗️  Structure :\n";
    echo "    - Rows avec g-4 : $row_g4_count\n";
    echo "    - Colonnes col-md-3 : $col_md3_count\n";
    echo "    - Cartes stats-card : $stats_card_count\n";
    
    // Vérifier la cohérence
    if ($col_md3_count === 4 && $row_g4_count >= 1) {
        echo "  ✅ Structure correcte (4 cartes en ligne)\n";
    } elseif ($col_md3_count > 0) {
        echo "  ⚠️  Structure partielle ($col_md3_count cartes)\n";
    } else {
        echo "  ❌ Pas de structure de cartes détectée\n";
    }
    
    // Vérifier les classes spécifiques
    $has_admin_messages_class = strpos($content, 'admin-messages') !== false;
    if ($page === 'admin-messages.php' && $has_admin_messages_class) {
        echo "  ✅ Classe admin-messages présente\n";
    } elseif ($page === 'admin-messages.php') {
        echo "  ❌ Classe admin-messages manquante\n";
    }
    
    echo "\n";
}

echo "🎨 2. VÉRIFICATION CSS\n";
echo "----------------------\n";

$css_file = 'assets/css/admin-responsive.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    
    // Vérifier les styles de cartes
    $stats_card_rules = preg_match_all('/\.stats-card[^{]*{[^}]*}/', $css_content);
    $row_g4_rules = preg_match_all('/\.row\.g-4[^{]*{[^}]*}/', $css_content);
    $col_md3_rules = preg_match_all('/\.col-md-3[^{]*{[^}]*}/', $css_content);
    $admin_messages_rules = preg_match_all('/\.admin-messages[^{]*{[^}]*}/', $css_content);
    
    echo "📄 $css_file :\n";
    echo "  🎨 Règles CSS :\n";
    echo "    - .stats-card : $stats_card_rules règles\n";
    echo "    - .row.g-4 : $row_g4_rules règles\n";
    echo "    - .col-md-3 : $col_md3_rules règles\n";
    echo "    - .admin-messages : $admin_messages_rules règles\n";
    
    // Vérifier les media queries importantes
    $mobile_queries = preg_match_all('/@media[^{]*max-width:\s*768px[^{]*{/', $css_content);
    $small_mobile_queries = preg_match_all('/@media[^{]*max-width:\s*480px[^{]*{/', $css_content);
    
    echo "  📱 Media Queries :\n";
    echo "    - Mobile (768px) : $mobile_queries\n";
    echo "    - Petit mobile (480px) : $small_mobile_queries\n";
    
    // Vérifier les propriétés flex importantes
    $flex_properties = substr_count($css_content, 'flex:');
    $flex_wrap_properties = substr_count($css_content, 'flex-wrap');
    $gap_properties = substr_count($css_content, 'gap:');
    
    echo "  ⚡ Propriétés Flexbox :\n";
    echo "    - flex : $flex_properties occurrences\n";
    echo "    - flex-wrap : $flex_wrap_properties occurrences\n";
    echo "    - gap : $gap_properties occurrences\n";
} else {
    echo "❌ Fichier CSS introuvable : $css_file\n";
}

echo "\n🧪 3. TESTS DE RESPONSIVITÉ\n";
echo "---------------------------\n";

// Simuler différentes tailles d'écran
$test_sizes = [
    'Desktop' => '≥ 992px',
    'Tablette' => '768px - 991px', 
    'Mobile' => '481px - 767px',
    'Petit Mobile' => '≤ 480px'
];

echo "📱 Tailles d'écran à tester :\n";
foreach ($test_sizes as $device => $size) {
    echo "  - $device : $size\n";
}

echo "\n💡 POINTS DE CONTRÔLE :\n";
echo "1. ✅ Les 4 cartes restent en ligne horizontale sur mobile\n";
echo "2. ✅ Pas de débordement horizontal (overflow-x)\n";
echo "3. ✅ Texte lisible sur toutes les tailles\n";
echo "4. ✅ Icônes et chiffres bien proportionnés\n";
echo "5. ✅ Espacement adaptatif selon la taille\n";

echo "\n🔧 SOLUTIONS SI PROBLÈME :\n";
echo "1. Vérifier que la classe 'admin-messages' englobe les cartes\n";
echo "2. S'assurer que le CSS admin-responsive.css est bien chargé\n";
echo "3. Contrôler l'ordre de chargement des CSS (Bootstrap puis custom)\n";
echo "4. Tester avec les outils développeur du navigateur\n";
echo "5. Vérifier qu'il n'y a pas de conflits CSS\n";

echo "\n🌐 POUR TESTER MANUELLEMENT :\n";
echo "1. Ouvrir admin-messages.php dans le navigateur\n";
echo "2. Redimensionner la fenêtre ou utiliser le mode responsive\n";
echo "3. Vérifier que les cartes restent alignées horizontalement\n";
echo "4. Contrôler qu'aucun scroll horizontal n'apparaît\n";

echo "\n✅ Diagnostic terminé !\n";
?>
