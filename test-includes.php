<?php
// Test minimal avec includes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Avant include common.php\n";
require_once 'includes/common.php';
echo "Test 2: Après include common.php\n";

require_once 'includes/currency_manager.php';
echo "Test 3: Après include currency_manager.php\n";

$current_currency = CurrencyManager::getCurrentCurrency();
echo "Test 4: Devise récupérée: " . print_r($current_currency, true) . "\n";

echo "Test terminé avec succès\n";
?>
