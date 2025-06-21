<?php
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();

$id = intval($_GET['id'] ?? 0);
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF suppression commande');
    header('Location: commandes.php');
    exit;
  }
  // Vérification d'existence de la commande
  $check = $conn->prepare("SELECT COUNT(*) FROM Commandes WHERE CommandeID=?");
  $check->execute([$id]);
  if ($check->fetchColumn() == 0) {
    set_message('Commande inexistante ou déjà supprimée.', 'error');
    header('Location: commandes.php');
    exit;
  }
  try {
    $sql = "DELETE FROM Commandes WHERE CommandeID=?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$id]);
    if ($result) {
      set_message('Commande supprimée.');
      log_admin_action('Suppression commande', "ID: $id");
    } else {
      set_message('Erreur lors de la suppression.', 'error');
      log_admin_action('Erreur suppression commande', "ID: $id");
    }
    header('Location: commandes.php');
    exit;
  } catch (PDOException $e) {
    set_message('Erreur base de données.', 'error');
    log_admin_action('Erreur PDO suppression commande', $e->getMessage());
    header('Location: commandes.php');
    exit;
  }
} else {
  echo "ID invalide.";
}
