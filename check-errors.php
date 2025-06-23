<?php
echo "=== DERNIÈRES ERREURS PHP ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Vérifier les erreurs dans le log système
$log_files = [
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log', 
    '/usr/local/var/log/php-fpm.log',
    'error.log', // log local
    ini_get('error_log') // log configuré dans PHP
];

foreach ($log_files as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        echo "📁 Log trouvé: $log_file\n";
        $lines = file($log_file);
        $recent_lines = array_slice($lines, -20); // 20 dernières lignes
        
        foreach ($recent_lines as $line) {
            if (stripos($line, 'panier') !== false || 
                stripos($line, 'php') !== false ||
                stripos($line, 'error') !== false) {
                echo "  " . trim($line) . "\n";
            }
        }
        echo "\n";
        break; // Prendre le premier log trouvé
    }
}

// Afficher les dernières erreurs de error_log()
echo "📋 CONFIGURATION PHP:\n";
echo "Error reporting: " . error_reporting() . "\n";
echo "Display errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
echo "Log errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "\n";
echo "Error log: " . ini_get('error_log') . "\n";

echo "\n=== FIN ===\n";
?>
