<?php
/**
 * Système de Notifications et Alertes - La Mangeoire
 */

require_once 'db_connexion.php';
require_once 'includes/email_manager.php';

class AlertManager {
    private $pdo;
    private $emailManager;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->emailManager = new EmailManager();
    }
    
    /**
     * Vérifie et envoie les alertes nécessaires
     */
    public function checkAndSendAlerts() {
        $alerts = [];
        
        // Vérifier les échecs de paiement
        $alerts = array_merge($alerts, $this->checkPaymentFailures());
        
        // Vérifier la configuration HTTPS
        $alerts = array_merge($alerts, $this->checkHTTPSStatus());
        
        // Vérifier les erreurs système
        $alerts = array_merge($alerts, $this->checkSystemErrors());
        
        // Vérifier les volumes anormaux
        $alerts = array_merge($alerts, $this->checkAbnormalVolumes());
        
        // Envoyer les alertes par email si nécessaire
        if (!empty($alerts)) {
            $this->sendAlertEmail($alerts);
        }
        
        return $alerts;
    }
    
    /**
     * Vérifie les échecs de paiement récents
     */
    private function checkPaymentFailures() {
        $alerts = [];
        
        try {
            // Échecs dans la dernière heure
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as failed_count 
                FROM paiements 
                WHERE statut = 'failed' 
                AND date_creation >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $failed_count = $stmt->fetch()['failed_count'];
            
            if ($failed_count > 5) {
                $alerts[] = [
                    'level' => 'high',
                    'type' => 'payment_failures',
                    'message' => "Échecs de paiement élevés: $failed_count dans la dernière heure",
                    'count' => $failed_count,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
            // Taux d'échec anormal (> 20%)
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN statut = 'failed' THEN 1 END) as failed
                FROM paiements 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 6 HOUR)
            ");
            $stats = $stmt->fetch();
            
            if ($stats['total'] > 0) {
                $failure_rate = ($stats['failed'] / $stats['total']) * 100;
                if ($failure_rate > 20) {
                    $alerts[] = [
                        'level' => 'medium',
                        'type' => 'high_failure_rate',
                        'message' => "Taux d'échec élevé: " . number_format($failure_rate, 1) . "% sur 6h",
                        'rate' => $failure_rate,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                }
            }
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'high',
                'type' => 'system_error',
                'message' => 'Erreur lors de la vérification des paiements: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Vérifie le statut HTTPS
     */
    private function checkHTTPSStatus() {
        $alerts = [];
        
        if (!function_exists('HTTPSManager::isHTTPS') || !HTTPSManager::isHTTPS()) {
            $alerts[] = [
                'level' => 'high',
                'type' => 'security',
                'message' => 'Site non sécurisé - HTTPS requis pour les paiements',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Vérifie les erreurs système
     */
    private function checkSystemErrors() {
        $alerts = [];
        
        // Vérifier les logs d'erreur récents
        $error_log = 'logs/errors/' . date('Y-m-d') . '.log';
        if (file_exists($error_log)) {
            $errors = file($error_log);
            $recent_errors = array_slice($errors, -10); // 10 dernières erreurs
            
            if (count($recent_errors) > 5) {
                $alerts[] = [
                    'level' => 'medium',
                    'type' => 'system_errors',
                    'message' => 'Nombre élevé d\'erreurs système: ' . count($recent_errors) . ' récentes',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Vérifie les volumes de transaction anormaux
     */
    private function checkAbnormalVolumes() {
        $alerts = [];
        
        try {
            // Volume dans la dernière heure vs moyenne
            $stmt = $this->pdo->query("
                SELECT 
                    (SELECT COUNT(*) FROM paiements WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 1 HOUR)) as current_hour,
                    (SELECT COUNT(*) / 24 FROM paiements WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as avg_hour
            ");
            $volumes = $stmt->fetch();
            
            if ($volumes['avg_hour'] > 0 && $volumes['current_hour'] > ($volumes['avg_hour'] * 3)) {
                $alerts[] = [
                    'level' => 'medium',
                    'type' => 'high_volume',
                    'message' => "Volume de transactions élevé: {$volumes['current_hour']} vs moyenne {$volumes['avg_hour']}",
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
        } catch (Exception $e) {
            // Pas d'alerte pour cette erreur, on log seulement
            error_log("Erreur vérification volumes: " . $e->getMessage());
        }
        
        return $alerts;
    }
    
    /**
     * Envoie un email d'alerte avec toutes les alertes
     */
    private function sendAlertEmail($alerts) {
        $admin_email = getenv('ADMIN_EMAIL') ?: 'admin@votredomaine.com';
        
        // Classer les alertes par niveau
        $high_alerts = array_filter($alerts, fn($a) => $a['level'] === 'high');
        $medium_alerts = array_filter($alerts, fn($a) => $a['level'] === 'medium');
        
        // Sujet selon la criticité
        $subject = count($high_alerts) > 0 ? 
            '[URGENT] Alertes Système - La Mangeoire' : 
            '[INFO] Notifications Système - La Mangeoire';
        
        // Corps de l'email
        $body = $this->generateAlertEmailBody($alerts, $high_alerts, $medium_alerts);
        
        try {
            // Déterminer le niveau global
            $global_level = count($high_alerts) > 0 ? 'CRITICAL' : (count($medium_alerts) > 0 ? 'WARNING' : 'INFO');
            
            $this->emailManager->sendAlert(
                $subject,
                $body,
                $global_level
            );
            
            // Logger l'envoi d'alerte
            $this->logAlert('email_sent', "Alerte envoyée: " . count($alerts) . " notifications");
            
        } catch (Exception $e) {
            // Logger l'erreur d'envoi
            $this->logAlert('email_error', "Erreur envoi alerte: " . $e->getMessage());
        }
    }
    
    /**
     * Génère le corps HTML de l'email d'alerte
     */
    private function generateAlertEmailBody($alerts, $high_alerts, $medium_alerts) {
        $html = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .alert { margin: 15px 0; padding: 15px; border-radius: 8px; border-left: 4px solid; }
                .alert-high { background: #ffebee; border-color: #f44336; }
                .alert-medium { background: #fff8e1; border-color: #ff9800; }
                .alert-time { font-size: 12px; color: #666; margin-top: 5px; }
                .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                .footer { background: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚨 Alertes Système</h1>
                    <p>Restaurant La Mangeoire - Monitoring</p>
                </div>
                
                <div class="content">
                    <div class="summary">
                        <h3>📊 Résumé</h3>
                        <p><strong>Total des alertes:</strong> ' . count($alerts) . '</p>
                        <p><strong>Alertes critiques:</strong> ' . count($high_alerts) . '</p>
                        <p><strong>Alertes moyennes:</strong> ' . count($medium_alerts) . '</p>
                        <p><strong>Timestamp:</strong> ' . date('d/m/Y H:i:s') . '</p>
                    </div>';
        
        if (count($high_alerts) > 0) {
            $html .= '<h3>🚨 Alertes Critiques</h3>';
            foreach ($high_alerts as $alert) {
                $html .= '<div class="alert alert-high">
                    <strong>' . htmlspecialchars($alert['message']) . '</strong>
                    <div class="alert-time">Type: ' . htmlspecialchars($alert['type']) . ' | ' . $alert['timestamp'] . '</div>
                </div>';
            }
        }
        
        if (count($medium_alerts) > 0) {
            $html .= '<h3>⚠️ Alertes d\'Information</h3>';
            foreach ($medium_alerts as $alert) {
                $html .= '<div class="alert alert-medium">
                    <strong>' . htmlspecialchars($alert['message']) . '</strong>
                    <div class="alert-time">Type: ' . htmlspecialchars($alert['type']) . ' | ' . $alert['timestamp'] . '</div>
                </div>';
            }
        }
        
        $html .= '
                    <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
                        <h4>🔧 Actions Recommandées</h4>
                        <ul>
                            <li>Vérifiez le dashboard administrateur</li>
                            <li>Consultez les logs détaillés</li>
                            <li>Contactez l\'équipe technique si nécessaire</li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Système de monitoring automatique - Restaurant La Mangeoire</p>
                    <p>Pour désactiver ces notifications, contactez l\'administrateur système</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Log une alerte dans la base de données
     */
    private function logAlert($type, $message) {
        try {
            $level = match($type) {
                'email_error', 'system_error' => 'error',
                'payment_failure' => 'critical',
                'https_issue' => 'warning',
                'email_sent' => 'info',
                default => 'info'
            };
            
            $stmt = $this->pdo->prepare("
                INSERT INTO alert_logs (level, message, context) 
                VALUES (?, ?, ?)
            ");
            $context = json_encode(['type' => $type, 'timestamp' => date('Y-m-d H:i:s')]);
            $stmt->execute([$level, $message, $context]);
        } catch (Exception $e) {
            error_log("Erreur logging alerte: " . $e->getMessage());
        }
    }
    
    /**
     * Vérifie s'il faut envoyer une alerte (éviter le spam)
     */
    public function shouldSendAlert($alert_type, $cooldown_minutes = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM alert_logs 
                WHERE JSON_EXTRACT(context, '$.type') = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$alert_type, $cooldown_minutes]);
            
            return $stmt->fetch()['count'] == 0;
        } catch (Exception $e) {
            return true; // En cas d'erreur, on autorise l'envoi
        }
    }
}

// Script d'exécution si appelé directement
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $alertManager = new AlertManager($pdo);
    $alerts = $alertManager->checkAndSendAlerts();
    
    echo json_encode([
        'success' => true,
        'alerts_found' => count($alerts),
        'timestamp' => date('c')
    ]);
}
?>
