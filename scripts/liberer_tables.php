<?php
// Script à exécuter régulièrement (ex: cron) pour libérer automatiquement les tables dont la réservation est terminée
require_once __DIR__ . '/../db_connexion.php';

$now = date('Y-m-d H:i:s');
// On considère qu'une table est à libérer si aucune réservation future n'est en cours sur cette table
$sql = "SELECT t.TableID
        FROM TablesRestaurant t
        LEFT JOIN Reservations r ON t.TableID = r.TableID AND r.Statut = 'Réservée' AND r.DateReservation > ?
        WHERE t.Statut = 'Réservée'
        GROUP BY t.TableID
        HAVING COUNT(r.ReservationID) = 0";
$stmt = $conn->prepare($sql);
$stmt->execute([$now]);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
if ($tables) {
  $in = implode(',', array_fill(0, count($tables), '?'));
  $sql = "UPDATE TablesRestaurant SET Statut = 'Libre' WHERE TableID IN ($in)";
  $stmt2 = $conn->prepare($sql);
  $stmt2->execute($tables);
  echo count($tables) . " table(s) libérée(s).\n";
} else {
  echo "Aucune table à libérer.\n";
}
