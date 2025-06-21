<?php
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();

$id = intval($_GET['id'] ?? 0);
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF suppression menu');
    header('Location: menus.php');
    exit;
  }
  // Vérification d'existence du menu
  $check = $conn->prepare("SELECT COUNT(*) FROM Menus WHERE MenuID=?");
  $check->execute([$id]);
  if ($check->fetchColumn() == 0) {
    set_message('Menu inexistant ou déjà supprimé.', 'error');
    header('Location: menus.php');
    exit;
  }
  try {
    $sql = "DELETE FROM Menus WHERE MenuID=?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$id]);
    if ($result) {
      set_message('Menu supprimé.');
      log_admin_action('Suppression menu', "ID: $id");
    } else {
      set_message('Erreur lors de la suppression.', 'error');
      log_admin_action('Erreur suppression menu', "ID: $id");
    }
    header('Location: menus.php');
    exit;
  } catch (PDOException $e) {
    set_message('Erreur base de données.', 'error');
    log_admin_action('Erreur PDO suppression menu', $e->getMessage());
    header('Location: menus.php');
    exit;
  }
} else {
  echo "ID invalide.";
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Supprimer un menu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .form-container {
      max-width: 400px;
      margin: 40px auto;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
      padding: 2rem 2.5rem 2.5rem 2.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .form-container h1 {
      margin-bottom: 1.5rem;
      color: #b01e28;
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
    }

    .form-container .alert {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.8rem 1rem;
      border-radius: 6px;
      font-size: 1rem;
      text-align: center;
    }

    .form-container .alert-success {
      background: #e6f9ed;
      color: #217a3c;
      border: 1px solid #b6e2c7;
    }

    .form-container .alert-error {
      background: #fdeaea;
      color: #b01e28;
      border: 1px solid #f5c2c7;
    }

    .back-link {
      display: inline-block;
      margin-bottom: 1.5rem;
      color: #b01e28;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: #8c181f;
      text-decoration: underline;
    }

    .delete-btn {
      background: #b01e28;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0.8rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 1rem;
    }

    .delete-btn:hover {
      background: #8c181f;
    }

    .admin-nav {
      width: 100vw;
      background: #b01e28;
      color: #fff;
      padding: 0.7rem 0;
      margin-bottom: 2rem;
      text-align: center;
      font-weight: 600;
      letter-spacing: 1px;
    }
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="form-container">
    <h1>Supprimer un menu</h1>
    <?php if ($id > 0): ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <p>Êtes-vous sûr de vouloir supprimer ce menu (ID <?= $id ?>) ?</p>
        <button type="submit" style="background:#b01e28;color:#fff;">Confirmer la suppression</button>
        <a href="menus.php" class="back-link">Annuler</a>
      </form>
      <?php display_message(); ?>
    <?php else: ?>
      <div class="alert alert-error">ID invalide.</div>
      <a href="menus.php" class="back-link">Retour</a>
    <?php endif; ?>
  </div>
</body>

</html>