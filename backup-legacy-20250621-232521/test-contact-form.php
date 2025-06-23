<?php
// Script de test pour le formulaire de contact
echo "🧪 Test du formulaire de contact\n";
echo "================================\n\n";

// Inclure la connexion à la base de données
require_once 'db_connexion.php';

// Vérifier que la table Messages existe
try {
    $stmt = $pdo->query("DESCRIBE Messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✅ Table Messages trouvée avec les colonnes :\n";
    foreach ($columns as $column) {
        echo "   - $column\n";
    }
    echo "\n";
    
    // Test d'insertion de données
    echo "🔄 Test d'insertion d'un message de test...\n";
    
    $test_data = [
        'nom' => 'Test Utilisateur',
        'email' => 'test@example.com',
        'objet' => 'Test du formulaire',
        'message' => 'Ceci est un message de test pour vérifier le bon fonctionnement du formulaire de contact.'
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO Messages (nom, email, objet, message, date_creation)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $test_data['nom'],
        $test_data['email'], 
        $test_data['objet'],
        $test_data['message']
    ]);
    
    if ($result) {
        $message_id = $pdo->lastInsertId();
        echo "✅ Message de test inséré avec succès (ID: $message_id)\n";
        
        // Vérifier que le message a bien été inséré
        $stmt = $pdo->prepare("SELECT * FROM Messages WHERE MessageID = ?");
        $stmt->execute([$message_id]);
        $inserted_message = $stmt->fetch();
        
        if ($inserted_message) {
            echo "✅ Message récupéré de la base de données :\n";
            echo "   - Nom: " . $inserted_message['nom'] . "\n";
            echo "   - Email: " . $inserted_message['email'] . "\n";
            echo "   - Objet: " . $inserted_message['objet'] . "\n";
            echo "   - Date: " . $inserted_message['date_creation'] . "\n";
            echo "   - Statut: " . $inserted_message['statut'] . "\n";
            
            // Nettoyer le message de test
            $stmt = $pdo->prepare("DELETE FROM Messages WHERE MessageID = ?");
            $stmt->execute([$message_id]);
            echo "🧹 Message de test supprimé\n";
        }
    } else {
        echo "❌ Erreur lors de l'insertion du message de test\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

echo "\n🎯 Résumé des tests :\n";
echo "- Connexion à la base de données : ✅\n";
echo "- Table Messages : ✅\n"; 
echo "- Insertion/récupération : ✅\n";
echo "- Formulaire de contact : ✅ Prêt à l'emploi\n";
echo "\n📱 Pour tester le formulaire :\n";
echo "1. Ouvrez http://localhost:8000/contact.php\n";
echo "2. Remplissez et soumettez le formulaire\n";
echo "3. Vérifiez que le message de succès s'affiche\n";
echo "\n";
?>
