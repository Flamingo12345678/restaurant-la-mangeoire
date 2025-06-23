<?php
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();

$id = intval($_GET['id'] ?? 0);
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF suppression paiement');
    header('Location: paiements.php');
    exit;
  }
  // Vérification d'existence du paiement
  $check = $pdo->prepare("SELECT COUNT(*) FROM Paiements WHERE PaiementID=?");
  $check->execute([$id]);
  if ($check->fetchColumn() == 0) {
    set_message('Paiement inexistant ou déjà supprimé.', 'error');
    header('Location: paiements.php');
    exit;
  }
  try {
    $sql = "DELETE FROM Paiements WHERE PaiementID=?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$id]);
    if ($result) {
      set_message('Paiement supprimé.');
      log_admin_action('Suppression paiement', "ID: $id");
    } else {
      set_message('Erreur lors de la suppression.', 'error');
      log_admin_action('Erreur suppression paiement', "ID: $id");
    }
    header('Location: paiements.php');
    exit;
  } catch (PDOException $e) {
    set_message('Erreur base de données.', 'error');
    log_admin_action('Erreur PDO suppression paiement', $e->getMessage());
    header('Location: paiements.php');
    exit;
  }
} else {
  echo "ID invalide.";
}
