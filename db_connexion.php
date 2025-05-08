<?php
// Chargement automatique du fichier .env (toujours, même en production)
require_once(__DIR__ . '/vendor/autoload.php');
if (file_exists(__DIR__ . '/.env')) {
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
}

// Connexion PDO MySQL centralisée
$host = trim(getenv('MYSQLHOST') ?: ($_ENV['MYSQLHOST'] ?? ''), "\" ");
$db   = trim(getenv('MYSQLDATABASE') ?: ($_ENV['MYSQLDATABASE'] ?? ''), "\" ");
$user = trim(getenv('MYSQLUSER') ?: ($_ENV['MYSQLUSER'] ?? ''), "\" ");
$pass = trim(getenv('MYSQLPASSWORD') ?: ($_ENV['MYSQLPASSWORD'] ?? ''), "\" ");
$port = trim(getenv('MYSQLPORT') ?: ($_ENV['MYSQLPORT'] ?? ''), "\" ");
$charset = 'utf8mb4';

// Vérification stricte des variables d'environnement
$envVars = ['MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT'];
$missing = [];
foreach ($envVars as $var) {
  $val = trim(getenv($var) ?: ($_ENV[$var] ?? ''), "\" ");
  if (empty($val)) {
    $missing[] = $var;
  }
}

if (count($missing) > 0) {
  echo "<pre>Variables d'environnement manquantes : " . implode(', ', $missing) . "</pre>";
  die("Erreur : une ou plusieurs variables d'environnement MySQL sont manquantes. Vérifiez la configuration Railway (" . implode(', ', $envVars) . ")");
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
// (DEBUG supprimé)
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
  $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  if (getenv('RAILWAY_ENVIRONMENT') || !empty($_ENV['RAILWAY_ENVIRONMENT'])) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
  }
  die('Erreur de connexion à la base de données.');
}
