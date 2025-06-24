#!/usr/bin/env php
<?php
/**
 * 🎯 TEST ULTRA-RAPIDE (< 5 secondes)
 * 
 * Vérification express pour production
 * 
 * Usage: php ultra-check.php
 * Retourne: 0 = OK, 1 = Problème
 */

$start_time = microtime(true);

// Tests essentiels uniquement
$tests = [
    'ajouter-au-panier.php' => 'Ajout panier',
    'includes/CartManager.php' => 'Gestionnaire',
    'api/cart-summary.php' => 'API résumé',
    '.htaccess' => 'Config web'
];

$issues = 0;

foreach ($tests as $file => $name) {
    if (!file_exists($file)) {
        echo "❌ $name\n";
        $issues++;
    }
}

// Test DB ultra-rapide
try {
    $pdo = new PDO("mysql:host=localhost;dbname=restaurant_db", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    $pdo->query("SELECT 1");
} catch (Exception $e) {
    echo "❌ Base données\n";
    $issues++;
}

$end_time = microtime(true);
$duration = round(($end_time - $start_time) * 1000, 1);

if ($issues === 0) {
    echo "✅ OK ({$duration}ms)\n";
    exit(0);
} else {
    echo "⚠️  $issues problème(s) ({$duration}ms)\n";
    exit(1);
}
?>
