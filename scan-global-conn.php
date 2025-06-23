<?php
echo "<h1>🔍 Scan Global : Détection des fichiers avec \$conn</h1>\n";

// Obtenir tous les fichiers PHP du projet
$phpFiles = glob('*.php');

echo "<h2>📋 Analyse de " . count($phpFiles) . " fichiers PHP</h2>\n";

$files_with_conn = [];
$total_occurrences = 0;

foreach ($phpFiles as $file) {
    if (!file_exists($file)) {
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Compter les occurrences de $conn
    $count = preg_match_all('/\$conn(?:->|\s)/i', $content);
    
    if ($count > 0) {
        $files_with_conn[$file] = $count;
        $total_occurrences += $count;
    }
}

echo "<h2>📊 Résultats</h2>\n";

if (empty($files_with_conn)) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-left: 5px solid #4CAF50; margin: 10px 0;'>\n";
    echo "<h3>🎉 EXCELLENT !</h3>\n";
    echo "<p><strong>Aucun fichier ne contient plus de référence à \$conn</strong></p>\n";
    echo "<p>Tous vos fichiers utilisent maintenant \$pdo correctement !</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 5px solid #ffc107; margin: 10px 0;'>\n";
    echo "<h3>⚠️ Fichiers restants avec \$conn :</h3>\n";
    echo "<ul>\n";
    foreach ($files_with_conn as $file => $count) {
        echo "<li><strong>$file</strong> : $count occurrences</li>\n";
    }
    echo "</ul>\n";
    echo "<p><strong>Total : $total_occurrences occurrences</strong></p>\n";
    echo "</div>\n";
}

echo "<h2>🎯 Status du Projet</h2>\n";

// Vérifier les fichiers principaux
$main_files = [
    'panier.php' => 'Panier',
    'ajouter-au-panier.php' => 'Ajout au panier',
    'passer-commande.php' => 'Commande',
    'confirmation-commande.php' => 'Confirmation',
    'connexion-unifiee.php' => 'Connexion',
    'mon-compte.php' => 'Mon compte',
    'db_connexion.php' => 'Connexion DB'
];

echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>\n";
echo "<tr><th>Fichier</th><th>Description</th><th>Status \$pdo</th><th>Syntaxe</th></tr>\n";

foreach ($main_files as $file => $description) {
    if (file_exists($file)) {
        // Vérifier si le fichier contient $conn
        $content = file_get_contents($file);
        $has_conn = preg_match('/\$conn(?:->|\s)/i', $content) > 0;
        
        // Vérifier la syntaxe
        $syntax_check = shell_exec("php -l $file 2>&1");
        $syntax_ok = strpos($syntax_check, 'No syntax errors') !== false;
        
        $pdo_status = $has_conn ? "❌ \$conn" : "✅ \$pdo";
        $syntax_status = $syntax_ok ? "✅ OK" : "❌ Erreurs";
        
        echo "<tr><td>$file</td><td>$description</td><td>$pdo_status</td><td>$syntax_status</td></tr>\n";
    } else {
        echo "<tr><td>$file</td><td>$description</td><td>❓ Absent</td><td>❓ N/A</td></tr>\n";
    }
}

echo "</table>\n";

echo "<h2>🚀 Recommandations</h2>\n";

if (empty($files_with_conn)) {
    echo "<p><strong>🎊 Félicitations ! Votre projet est maintenant 100% cohérent !</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Tous les fichiers utilisent \$pdo</li>\n";
    echo "<li>✅ Connexion base de données unifiée</li>\n";
    echo "<li>✅ Plus d'erreurs 'Undefined variable \$conn'</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Votre site de restaurant est prêt à recevoir des commandes !</strong></p>\n";
} else {
    echo "<p>Il reste quelques fichiers à corriger :</p>\n";
    echo "<ul>\n";
    foreach ($files_with_conn as $file => $count) {
        echo "<li>Corriger <strong>$file</strong> ($count occurrences)</li>\n";
    }
    echo "</ul>\n";
}
?>
