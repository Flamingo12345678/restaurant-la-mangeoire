<?php
require_once 'db_connexion.php';

echo "=== DIAGNOSTIC DES TABLES DE RÉSERVATIONS ===" . PHP_EOL . PHP_EOL;

// Lister toutes les tables
echo "📋 Tables existantes :" . PHP_EOL;
$tables = $conn->query("SHOW TABLES")->fetchAll();
foreach($tables as $table) { 
    echo "- " . $table[0] . PHP_EOL; 
}

echo PHP_EOL . "🔍 Structure de la table 'Reservations' :" . PHP_EOL;
try {
    $desc = $conn->query("DESCRIBE Reservations")->fetchAll();
    foreach($desc as $col) { 
        echo "  " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL; 
    }
    
    // Compter les enregistrements
    $count = $conn->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
    echo "  📊 Nombre d'enregistrements : " . $count . PHP_EOL;
    
} catch(Exception $e) {
    echo "  ❌ Erreur : " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "🔍 Structure de la table 'reservations' :" . PHP_EOL;
try {
    $desc = $conn->query("DESCRIBE reservations")->fetchAll();
    foreach($desc as $col) { 
        echo "  " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL; 
    }
    
    // Compter les enregistrements
    $count = $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    echo "  📊 Nombre d'enregistrements : " . $count . PHP_EOL;
    
} catch(Exception $e) {
    echo "  ❌ Erreur : " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "✅ Diagnostic terminé !" . PHP_EOL;
?>
