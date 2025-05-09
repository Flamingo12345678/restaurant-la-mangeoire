<?php
// Script pour mettre à jour la structure de la base de données

// Connection à la base de données
require_once 'db_connexion.php';

echo "Début de la mise à jour de la base de données...\n";

try {
    // Lecture du fichier SQL
    $sql = file_get_contents('update_database.sql');
    
    // Exécution du script SQL
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
    $conn->exec($sql);
    
    echo "La base de données a été mise à jour avec succès!\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la base de données: " . $e->getMessage() . "\n";
    
    // Afficher plus d'informations sur l'erreur
    echo "Code d'erreur: " . $e->getCode() . "\n";
    if ($e->getCode() == '42S21') {
        echo "La table DetailsCommande existe déjà. Ce n'est pas un problème.\n";
    } elseif ($e->getCode() == '42S22') {
        echo "Une colonne mentionnée n'existe pas dans la table.\n";
    } elseif ($e->getCode() == '42000') {
        echo "Erreur de syntaxe SQL.\n";
    }
}
?>
