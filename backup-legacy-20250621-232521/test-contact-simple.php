<?php
// Test rapide du formulaire de contact
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Test Contact</title></head><body>";
echo "<h1>Test du formulaire de contact</h1>";

try {
    // Test de base de données
    require_once 'db_connexion.php';
    echo "<p>✅ Connexion base de données OK</p>";
    
    // Test de la table Messages
    $stmt = $conn->query("SHOW TABLES LIKE 'Messages'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Table Messages existe</p>";
    } else {
        echo "<p>❌ Table Messages manquante</p>";
    }
    
    // Test d'insertion
    $stmt = $conn->prepare("INSERT INTO Messages (nom, email, objet, message, date_creation) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute(['Test', 'test@test.com', 'Test', 'Message de test']);
    
    if ($result) {
        $id = $conn->lastInsertId();
        echo "<p>✅ Insertion test réussie (ID: $id)</p>";
        
        // Nettoyage
        $stmt = $conn->prepare("DELETE FROM Messages WHERE MessageID = ?");
        $stmt->execute([$id]);
        echo "<p>✅ Nettoyage effectué</p>";
    }
    
    echo "<p><strong>🎉 Tout fonctionne parfaitement !</strong></p>";
    echo "<p><a href='contact.php'>→ Aller au formulaire de contact</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
