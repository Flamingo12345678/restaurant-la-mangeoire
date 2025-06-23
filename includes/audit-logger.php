<?php
/**
 * Système d'Audit et Logs - La Mangeoire
 * Date: 21 juin 2025
 * 
 * Système complet de journalisation des actions utilisateurs et système
 */

class AuditLogger {
    private $connexion;
    private $logFile;
    private $maxLogSize = 10485760; // 10MB
    
    public function __construct() {
        global $conn;
        $this->connexion = $conn;
        $this->logFile = __DIR__ . '/logs/audit.log';
        $this->ensureLogDirectory();
        $this->createAuditTable();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function createAuditTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                user_id INT NULL,
                user_type ENUM('client', 'employe', 'admin', 'system') DEFAULT 'system',
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NULL,
                entity_id INT NULL,
                details JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
                INDEX idx_timestamp (timestamp),
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_severity (severity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->connexion->exec($sql);
        } catch (Exception $e) {
            error_log("Erreur création table audit: " . $e->getMessage());
        }
    }
    
    public function log($action, $details = [], $severity = 'info', $entityType = null, $entityId = null) {
        // Log en base de données
        $this->logToDatabase($action, $details, $severity, $entityType, $entityId);
        
        // Log en fichier
        $this->logToFile($action, $details, $severity, $entityType, $entityId);
    }
    
    private function logToDatabase($action, $details, $severity, $entityType, $entityId) {
        try {
            $stmt = $this->connexion->prepare("
                INSERT INTO audit_logs 
                (user_id, user_type, action, entity_type, entity_id, details, ip_address, user_agent, severity)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $userId = $this->getCurrentUserId();
            $userType = $this->getCurrentUserType();
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $detailsJson = json_encode($details);
            
            $stmt->execute([
                $userId, $userType, $action, $entityType, $entityId,
                $detailsJson, $ipAddress, $userAgent, $severity
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur log audit DB: " . $e->getMessage());
        }
    }
    
    private function logToFile($action, $details, $severity, $entityType, $entityId) {
        try {
            // Rotation des logs si nécessaire
            if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxLogSize) {
                $this->rotateLogFile();
            }
            
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => strtoupper($severity),
                'user_id' => $this->getCurrentUserId(),
                'user_type' => $this->getCurrentUserType(),
                'ip' => $this->getClientIP(),
                'action' => $action,
                'entity' => $entityType ? "$entityType:$entityId" : null,
                'details' => $details
            ];
            
            $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            error_log("Erreur log audit fichier: " . $e->getMessage());
        }
    }
    
    private function rotateLogFile() {
        $backupFile = $this->logFile . '.' . date('Y-m-d_H-i-s');
        rename($this->logFile, $backupFile);
        
        // Compresser l'ancien fichier
        if (function_exists('gzopen')) {
            $this->compressLogFile($backupFile);
        }
    }
    
    private function compressLogFile($filename) {
        $gz = gzopen($filename . '.gz', 'w9');
        $file = fopen($filename, 'r');
        
        while (!feof($file)) {
            gzwrite($gz, fread($file, 8192));
        }
        
        fclose($file);
        gzclose($gz);
        unlink($filename);
    }
    
    private function getCurrentUserId() {
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        } elseif (isset($_SESSION['employe_id'])) {
            return $_SESSION['employe_id'];
        } elseif (isset($_SESSION['admin_id'])) {
            return $_SESSION['admin_id'];
        }
        return null;
    }
    
    private function getCurrentUserType() {
        if (isset($_SESSION['user_id'])) {
            return 'client';
        } elseif (isset($_SESSION['employe_id'])) {
            return 'employe';
        } elseif (isset($_SESSION['admin_id'])) {
            return 'admin';
        }
        return 'system';
    }
    
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    public function getRecentLogs($limit = 100) {
        try {
            $stmt = $this->connexion->prepare("
                SELECT * FROM audit_logs 
                ORDER BY timestamp DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getLogsByUser($userId, $userType = null, $limit = 50) {
        try {
            $sql = "SELECT * FROM audit_logs WHERE user_id = ?";
            $params = [$userId];
            
            if ($userType) {
                $sql .= " AND user_type = ?";
                $params[] = $userType;
            }
            
            $sql .= " ORDER BY timestamp DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->connexion->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getLogsByAction($action, $limit = 50) {
        try {
            $stmt = $this->connexion->prepare("
                SELECT * FROM audit_logs 
                WHERE action = ? 
                ORDER BY timestamp DESC 
                LIMIT ?
            ");
            $stmt->execute([$action, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getLogsBySeverity($severity, $limit = 50) {
        try {
            $stmt = $this->connexion->prepare("
                SELECT * FROM audit_logs 
                WHERE severity = ? 
                ORDER BY timestamp DESC 
                LIMIT ?
            ");
            $stmt->execute([$severity, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total des logs
            $stmt = $this->connexion->query("SELECT COUNT(*) as total FROM audit_logs");
            $stats['total_logs'] = $stmt->fetch()['total'];
            
            // Logs par sévérité
            $stmt = $this->connexion->query("
                SELECT severity, COUNT(*) as count 
                FROM audit_logs 
                GROUP BY severity
            ");
            $stats['by_severity'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Logs par type d'utilisateur
            $stmt = $this->connexion->query("
                SELECT user_type, COUNT(*) as count 
                FROM audit_logs 
                GROUP BY user_type
            ");
            $stats['by_user_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Actions les plus fréquentes
            $stmt = $this->connexion->query("
                SELECT action, COUNT(*) as count 
                FROM audit_logs 
                GROUP BY action 
                ORDER BY count DESC 
                LIMIT 10
            ");
            $stats['top_actions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Logs des dernières 24h
            $stmt = $this->connexion->query("
                SELECT COUNT(*) as count 
                FROM audit_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stats['last_24h'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function cleanOldLogs($daysToKeep = 90) {
        try {
            $stmt = $this->connexion->prepare("
                DELETE FROM audit_logs 
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function exportLogs($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT * FROM audit_logs WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $sql .= " AND timestamp >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND timestamp <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY timestamp DESC";
            
            $stmt = $this->connexion->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $filename = 'export_audit_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = __DIR__ . '/logs/' . $filename;
            
            $file = fopen($filepath, 'w');
            
            if (!empty($logs)) {
                // En-têtes CSV
                fputcsv($file, array_keys($logs[0]));
                
                // Données
                foreach ($logs as $log) {
                    fputcsv($file, $log);
                }
            }
            
            fclose($file);
            
            return $filepath;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Actions communes à logger
class AuditActions {
    const USER_LOGIN = 'user_login';
    const USER_LOGOUT = 'user_logout';
    const USER_REGISTER = 'user_register';
    const PASSWORD_CHANGE = 'password_change';
    
    const ORDER_CREATE = 'order_create';
    const ORDER_UPDATE = 'order_update';
    const ORDER_CANCEL = 'order_cancel';
    const ORDER_COMPLETE = 'order_complete';
    
    const PAYMENT_ATTEMPT = 'payment_attempt';
    const PAYMENT_SUCCESS = 'payment_success';
    const PAYMENT_FAILURE = 'payment_failure';
    const PAYMENT_REFUND = 'payment_refund';
    
    const MENU_CREATE = 'menu_create';
    const MENU_UPDATE = 'menu_update';
    const MENU_DELETE = 'menu_delete';
    
    const RESERVATION_CREATE = 'reservation_create';
    const RESERVATION_UPDATE = 'reservation_update';
    const RESERVATION_CANCEL = 'reservation_cancel';
    
    const ADMIN_ACCESS = 'admin_access';
    const SYSTEM_ERROR = 'system_error';
    const SECURITY_BREACH = 'security_breach';
}

// Instance globale
$auditLogger = new AuditLogger();

// Fonctions utilitaires
function audit_log($action, $details = [], $severity = 'info', $entityType = null, $entityId = null) {
    global $auditLogger;
    $auditLogger->log($action, $details, $severity, $entityType, $entityId);
}

function audit_login($userId, $userType) {
    audit_log(AuditActions::USER_LOGIN, [
        'user_id' => $userId,
        'user_type' => $userType
    ], 'info');
}

function audit_order($orderId, $action, $details = []) {
    audit_log($action, $details, 'info', 'order', $orderId);
}

function audit_payment($paymentId, $action, $details = []) {
    $severity = in_array($action, [AuditActions::PAYMENT_FAILURE]) ? 'warning' : 'info';
    audit_log($action, $details, $severity, 'payment', $paymentId);
}

function audit_error($message, $details = []) {
    audit_log(AuditActions::SYSTEM_ERROR, array_merge($details, ['message' => $message]), 'error');
}

function audit_security($message, $details = []) {
    audit_log(AuditActions::SECURITY_BREACH, array_merge($details, ['message' => $message]), 'critical');
}
?>
