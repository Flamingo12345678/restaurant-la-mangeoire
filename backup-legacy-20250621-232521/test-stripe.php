<?php
/**
 * Test d'intégration Stripe pour le Restaurant La Mangeoire
 * Ce fichier permet de tester la configuration Stripe et la connexion à l'API
 */

// Charger l'autoloader Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Erreur: L\'autoloader Composer est manquant. Veuillez exécuter "composer install".');
}

require_once __DIR__ . '/vendor/autoload.php';

// Charger la configuration Stripe
require_once __DIR__ . '/includes/stripe-config.php';

echo "<h1>Test d'intégration Stripe - Restaurant La Mangeoire</h1>\n";
echo "<hr>\n";

// Test 1: Vérifier les clés API
echo "<h2>1. Vérification des clés API</h2>\n";
echo "<p><strong>Clé publique configurée:</strong> " . (defined('STRIPE_PUBLISHABLE_KEY') && STRIPE_PUBLISHABLE_KEY ? "✅ Oui" : "❌ Non") . "</p>\n";
echo "<p><strong>Clé secrète configurée:</strong> " . (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY ? "✅ Oui" : "❌ Non") . "</p>\n";

// Test 2: Vérifier la classe Stripe
echo "<h2>2. Vérification de la classe Stripe</h2>\n";
if (class_exists('\Stripe\Stripe')) {
    echo "<p>✅ Classe Stripe disponible</p>\n";
    echo "<p><strong>Version API configurée:</strong> " . \Stripe\Stripe::getApiVersion() . "</p>\n";
} else {
    echo "<p>❌ Classe Stripe non disponible</p>\n";
}

// Test 3: Test de connexion à l'API
echo "<h2>3. Test de connexion à l'API Stripe</h2>\n";
try {
    if (STRIPE_SECRET_KEY) {
        // Tenter de récupérer les détails du compte
        $account = \Stripe\Account::retrieve();
        echo "<p>✅ Connexion réussie à l'API Stripe</p>\n";
        echo "<p><strong>ID du compte:</strong> " . $account->id . "</p>\n";
        echo "<p><strong>Nom du compte:</strong> " . ($account->display_name ?: 'Non configuré') . "</p>\n";
        echo "<p><strong>Pays:</strong> " . $account->country . "</p>\n";
        echo "<p><strong>Mode:</strong> " . ($account->livemode ? 'Production' : 'Test') . "</p>\n";
    } else {
        echo "<p>❌ Impossible de tester la connexion : clé secrète manquante</p>\n";
    }
} catch (\Stripe\Exception\AuthenticationException $e) {
    echo "<p>❌ Erreur d'authentification: " . $e->getMessage() . "</p>\n";
} catch (\Stripe\Exception\ApiConnectionException $e) {
    echo "<p>❌ Erreur de connexion à l'API: " . $e->getMessage() . "</p>\n";
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

// Test 4: Test de création d'une session de paiement (simulation)
echo "<h2>4. Test de création d'une session de paiement</h2>\n";
try {
    if (STRIPE_SECRET_KEY && class_exists('\Stripe\Checkout\Session')) {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => STRIPE_CURRENCY,
                    'product_data' => [
                        'name' => 'Test - Commande Restaurant La Mangeoire',
                    ],
                    'unit_amount' => 2500, // 25.00 EUR
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => STRIPE_SUCCESS_URL,
            'cancel_url' => STRIPE_CANCEL_URL,
        ]);
        
        echo "<p>✅ Session de paiement créée avec succès</p>\n";
        echo "<p><strong>ID de session:</strong> " . $session->id . "</p>\n";
        echo "<p><strong>URL de paiement:</strong> <a href='" . $session->url . "' target='_blank'>Ouvrir la page de paiement</a></p>\n";
    } else {
        echo "<p>❌ Impossible de créer une session de test</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Erreur lors de la création de la session: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Test effectué le " . date('Y-m-d H:i:s') . "</em></p>\n";
