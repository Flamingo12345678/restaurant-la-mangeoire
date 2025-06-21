<?php
/**
 * Configuration Stripe pour le Restaurant La Mangeoire
 * Ce fichier contient les clés d'API Stripe et la configuration de base pour l'intégration des paiements
 */

// Assurez-vous que l'autoloader de Composer est chargé
// Note: L'autoloader doit être inclus avant ce fichier dans les scripts principaux
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Erreur: L\'autoloader Composer est manquant. Avez-vous exécuté "composer install"?');
}

// Chargement des variables d'environnement si disponibles
if (file_exists(__DIR__ . '/../.env')) {
    if (!class_exists('\\Dotenv\\Dotenv')) {
        die('Erreur: Dotenv ne peut pas être chargé. Avez-vous exécuté "composer install"?');
    }
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Clés API Stripe
// En production, ces valeurs devraient être définies dans des variables d'environnement
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? getenv('STRIPE_PUBLISHABLE_KEY'));
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));

// Configuration de base
define('STRIPE_CURRENCY', 'EUR'); // Euro
define('STRIPE_LOCALE', 'fr'); // Langue française

// URL de redirection après paiement
define('STRIPE_SUCCESS_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/confirmation-stripe.php?status=success&session_id={CHECKOUT_SESSION_ID}');
define('STRIPE_CANCEL_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/confirmation-stripe.php?status=cancel&session_id={CHECKOUT_SESSION_ID}');

// URL pour les webhooks Stripe (notifications de paiement)
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? ''));

// Configuration Stripe API
try {
    // Vérifier que la classe Stripe est disponible
    if (class_exists('\Stripe\Stripe')) {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        \Stripe\Stripe::setApiVersion('2023-10-16'); // Utiliser la version la plus récente stable
    } else {
        error_log('Erreur: La classe Stripe n\'est pas disponible.');
    }
} catch (Exception $e) {
    error_log('Erreur Stripe: ' . $e->getMessage());
}
