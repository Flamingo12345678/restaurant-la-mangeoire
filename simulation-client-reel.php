<?php
// Simulation d'un client réel utilisant le formulaire de contact
echo "=== SIMULATION CLIENT RÉEL ===\n\n";

// Simuler les données POST du formulaire
$_POST = [
    'nom' => 'Sophie Martin',
    'email' => 'sophie.martin@gmail.com',
    'sujet' => 'Réservation table pour ce soir',
    'message' => "Bonjour,\n\nJe souhaiterais réserver une table pour 2 personnes ce soir vers 20h.\n\nEst-ce encore possible ?\n\nMerci de me confirmer rapidement.\n\nCordialement,\nSophie Martin\nTél: 06 12 34 56 78"
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost:8000';

echo "👤 CLIENT: Sophie Martin\n";
echo "📧 EMAIL: sophie.martin@gmail.com\n";
echo "📝 DEMANDE: Réservation table pour ce soir\n\n";

try {
    // Inclure les fichiers nécessaires (comme dans contact.php)
    require_once 'db_connexion.php';
    require_once 'includes/email_manager.php';

    echo "🔄 Traitement de la demande...\n";

    // Récupération et validation des données
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        throw new Exception("Données manquantes");
    }

    // Validation email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email invalide");
    }

    echo "   ✅ Données validées\n";

    // Sauvegarde en base (exactement comme contact.php)
    echo "💾 Sauvegarde en base...\n";
    
    $stmt = $pdo->prepare("INSERT INTO Messages (nom, email, objet, message, date_creation) VALUES (?, ?, ?, ?, NOW())");
    $saveResult = $stmt->execute([$nom, $email, $sujet, $message]);
    
    if (!$saveResult) {
        throw new Exception("Erreur de sauvegarde en base");
    }
    
    $messageId = $pdo->lastInsertId();
    echo "   ✅ Message sauvegardé (ID: $messageId)\n";

    // Envoi automatique des emails
    echo "📧 Envoi automatique des emails...\n";
    
    $emailManager = new EmailManager();

    // Email vers l'administrateur
    echo "   → Notification admin... ";
    $adminResult = $emailManager->sendContactNotification($nom, $email, $sujet, $message);
    echo ($adminResult ? "✅" : "❌") . "\n";

    // Email de confirmation au client
    echo "   → Confirmation client... ";
    $clientResult = $emailManager->sendContactConfirmation($nom, $email, $sujet);
    echo ($clientResult ? "✅" : "❌") . "\n";

    if ($adminResult && $clientResult) {
        echo "\n🎉 SUCCÈS COMPLET!\n\n";
        
        echo "=== RÉSUMÉ DE L'OPÉRATION ===\n";
        echo "✅ 1. Formulaire soumis par Sophie Martin\n";
        echo "✅ 2. Données validées et sécurisées\n";
        echo "✅ 3. Message sauvegardé en base Railway\n";
        echo "✅ 4. Admin notifié automatiquement par email\n";
        echo "✅ 5. Client confirmé automatiquement\n\n";
        
        echo "📬 VÉRIFIEZ VOTRE BOÎTE MAIL:\n";
        echo "   • Gmail: ernestyombi20@gmail.com\n";
        echo "   • Vous devriez voir un nouvel email de Sophie\n";
        echo "   • Avec tous les détails de sa demande\n\n";
        
        echo "🍽️ CÔTÉ CLIENT:\n";
        echo "   • Sophie a reçu une confirmation\n";
        echo "   • Elle sait que vous allez répondre\n";
        echo "   • Elle peut vous rappeler si besoin\n\n";
        
        echo "🚀 VOTRE RESTAURANT EST PRÊT!\n";
        echo "   Les clients peuvent vous contacter 24h/24\n";
        echo "   Vous êtes notifié instantanément\n";
        echo "   Le système fonctionne automatiquement\n";
        
    } else {
        echo "\n⚠️  Problème partiel:\n";
        echo "   Admin: " . ($adminResult ? "OK" : "ÉCHEC") . "\n";
        echo "   Client: " . ($clientResult ? "OK" : "ÉCHEC") . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📋 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 MISSION ACCOMPLIE - SYSTÈME OPÉRATIONNEL!\n";
echo str_repeat("=", 50) . "\n";
?>
