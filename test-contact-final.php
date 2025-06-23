<?php
echo "=== TEST FINAL SYSTÈME CONTACT ===\n";

// Charger les dépendances
require_once 'vendor/autoload.php';
require_once 'db_connexion.php';
require_once 'includes/email_manager.php';

try {
    // Créer le gestionnaire d'emails
    $emailManager = new EmailManager();
    
    echo "✅ EmailManager créé\n";
    
    // Vérifier la configuration
    $config = $emailManager->testConfiguration();
    echo "\n=== CONFIGURATION ===\n";
    foreach ($config as $key => $value) {
        echo "  $key: $value\n";
    }
    
    // Simuler un message de contact
    $nom = "Jean Martin";
    $email = "jean.martin@test.com";
    $sujet = "Demande de réservation";
    $message = "Bonjour,\n\nJe souhaiterais réserver une table pour 4 personnes ce vendredi soir.\n\nMerci de me confirmer la disponibilité.\n\nCordialement,\nJean Martin";
    
    echo "\n=== SIMULATION MESSAGE CONTACT ===\n";
    echo "Client: $nom ($email)\n";
    echo "Sujet: $sujet\n";
    
    // 1. Sauvegarde en base (comme dans contact.php)
    echo "\n1. Sauvegarde en base...\n";
    $stmt = $pdo->prepare("INSERT INTO Messages (nom, email, objet, message, date_creation) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$nom, $email, $sujet, $message]);
    
    if ($result) {
        $messageId = $pdo->lastInsertId();
        echo "   ✅ Message sauvegardé (ID: $messageId)\n";
    } else {
        throw new Exception("Erreur sauvegarde base");
    }
    
    // 2. Envoi notification admin
    echo "\n2. Envoi notification admin...\n";
    $adminResult = $emailManager->sendContactNotification($nom, $email, $sujet, $message);
    echo "   " . ($adminResult ? "✅ Admin notifié" : "❌ Échec notification admin") . "\n";
    
    // 3. Envoi confirmation client  
    echo "\n3. Envoi confirmation client...\n";
    $clientResult = $emailManager->sendContactConfirmation($nom, "ernestyombi20@gmail.com", $sujet);
    echo "   " . ($clientResult ? "✅ Confirmation envoyée" : "❌ Échec confirmation") . "\n";
    
    // Résultat final
    if ($adminResult && $clientResult) {
        echo "\n🎉 SYSTÈME DE CONTACT OPÉRATIONNEL!\n";
        echo "📧 Vérifiez votre boîte mail: ernestyombi20@gmail.com\n";
        echo "📧 2 emails devraient être arrivés:\n";
        echo "   • Notification admin avec détails du message\n";
        echo "   • Confirmation client automatique\n";
    } else {
        echo "\n⚠️ Système partiellement fonctionnel\n";
        echo "Base de données: ✅\n";
        echo "Email admin: " . ($adminResult ? "✅" : "❌") . "\n";
        echo "Email client: " . ($clientResult ? "✅" : "❌") . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== STATUS ===\n";
echo "Le formulaire /contact.php devrait maintenant envoyer des emails automatiquement.\n";
echo "Chaque soumission déclenche:\n";
echo "1. Sauvegarde en base Railway\n";
echo "2. Email notification à l'admin\n";  
echo "3. Email de confirmation au client\n";
?>
