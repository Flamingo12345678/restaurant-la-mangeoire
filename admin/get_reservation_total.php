<?php
// Fichier pour récupérer le montant total des commandes d'une réservation via AJAX
require_once __DIR__ . '/../includes/common.php';
require_admin();
require_once '../db_connexion.php';

// Fonction pour calculer le montant total des commandes par réservation
function get_total_commandes_by_reservation($conn, $reservation_id) {
  // Vérifier si les colonnes existent
  function checkColumnExists($conn, $tableName, $columnName) {
    try {
        $stmt = $conn->prepare("SELECT $columnName FROM $tableName LIMIT 1");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column") !== false) {
            return false;
        }
        return false;
    }
  }
  
  $hasPrixUnitaire = checkColumnExists($conn, 'Commandes', 'PrixUnitaire');
  $hasMontantTotal = checkColumnExists($conn, 'Commandes', 'MontantTotal');
  
  if ($hasPrixUnitaire && $hasMontantTotal) {
    // Si les colonnes existent, utiliser les valeurs stockées
    $sql = "SELECT 
              SUM(CASE 
                  WHEN MontantTotal IS NOT NULL THEN MontantTotal 
                  WHEN PrixUnitaire IS NOT NULL THEN PrixUnitaire * Quantite
                  ELSE 0
              END) as Total
            FROM Commandes
            WHERE ReservationID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reservation_id]);
    $totalFromCommandes = $stmt->fetchColumn() ?: 0;
    
    if ($totalFromCommandes > 0) {
      return $totalFromCommandes;
    }
  }
  
  // Fallback: calculer à partir des prix des menus
  $sql = "SELECT SUM(m.Prix * c.Quantite) as Total
          FROM Commandes c
          JOIN Menus m ON c.MenuID = m.MenuID
          WHERE c.ReservationID = ?";
  
  $stmt = $conn->prepare($sql);
  $stmt->execute([$reservation_id]);
  return $stmt->fetchColumn() ?: 0;
}

// Vérification des paramètres
if (isset($_GET['reservation_id'])) {
  $reservation_id = intval($_GET['reservation_id']);
  
  // Vérifier que la réservation existe
  $check_res = $conn->prepare("SELECT ReservationID FROM Reservations WHERE ReservationID = ?");
  $check_res->execute([$reservation_id]);
  
  if ($check_res->rowCount() > 0) {
    // Récupérer le montant total des commandes
    $total = get_total_commandes_by_reservation($conn, $reservation_id);
    
    // Retourner le résultat au format JSON
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'total' => $total
    ]);
    exit;
  }
}

// En cas d'erreur ou de paramètres manquants
header('Content-Type: application/json');
echo json_encode([
  'success' => false,
  'message' => 'Réservation introuvable ou aucune commande associée'
]);
