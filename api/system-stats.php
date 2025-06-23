<?php
/**
 * API pour récupérer les statistiques système en temps réel
 * Utilisée par le dashboard-admin.php pour les mises à jour AJAX
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Vérification d'accès admin
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

require_once '../db_connexion.php';
require_once '../includes/system-stats.php';

try {
    // Récupérer les statistiques
    $stats = getSystemStats();
    $services = checkSystemServices($conn);
    $uptime = getSystemUptime();
    $recent_events = getRecentSystemEvents($conn, 5);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'services' => $services,
        'uptime' => $uptime,
        'events' => $recent_events
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statistiques',
        'message' => $e->getMessage()
    ]);
}
?>
