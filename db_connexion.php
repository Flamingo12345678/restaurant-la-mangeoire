<?php
// Connexion PDO MySQL centralisée
$host = getenv('MYSQLHOST') ?: 'localhost';
$db   = getenv('MYSQLDATABASE') ?: 'restaurant_la_mangeoire';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
// Debug temporaire : afficher les variables d'environnement et le DSN
if (getenv('RAILWAY_ENVIRONMENT')) { // Ne s'affiche que sur Railway
  echo "<pre>";
  echo "HOST: $host\n";
  echo "DB: $db\n";
  echo "USER: $user\n";
  echo "PORT: $port\n";
  echo "DSN: $dsn\n";
  echo "</pre>";
}
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
  $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  // Debug temporaire : afficher l'erreur PDO sur Railway
  if (getenv('RAILWAY_ENVIRONMENT')) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
  }
  // Ne pas afficher le détail en production
  die('Erreur de connexion à la base de données.');
}
