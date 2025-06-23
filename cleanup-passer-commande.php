<?php
/**
 * Script de nettoyage pour supprimer tout le code de paiement de passer-commande.php
 */

echo "=== NETTOYAGE PASSER-COMMANDE.PHP ===\n";

$content = file_get_contents('passer-commande.php');

// Supprimer toutes les références aux méthodes de paiement
$patterns = [
    // Variables PHP
    '/\$selectedPayment[^;]*;/',
    '/\$recommendedPaymentMethods[^;]*;/',
    '/\$availablePaymentMethods[^;]*;/',
    '/foreach \(\$recommendedPaymentMethods[^}]*}\s*/',
    
    // HTML
    '/<ul class="nav nav-tabs"[^>]*>.*?<\/ul>/s',
    '/<div class="tab-content[^>]*>.*?<\/div>/s',
    '/<li class="nav-item"[^>]*>.*?<\/li>/s',
    '/<div class="tab-pane[^>]*>.*?<\/div>/s',
    
    // JavaScript
    '/function selectPaymentTab[^}]*}/s',
    '/function updatePaymentTotal[^}]*}/s',
    '/\/\/ Gestion des onglets de paiement[\s\S]*?(?=<\/script>)/',
    
    // CSS
    '/\/\* Styles pour les onglets de paiement \*\/[\s\S]*?(?=\/\*|<\/style>)/',
    
    // Commentaires
    '/<!-- Onglets de navigation -->/',
    '/<!-- Contenu des onglets -->/',
];

$replacements = array_fill(0, count($patterns), '');

// Appliquer les suppressions
$content = preg_replace($patterns, $replacements, $content);

// Nettoyer les lignes vides multiples
$content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

// Sauvegarder
file_put_contents('passer-commande.php', $content);

echo "✅ Nettoyage terminé\n";

// Vérifier le résultat
$newContent = file_get_contents('passer-commande.php');
if (strpos($newContent, 'mode_paiement') === false) {
    echo "✅ Toutes les références aux paiements supprimées\n";
} else {
    echo "⚠️  Certaines références aux paiements subsistent\n";
}

if (strpos($newContent, 'payment-tabs') === false) {
    echo "✅ Interface de paiement supprimée\n";
} else {
    echo "⚠️  Interface de paiement encore présente\n";
}

// Vérifier la syntaxe
$output = shell_exec('php -l passer-commande.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "✅ Syntaxe PHP correcte\n";
} else {
    echo "❌ Erreur de syntaxe: $output\n";
}

echo "\n=== NETTOYAGE TERMINÉ ===\n";
?>
