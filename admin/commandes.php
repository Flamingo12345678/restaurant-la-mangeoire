<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['menu_id'], $_POST['quantite'])) {
  $reservation_id = intval($_POST['reservation_id']);
  $menu_id = intval($_POST['menu_id']);
  $quantite = intval($_POST['quantite']);
  if ($reservation_id > 0 && $menu_id > 0 && $quantite > 0) {
    $sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$reservation_id, $menu_id, $quantite]);
    if ($result) {
      $message = 'Commande ajoutée.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Commandes WHERE CommandeID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Commande supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}
$commandes = [];
$sql = "SELECT * FROM Commandes ORDER BY CommandeID DESC";
$stmt = $conn->query($sql);
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $commandes[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Commandes</title>
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
  <h1>Commandes</h1>
  <a href="index.php">&larr; Retour admin</a>
  <?php if ($message): ?><div><?= $message ?></div><?php endif; ?>
  <h2>Ajouter une commande</h2>
  <form method="post" class="admin-form">
    <input type="number" name="reservation_id" placeholder="ID réservation *" required min="1">
    <input type="number" name="menu_id" placeholder="ID menu *" required min="1">
    <input type="number" name="quantite" placeholder="Quantité *" min="1" required>
    <button type="submit">Ajouter</button>
  </form>
  <h2>Liste des commandes</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Réservation</th>
        <th>Menu</th>
        <th>Quantité</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($commandes as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['CommandeID']) ?></td>
          <td><?= htmlspecialchars($c['ReservationID']) ?></td>
          <td><?= htmlspecialchars($c['MenuID']) ?></td>
          <td><?= htmlspecialchars($c['Quantite']) ?></td>
          <td><a href="?delete=<?= $c['CommandeID'] ?>" onclick="return confirm('Supprimer cette commande ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>