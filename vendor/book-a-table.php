<?php
require 'vendor/autoload.php';
use Carbon\Carbon;

function verifierDisponibilite($dateReservation) {
    // Connexion à SQL Server avec PDO
    try {
        $conn = new PDO("sqlsrv:Server=localhost;Database=restaurant", "username", "password");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si la table est déjà réservée pour cette date
        $sql = "SELECT * FROM reservations WHERE date_reservation = :date_reservation";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':date_reservation', $dateReservation);
        $stmt->execute();
        
        // Si une réservation existe pour cette date
        if ($stmt->rowCount() > 0) {
            return false; // La table est déjà réservée
        } else {
            return true; // La table est disponible
        }
    } catch (PDOException $e) {
        echo "Erreur de connexion : " . $e->getMessage();
        return false;
    }
}


?>
