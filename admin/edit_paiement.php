<?php
session_start();
require_once '../db_connexion.php';
require_once 'utils.php';
$message = '';
function log_admin_action($action, $details = '')
{
  $logfile = __DIR__ . '/../admin/admin_actions.log';
  $date = date('Y-m-d H:i:s');
  $user = $_SESSION['admin'] ?? 'inconnu';
  $entry = "[$date] [$user] $action $details\n";
  file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}
require_role('superadmin');
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $montant = floatval($_POST['montant'] ?? 0);
    $date = $_POST['date_paiement'] ?? '';
    $mode = trim($_POST['mode'] ?? '');
    if ($reservation_id > 0 && $montant > 0 && $date) {
      try {
        $sql = "UPDATE Paiements SET ReservationID=?, Montant=?, DatePaiement=?, ModePaiement=? WHERE PaiementID=?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$reservation_id, $montant, $date, $mode, $id]);
        if ($result) {
          $message = 'Paiement modifié.';
          log_admin_action('Modification paiement', "ID: $id, ReservationID: $reservation_id, Montant: $montant, Date: $date");
        } else {
          $message = 'Erreur lors de la modification.';
          log_admin_action('Erreur modification paiement', "ID: $id, ReservationID: $reservation_id, Montant: $montant, Date: $date");
        }
      } catch (PDOException $e) {
        $message = 'Erreur base de données.';
        log_admin_action('Erreur PDO modification paiement', $e->getMessage());
      }
    } else {
      $message = 'Champs invalides.';
    }
  }
  $sql = "SELECT * FROM Paiements WHERE PaiementID=?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);
  $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  $paiement = null;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Modifier un paiement</title>
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

    .form-container form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .form-container input {
      padding: 0.7rem 1rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: border 0.2s;
    }

    .form-container input:focus {
      border-color: #b01e28;
      outline: none;
    }

    .form-container button {
      background: #b01e28;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0.8rem 0;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .form-container button:hover {
      background: #8c181f;
    }

    .alert {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.8rem 1rem;
      border-radius: 6px;
      font-size: 1rem;
      text-align: center;
    }

    .alert-success {
      background: #e6f9ed;
      color: #217a3c;
      border: 1px solid #b6e2c7;
    }

    .alert-error {
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
  <div class="admin-nav">Administration – Modifier un paiement</div>
  <div class="form-container">
    <a href="paiements.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Modifier un paiement</h1>
    <?php if ($message): ?>
      <div class="alert <?= strpos($message, 'modifié') !== false ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <?php if ($paiement): ?>
      <form method="post" autocomplete="off">
        <input type="number" name="reservation_id" value="<?= htmlspecialchars($paiement['ReservationID']) ?>" placeholder="ID réservation" required>
        <input type="number" name="montant" value="<?= htmlspecialchars($paiement['Montant']) ?>" step="0.01" min="0" placeholder="Montant" required>
        <input type="date" name="date_paiement" value="<?= htmlspecialchars($paiement['DatePaiement']) ?>" required>
        <input type="text" name="mode" value="<?= htmlspecialchars($paiement['ModePaiement']) ?>" placeholder="Mode de paiement">
        <button type="submit">Enregistrer</button>
      </form>
    <?php else: ?>
      <div class="alert alert-error">Paiement introuvable.</div>
    <?php endif; ?>
  </div>
</body>

</html>