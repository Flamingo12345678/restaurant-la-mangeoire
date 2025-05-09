<?php
// Fichier pour récupérer le montant total d'une commande via AJAX
require_once __DIR__ . '/../includes/common.php';
require_admin();
require_once '../db_connexion.php';

// Vérification des paramètres
if (isset($_GET['commande_id'])) {
  $commande_id = intval($_GET['commande_id']);
  
  // Vérifier que la commande existe
  $check_cmd = $conn->prepare("SELECT CommandeID FROM Commandes WHERE CommandeID = ?");
  $check_cmd->execute([$commande_id]);
  
  if ($check_cmd->rowCount() > 0) {
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
                CASE 
                  WHEN MontantTotal IS NOT NULL THEN MontantTotal 
                  WHEN PrixUnitaire IS NOT NULL THEN PrixUnitaire * Quantite
                  ELSE 0
                END as Total
              FROM Commandes
              WHERE CommandeID = ?";
      
      $stmt = $conn->prepare($sql);
      $stmt->execute([$commande_id]);
      $total = $stmt->fetchColumn() ?: 0;
    } else {
      // Fallback: Calculer à partir des prix des menus
      $sql = "SELECT m.Prix * c.Quantite as Total
              FROM Commandes c
              JOIN Menus m ON c.MenuID = m.MenuID
              WHERE c.CommandeID = ?";
      
      $stmt = $conn->prepare($sql);
      $stmt->execute([$commande_id]);
      $total = $stmt->fetchColumn() ?: 0;
    }
    
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
  'message' => 'Commande introuvable'
]);
