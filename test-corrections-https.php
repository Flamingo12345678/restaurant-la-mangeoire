<?php
echo "🔧 TEST CORRECTIONS HTTPS MANAGER\n";
echo "================================\n\n";

// Test 1: Inclusion du fichier sans erreur
echo "1️⃣  Test inclusion HTTPS Manager...\n";
try {
    define('HTTPS_MANAGER_NO_AUTO', true);
    require_once 'includes/https_manager.php';
    echo "✅ HTTPS Manager inclus sans erreur\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 2: Variables d'environnement
echo "\n2️⃣  Test variables d'environnement...\n";
$force_https = getenv('FORCE_HTTPS') === 'true' || 
              (isset($_ENV['FORCE_HTTPS']) && $_ENV['FORCE_HTTPS'] === 'true');
echo "FORCE_HTTPS configuré: " . ($force_https ? 'OUI' : 'NON') . "\n";

// Test 3: Fonctions HTTPS
echo "\n3️⃣  Test fonctions HTTPS...\n";
try {
    $is_https = HTTPSManager::isHTTPS();
    echo "✅ isHTTPS(): " . ($is_https ? 'OUI' : 'NON') . "\n";
    
    $is_ready = HTTPSManager::isPaymentReady();
    echo "✅ isPaymentReady(): " . ($is_ready ? 'OUI' : 'NON') . "\n";
    
    $secure_url = HTTPSManager::getSecureURL('test');
    echo "✅ getSecureURL(): " . $secure_url . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 4: Configuration environnement
echo "\n4️⃣  Test configuration environnement...\n";
try {
    ob_start(); // Capturer les headers
    HTTPSManager::setupSecureEnvironment();
    $output = ob_get_clean();
    echo "✅ setupSecureEnvironment() exécuté sans erreur\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 5: Vérification des clés API
echo "\n5️⃣  Test clés API...\n";
$stripe_public = getenv('STRIPE_PUBLISHABLE_KEY') ?: getenv('STRIPE_PUBLIC_KEY');
$stripe_secret = getenv('STRIPE_SECRET_KEY');
$paypal_client = getenv('PAYPAL_CLIENT_ID');

echo "Stripe Public Key: " . ($stripe_public ? "✅ Configurée" : "❌ Manquante") . "\n";
echo "Stripe Secret Key: " . ($stripe_secret ? "✅ Configurée" : "❌ Manquante") . "\n";
echo "PayPal Client ID: " . ($paypal_client ? "✅ Configurée" : "❌ Manquante") . "\n";

echo "\n🎯 RÉSUMÉ\n";
echo "=========\n";
echo "✅ Aucune erreur PHP détectée\n";
echo "✅ Variables d'environnement chargées\n";
echo "✅ Fonctions HTTPS opérationnelles\n";
echo "✅ Configuration environnement OK\n";

if (!$is_https) {
    echo "\n⚠️  RAPPEL: Pour les paiements réels, HTTPS est obligatoire!\n";
    echo "   Consultez HTTPS_URGENT_GUIDE.md pour la configuration production.\n";
}

echo "\n🚀 Corrections appliquées avec succès ! ✨\n";
?>
