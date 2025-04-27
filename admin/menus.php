<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php'; // Assurez-vous que $conn est un objet mysqli

// Gestion de l'ajout d'un menu
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['prix'])) {
  $nom = trim($_POST['nom']);
  $prix = floatval($_POST['prix']);
  if ($nom && $prix > 0) {
    $sql = "INSERT INTO Menus (NomItem, Prix) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      if ($stmt->execute([$nom, $prix])) {
        $message = 'Menu ajouté avec succès.';
      } else {
        $message = 'Erreur lors de l\'ajout.';
      }
    } else {
      $message = 'Erreur lors de la préparation.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}

// Gestion de la suppression d'un menu
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Menus WHERE MenuID = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if ($stmt->execute([$id])) {
      $message = 'Menu supprimé.';
    } else {
      $message = 'Erreur lors de la suppression.';
    }
  } else {
    $message = 'Erreur lors de la préparation.';
  }
}

// Récupération de la liste des menus
$menus = [];
$sql = "SELECT MenuID, NomItem, Prix FROM Menus ORDER BY MenuID DESC";
$stmt = $conn->prepare($sql);
if ($stmt && $stmt->execute()) {
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Gestion des menus</title>
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
  <h1>Menus</h1>
  <a href="index.php">&larr; Retour admin</a>
  <?php if ($message): ?>
    <div class="<?= strpos($message, 'succès') !== false ? 'success-message' : 'error-message' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <h2>Ajouter un menu</h2>
  <form method="post" class="admin-form">
    <input type="text" name="nom" placeholder="Nom du menu *" required maxlength="100">
    <input type="number" name="prix" placeholder="Prix *" step="0.01" min="0" required>
    <button type="submit">Ajouter</button>
  </form>
  <h2>Liste des menus</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Prix</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($menus as $menu): ?>
        <tr>
          <td><?= htmlspecialchars($menu['MenuID']) ?></td>
          <td><?= htmlspecialchars($menu['NomItem']) ?></td>
          <td><?= htmlspecialchars($menu['Prix']) ?> XAF</td>
          <td><a href="?delete=<?= $menu['MenuID'] ?>" onclick="return confirm('Supprimer ce menu ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>