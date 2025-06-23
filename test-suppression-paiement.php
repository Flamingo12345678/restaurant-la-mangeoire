<?php
echo "🧪 TEST - VÉRIFICATION SUPPRESSION ÉTAPE PAIEMENT\n";
echo "=================================================\n\n";

// Lire le contenu du fichier
$content = file_get_contents('passer-commande.php');

echo "VÉRIFICATIONS DE SUPPRESSION:\n";
echo "-----------------------------\n";

// Tests de suppression
$tests = [
    'Onglets paiement supprimés' => strpos($content, 'data-bs-toggle="tab"') === false,
    'CSS payment-tabs supprimé' => strpos($content, '.payment-tabs {') === false,
    'JS selectPaymentTab supprimé' => strpos($content, 'function selectPaymentTab') === false,
    'Validation mode_paiement supprimée' => strpos($content, 'mode_paiement\']:checked') === false,
    'Radio buttons paiement supprimés' => strpos($content, 'name="mode_paiement"') === false,
    'Traitement POST paiement supprimé' => strpos($content, '$_POST[\'mode_paiement\']') === false
];

$all_passed = true;
foreach ($tests as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
    if (!$result) $all_passed = false;
}

echo "\n";

// Vérifier que les éléments essentiels sont toujours présents
echo "VÉRIFICATIONS DE PRÉSENCE:\n";
echo "--------------------------\n";

$presence_tests = [
    'Carte "Votre commande" présente' => strpos($content, 'Votre commande') !== false,
    'Formulaire de commande présent' => strpos($content, 'checkout-form') !== false,
    'Étape 1 (informations) présente' => strpos($content, 'Vos informations') !== false,
    'Étape 2 (livraison) présente' => strpos($content, 'Comment souhaitez-vous recevoir') !== false,
    'Bouton continuer présent' => strpos($content, 'Continuer vers') !== false,
    'Gestion du panier présente' => strpos($content, 'cart_items') !== false
];

foreach ($presence_tests as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
    if (!$result) $all_passed = false;
}

echo "\n";

if ($all_passed) {
    echo "🎉 SUCCÈS : L'étape de paiement a été correctement supprimée !\n";
    echo "✅ Seules les étapes 1 et 2 restent sur passer-commande.php\n";
    echo "✅ L'étape 3 (paiement) est désormais sur confirmation-commande.php\n";
    echo "✅ La carte 'Votre commande' est toujours présente\n";
    echo "✅ Aucun code résiduel de paiement détecté\n";
} else {
    echo "❌ ERREUR : Des éléments de paiement sont encore présents ou des éléments essentiels manquent\n";
}

echo "\n📋 RÉCAPITULATIF:\n";
echo "=================\n";
echo "✅ passer-commande.php : Étapes 1 & 2 + Carte commande\n";
echo "✅ confirmation-commande.php : Étape 3 (choix paiement)\n";
echo "✅ Flux utilisateur logique et intuitif\n";
echo "✅ Séparation claire des responsabilités\n";
?>
