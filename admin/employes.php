<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['prenom'], $_POST['poste'], $_POST['salaire'])) {
  $nom = trim($_POST['nom']);
  $prenom = trim($_POST['prenom']);
  $poste = trim($_POST['poste']);
  $salaire = floatval($_POST['salaire']);
  $date = $_POST['date_embauche'] ?? date('Y-m-d');
  if ($nom && $prenom && $poste && $salaire > 0) {
    $sql = "INSERT INTO Employes (Nom, Prenom, Poste, Salaire, DateEmbauche) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$nom, $prenom, $poste, $salaire, $date]);
    if ($result) {
      $message = 'Employé ajouté.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Employes WHERE EmployeID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Employé supprimé.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}
$employes = [];
$sql = "SELECT * FROM Employes ORDER BY EmployeID DESC";
$stmt = $conn->query($sql);
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $employes[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Employés</title>
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
  <h1>Employés</h1>
  <a href="index.php">&larr; Retour admin</a>
  <?php if ($message): ?><div><?= $message ?></div><?php endif; ?>
  <h2>Ajouter un employé</h2>
  <form method="post" class="admin-form">
    <input type="text" name="nom" placeholder="Nom *" required maxlength="100">
    <input type="text" name="prenom" placeholder="Prénom *" required maxlength="100">
    <input type="text" name="poste" placeholder="Poste *" required maxlength="50">
    <input type="number" name="salaire" placeholder="Salaire *" min="0" step="0.01" required>
    <input type="date" name="date_embauche" placeholder="Date d'embauche" value="<?= date('Y-m-d') ?>">
    <button type="submit">Ajouter</button>
  </form>
  <h2>Liste des employés</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Poste</th>
        <th>Salaire</th>
        <th>Date embauche</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employes as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e['EmployeID']) ?></td>
          <td><?= htmlspecialchars($e['Nom']) ?></td>
          <td><?= htmlspecialchars($e['Prenom']) ?></td>
          <td><?= htmlspecialchars($e['Poste']) ?></td>
          <td><?= htmlspecialchars($e['Salaire']) ?></td>
          <td><?= htmlspecialchars($e['DateEmbauche']) ?></td>
          <td><a href="?delete=<?= $e['EmployeID'] ?>" onclick="return confirm('Supprimer cet employé ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>