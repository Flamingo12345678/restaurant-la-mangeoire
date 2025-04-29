<?php
// Chargement automatique du fichier .env en local (si présent)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
  if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad(); // safeLoad évite les erreurs si déjà chargé
  }
}

// Connexion PDO MySQL centralisée
$host = $_ENV['MYSQLHOST'] ?? null;
$db   = $_ENV['MYSQLDATABASE'] ?? null;
$user = $_ENV['MYSQLUSER'] ?? null;
$pass = $_ENV['MYSQLPASSWORD'] ?? null;
$port = $_ENV['MYSQLPORT'] ?? null;
$charset = 'utf8mb4';

// Vérification stricte des variables d'environnement
$envVars = ['MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT'];
$missing = [];
foreach ($envVars as $var) {
  if (empty($_ENV[$var])) {
    $missing[] = $var;
  }
}
if (count($missing) > 0) {
  echo "<pre>Variables d'environnement manquantes : " . implode(', ', $missing) . "</pre>";
  die("Erreur : une ou plusieurs variables d'environnement MySQL sont manquantes. Vérifiez la configuration Railway (" . implode(', ', $envVars) . ")");
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
// Debug : afficher les variables d'environnement et le DSN si DEBUG_DB est présent
if (!empty($_ENV['DEBUG_DB']) || !empty($_ENV['RAILWAY_ENVIRONMENT'])) {
  echo "<pre>";
  foreach ($envVars as $var) {
    echo "$var: " . ($_ENV[$var] ?? '') . "\n";
  }
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
  if (!empty($_ENV['RAILWAY_ENVIRONMENT'])) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
  }
  // Ne pas afficher le détail en production
  die('Erreur de connexion à la base de données.');
}
