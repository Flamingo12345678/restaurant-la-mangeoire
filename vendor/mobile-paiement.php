<?php
require 'vendor/autoload.php'; // Charger automatiquement les dépendances via Composer

use Mollie\Api\MollieApiClient;

// Initialiser le client Mollie avec votre clé API
try {
    $mollie = new MollieApiClient();
    $mollie->setApiKey("votre_api_key"); // Remplacez par votre clé API Mollie
} catch (Exception $e) {
    die("Erreur d'initialisation Mollie : " . htmlspecialchars($e->getMessage()));
}

// Données de la commande récupérées depuis une session ou une base de données
$commandeId = 123; // Exemple d'ID de commande
$montant = "25.50"; // Exemple de montant total
$description = "Commande de repas - ID $commandeId";

// URL de redirection après le paiement
$redirectUrl = "https://votre-site.com/merci?commande_id=$commandeId";

// Créer un paiement avec Mollie
try {
    $payment = $mollie->payments->create([
        "amount" => [
            "currency" => "EUR",
            "value" => $montant // Le montant total de la commande
        ],
        "description" => $description,
        "redirectUrl" => $redirectUrl,
        "metadata" => [
            "commande_id" => $commandeId, // Ajouter des métadonnées pour faciliter le suivi
        ],
    ]);

    // Générer un lien de paiement
    $paymentUrl = $payment->getCheckoutUrl();
    echo "Cliquez ici pour payer : <a href='" . htmlspecialchars($paymentUrl) . "'>Payer maintenant</a>";

} catch (Exception $e) {
    die("Erreur lors de la création du paiement : " . htmlspecialchars($e->getMessage()));
}
?>
