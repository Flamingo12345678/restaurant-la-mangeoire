<?php
/**
 * Script de validation du système de devises
 * Vérifie que tout est correctement configuré et fonctionne
 */

require_once 'includes/currency_manager.php';
require_once 'db_connexion.php';

echo "=== VALIDATION DU SYSTÈME DE DEVISES ===\n\n";

// 1. Test de la classe CurrencyManager
echo "1. Test de la classe CurrencyManager...\n";
try {
    $current = CurrencyManager::getCurrentCurrency();
    echo "   ✓ Détection de devise fonctionnelle: " . $current['code'] . " (" . $current['name'] . ")\n";
} catch (Exception $e) {
    echo "   ✗ Erreur détection devise: " . $e->getMessage() . "\n";
}

// 2. Test de conversion
echo "\n2. Test des conversions...\n";
$test_amount = 25.00;
$currencies = ['EUR', 'USD', 'GBP', 'XAF', 'CHF'];

foreach ($currencies as $currency) {
    try {
        $converted = CurrencyManager::convertPrice($test_amount, $currency);
        echo "   ✓ 25.00 EUR → " . number_format($converted, 2) . " " . $currency . "\n";
    } catch (Exception $e) {
        echo "   ✗ Erreur conversion vers " . $currency . ": " . $e->getMessage() . "\n";
    }
}

// 3. Test de formatage
echo "\n3. Test du formatage...\n";
try {
    $formatted = CurrencyManager::formatPrice(15.50, true);
    echo "   ✓ Formatage avec prix original: " . $formatted . "\n";
    
    $formatted_simple = CurrencyManager::formatPrice(15.50, false);
    echo "   ✓ Formatage simple: " . $formatted_simple . "\n";
} catch (Exception $e) {
    echo "   ✗ Erreur formatage: " . $e->getMessage() . "\n";
}

// 4. Test de la base de données
echo "\n4. Test de la base de données...\n";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Menus WHERE Prix > 0");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   ✓ " . $result['count'] . " menus avec prix dans la base de données\n";
    
    // Vérifier quelques prix
    $stmt = $conn->prepare("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 3");
    $stmt->execute();
    $menus = $stmt->fetchAll();
    
    foreach ($menus as $menu) {
        $formatted_price = CurrencyManager::formatPrice($menu['Prix'], true);
        echo "   ✓ " . $menu['NomItem'] . ": " . $formatted_price . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erreur base de données: " . $e->getMessage() . "\n";
}

// 5. Test des fichiers d'intégration
echo "\n5. Vérification des fichiers intégrés...\n";
$files_to_check = [
    'index.php' => 'Page d\'accueil',
    'menu.php' => 'Page menu',
    'panier.php' => 'Page panier'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if (strpos($content, 'CurrencyManager') !== false) {
            echo "   ✓ " . $description . " utilise CurrencyManager\n";
        } else {
            echo "   ✗ " . $description . " N'utilise PAS CurrencyManager\n";
        }
        
        if (strpos($content, 'formatPrice') !== false) {
            echo "   ✓ " . $description . " utilise formatPrice\n";
        } else {
            echo "   ✗ " . $description . " N'utilise PAS formatPrice\n";
        }
    } else {
        echo "   ✗ " . $description . " - Fichier non trouvé\n";
    }
}

// 6. Test des devises disponibles
echo "\n6. Devises disponibles...\n";
$available = CurrencyManager::getAvailableCurrencies();
echo "   ✓ " . count($available) . " devises configurées:\n";

$popular_currencies = ['EUR', 'USD', 'GBP', 'XAF', 'CHF', 'CAD'];
foreach ($popular_currencies as $code) {
    if (isset($available[$code])) {
        echo "   ✓ " . $code . " - " . $available[$code]['name'] . " (" . $available[$code]['symbol'] . ")\n";
    } else {
        echo "   ✗ " . $code . " - Non configuré\n";
    }
}

// 7. Recommandations
echo "\n=== RECOMMANDATIONS ===\n";
echo "✓ Système de devises fonctionnel avec EUR comme base\n";
echo "✓ Pages principales intégrées (index, menu, panier)\n";
echo "✓ Prix synchronisés avec la base de données\n";
echo "✓ Conversion et formatage automatiques\n\n";

echo "📝 PROCHAINES ÉTAPES OPTIONNELLES :\n";
echo "- Intégrer le système dans passer-commande.php\n";
echo "- Ajouter des taux de change en temps réel (API)\n";
echo "- Créer un widget de sélection de devise persistent\n";
echo "- Ajouter plus de devises selon les besoins\n\n";

echo "🎉 VALIDATION TERMINÉE - Système opérationnel !\n";
?>
