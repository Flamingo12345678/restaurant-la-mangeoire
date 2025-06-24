<?php
// Fonction simple pour charger le fichier .env
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Diviser la ligne en clé=valeur
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, "\" \t\n\r\0\x0B");
            
            // Définir la variable d'environnement
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

// Charger le fichier .env approprié selon l'environnement
$envLoaded = false;

// 1. D'abord essayer de charger .env.production si on est en production
if (getenv('RAILWAY_ENVIRONMENT') || getenv('APP_ENV') === 'production') {
    $envLoaded = loadEnvFile(__DIR__ . '/.env.production');
}

// 2. Si pas de fichier de production, essayer .env
if (!$envLoaded) {
    $envLoaded = loadEnvFile(__DIR__ . '/.env');
}

// 3. Si aucun fichier n'est trouvé, vérifier si les variables sont déjà définies
if (!$envLoaded) {
    // Vérifier si les variables essentielles sont déjà dans l'environnement (Railway)
    if (getenv('MYSQLHOST') || getenv('MYSQL_URL')) {
        $envLoaded = true; // Variables disponibles via l'environnement
    } else {
        die('Erreur : impossible de charger les variables d\'environnement (.env ou variables système)');
    }
}

// Récupération des variables d'environnement
if (!function_exists('getEnvVar')) {
    function getEnvVar($key, $default = '') {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

$host = getEnvVar('MYSQLHOST');
$db   = getEnvVar('MYSQLDATABASE');
$user = getEnvVar('MYSQLUSER');
$pass = getEnvVar('MYSQLPASSWORD');
$port = getEnvVar('MYSQLPORT');
$charset = 'utf8mb4';

// Vérification des variables requises
$envVars = ['MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT'];
$missing = [];
foreach ($envVars as $var) {
    $val = getEnvVar($var);
    if (empty($val)) {
        $missing[] = $var;
    }
}

if (count($missing) > 0) {
    echo "<pre>Variables d'environnement manquantes : " . implode(', ', $missing) . "</pre>";
    echo "<pre>Variables disponibles : " . print_r($_ENV, true) . "</pre>";
    die("Erreur : variables manquantes dans le fichier .env");
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
// (DEBUG supprimé)
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  if (getenv('RAILWAY_ENVIRONMENT') || !empty($_ENV['RAILWAY_ENVIRONMENT'])) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
  }
  die('Erreur de connexion à la base de données.');
}
