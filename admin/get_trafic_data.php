<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
  header('HTTP/1.1 403 Forbidden');
  echo json_encode(['error' => 'Accès non autorisé']);
  exit;
}

require_once '../db_connexion.php';

// Récupérer les 7 derniers jours de réservations
$sql = "SELECT DATE(DateReservation) as jour, COUNT(*) as nombre 
       FROM Reservations 
       WHERE DateReservation >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
       GROUP BY DATE(DateReservation)
       ORDER BY jour";

$stmt = $conn->query($sql);
$trafficArray = [];
$labels = [];

// Créer un tableau avec tous les jours des 7 derniers jours
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $labels[] = date('d/m', strtotime($date));
  $trafficArray[$date] = 0;
}

// Remplir avec les données réelles
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $trafficArray[$row['jour']] = (int)$row['nombre'];
  }
}

// Préparer la réponse
$response = [
  'traffic' => array_values($trafficArray),
  'labels' => $labels
];

header('Content-Type: application/json');
echo json_encode($response);