<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';

// Gestion de l'ajout d'un client
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['prenom'], $_POST['email'])) {
  $nom = trim($_POST['nom']);
  $prenom = trim($_POST['prenom']);
  $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
  $tel = trim($_POST['telephone'] ?? '');
  if ($nom && $prenom && $email) {
    $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$nom, $prenom, $email, $tel]);
    if ($result) {
      $message = '<span class="success-message">Client ajouté avec succès.</span>';
    } else {
      $message = '<span class="error-message">Erreur lors de l\'ajout.</span>';
    }
  } else {
    $message = '<span class="error-message">Champs invalides.</span>';
  }
}
// Suppression d'un client
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Clients WHERE ClientID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = '<span class="success-message">Client supprimé.</span>';
  } else {
    $message = '<span class="error-message">Erreur lors de la suppression.</span>';
  }
}
// Liste des clients
$clients = [];
$sql = "SELECT * FROM Clients ORDER BY ClientID DESC";
$stmt = $conn->query($sql);
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $clients[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Clients</title>
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
  <h1>Clients</h1>
  <a href="index.php">&larr; Retour admin</a>
  <?php if ($message): ?><div><?= $message ?></div><?php endif; ?>
  <h2>Ajouter un client</h2>
  <form method="post" class="admin-form">
    <input type="text" name="nom" placeholder="Nom *" required maxlength="100">
    <input type="text" name="prenom" placeholder="Prénom *" required maxlength="100">
    <input type="email" name="email" placeholder="Email *" required maxlength="100">
    <input type="text" name="telephone" placeholder="Téléphone" maxlength="20">
    <button type="submit">Ajouter</button>
  </form>
  <h2>Liste des clients</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($clients as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['ClientID']) ?></td>
          <td><?= htmlspecialchars($c['Nom']) ?></td>
          <td><?= htmlspecialchars($c['Prenom']) ?></td>
          <td><?= htmlspecialchars($c['Email']) ?></td>
          <td><?= htmlspecialchars($c['Telephone']) ?></td>
          <td><a href="?delete=<?= $c['ClientID'] ?>" onclick="return confirm('Supprimer ce client ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>