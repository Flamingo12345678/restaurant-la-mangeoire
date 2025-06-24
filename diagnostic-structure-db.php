<?php
/**
 * Diagnostic de la structure de la base de données
 * Pour comprendre l'incohérence entre Utilisateurs et Clients
 */

require_once 'db_connexion.php';

echo "=== DIAGNOSTIC STRUCTURE BASE DE DONNÉES ===\n\n";

// 1. Vérifier les tables existantes
echo "1. TABLES EXISTANTES:\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "   - $table\n";
}
echo "\n";

// 2. Structure table Clients
if (in_array('Clients', $tables)) {
    echo "2. STRUCTURE TABLE CLIENTS:\n";
    $stmt = $pdo->query("DESCRIBE Clients");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) {$col['Key']}\n";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Clients");
    $count = $stmt->fetchColumn();
    echo "   Nombre d'enregistrements: $count\n\n";
}

// 3. Structure table Utilisateurs  
if (in_array('Utilisateurs', $tables)) {
    echo "3. STRUCTURE TABLE UTILISATEURS:\n";
    $stmt = $pdo->query("DESCRIBE Utilisateurs");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) {$col['Key']}\n";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Utilisateurs");
    $count = $stmt->fetchColumn();
    echo "   Nombre d'enregistrements: $count\n\n";
}

// 4. Structure table Panier
if (in_array('Panier', $tables)) {
    echo "4. STRUCTURE TABLE PANIER:\n";
    $stmt = $pdo->query("DESCRIBE Panier");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) {$col['Key']}\n";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Panier");
    $count = $stmt->fetchColumn();
    echo "   Nombre d'enregistrements: $count\n\n";
}

// 5. Vérifier les contraintes de clés étrangères
echo "5. CONTRAINTES CLÉS ÉTRANGÈRES TABLE PANIER:\n";
try {
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'Panier' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll();
    
    if (empty($constraints)) {
        echo "   Aucune contrainte de clé étrangère trouvée\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "   - {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
        }
    }
} catch (Exception $e) {
    echo "   Erreur lors de la vérification des contraintes: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>
