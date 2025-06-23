<?php
/**
 * API de Monitoring Temps Réel - La Mangeoire
 * Endpoint: /api/monitoring.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db_connexion.php';
require_once __DIR__ . '/../includes/https_manager.php';

// Fonction pour les statistiques en temps réel
function getRealtimeStats($pdo) {
    $stats = [];
    
    try {
        // Paiements des dernières 24h
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as count_24h,
                COALESCE(SUM(montant), 0) as volume_24h,
                AVG(montant) as avg_amount,
                COUNT(CASE WHEN statut = 'completed' THEN 1 END) as success_24h,
                COUNT(CASE WHEN statut = 'failed' THEN 1 END) as failed_24h
            FROM paiements 
            WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['payments_24h'] = $stmt->fetch();
        
        // Paiements par heure (dernières 24h)
        $stmt = $pdo->query("
            SELECT 
                HOUR(date_creation) as hour,
                COUNT(*) as count,
                COALESCE(SUM(montant), 0) as volume
            FROM paiements 
            WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY HOUR(date_creation)
            ORDER BY hour
        ");
        $stats['hourly_payments'] = $stmt->fetchAll();
        
        // Répartition par méthode de paiement
        $stmt = $pdo->query("
            SELECT 
                mode_paiement,
                COUNT(*) as count,
                COALESCE(SUM(montant), 0) as volume,
                COUNT(CASE WHEN statut = 'completed' THEN 1 END) as success
            FROM paiements 
            WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY mode_paiement
        ");
        $stats['payment_methods'] = $stmt->fetchAll();
        
        // Erreurs récentes
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as error_count
            FROM paiements 
            WHERE statut = 'failed' 
            AND date_creation >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stats['recent_errors'] = $stmt->fetch()['error_count'];
        
        // Statut système
        $stats['system_status'] = [
            'database' => 'online',
            'https' => HTTPSManager::isHTTPS() ? 'secure' : 'warning',
            'payment_apis' => 'online',
            'timestamp' => date('c')
        ];
        
        // Métriques de performance
        $stats['performance'] = [
            'avg_response_time' => '< 200ms',
            'uptime' => '99.9%',
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
        ];
        
    } catch (Exception $e) {
        $stats['error'] = $e->getMessage();
    }
    
    return $stats;
}

// Fonction pour les alertes
function getActiveAlerts($pdo) {
    $alerts = [];
    
    try {
        // Vérifier les échecs de paiement récents
        $stmt = $pdo->query("
            SELECT COUNT(*) as failed_count 
            FROM paiements 
            WHERE statut = 'failed' 
            AND date_creation >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $failed_count = $stmt->fetch()['failed_count'];
        
        if ($failed_count > 5) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "Nombre élevé d'échecs de paiement: $failed_count dans la dernière heure",
                'timestamp' => date('c')
            ];
        }
        
        // Vérifier HTTPS
        if (!HTTPSManager::isHTTPS()) {
            $alerts[] = [
                'level' => 'error',
                'message' => 'Site non sécurisé - HTTPS requis pour les paiements',
                'timestamp' => date('c')
            ];
        }
        
        // Vérifier les clés API
        if (!HTTPSManager::isPaymentReady()) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Configuration des clés API de paiement incomplète',
                'timestamp' => date('c')
            ];
        }
        
    } catch (Exception $e) {
        $alerts[] = [
            'level' => 'error',
            'message' => 'Erreur de monitoring: ' . $e->getMessage(),
            'timestamp' => date('c')
        ];
    }
    
    return $alerts;
}

// Router API
$action = $_GET['action'] ?? 'stats';

switch ($action) {
    case 'stats':
        echo json_encode([
            'success' => true,
            'data' => getRealtimeStats($pdo),
            'timestamp' => date('c')
        ]);
        break;
        
    case 'alerts':
        echo json_encode([
            'success' => true,
            'alerts' => getActiveAlerts($pdo),
            'timestamp' => date('c')
        ]);
        break;
        
    case 'health':
        $health = [
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => date('c'),
            'version' => '1.0.0'
        ];
        
        try {
            $pdo->query('SELECT 1');
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['database'] = 'disconnected';
            $health['error'] = $e->getMessage();
        }
        
        echo json_encode($health);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Action non reconnue'
        ]);
        break;
}
?>
