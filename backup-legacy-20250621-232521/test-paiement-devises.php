<?php
/**
 * Test rapide pour vérifier le système de devises sur la page de paiement
 */
require_once 'includes/currency_manager.php';
require_once 'db_connexion.php';

echo "=== TEST PAIEMENT AVEC SYSTÈME DE DEVISES ===\n\n";

// 1. Test du CurrencyManager
echo "1. Test du gestionnaire de devises...\n";
$current_currency = CurrencyManager::getCurrentCurrency();
echo "   ✓ Devise détectée: " . $current_currency['code'] . " (" . $current_currency['name'] . ")\n";

// 2. Récupérer une commande de test depuis la DB
echo "\n2. Test avec données de commande réelles...\n";
try {
    $stmt = $pdo->prepare("SELECT CommandeID, MontantTotal FROM Commandes ORDER BY CommandeID DESC LIMIT 1");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "   ✓ Commande trouvée: #" . $order['CommandeID'] . "\n";
        echo "   ✓ Montant en EUR: " . number_format($order['MontantTotal'], 2) . " €\n";
        echo "   ✓ Montant formaté: " . CurrencyManager::formatPrice($order['MontantTotal'], true) . "\n";
        
        // Test boutons de paiement
        echo "\n3. Simulation boutons de paiement...\n";
        echo "   ✓ Bouton Stripe: 'Payer " . CurrencyManager::formatPrice($order['MontantTotal'], true) . " avec Stripe'\n";
        echo "   ✓ Bouton PayPal: 'Payer " . CurrencyManager::formatPrice($order['MontantTotal'], true) . " avec PayPal'\n";
        
    } else {
        echo "   ⚠ Aucune commande trouvée dans la base de données\n";
        
        // Test avec montant fictif
        echo "\n3. Test avec montant fictif (39.00 EUR)...\n";
        $test_amount = 39.00;
        echo "   ✓ Montant formaté: " . CurrencyManager::formatPrice($test_amount, true) . "\n";
        echo "   ✓ Bouton Stripe: 'Payer " . CurrencyManager::formatPrice($test_amount, true) . " avec Stripe'\n";
        echo "   ✓ Bouton PayPal: 'Payer " . CurrencyManager::formatPrice($test_amount, true) . " avec PayPal'\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erreur base de données: " . $e->getMessage() . "\n";
}

// 4. Test changement de devise
echo "\n4. Test changement de devise...\n";
$currencies_to_test = ['FR', 'US', 'CM', 'GB'];
$test_amount = 39.00;

foreach ($currencies_to_test as $country) {
    CurrencyManager::setCurrency($country);
    $formatted = CurrencyManager::formatPrice($test_amount, false);
    $currency_info = CurrencyManager::getCurrentCurrency();
    echo "   ✓ " . $country . " (" . $currency_info['code'] . "): " . $formatted . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
echo "✅ La page de paiement devrait maintenant afficher les bons montants !\n\n";

echo "💡 POUR TESTER :\n";
echo "1. Allez sur http://localhost:8000/passer-commande.php\n";
echo "2. Passez une commande test\n";
echo "3. Allez sur la page de paiement\n";
echo "4. Vérifiez que les boutons affichent le bon montant\n";
?>
