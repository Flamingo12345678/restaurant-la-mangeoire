<?php
echo "<h1>🔧 Correction Globale : $pdo → $pdo</h1>\n";

// Liste des fichiers PHP à traiter
$files_to_process = [
    'panier.php',
    'ajouter-au-panier.php',
    'clients.php',
    'auth_process.php',
    'add_password_field.php',
    'admin-messages-new.php',
    'admin-messages-fixed.php'
];

$corrections_made = 0;
$files_processed = 0;

foreach ($files_to_process as $file) {
    if (!file_exists($file)) {
        echo "⚠️ Fichier non trouvé : $file<br>\n";
        continue;
    }
    
    echo "<h3>📁 Traitement de : $file</h3>\n";
    
    // Lire le contenu du fichier
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Remplacer toutes les occurrences de $pdo par $pdo
    $patterns = [
        '/\$pdo->prepare\(/i' => '$pdo->prepare(',
        '/\$pdo->query\(/i' => '$pdo->query(',
        '/\$pdo->exec\(/i' => '$pdo->exec(',
        '/\$pdo->beginTransaction\(/i' => '$pdo->beginTransaction(',
        '/\$pdo->commit\(/i' => '$pdo->commit(',
        '/\$pdo->rollBack\(/i' => '$pdo->rollBack(',
        '/\$pdo->lastInsertId\(/i' => '$pdo->lastInsertId(',
        '/\$pdo->getAttribute\(/i' => '$pdo->getAttribute(',
        '/\$pdo->setAttribute\(/i' => '$pdo->setAttribute(',
        '/\$pdo->errorCode\(/i' => '$pdo->errorCode(',
        '/\$pdo->errorInfo\(/i' => '$pdo->errorInfo(',
        '/\$pdo->inTransaction\(/i' => '$pdo->inTransaction('
    ];
    
    $file_changes = 0;
    foreach ($patterns as $pattern => $replacement) {
        $new_content = preg_replace($pattern, $replacement, $content);
        if ($new_content !== $content) {
            $changes = preg_match_all($pattern, $content);
            $file_changes += $changes;
            $content = $new_content;
        }
    }
    
    // Écrire le fichier modifié si des changements ont été faits
    if ($content !== $original_content) {
        if (file_put_contents($file, $content)) {
            echo "✅ $file : $file_changes corrections appliquées<br>\n";
            $corrections_made += $file_changes;
            $files_processed++;
        } else {
            echo "❌ Erreur lors de l'écriture de $file<br>\n";
        }
    } else {
        echo "ℹ️ $file : aucune correction nécessaire<br>\n";
    }
}

echo "<h2>📊 Résumé</h2>\n";
echo "<div style='background: #e8f5e8; padding: 15px; border-left: 5px solid #4CAF50; margin: 10px 0;'>\n";
echo "<strong>✅ Correction terminée !</strong><br>\n";
echo "Fichiers traités : <strong>$files_processed</strong><br>\n";
echo "Total corrections : <strong>$corrections_made</strong><br>\n";
echo "</div>\n";

// Vérification finale
echo "<h2>🔍 Vérification finale</h2>\n";

foreach ($files_to_process as $file) {
    if (file_exists($file)) {
        // Vérifier la syntaxe PHP
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $file - Syntaxe OK<br>\n";
        } else {
            echo "❌ $file - Erreurs de syntaxe<br>\n";
            echo "<pre>$output</pre>\n";
        }
    }
}

echo "<h3>🎯 Prochaines étapes</h3>\n";
echo "<p>Tous les fichiers principaux ont été corrigés. Votre système utilise maintenant \$pdo de manière cohérente.</p>\n";
?>
