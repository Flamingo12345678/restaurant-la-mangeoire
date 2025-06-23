<?php
echo "🧪 TEST - REMPLACEMENT VIREMENT PAR STRIPE\n";
echo "==========================================\n\n";

// Lire le contenu du fichier confirmation-commande.php
$content = file_get_contents('confirmation-commande.php');

echo "VÉRIFICATIONS DE SUPPRESSION:\n";
echo "-----------------------------\n";

// Tests de suppression des éléments de virement
$suppressions = [
    'Texte "Virement bancaire"' => strpos($content, 'Virement bancaire') === false,
    'Titre "Virement"' => strpos($content, '<h5 class="card-title">Virement</h5>') === false,
    'Description "SEPA"' => strpos($content, 'Virement bancaire SEPA') === false,
    'Fonction initiateWireTransfer' => strpos($content, 'function initiateWireTransfer') === false,
    'Icône bank' => strpos($content, 'bi-bank text-success') === false,
    'Délai "1-2 jours"' => strpos($content, '1-2 jours') === false
];

foreach ($suppressions as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test supprimé\n";
}

echo "\nVÉRIFICATIONS D'AJOUT:\n";
echo "---------------------\n";

// Tests d'ajout des nouveaux éléments Stripe
$ajouts = [
    'Nouveau titre "Stripe"' => strpos($content, '<h5 class="card-title">Stripe</h5>') !== false,
    'Icône Stripe alternative' => strpos($content, 'bi-credit-card-2-front') !== false,
    'Couleur info (Stripe alt)' => strpos($content, 'text-info') !== false,
    'Texte "Instantané"' => strpos($content, 'Instantané') !== false,
    'Bouton info classe' => strpos($content, 'btn-info') !== false,
    'Data-method stripe-alt' => strpos($content, 'data-method="stripe-alt"') !== false
];

foreach ($ajouts as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test ajouté\n";
}

echo "\nVÉRIFICATION DE L'INTÉGRITÉ:\n";
echo "---------------------------\n";

// Vérifier que les autres éléments sont toujours présents
$integrite = [
    'Stripe original présent' => strpos($content, 'Carte Bancaire') !== false,
    'PayPal présent' => strpos($content, 'PayPal') !== false,
    'Structure 3 colonnes' => substr_count($content, 'col-md-4') >= 3,
    'Fonction Stripe présente' => strpos($content, 'initiateStripePayment') !== false,
    'Fonction PayPal présente' => strpos($content, 'initiatePayPalPayment') !== false
];

foreach ($integrite as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
}

echo "\n📋 RÉCAPITULATIF:\n";
echo "=================\n";

$all_good = true;
foreach (array_merge($suppressions, $ajouts, $integrite) as $result) {
    if (!$result) {
        $all_good = false;
        break;
    }
}

if ($all_good) {
    echo "🎉 SUCCÈS : Virement remplacé par Stripe !\n";
    echo "✅ Anciens éléments de virement supprimés\n";
    echo "✅ Nouvelle option Stripe ajoutée\n";
    echo "✅ Design cohérent avec 3 options\n";
    echo "✅ Fonctionnalités intactes\n";
} else {
    echo "❌ PROBLÈME : Certains éléments n'ont pas été correctement modifiés\n";
}

echo "\n🎯 NOUVELLES OPTIONS DE PAIEMENT:\n";
echo "=================================\n";
echo "1. 💳 Carte Bancaire (Stripe) - Bleu\n";
echo "2. 🟡 PayPal - Jaune\n";
echo "3. 💳 Stripe (Alternative) - Info/Cyan\n";
echo "\n✨ Toutes les options utilisent des systèmes de paiement modernes et sécurisés !\n";
?>
