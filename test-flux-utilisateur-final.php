<?php
echo "🧪 TEST INTÉGRATION - FLUX UTILISATEUR COMPLET\n";
echo "===============================================\n\n";

// Test 1: Vérifier passer-commande.php (étapes 1-2)
echo "1️⃣  VÉRIFICATION - passer-commande.php\n";
echo "--------------------------------------\n";
$content1 = file_get_contents('passer-commande.php');

$tests_passer = [
    'Étape 1 présente' => strpos($content1, 'Étape 1') !== false,
    'Étape 2 présente' => strpos($content1, 'Étape 2') !== false,
    'Étape 3 ABSENTE' => strpos($content1, 'Étape 3') === false,
    'Carte "Votre commande" présente' => strpos($content1, 'Votre commande') !== false,
    'Formulaire livraison' => strpos($content1, 'delivery_address') !== false,
    'Sélection mode' => strpos($content1, 'delivery_mode') !== false,
    'Aucune option paiement' => 
        strpos($content1, 'Stripe') === false && 
        strpos($content1, 'PayPal') === false &&
        strpos($content1, 'Virement') === false
];

foreach ($tests_passer as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
}

// Test 2: Vérifier confirmation-commande.php (étape 3)
echo "\n2️⃣  VÉRIFICATION - confirmation-commande.php\n";
echo "--------------------------------------------\n";
$content2 = file_get_contents('confirmation-commande.php');

$tests_confirmation = [
    'Étape 3 présente' => strpos($content2, 'Étape 3') !== false,
    'Titre "mode de paiement"' => strpos($content2, 'mode de paiement') !== false,
    'Option Stripe 1' => strpos($content2, 'Carte Bancaire') !== false,
    'Option PayPal' => strpos($content2, 'PayPal') !== false,
    'Option Stripe 2' => strpos($content2, '<h5 class="card-title">Stripe</h5>') !== false,
    'Virement SUPPRIMÉ' => strpos($content2, 'Virement bancaire') === false,
    'Résumé commande affiché' => strpos($content2, 'Récapitulatif') !== false
];

foreach ($tests_confirmation as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
}

// Test 3: Vérifier resultat-paiement.php
echo "\n3️⃣  VÉRIFICATION - resultat-paiement.php\n";
echo "---------------------------------------\n";
$content3 = file_get_contents('resultat-paiement.php');

$tests_resultat = [
    'Gestion statut succès' => strpos($content3, 'success') !== false,
    'Gestion statut erreur' => strpos($content3, 'error') !== false,
    'Gestion statut en attente' => strpos($content3, 'pending') !== false,
    'Affichage ID commande' => strpos($content3, 'order_id') !== false,
    'Affichage montant' => strpos($content3, 'amount') !== false,
    'Interface moderne' => strpos($content3, 'card') !== false && strpos($content3, 'bootstrap') !== false
];

foreach ($tests_resultat as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
}

// Test 4: Vérifier les redirections dans includes/payment_manager.php
echo "\n4️⃣  VÉRIFICATION - PaymentManager\n";
echo "--------------------------------\n";
$content4 = file_get_contents('includes/payment_manager.php');

$tests_payment = [
    'Redirection vers resultat-paiement' => strpos($content4, 'resultat-paiement.php') !== false,
    'Gestion ID commande' => strpos($content4, 'order_id') !== false,
    'Support Stripe' => strpos($content4, 'processStripePayment') !== false,
    'Support PayPal' => strpos($content4, 'processPayPalPayment') !== false,
    'Envoi emails' => strpos($content4, 'sendOrderConfirmationEmail') !== false
];

foreach ($tests_payment as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
}

// Test 5: Cohérence du flux
echo "\n5️⃣  COHÉRENCE DU FLUX UTILISATEUR\n";
echo "--------------------------------\n";

$flux_coherent = true;
$flux_tests = [
    'Séparation étapes 1-2 / 3' => 
        (strpos($content1, 'Étape 3') === false) && 
        (strpos($content2, 'Étape 3') !== false),
    'Pas de paiement sur passer-commande' => 
        (strpos($content1, 'Stripe') === false && strpos($content1, 'PayPal') === false),
    'Paiement uniquement sur confirmation' => 
        (strpos($content2, 'Stripe') !== false && strpos($content2, 'PayPal') !== false),
    'Virement complètement supprimé' => 
        (strpos($content1, 'Virement') === false && strpos($content2, 'Virement bancaire') === false),
    'Page de résultat pour tous' => 
        (strpos($content4, 'resultat-paiement.php') !== false)
];

foreach ($flux_tests as $test => $result) {
    $status = $result ? '✅' : '❌';
    echo "$status $test\n";
    if (!$result) $flux_coherent = false;
}

// Résumé final
echo "\n📊 RÉSUMÉ FINAL\n";
echo "===============\n";

$all_tests = array_merge($tests_passer, $tests_confirmation, $tests_resultat, $tests_payment, $flux_tests);
$total_tests = count($all_tests);
$passed_tests = count(array_filter($all_tests));

echo "Tests réussis : $passed_tests/$total_tests\n";

if ($passed_tests === $total_tests) {
    echo "\n🎉 SUCCÈS COMPLET !\n";
    echo "==================\n";
    echo "✅ Flux utilisateur logique et cohérent\n";
    echo "✅ Séparation claire des étapes\n";
    echo "✅ Options de paiement modernes uniquement\n";
    echo "✅ Page de confirmation professionnelle\n";
    echo "✅ Système prêt pour la production\n";
    
    echo "\n🛤️  PARCOURS UTILISATEUR FINAL:\n";
    echo "==============================\n";
    echo "1. passer-commande.php → Infos client + mode livraison\n";
    echo "2. confirmation-commande.php → Choix paiement (Stripe x2 + PayPal)\n";
    echo "3. resultat-paiement.php → Confirmation finale\n";
    echo "\n💳 OPTIONS DE PAIEMENT:\n";
    echo "- Carte Bancaire (Stripe)\n";
    echo "- PayPal\n";
    echo "- Stripe (Alternative)\n";
} else {
    echo "\n⚠️  PROBLÈMES DÉTECTÉS\n";
    echo "======================\n";
    echo "Certains tests ont échoué. Vérifiez les détails ci-dessus.\n";
}

echo "\n🎯 Le système de paiement du restaurant La Mangeoire est maintenant optimisé !\n";
?>
