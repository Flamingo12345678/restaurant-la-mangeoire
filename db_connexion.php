<?php
// Connexion PDO MySQL centralisée
$host = 'localhost';
$db   = 'restaurant_la_mangeoire';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
  $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  // Ne pas afficher le détail en production
  die('Erreur de connexion à la base de données.');
}
