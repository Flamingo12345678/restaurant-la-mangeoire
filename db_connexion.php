<?php
// Connexion PDO MySQL centralisée
$host = getenv('MYSQLHOST');
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$port = getenv('MYSQLPORT');
$charset = 'utf8mb4';

// Vérification stricte des variables d'environnement
if (!$host || !$db || !$user || !$pass || !$port) {
  die("Erreur : une ou plusieurs variables d'environnement MySQL sont manquantes. Vérifiez la configuration Railway (MYSQLHOST, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD, MYSQLPORT).");
}

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
