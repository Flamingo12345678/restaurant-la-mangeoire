<?php
/**
 * Système de monitoring et logs pour La Mangeoire
 * Surveillance des paiements et erreurs en production
 */

class ProductionMonitor {
    
    private $log_dir;
    private $max_log_size = 10485760; // 10MB
    
    public function __construct() {
        $this->log_dir = __DIR__ . '/../logs';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        if (!is_dir($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }
        
        // Protection .htaccess
        $htaccess_file = $this->log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Order deny,allow\nDeny from all\n");
        }
    }
    
    /**
     * Log des paiements
     */
    public function logPayment($data) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'payment',
            'data' => $data
        ];
        
        $this->writeLog('payment.log', $log_entry);
    }
    
    /**
     * Log des erreurs
     */
    public function logError($error, $context = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'error' => $error,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->writeLog('error.log', $log_entry);
    }
    
    /**
     * Log des tentatives de sécurité
     */
    public function logSecurity($event, $details = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'security',
            'event' => $event,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->writeLog('security.log', $log_entry);
    }
    
    /**
     * Écrire dans un fichier de log
     */
    private function writeLog($filename, $data) {
        $log_file = $this->log_dir . '/' . $filename;
        
        // Rotation des logs si trop gros
        if (file_exists($log_file) && filesize($log_file) > $this->max_log_size) {
            $this->rotateLog($log_file);
        }
        
        $log_line = json_encode($data) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rotation des logs
     */
    private function rotateLog($log_file) {
        $backup_file = $log_file . '.' . date('Y-m-d_H-i-s');
        rename($log_file, $backup_file);
        
        // Compresser l'ancien log
        if (function_exists('gzopen')) {
            $this->compressLog($backup_file);
        }
    }
    
    /**
     * Compresser un log
     */
    private function compressLog($file) {
        $compressed_file = $file . '.gz';
        $data = file_get_contents($file);
        $gz = gzopen($compressed_file, 'w9');
        gzwrite($gz, $data);
        gzclose($gz);
        unlink($file);
    }
    
    /**
     * Obtenir les statistiques des paiements
     */
    public function getPaymentStats($hours = 24) {
        $log_file = $this->log_dir . '/payment.log';
        if (!file_exists($log_file)) {
            return ['total' => 0, 'success' => 0, 'failed' => 0];
        }
        
        $since = time() - ($hours * 3600);
        $stats = ['total' => 0, 'success' => 0, 'failed' => 0, 'amounts' => []];
        
        $handle = fopen($log_file, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $entry = json_decode($line, true);
                if ($entry && strtotime($entry['timestamp']) > $since) {
                    $stats['total']++;
                    
                    if (isset($entry['data']['success']) && $entry['data']['success']) {
                        $stats['success']++;
                        if (isset($entry['data']['amount'])) {
                            $stats['amounts'][] = floatval($entry['data']['amount']);
                        }
                    } else {
                        $stats['failed']++;
                    }
                }
            }
            fclose($handle);
        }
        
        if (!empty($stats['amounts'])) {
            $stats['total_amount'] = array_sum($stats['amounts']);
            $stats['average_amount'] = $stats['total_amount'] / count($stats['amounts']);
        }
        
        return $stats;
    }
    
    /**
     * Vérifier l'état du système
     */
    public function getSystemHealth() {
        $health = [
            'timestamp' => date('Y-m-d H:i:s'),
            'disk_space' => $this->getDiskSpace(),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'log_sizes' => $this->getLogSizes(),
            'last_payment' => $this->getLastPaymentTime(),
            'error_count_24h' => $this->getErrorCount(24)
        ];
        
        return $health;
    }
    
    private function getDiskSpace() {
        $bytes = disk_free_space('.');
        return $bytes ? round($bytes / 1024 / 1024, 2) . ' MB' : 'unknown';
    }
    
    private function getLogSizes() {
        $sizes = [];
        $logs = ['payment.log', 'error.log', 'security.log'];
        
        foreach ($logs as $log) {
            $file = $this->log_dir . '/' . $log;
            $sizes[$log] = file_exists($file) ? round(filesize($file) / 1024, 2) . ' KB' : '0 KB';
        }
        
        return $sizes;
    }
    
    private function getLastPaymentTime() {
        $log_file = $this->log_dir . '/payment.log';
        if (!file_exists($log_file)) return null;
        
        $lines = file($log_file);
        if (empty($lines)) return null;
        
        $last_line = trim(end($lines));
        $entry = json_decode($last_line, true);
        
        return $entry ? $entry['timestamp'] : null;
    }
    
    private function getErrorCount($hours) {
        $log_file = $this->log_dir . '/error.log';
        if (!file_exists($log_file)) return 0;
        
        $since = time() - ($hours * 3600);
        $count = 0;
        
        $handle = fopen($log_file, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $entry = json_decode($line, true);
                if ($entry && strtotime($entry['timestamp']) > $since) {
                    $count++;
                }
            }
            fclose($handle);
        }
        
        return $count;
    }
    
    /**
     * Nettoyer les logs anciens
     */
    public function cleanupOldLogs($days = 30) {
        $cutoff = time() - ($days * 24 * 3600);
        $files = glob($this->log_dir . '/*.log.*');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    /**
     * Envoyer une alerte email en cas d'erreur critique
     */
    public function sendAlert($subject, $message) {
        if (!empty($_ENV['ADMIN_EMAIL'])) {
            $to = $_ENV['ADMIN_EMAIL'];
            $headers = "From: " . ($_ENV['FROM_EMAIL'] ?? 'noreply@restaurant.com') . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $html_message = "
            <h2>🚨 Alerte Système - La Mangeoire</h2>
            <p><strong>Heure:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Sujet:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545;'>
                $message
            </div>
            <p><em>Système de monitoring automatique</em></p>
            ";
            
            mail($to, "[URGENT] $subject", $html_message, $headers);
        }
    }
}

// Instance globale
$monitor = new ProductionMonitor();

// Handler d'erreurs global
set_error_handler(function($severity, $message, $file, $line) use ($monitor) {
    if (error_reporting() & $severity) {
        $monitor->logError($message, [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);
        
        // Alerte pour erreurs critiques
        if ($severity === E_ERROR || $severity === E_CORE_ERROR) {
            $monitor->sendAlert('Erreur PHP Critique', "$message dans $file:$line");
        }
    }
    return false;
});

// Handler d'exceptions
set_exception_handler(function($exception) use ($monitor) {
    $monitor->logError($exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    $monitor->sendAlert('Exception Non Gérée', $exception->getMessage());
});
?>
