<?php
// Script to update the database with payment-related tables and fields

require_once 'db_connexion.php';

try {
    // Get the SQL queries from the file
    $sql = file_get_contents('update_paiements.sql');
    
    // Execute the SQL queries
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    echo "Database updated successfully with payment-related tables and fields!";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
