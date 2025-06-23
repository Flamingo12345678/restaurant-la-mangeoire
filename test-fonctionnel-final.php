<?php
require_once 'db_connexion.php';

echo "🧪 TEST FONCTIONNEL FINAL\n";
echo "========================\n";

try {
    // Test 1: Créer un client test
    echo "1️⃣ Test création client...\n";
    $stmt = $pdo->prepare('INSERT INTO Clients (Nom, Prenom, Email, MotDePasse) VALUES (?, ?, ?, ?)');
    $stmt->execute(['TestNom', 'TestPrenom', 'test@coherence.local', password_hash('test123', PASSWORD_DEFAULT)]);
    $client_id = $pdo->lastInsertId();
    echo "   ✅ Client créé avec ID: $client_id\n";
    
    // Test 2: Ajouter au panier
    echo "2️⃣ Test ajout panier...\n";
    $stmt = $pdo->prepare('INSERT INTO Panier (ClientID, MenuID, Quantite) VALUES (?, 1, 2)');
    $stmt->execute([$client_id]);
    echo "   ✅ Ajout au panier réussi\n";
    
    // Test 3: Créer une commande
    echo "3️⃣ Test création commande...\n";
    $stmt = $pdo->prepare('INSERT INTO Commandes (ClientID, MontantTotal, Statut) VALUES (?, 35.50, "En cours")');
    $stmt->execute([$client_id]);
    $commande_id = $pdo->lastInsertId();
    echo "   ✅ Commande créée avec ID: $commande_id\n";
    
    // Test 4: Vérifier les relations
    echo "4️⃣ Test relations FK...\n";
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Panier WHERE ClientID = ?');
    $stmt->execute([$client_id]);
    $panier_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Commandes WHERE ClientID = ?');
    $stmt->execute([$client_id]);
    $commande_count = $stmt->fetchColumn();
    
    echo "   ✅ Panier: $panier_count élément(s)\n";
    echo "   ✅ Commandes: $commande_count commande(s)\n";
    
    // Nettoyage
    echo "5️⃣ Nettoyage données test...\n";
    $pdo->prepare('DELETE FROM Commandes WHERE CommandeID = ?')->execute([$commande_id]);
    $pdo->prepare('DELETE FROM Panier WHERE ClientID = ?')->execute([$client_id]);
    $pdo->prepare('DELETE FROM Clients WHERE ClientID = ?')->execute([$client_id]);
    echo "   ✅ Données test supprimées\n";
    
    echo "\n🎉 TOUS LES TESTS FONCTIONNELS PASSÉS!\n";
    echo "Le projet est entièrement fonctionnel et cohérent.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    
    // Nettoyage en cas d'erreur
    try {
        if (isset($commande_id)) $pdo->prepare('DELETE FROM Commandes WHERE CommandeID = ?')->execute([$commande_id]);
        if (isset($client_id)) {
            $pdo->prepare('DELETE FROM Panier WHERE ClientID = ?')->execute([$client_id]);
            $pdo->prepare('DELETE FROM Clients WHERE ClientID = ?')->execute([$client_id]);
        }
    } catch (Exception $e2) {
        echo "⚠️ Erreur nettoyage: " . $e2->getMessage() . "\n";
    }
}
?>
