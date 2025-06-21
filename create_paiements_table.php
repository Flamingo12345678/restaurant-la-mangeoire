<?php
// Script to create the Paiements table

require_once 'db_connexion.php';

try {
    // Get the SQL queries from the file
    $sql = file_get_contents('create_paiements_table.sql');
    
    // Execute the SQL queries
    $conn->exec($sql);
    
    echo "Paiements table created successfully!";
} catch (PDOException $e) {
    echo "Error creating Paiements table: " . $e->getMessage();
}
?>
