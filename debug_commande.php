<?php
session_start();
require_once 'db_connexion.php';

echo "<h1>Debug Information</h1>";

// Test connexion base de données
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Commandes");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ Connexion base de données OK - " . $result['count'] . " commandes trouvées</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur base de données: " . $e->getMessage() . "</p>";
}

// Test structure table Commandes
try {
    $stmt = $pdo->prepare("DESCRIBE Commandes");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Structure table Commandes:</h2>";
    echo "<pre>";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Erreur structure table: " . $e->getMessage() . "</p>";
}

// Test structure table Panier
try {
    $stmt = $pdo->prepare("DESCRIBE Panier");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Structure table Panier:</h2>";
    echo "<pre>";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Erreur structure table Panier: " . $e->getMessage() . "</p>";
}

// Vérifier les erreurs de session
if (isset($_SESSION['debug_error'])) {
    echo "<h2>Erreur de session trouvée:</h2>";
    echo "<pre>" . $_SESSION['debug_error'] . "</pre>";
    echo "<h3>Stack trace:</h3>";
    echo "<pre>" . $_SESSION['debug_trace'] . "</pre>";
    unset($_SESSION['debug_error'], $_SESSION['debug_trace']);
}

// Test CartManager
try {
    require_once 'includes/CartManager.php';
    $cartManager = new CartManager($pdo);
    echo "<p>✅ CartManager initialisé avec succès</p>";
    
    $summary = $cartManager->getSummary();
    echo "<h2>Résumé du panier:</h2>";
    echo "<pre>";
    print_r($summary);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Erreur CartManager: " . $e->getMessage() . "</p>";
}

// Test session info
echo "<h2>Informations de session:</h2>";
echo "<pre>";
print_r([
    'session_id' => session_id(),
    'client_id' => $_SESSION['client_id'] ?? 'Non défini',
    'panier_session' => isset($_SESSION['cart_items']) ? count($_SESSION['cart_items']) : 'Non défini'
]);
echo "</pre>";
?>
