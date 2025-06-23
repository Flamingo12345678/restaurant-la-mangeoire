<?php

require_once 'check_admin_access.php';
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();

$id = intval($_GET['id'] ?? 0);
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF suppression employé');
    header('Location: employes.php');
    exit;
  }
  // Vérification d'existence de l'employé
  $check = $conn->prepare("SELECT COUNT(*) FROM Employes WHERE EmployeID=?");
  $check->execute([$id]);
  if ($check->fetchColumn() == 0) {
    set_message('Employé inexistant ou déjà supprimé.', 'error');
    header('Location: employes.php');
    exit;
  }
  try {
    $sql = "DELETE FROM Employes WHERE EmployeID=?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$id]);
    if ($result) {
      set_message('Employé supprimé.');
      log_admin_action('Suppression employé', "ID: $id");
    } else {
      set_message('Erreur lors de la suppression.', 'error');
      log_admin_action('Erreur suppression employé', "ID: $id");
    }
    header('Location: employes.php');
    exit;
  } catch (PDOException $e) {
    set_message('Erreur base de données.', 'error');
    log_admin_action('Erreur PDO suppression employé', $e->getMessage());
    header('Location: employes.php');
    exit;
  }
} else {
  echo "ID invalide.";
}

// Gestion centralisée des messages - moved to common.php
// function set_message and display_message are now defined in common.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Supprimer un employé</title>
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

    .form-container .alert-danger {
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
  <link rel="stylesheet" href="../assets/css/admin-inline-fixes.css">
</head>

<body class="admin-body">
  <div class="form-container">
    <h1>Supprimer un employé</h1>
    <?php if ($id > 0): ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <p>Êtes-vous sûr de vouloir supprimer cet employé (ID <?= $id ?>) ?</p>
        <button type="submit" class="admin-delete-btn">Confirmer la suppression</button>
        <a href="employes.php" class="back-link">Annuler</a>
      </form>
      <?php display_message(); ?>
    <?php else: ?>
      <div class="alert alert-danger">ID invalide.</div>
      <a href="employes.php" class="back-link">Retour</a>
    <?php endif; ?>
  </div>
  <script src="../assets/js/admin-remove-inline-styles.js"></script>
</body>

</html>