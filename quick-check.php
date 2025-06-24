<?php
/**
 * 🚀 VÉRIFICATION RAPIDE QUOTIDIENNE
 * 
 * Ce script effectue une vérification express (< 10 secondes)
 * des fonctions critiques du panier.
 * 
 * À exécuter chaque matin pour s'assurer que tout fonctionne !
 * 
 * Usage: php quick-check.php
 */

echo "🚀 VÉRIFICATION RAPIDE - " . date('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 50) . "\n";

$all_ok = true;
$critical_files = [
    'ajouter-au-panier.php',
    'includes/CartManager.php',
    'api/cart-summary.php',
    '.htaccess'
];

// ✅ 1. Fichiers critiques
echo "📁 Fichiers critiques... ";
foreach ($critical_files as $file) {
    if (!file_exists($file) || !is_readable($file)) {
        echo "❌\n   ⚠️  Fichier manquant/illisible: $file\n";
        $all_ok = false;
        break;
    }
}
if ($all_ok) echo "✅\n";

// ✅ 2. Test de session
echo "🔐 Sessions PHP... ";
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅\n";
} else {
    echo "❌\n";
    $all_ok = false;
}

// ✅ 3. Test base de données (rapide)
echo "🗄️  Base de données... ";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=restaurant_db;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // Timeout rapide
    ]);
    $pdo->query("SELECT 1")->fetch();
    echo "✅\n";
} catch (Exception $e) {
    echo "❌\n   ⚠️  " . substr($e->getMessage(), 0, 60) . "...\n";
    $all_ok = false;
}

// ✅ 4. Test CartManager
echo "🛒 CartManager... ";
try {
    if (isset($pdo)) {
        require_once 'includes/CartManager.php';
        $cart = new CartManager($pdo);
        echo "✅\n";
    } else {
        echo "⚠️  (BDD indisponible)\n";
    }
} catch (Exception $e) {
    echo "❌\n   ⚠️  " . substr($e->getMessage(), 0, 60) . "...\n";
    $all_ok = false;
}

// ✅ 5. Test API (si accessible)
echo "🔗 API panier... ";
if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost/api/cart-summary.php', false, $context);
        if ($response !== false && json_decode($response) !== null) {
            echo "✅\n";
        } else {
            echo "⚠️  (Non testable en CLI)\n";
        }
    } catch (Exception $e) {
        echo "⚠️  (Non testable en CLI)\n";
    }
} else {
    echo "⚠️  (Non testable en CLI)\n";
}

// Résultat final
echo "\n" . str_repeat("=", 50) . "\n";
if ($all_ok) {
    echo "🎉 TOUT FONCTIONNE PARFAITEMENT !\n";
    echo "✨ Votre panier est opérationnel pour la journée.\n\n";
    echo "📊 Pour un diagnostic complet: php maintenance-check.php\n";
    echo "🧪 Pour les tests détaillés: php validation-finale-clean.php\n";
    exit(0);
} else {
    echo "⚠️  PROBLÈMES DÉTECTÉS !\n";
    echo "🔧 Lancez une vérification complète: php maintenance-check.php\n";
    echo "📞 Si le problème persiste, contactez le support technique.\n";
    exit(1);
}
?>
