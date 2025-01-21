<?php
require 'vendor/autoload.php';

// Paramètres de connexion à la base de données
$serverName = "FLAMINGO\SQLEXPRESS"; // Nom du serveur ou adresse IP
$connectionOptions = array(
  "Database" => "Gestionclients", // Nom de la base de données
  "Uid" => "your_username", // Nom d'utilisateur
  "PWD" => "your_password" // Mot de passe
);

// Créer une connexion
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Vérifier la connexion
if ($conn === false) {
  die(print_r(sqlsrv_errors(), true));
} else {
  echo "Connexion établie avec succès";
}

// Fermer la connexion (à utiliser à la fin des opérations)
sqlsrv_close($conn);
