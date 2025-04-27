<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero'], $_POST['capacite'])) {
  $numero = intval($_POST['numero']);
  $capacite = intval($_POST['capacite']);
  if ($numero > 0 && $capacite > 0) {
    $sql = "INSERT INTO Tables (NumeroTable, Capacite) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$numero, $capacite]);
    if ($result) {
      $message = 'Table ajoutée.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Tables WHERE TableID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Table supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}
$tables = [];
$sql = "SELECT * FROM Tables ORDER BY TableID DESC";
try {
  $stmt = $conn->query($sql);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[] = $row;
  }
} catch (PDOException $e) {
  $message = 'Erreur lors de la récupération des tables.';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Tables</title>
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
  <h1>Tables</h1>
  <a href="index.php">&larr; Retour admin</a>
  <?php if ($message): ?><div><?= $message ?></div><?php endif; ?>
  <h2>Ajouter une table</h2>
  <form method="post" class="admin-form">
    <input type="number" name="numero" placeholder="Numéro de table *" required min="1">
    <input type="number" name="capacite" placeholder="Capacité *" min="1" required>
    <button type="submit">Ajouter</button>
  </form>
  <h2>Liste des tables</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Numéro</th>
        <th>Capacité</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tables as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['TableID']) ?></td>
          <td><?= htmlspecialchars($t['NumeroTable']) ?></td>
          <td><?= htmlspecialchars($t['Capacite']) ?></td>
          <td><a href="?delete=<?= $t['TableID'] ?>" onclick="return confirm('Supprimer cette table ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>