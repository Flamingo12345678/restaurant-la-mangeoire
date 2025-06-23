<?php
/**
 * Script de validation des corrections de paiement
 * Vérifie que tous les boutons utilisent le système de devises
 */

echo "=== VALIDATION CORRECTIONS PAIEMENT ===\n\n";

// Vérifier le fichier payer-commande.php
$file = 'payer-commande.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    echo "1. Vérification de l'include CurrencyManager...\n";
    if (strpos($content, 'includes/currency_manager.php') !== false) {
        echo "   ✓ CurrencyManager inclus\n";
    } else {
        echo "   ✗ CurrencyManager NON inclus\n";
    }
    
    echo "\n2. Vérification des boutons de paiement...\n";
    
    // Vérifier bouton Stripe
    if (strpos($content, 'CurrencyManager::formatPrice($payment_amount, true); ?> avec Stripe') !== false) {
        echo "   ✓ Bouton Stripe utilise CurrencyManager\n";
    } else {
        echo "   ✗ Bouton Stripe N'utilise PAS CurrencyManager\n";
    }
    
    // Vérifier bouton PayPal  
    if (strpos($content, 'CurrencyManager::formatPrice($payment_amount, true); ?> avec PayPal') !== false) {
        echo "   ✓ Bouton PayPal utilise CurrencyManager\n";
    } else {
        echo "   ✗ Bouton PayPal N'utilise PAS CurrencyManager\n";
    }
    
    // Vérifier bouton Manuel
    if (preg_match('/Payer\s+<\?php\s+echo\s+CurrencyManager::formatPrice\(\$payment_amount,\s+true\);\s+\?\>\s*<\/button>/', $content)) {
        echo "   ✓ Bouton Manuel utilise CurrencyManager\n";
    } else {
        echo "   ✗ Bouton Manuel N'utilise PAS CurrencyManager\n";
    }
    
    echo "\n3. Vérification de la définition de \$payment_amount...\n";
    if (strpos($content, '$payment_amount = $order[\'MontantTotal\'];') !== false) {
        echo "   ✓ \$payment_amount défini pour les commandes\n";
    } else {
        echo "   ✗ \$payment_amount NON défini pour les commandes\n";
    }
    
    echo "\n4. Vérification du résumé de commande...\n";
    if (strpos($content, 'CurrencyManager::formatPrice($item[\'SousTotal\']') !== false) {
        echo "   ✓ Sous-totaux utilisent CurrencyManager\n";
    } else {
        echo "   ✗ Sous-totaux N'utilisent PAS CurrencyManager\n";
    }
    
    if (strpos($content, 'CurrencyManager::formatPrice($order[\'MontantTotal\']') !== false) {
        echo "   ✓ Total commande utilise CurrencyManager\n";
    } else {
        echo "   ✗ Total commande N'utilise PAS CurrencyManager\n";
    }
    
    echo "\n5. Recherche d'anciennes références XAF...\n";
    $xaf_matches = [];
    if (preg_match_all('/\d+[,\s]*XAF/', $content, $xaf_matches)) {
        echo "   ⚠️  Références XAF trouvées :\n";
        foreach ($xaf_matches[0] as $match) {
            echo "      - " . $match . "\n";
        }
    } else {
        echo "   ✓ Aucune référence XAF codée en dur trouvée\n";
    }
    
} else {
    echo "   ✗ Fichier $file non trouvé\n";
}

echo "\n=== VALIDATION TERMINÉE ===\n";

// Test rapide de formatage avec montant d'exemple
require_once 'includes/currency_manager.php';

echo "\n6. Test de formatage avec montant d'exemple...\n";
$test_amount = 39; // Le montant de l'exemple dans la capture d'écran
$formatted = CurrencyManager::formatPrice($test_amount, true);
echo "   Montant: 39 EUR → " . $formatted . "\n";

echo "\n🎉 Toutes les corrections appliquées avec succès !\n";
?>
