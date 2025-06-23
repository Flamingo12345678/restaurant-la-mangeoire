<?php
/**
 * Script pour vérifier les tables existantes
 */

// Charger les variables d'environnement
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"');
        }
    }
}

// Configuration de la base de données
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$dbname = $_ENV['MYSQLDATABASE'] ?? '';
$username = $_ENV['MYSQLUSER'] ?? '';
$password = $_ENV['MYSQLPASSWORD'] ?? '';
$port = $_ENV['MYSQLPORT'] ?? 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "=== TABLES EXISTANTES ===" . PHP_EOL;
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($tables as $table) {
        echo "- $table" . PHP_EOL;
        
        // Compter les enregistrements
        try {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $count_stmt->fetchColumn();
            echo "  → $count enregistrements" . PHP_EOL;
        } catch(Exception $e) {
            echo "  → Erreur lors du comptage: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "=== STRUCTURE TABLES CRITIQUES ===" . PHP_EOL;
    
    // Vérifier la structure des tables importantes
    $critical_tables = ['paiements', 'alert_logs', 'panier', 'tables', 'articles'];
    
    foreach($critical_tables as $table) {
        if (in_array($table, $tables)) {
            echo PHP_EOL . "--- Structure de $table ---" . PHP_EOL;
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            foreach($columns as $column) {
                echo "  {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}" . PHP_EOL;
            }
        } else {
            echo PHP_EOL . "❌ Table $table n'existe pas" . PHP_EOL;
        }
    }

} catch(Exception $e) {
    echo "Erreur de connexion: " . $e->getMessage() . PHP_EOL;
}
?>
