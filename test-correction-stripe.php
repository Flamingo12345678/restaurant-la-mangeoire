<?php
/**
 * Test de correction Stripe - Problème PaymentIntent
 * Vérifie que la configuration automatic_payment_methods est correcte
 */

echo "🔧 TEST - CORRECTION ERREUR STRIPE PAYMENTINTENT\n";
echo "================================================\n\n";

// Vérifier les fichiers de PaymentManager
$files_to_check = [
    'includes/payment_manager.php',
    'includes/payment_manager_complete.php'
];

$all_fixed = true;
$issues_fixed = 0;

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        echo "❌ Fichier manquant: $file\n";
        $all_fixed = false;
        continue;
    }
    
    $content = file_get_contents($file);
    echo "📁 Vérification: $file\n";
    
    // Vérifier la présence de automatic_payment_methods
    $has_automatic_methods = strpos($content, 'automatic_payment_methods') !== false;
    $has_allow_redirects = strpos($content, 'allow_redirects') !== false;
    $has_never_setting = strpos($content, "'allow_redirects' => 'never'") !== false;
    
    echo "  ✓ automatic_payment_methods: " . ($has_automatic_methods ? "✅ OUI" : "❌ NON") . "\n";
    echo "  ✓ allow_redirects: " . ($has_allow_redirects ? "✅ OUI" : "❌ NON") . "\n";
    echo "  ✓ Configuration 'never': " . ($has_never_setting ? "✅ OUI" : "❌ NON") . "\n";
    
    if ($has_automatic_methods && $has_allow_redirects && $has_never_setting) {
        echo "  🎉 Correction appliquée avec succès!\n";
        $issues_fixed++;
    } else {
        echo "  ❌ Correction incomplète\n";
        $all_fixed = false;
    }
    
    echo "\n";
}

// Vérifier la structure PaymentIntent::create
echo "🔍 ANALYSE DE LA STRUCTURE PAYMENTINTENT\n";
echo "========================================\n";

$sample_file = 'includes/payment_manager.php';
if (file_exists($sample_file)) {
    $content = file_get_contents($sample_file);
    
    // Extraire la section PaymentIntent::create
    $pattern = '/PaymentIntent::create\(\[(.*?)\]\);/s';
    if (preg_match($pattern, $content, $matches)) {
        echo "📋 Configuration PaymentIntent trouvée:\n";
        echo "```php\n";
        echo "PaymentIntent::create([\n" . $matches[1] . "]);\n";
        echo "```\n\n";
        
        // Vérifier les paramètres spécifiques
        $config_checks = [
            'amount' => strpos($matches[1], "'amount'") !== false,
            'currency' => strpos($matches[1], "'currency'") !== false,
            'automatic_payment_methods' => strpos($matches[1], "'automatic_payment_methods'") !== false,
            'allow_redirects_never' => strpos($matches[1], "'allow_redirects' => 'never'") !== false,
            'metadata' => strpos($matches[1], "'metadata'") !== false
        ];
        
        echo "🔧 Paramètres de configuration:\n";
        foreach ($config_checks as $param => $exists) {
            echo "  • $param: " . ($exists ? "✅" : "❌") . "\n";
        }
        
        echo "\n";
    }
}

// Résumé final
echo "📊 RÉSUMÉ DE LA CORRECTION\n";
echo "==========================\n";

if ($all_fixed) {
    echo "🎉 SUCCÈS: Erreur Stripe corrigée!\n";
    echo "✅ $issues_fixed fichiers corrigés\n";
    echo "✅ Configuration automatic_payment_methods ajoutée\n";
    echo "✅ allow_redirects défini à 'never'\n\n";
    
    echo "🔧 SOLUTION APPLIQUÉE:\n";
    echo "La configuration suivante a été ajoutée au PaymentIntent:\n";
    echo "```php\n";
    echo "'automatic_payment_methods' => [\n";
    echo "    'enabled' => true,\n";
    echo "    'allow_redirects' => 'never'\n";
    echo "]\n";
    echo "```\n\n";
    
    echo "📝 EXPLICATION:\n";
    echo "Cette configuration désactive les méthodes de paiement qui nécessitent\n";
    echo "une redirection (comme certains portefeuilles électroniques européens)\n";
    echo "et garde uniquement les cartes bancaires directes.\n\n";
    
    echo "🚀 RÉSULTAT:\n";
    echo "Plus besoin de 'return_url' car aucune méthode de paiement\n";
    echo "ne redirigera l'utilisateur hors de votre site.\n";
    
} else {
    echo "❌ PROBLÈME: Correction incomplète\n";
    echo "Certains fichiers n'ont pas été corrigés correctement.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test terminé - " . date('Y-m-d H:i:s') . "\n";
?>
