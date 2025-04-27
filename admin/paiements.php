<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['montant'], $_POST['date_paiement'])) {
  $reservation_id = intval($_POST['reservation_id']);
  $montant = floatval($_POST['montant']);
  $date = $_POST['date_paiement'];
  $mode = trim($_POST['mode'] ?? '');
  if ($reservation_id > 0 && $montant > 0 && $date) {
    $sql = "INSERT INTO Paiements (ReservationID, Montant, DatePaiement, ModePaiement) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$reservation_id, $montant, $date, $mode]);
    if ($result) {
      $message = 'Paiement ajouté.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Paiements WHERE PaiementID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Paiement supprimé.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}
$paiements = [];
$sql = "SELECT * FROM Paiements ORDER BY PaiementID DESC";
$stmt = $conn->query($sql);
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $paiements[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Paiements</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .success-message {
      color: #2e7d32;
      font-weight: bold;
    }

    .error-message {
      color: #c62828;
      font-weight: bold;
    }

    .admin-table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 1em;
      background: #fff;
      box-shadow: 0 2px 8px #0001;
    }

    .admin-table th,
    .admin-table td {
      border: 1px solid #ddd;
      padding: 8px 12px;
      text-align: left;
    }

    .admin-table th {
      background: #f5f5f5;
      font-weight: bold;
    }

    .admin-table tr:nth-child(even) {
      background: #fafafa;
    }

    .admin-table tr:hover {
      background: #f1f8e9;
    }

    @media (max-width: 700px) {

      .admin-table,
      .admin-table thead,
      .admin-table tbody,
      .admin-table th,
      .admin-table td,
      .admin-table tr {
        display: block;
      }

      .admin-table tr {
        margin-bottom: 1em;
      }

      .admin-table td,
      .admin-table th {
        padding: 10px 5px;
        border: none;
        border-bottom: 1px solid #eee;
      }

      .admin-table th {
        background: #e0e0e0;
      }
    }

    .admin-form input,
    .admin-form button {
      margin: 0.2em 0.5em 0.2em 0;
      padding: 0.5em;
      border-radius: 4px;
      border: 1px solid #bbb;
    }

    .admin-form button {
      background: #388e3c;
      color: #fff;
      border: none;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.2s;
    }

    .admin-form button:hover {
      background: #2e7d32;
    }

    .admin-form {
      margin-bottom: 1.5em;
      background: #f9fbe7;
      padding: 1em;
      border-radius: 8px;
      box-shadow: 0 1px 4px #0001;
      max-width: 500px;
    }
  </style>
</head>

<body>
  <h1>Paiements</h1>
  <a href="index.php">&larr; Retour admin</a>
  <?php if ($message): ?><div><?= $message ?></div><?php endif; ?>
  <h2>Ajouter un paiement</h2>
  <form method="post" class="admin-form">
    <input type="number" name="reservation_id" placeholder="ID réservation *" required min="1">
    <input type="number" name="montant" placeholder="Montant *" step="0.01" min="0" required>
    <input type="date" name="date_paiement" placeholder="Date de paiement *" required>
    <input type="text" name="mode" placeholder="Mode de paiement" maxlength="50">
    <button type="submit">Ajouter</button>
  </form>
  <h2>Liste des paiements</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Réservation</th>
        <th>Montant</th>
        <th>Date</th>
        <th>Mode</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($paiements as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['PaiementID']) ?></td>
          <td><?= htmlspecialchars($p['ReservationID']) ?></td>
          <td><?= htmlspecialchars($p['Montant']) ?></td>
          <td><?= htmlspecialchars($p['DatePaiement']) ?></td>
          <td><?= htmlspecialchars($p['ModePaiement']) ?></td>
          <td><a href="?delete=<?= $p['PaiementID'] ?>" onclick="return confirm('Supprimer ce paiement ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>