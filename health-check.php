<?php
/**
 * Test de Santé Système - Restaurant La Mangeoire
 * Vérifie tous les composants critiques du système de paiement
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Test Santé Système</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.test-section { margin-bottom: 30px; padding: 15px; border-left: 4px solid #007cba; background: #f9f9f9; }
.success { color: #28a745; }
.warning { color: #ffc107; }
.error { color: #dc3545; }
.info { color: #17a2b8; }
h1 { color: #333; text-align: center; }
h2 { color: #007cba; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🏥 Test de Santé Système - La Mangeoire</h1>";
echo "<p class='info'>Exécuté le : " . date('d/m/Y H:i:s') . "</p>";

$health_status = [];

// 1. Test de connectivité base de données
echo "<div class='test-section'>";
echo "<h2>🗄️ Base de Données</h2>";
try {
    // Charger variables d'environnement
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

    $host = $_ENV['MYSQLHOST'] ?? 'localhost';
    $dbname = $_ENV['MYSQLDATABASE'] ?? '';
    $username = $_ENV['MYSQLUSER'] ?? '';
    $password = $_ENV['MYSQLPASSWORD'] ?? '';
    $port = $_ENV['MYSQLPORT'] ?? 3306;

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<p class='success'>✅ Connexion base de données : OK</p>";
    
    // Vérifier tables critiques
    $tables = ['paiements', 'Commandes', 'alert_logs', 'Clients'];
    foreach($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "<p class='success'>✅ Table $table : $count enregistrements</p>";
        } catch(Exception $e) {
            echo "<p class='error'>❌ Table $table : " . $e->getMessage() . "</p>";
            $health_status['db_' . $table] = false;
        }
    }
    
    $health_status['database'] = true;
} catch(Exception $e) {
    echo "<p class='error'>❌ Erreur base de données : " . $e->getMessage() . "</p>";
    $health_status['database'] = false;
}
echo "</div>";

// 2. Test des fichiers système
echo "<div class='test-section'>";
echo "<h2>📁 Fichiers Système</h2>";
$required_files = [
    'includes/payment_manager.php' => 'Gestionnaire de paiements',
    'includes/email_manager.php' => 'Gestionnaire d\'emails',
    'includes/alert_manager.php' => 'Système d\'alertes',
    'api/monitoring.php' => 'API de monitoring',
    'dashboard-admin.php' => 'Dashboard admin',
    '.env' => 'Configuration environnement'
];

foreach($required_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<p class='success'>✅ $description : OK ($size bytes)</p>";
    } else {
        echo "<p class='error'>❌ $description : MANQUANT</p>";
        $health_status['file_' . basename($file)] = false;
    }
}
$health_status['files'] = true;
echo "</div>";

// 3. Test de l'API de monitoring
echo "<div class='test-section'>";
echo "<h2>🔗 API de Monitoring</h2>";
try {
    if (file_exists('api/monitoring.php')) {
        // Simuler un appel à l'API
        ob_start();
        $_GET['endpoint'] = 'stats';
        include 'api/monitoring.php';
        $api_output = ob_get_clean();
        
        if (!empty($api_output)) {
            $data = json_decode($api_output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p class='success'>✅ API monitoring : Opérationnelle</p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                $health_status['api'] = true;
            } else {
                echo "<p class='warning'>⚠️ API monitoring : Réponse invalide</p>";
                $health_status['api'] = false;
            }
        } else {
            echo "<p class='error'>❌ API monitoring : Pas de réponse</p>";
            $health_status['api'] = false;
        }
    } else {
        echo "<p class='error'>❌ API monitoring : Fichier manquant</p>";
        $health_status['api'] = false;
    }
} catch(Exception $e) {
    echo "<p class='error'>❌ API monitoring : " . $e->getMessage() . "</p>";
    $health_status['api'] = false;
}
echo "</div>";

// 4. Test de configuration email
echo "<div class='test-section'>";
echo "<h2>📧 Configuration Email</h2>";
try {
    if (class_exists('EmailManager') || file_exists('includes/email_manager.php')) {
        require_once 'includes/email_manager.php';
        $emailManager = new EmailManager();
        $config = $emailManager->testConfiguration();
        
        echo "<p class='success'>✅ EmailManager : Chargé</p>";
        echo "<p class='info'>📋 Configuration :</p>";
        echo "<pre>";
        foreach($config as $key => $value) {
            echo "$key: $value\n";
        }
        echo "</pre>";
        $health_status['email'] = true;
    } else {
        echo "<p class='error'>❌ EmailManager : Non disponible</p>";
        $health_status['email'] = false;
    }
} catch(Exception $e) {
    echo "<p class='error'>❌ Configuration email : " . $e->getMessage() . "</p>";
    $health_status['email'] = false;
}
echo "</div>";

// 5. Test des permissions et logs
echo "<div class='test-section'>";
echo "<h2>🔐 Permissions et Logs</h2>";
$log_dirs = ['logs/', 'logs/payments/', 'logs/alerts/', 'logs/security/'];

foreach($log_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p class='success'>✅ Répertoire $dir : Accessible en écriture</p>";
        } else {
            echo "<p class='warning'>⚠️ Répertoire $dir : Lecture seule</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ Répertoire $dir : Inexistant (sera créé si nécessaire)</p>";
    }
}

// Test de création de fichier log
try {
    $test_log = 'logs/health_check_' . date('Y-m-d') . '.log';
    if (!is_dir('logs')) mkdir('logs', 0755, true);
    
    $log_content = date('Y-m-d H:i:s') . " - Test de santé système\n";
    if (file_put_contents($test_log, $log_content, FILE_APPEND)) {
        echo "<p class='success'>✅ Écriture logs : OK</p>";
        $health_status['logs'] = true;
    } else {
        echo "<p class='error'>❌ Écriture logs : Impossible</p>";
        $health_status['logs'] = false;
    }
} catch(Exception $e) {
    echo "<p class='error'>❌ Logs : " . $e->getMessage() . "</p>";
    $health_status['logs'] = false;
}
echo "</div>";

// 6. Résumé final
echo "<div class='test-section'>";
echo "<h2>📊 Résumé de Santé</h2>";

$total_tests = count($health_status);
$passed_tests = count(array_filter($health_status));
$health_percentage = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;

if ($health_percentage >= 90) {
    $status_class = 'success';
    $status_icon = '🟢';
    $status_text = 'EXCELLENT';
} elseif ($health_percentage >= 70) {
    $status_class = 'warning';
    $status_icon = '🟡';
    $status_text = 'ACCEPTABLE';
} else {
    $status_class = 'error';
    $status_icon = '🔴';
    $status_text = 'CRITIQUE';
}

echo "<p class='$status_class'><strong>$status_icon État global : $status_text ($health_percentage%)</strong></p>";
echo "<p>Tests réussis : $passed_tests / $total_tests</p>";

if ($health_percentage >= 90) {
    echo "<p class='success'>🎉 Système prêt pour la production !</p>";
} elseif ($health_percentage >= 70) {
    echo "<p class='warning'>⚠️ Système fonctionnel avec quelques avertissements</p>";
} else {
    echo "<p class='error'>❌ Intervention requise avant mise en production</p>";
}

echo "<p class='info'>💡 Conseil : Exécutez ce test régulièrement pour surveiller la santé du système</p>";
echo "</div>";

echo "</div></body></html>";
?>
