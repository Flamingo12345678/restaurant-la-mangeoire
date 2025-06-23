<?php
/**
 * Test de la page passer-commande avec panier simulé
 */

session_start();

// Simuler un panier avec des articles
$_SESSION['panier'] = [
    [
        'id' => 1,
        'name' => 'Pizza Margherita',
        'price' => 12.90,
        'quantity' => 1
    ],
    [
        'id' => 2, 
        'name' => 'Burger Classique',
        'price' => 15.50,
        'quantity' => 1
    ]
];

echo "=== TEST PAGE PASSER-COMMANDE ===\n\n";

// Inclure la page en capturant les erreurs
ob_start();
$error_occurred = false;

try {
    include 'passer-commande.php';
    $content = ob_get_contents();
    
    // Vérifier si la carte "Votre commande" est présente
    if (strpos($content, 'Votre commande') !== false) {
        echo "✅ Carte 'Votre commande' trouvée dans le HTML\n";
    } else {
        echo "❌ Carte 'Votre commande' manquante\n";
    }
    
    // Vérifier d'autres éléments importants
    $checks = [
        'order-summary' => 'Classe CSS order-summary',
        'Pizza Margherita' => 'Article du panier',
        'Burger Classique' => 'Article du panier',
        'Total:' => 'Section total',
        'btn-confirm' => 'Bouton de confirmation'
    ];
    
    foreach ($checks as $search => $description) {
        if (strpos($content, $search) !== false) {
            echo "✅ $description trouvé\n";
        } else {
            echo "❌ $description manquant\n";
        }
    }
    
} catch (Exception $e) {
    $error_occurred = true;
    echo "❌ Erreur lors du chargement de la page: " . $e->getMessage() . "\n";
} catch (ParseError $e) {
    $error_occurred = true;
    echo "❌ Erreur de syntaxe: " . $e->getMessage() . "\n";
} catch (Error $e) {
    $error_occurred = true;
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
}

ob_end_clean();

if (!$error_occurred) {
    echo "\n🎉 Page passer-commande.php fonctionne correctement !\n";
} else {
    echo "\n⚠️  Des problèmes ont été détectés.\n";
}

// Nettoyer la session
unset($_SESSION['panier']);
?>
