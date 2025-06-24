<?php
/**
 * 🔍 DIAGNOSTIC ENVIRONNEMENT - RAILWAY
 * Script pour diagnostiquer les variables d'environnement disponibles
 * Date: 24 juin 2025
 */

echo "<h1>🔍 Diagnostic Environnement Railway</h1>";
echo "<hr>";

// 1. Vérifier si on est sur Railway
echo "<h2>📍 Détection de l'environnement</h2>";
$isRailway = !empty(getenv('RAILWAY_ENVIRONMENT')) || !empty($_SERVER['RAILWAY_ENVIRONMENT']);
echo "<p><strong>Railway détecté:</strong> " . ($isRailway ? "✅ OUI" : "❌ NON") . "</p>";

if ($isRailway) {
    echo "<p><strong>Environnement Railway:</strong> " . (getenv('RAILWAY_ENVIRONMENT') ?: $_SERVER['RAILWAY_ENVIRONMENT'] ?: 'Non défini') . "</p>";
}

// 2. Lister toutes les variables d'environnement disponibles
echo "<h2>🔧 Variables d'environnement disponibles</h2>";

echo "<h3>Via getenv():</h3>";
echo "<pre>";
$allEnvVars = [];
if (function_exists('getenv')) {
    // Récupérer toutes les variables d'environnement
    $env = getenv();
    if ($env) {
        foreach ($env as $key => $value) {
            $allEnvVars[$key] = $value;
            // Masquer les mots de passe
            $displayValue = (stripos($key, 'pass') !== false || stripos($key, 'secret') !== false || stripos($key, 'key') !== false) 
                ? str_repeat('*', min(strlen($value), 8)) 
                : $value;
            echo "$key = $displayValue\n";
        }
    }
}
echo "</pre>";

echo "<h3>Via \$_SERVER:</h3>";
echo "<pre>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'MYSQL') === 0 || strpos($key, 'RAILWAY') === 0 || strpos($key, 'DATABASE') !== false) {
        $allEnvVars[$key] = $value;
        $displayValue = (stripos($key, 'pass') !== false || stripos($key, 'secret') !== false || stripos($key, 'key') !== false) 
            ? str_repeat('*', min(strlen($value), 8)) 
            : $value;
        echo "$key = $displayValue\n";
    }
}
echo "</pre>";

echo "<h3>Via \$_ENV:</h3>";
echo "<pre>";
foreach ($_ENV as $key => $value) {
    $allEnvVars[$key] = $value;
    $displayValue = (stripos($key, 'pass') !== false || stripos($key, 'secret') !== false || stripos($key, 'key') !== false) 
        ? str_repeat('*', min(strlen($value), 8)) 
        : $value;
    echo "$key = $displayValue\n";
}
echo "</pre>";

// 3. Vérifier les variables MySQL spécifiques attendues
echo "<h2>🗄️ Variables Base de Données</h2>";
$mysqlVars = ['MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT'];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Variable</th><th>Valeur (getenv)</th><th>Valeur (\$_SERVER)</th><th>Valeur (\$_ENV)</th><th>Status</th></tr>";

foreach ($mysqlVars as $var) {
    $getenvVal = getenv($var) ?: '';
    $serverVal = $_SERVER[$var] ?? '';
    $envVal = $_ENV[$var] ?? '';
    
    $hasValue = !empty($getenvVal) || !empty($serverVal) || !empty($envVal);
    $status = $hasValue ? "✅ OK" : "❌ MANQUANT";
    
    // Masquer les mots de passe
    $displayGetenv = (stripos($var, 'pass') !== false) ? str_repeat('*', min(strlen($getenvVal), 8)) : $getenvVal;
    $displayServer = (stripos($var, 'pass') !== false) ? str_repeat('*', min(strlen($serverVal), 8)) : $serverVal;
    $displayEnv = (stripos($var, 'pass') !== false) ? str_repeat('*', min(strlen($envVal), 8)) : $envVal;
    
    echo "<tr>";
    echo "<td><strong>$var</strong></td>";
    echo "<td>$displayGetenv</td>";
    echo "<td>$displayServer</td>";
    echo "<td>$displayEnv</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Test de connexion simulé
echo "<h2>🔌 Test de Connexion Base de Données</h2>";

// Fonction de récupération des variables (comme dans db_connexion.php)
function getEnvVar($key, $default = '') {
    if (!empty($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    
    if (!empty($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    
    return $default;
}

$host = getEnvVar('MYSQLHOST');
$db   = getEnvVar('MYSQLDATABASE');
$user = getEnvVar('MYSQLUSER');
$pass = getEnvVar('MYSQLPASSWORD');
$port = getEnvVar('MYSQLPORT');

echo "<p><strong>Configuration détectée:</strong></p>";
echo "<ul>";
echo "<li>Host: " . ($host ?: "❌ MANQUANT") . "</li>";
echo "<li>Database: " . ($db ?: "❌ MANQUANT") . "</li>";
echo "<li>User: " . ($user ?: "❌ MANQUANT") . "</li>";
echo "<li>Password: " . ($pass ? "✅ PRÉSENT" : "❌ MANQUANT") . "</li>";
echo "<li>Port: " . ($port ?: "❌ MANQUANT") . "</li>";
echo "</ul>";

if ($host && $db && $user && $pass && $port) {
    echo "<p>✅ <strong>Toutes les variables sont présentes</strong></p>";
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        echo "<p>✅ <strong>Connexion à la base de données réussie !</strong></p>";
    } catch (PDOException $e) {
        echo "<p>❌ <strong>Erreur de connexion:</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ <strong>Variables manquantes, impossible de tester la connexion</strong></p>";
}

// 5. Informations système
echo "<h2>💻 Informations Système</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>OS:</strong> " . PHP_OS . "</li>";
echo "<li><strong>SAPI:</strong> " . php_sapi_name() . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Non défini') . "</li>";
echo "<li><strong>Script Path:</strong> " . __DIR__ . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Diagnostic généré le " . date('Y-m-d H:i:s') . "</small></p>";
?>
