<?php
/**
 * API Dashboard Admin - La Mangeoire
 * Date: 21 juin 2025
 * 
 * API pour alimenter le dashboard administrateur
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connexion.php';
require_once 'includes/audit-logger.php';

class DashboardAPI {
    private $pdoexion;
    private $auditLogger;
    
    public function __construct() {
        global $pdo, $auditLogger;
        $this->connexion = $pdo;
        $this->auditLogger = $auditLogger;
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'stats':
                return $this->getStats();
            case 'health':
                return $this->getSystemHealth();
            case 'logs':
                return $this->getRecentLogs();
            case 'export_logs':
                return $this->exportLogs();
            case 'clean_logs':
                return $this->cleanOldLogs();
            case 'clear_cache':
                return $this->clearCache();
            default:
                return ['error' => 'Action non reconnue'];
        }
    }
    
    private function getStats() {
        try {
            $stats = [];
            
            // Commandes aujourd'hui
            $stmt = $this->connexion->query("
                SELECT COUNT(*) as count 
                FROM commandes 
                WHERE DATE(date_commande) = CURDATE()
            ");
            $stats['orders_today'] = $stmt->fetch()['count'] ?? 0;
            
            // CA aujourd'hui
            $stmt = $this->connexion->query("
                SELECT SUM(total) as revenue 
                FROM commandes 
                WHERE DATE(date_commande) = CURDATE() 
                AND statut = 'confirmee'
            ");
            $revenue = $stmt->fetch()['revenue'] ?? 0;
            $stats['revenue_today'] = number_format($revenue, 2) . '€';
            
            // Sessions actives (estimation basée sur les logs récents)
            $stmt = $this->connexion->query("
                SELECT COUNT(DISTINCT ip_address) as count 
                FROM audit_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stats['active_sessions'] = $stmt->fetch()['count'] ?? 0;
            
            // Erreurs aujourd'hui
            $stmt = $this->connexion->query("
                SELECT COUNT(*) as count 
                FROM audit_logs 
                WHERE DATE(timestamp) = CURDATE() 
                AND severity IN ('error', 'critical')
            ");
            $stats['errors_today'] = $stmt->fetch()['count'] ?? 0;
            
            // Statistiques additionnelles
            $stats['total_clients'] = $this->getTableCount('clients');
            $stats['total_menus'] = $this->getTableCount('menus');
            $stats['total_commandes'] = $this->getTableCount('commandes');
            $stats['total_reservations'] = $this->getTableCount('reservations');
            
            return $stats;
            
        } catch (Exception $e) {
            return ['error' => 'Erreur récupération statistiques: ' . $e->getMessage()];
        }
    }
    
    private function getTableCount($table) {
        try {
            $stmt = $this->connexion->query("SELECT COUNT(*) as count FROM `$table`");
            return $stmt->fetch()['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getSystemHealth() {
        $health = [];
        
        // Test connexion base de données
        try {
            $this->connexion->query("SELECT 1");
            $health['database'] = true;
        } catch (Exception $e) {
            $health['database'] = false;
        }
        
        // Test configuration email
        $health['email'] = $this->testEmailConfig();
        
        // Test système de paiement (vérification config)
        $health['payment'] = $this->testPaymentConfig();
        
        // Test logs d'audit
        try {
            $stmt = $this->connexion->query("SELECT COUNT(*) FROM audit_logs LIMIT 1");
            $health['audit'] = true;
        } catch (Exception $e) {
            $health['audit'] = false;
        }
        
        // Test cache (système de fichiers)
        $health['cache'] = is_writable(__DIR__ . '/cache') || is_writable(sys_get_temp_dir());
        
        // Espace disque
        $diskFree = disk_free_space(__DIR__);
        $diskTotal = disk_total_space(__DIR__);
        $health['disk_space'] = $diskTotal > 0 ? (($diskTotal - $diskFree) / $diskTotal) * 100 : 0;
        
        // Mémoire PHP
        $health['memory_usage'] = memory_get_usage(true) / 1024 / 1024; // MB
        $health['memory_limit'] = $this->getMemoryLimit();
        
        return $health;
    }
    
    private function testEmailConfig() {
        return file_exists('.env') && getenv('SMTP_HOST') !== false;
    }
    
    private function testPaymentConfig() {
        // Vérifier la présence des clés de paiement dans l'environnement
        return (getenv('PAYPAL_CLIENT_ID') !== false) || (getenv('STRIPE_SECRET_KEY') !== false);
    }
    
    private function getMemoryLimit() {
        $limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            if ($matches[2] == 'M') {
                return $matches[1];
            } else if ($matches[2] == 'K') {
                return $matches[1] / 1024;
            }
        }
        return 128; // Valeur par défaut
    }
    
    private function getRecentLogs() {
        try {
            return $this->auditLogger->getRecentLogs(20);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function exportLogs() {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $filepath = $this->auditLogger->exportLogs($startDate, $endDate);
            
            if ($filepath && file_exists($filepath)) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                unlink($filepath); // Supprimer le fichier temporaire
                exit;
            } else {
                return ['error' => 'Impossible d\'exporter les logs'];
            }
        } catch (Exception $e) {
            return ['error' => 'Erreur export: ' . $e->getMessage()];
        }
    }
    
    private function cleanOldLogs() {
        try {
            $daysToKeep = $_POST['days'] ?? 90;
            $deleted = $this->auditLogger->cleanOldLogs($daysToKeep);
            
            // Logger l'action
            audit_log('logs_cleanup', ['deleted_count' => $deleted, 'days_kept' => $daysToKeep], 'info');
            
            return ['success' => true, 'deleted' => $deleted];
        } catch (Exception $e) {
            return ['error' => 'Erreur nettoyage: ' . $e->getMessage()];
        }
    }
    
    private function clearCache() {
        try {
            $cleared = 0;
            
            // Vider le cache des sessions
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
                $cleared++;
            }
            
            // Vider le cache OPCache si disponible
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $cleared++;
            }
            
            // Vider le cache de fichiers temporaires
            $tempDir = sys_get_temp_dir();
            $files = glob($tempDir . '/restaurant_*');
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $cleared++;
                }
            }
            
            // Logger l'action
            audit_log('cache_clear', ['items_cleared' => $cleared], 'info');
            
            return ['success' => true, 'cleared' => $cleared];
        } catch (Exception $e) {
            return ['error' => 'Erreur vidage cache: ' . $e->getMessage()];
        }
    }
    
    public function getPerformanceMetrics() {
        $metrics = [];
        
        try {
            // Temps de réponse moyen des requêtes
            $stmt = $this->connexion->query("
                SELECT AVG(TIME_TO_SEC(TIMEDIFF(NOW(), timestamp))) as avg_response_time
                FROM audit_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $metrics['avg_response_time'] = $stmt->fetch()['avg_response_time'] ?? 0;
            
            // Nombre de requêtes par minute
            $stmt = $this->connexion->query("
                SELECT COUNT(*) / 60 as requests_per_minute
                FROM audit_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $metrics['requests_per_minute'] = $stmt->fetch()['requests_per_minute'] ?? 0;
            
            // Taux d'erreur
            $stmt = $this->connexion->query("
                SELECT 
                    (COUNT(CASE WHEN severity IN ('error', 'critical') THEN 1 END) / COUNT(*)) * 100 as error_rate
                FROM audit_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $metrics['error_rate'] = $stmt->fetch()['error_rate'] ?? 0;
            
            return $metrics;
            
        } catch (Exception $e) {
            return ['error' => 'Erreur métriques: ' . $e->getMessage()];
        }
    }
    
    public function getTopErrors() {
        try {
            $stmt = $this->connexion->query("
                SELECT action, COUNT(*) as count, MAX(timestamp) as last_occurrence
                FROM audit_logs 
                WHERE severity IN ('error', 'critical')
                AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY action
                ORDER BY count DESC
                LIMIT 10
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

// Traitement de la requête
try {
    $api = new DashboardAPI();
    $result = $api->handleRequest();
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
