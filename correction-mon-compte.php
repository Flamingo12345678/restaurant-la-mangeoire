<?php
echo "<h1>🔧 Correction de mon-compte.php</h1>\n";

$file = 'mon-compte.php';

if (!file_exists($file)) {
    echo "❌ Fichier non trouvé : $file<br>\n";
    exit;
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

$total_changes = 0;
foreach ($patterns as $pattern => $replacement) {
    $matches = [];
    preg_match_all($pattern, $content, $matches);
    $changes = count($matches[0]);
    if ($changes > 0) {
        $content = preg_replace($pattern, $replacement, $content);
        $total_changes += $changes;
        echo "✅ Remplacé $changes occurrences de '$pattern'<br>\n";
    }
}

// Écrire le fichier modifié si des changements ont été faits
if ($content !== $original_content) {
    if (file_put_contents($file, $content)) {
        echo "<strong>✅ $file : $total_changes corrections appliquées</strong><br>\n";
    } else {
        echo "❌ Erreur lors de l'écriture de $file<br>\n";
    }
} else {
    echo "ℹ️ $file : aucune correction nécessaire<br>\n";
}

// Vérification finale
echo "<h2>🔍 Vérification finale</h2>\n";

// Vérifier la syntaxe PHP
$output = shell_exec("php -l $file 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "✅ $file - Syntaxe OK<br>\n";
} else {
    echo "❌ $file - Erreurs de syntaxe<br>\n";
    echo "<pre>$output</pre>\n";
}

echo "<h2>📊 Résumé</h2>\n";
echo "<div style='background: #e8f5e8; padding: 15px; border-left: 5px solid #4CAF50; margin: 10px 0;'>\n";
echo "<strong>✅ Correction terminée !</strong><br>\n";
echo "Total corrections : <strong>$total_changes</strong><br>\n";
echo "</div>\n";

echo "<p>🎯 Le fichier mon-compte.php utilise maintenant \$pdo correctement.</p>\n";
?>
