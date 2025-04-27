<?php
session_start();
function log_admin_action($action, $details = '')
{
  $logfile = __DIR__ . '/../admin/admin_actions.log';
  $date = date('Y-m-d H:i:s');
  $user = $_SESSION['admin'] ?? 'inconnu';
  $entry = "[$date] [$user] $action $details\n";
  file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
  // Contrôle de droits (préparation multi-niveaux)
}
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
  try {
    $sql = "DELETE FROM Employes WHERE EmployeID=?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$id]);
    if ($result) {
      log_admin_action('Suppression employé', "ID: $id");
      header('Location: employes.php?msg=supprime');
      exit;
    } else {
      echo "Erreur lors de la suppression.";
      log_admin_action('Erreur suppression employé', "ID: $id");
    }
  } catch (PDOException $e) {
    echo "Erreur base de données.";
    log_admin_action('Erreur PDO suppression employé', $e->getMessage());
  }
} else {
  echo "ID invalide.";
}
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
  <div class="admin-nav">Administration – Supprimer un employé</div>
  <div class="form-container">
    <a href="employes.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Supprimer un employé</h1>
    <?php if (isset($message) && $message): ?>
      <div class="alert alert-error"> <?= htmlspecialchars($message) ?> </div>
    <?php endif; ?>
    <?php if ($id > 0): ?>
      <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?');">
        <button type="submit" class="delete-btn">Confirmer la suppression</button>
      </form>
    <?php else: ?>
      <div class="alert alert-error">ID invalide.</div>
    <?php endif; ?>
  </div>
</body>

</html>