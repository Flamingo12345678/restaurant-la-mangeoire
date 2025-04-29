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
<<<<<<< HEAD
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
=======
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Segoe UI', Arial, sans-serif;
    }

    .sidebar {
      background: #1a237e;
      color: #fff;
      width: 240px;
      min-height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      z-index: 10;
    }

    .sidebar .logo {
      font-size: 2rem;
      font-weight: bold;
      padding: 32px 0 24px 0;
      text-align: center;
      letter-spacing: 2px;
      color: #fff;
    }

    .sidebar nav ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar nav ul li {
      margin: 0;
    }

    .sidebar nav ul li a {
      display: flex;
      align-items: center;
      color: #fff;
      text-decoration: none;
      padding: 16px 32px;
      font-size: 1.1rem;
      transition: background 0.2s;
      border-left: 4px solid transparent;
    }

    .sidebar nav ul li a.active,
    .sidebar nav ul li a:hover {
      background: #283593;
      border-left: 4px solid #42a5f5;
      color: #42a5f5;
    }

    .sidebar nav ul li a i {
      margin-right: 12px;
      font-size: 1.3rem;
    }

    .main-content {
      margin-left: 240px;
      min-height: 100vh;
      background: #f8f9fa;
      transition: margin-left 0.2s;
    }

    @media (max-width: 900px) {
      .main-content {
        margin-left: 0;
      }

      .sidebar {
        position: relative;
        width: 100%;
        flex-direction: row;
        height: auto;
      }
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
    }
  </style>
</head>

<body>
<<<<<<< HEAD
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
=======
  <div class="sidebar">
    <div class="logo">Employés</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
        <li><a href="clients.php"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
        <li><a href="employes.php" class="active"><i class="bi bi-person-badge"></i> Employés</a></li>
        <li><a href="menus.php"><i class="bi bi-list"></i> Menus</a></li>
        <li><a href="paiements.php"><i class="bi bi-credit-card"></i> Paiements</a></li>
        <li><a href="reservations.php"><i class="bi bi-calendar-check"></i> Réservations</a></li>
        <li><a href="tables.php"><i class="bi bi-table"></i> Tables</a></li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <div class="main-content">
    <div class="topbar">
      <div class="icons">
        <img src="../assets/img/favcon.jpeg" alt="Profil" style="width:50px;height:50px;border-radius:50%;background:#eee;">
      </div>
    </div>
    <div style="padding:40px;">
      <!-- Suppression du bouton retour à l'accueil -->
      <h2 style="margin-bottom:32px;">Gestion des employés</h2>
      <?php if ($message) echo $message; ?>
      <div style="max-width:100%;margin-bottom:40px;background:#fff;border-radius:16px;box-shadow:0 2px 12px #0001;padding:32px 32px 24px 32px;">
        <form method="post" class="form-section" style="background:none;box-shadow:none;padding:0;margin-bottom:0;display:flex;gap:24px;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;">
          <input type="text" name="nom" placeholder="Nom" required style="flex:1 1 180px;min-width:120px;margin-bottom:0;">
          <input type="text" name="prenom" placeholder="Prénom" required style="flex:1 1 180px;min-width:120px;margin-bottom:0;">
          <input type="text" name="poste" placeholder="Poste" required style="flex:1 1 180px;min-width:120px;margin-bottom:0;">
          <input type="number" name="salaire" placeholder="Salaire" required style="flex:1 1 120px;min-width:100px;margin-bottom:0;">
          <input type="date" name="date_embauche" placeholder="Date d'embauche" style="flex:1 1 180px;min-width:140px;margin-bottom:0;">
          <button type="submit" style="flex:0 0 160px;width:160px;background:#182a7e;color:#fff;font-weight:bold;font-size:1.1em;padding:12px 0;border-radius:8px;">Ajouter</button>
        </form>
      </div>
      <div style="overflow-x:auto;width:100%;">
        <table class="admin-table" style="width:100%;min-width:900px;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px #0001;background:#fff;">
          <thead style="background:#f5f5f5;">
            <tr>
              <th style="padding:18px 20px;">ID</th>
              <th style="padding:18px 20px;">Nom</th>
              <th style="padding:18px 20px;">Prénom</th>
              <th style="padding:18px 20px;">Poste</th>
              <th style="padding:18px 20px;">Salaire</th>
              <th style="padding:18px 20px;">Date embauche</th>
              <th style="padding:18px 20px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($employes as $e): ?>
              <tr style="background:#fff;">
                <td style="padding:16px 20px;"><?= htmlspecialchars($e['EmployeID']) ?></td>
                <td style="padding:16px 20px;"><?= htmlspecialchars($e['Nom']) ?></td>
                <td style="padding:16px 20px;"><?= htmlspecialchars($e['Prenom']) ?></td>
                <td style="padding:16px 20px;"><?= htmlspecialchars($e['Poste']) ?></td>
                <td style="padding:16px 20px;"><?= htmlspecialchars($e['Salaire']) ?></td>
                <td style="padding:16px 20px;"><?= htmlspecialchars($e['DateEmbauche']) ?></td>
                <td style="padding:16px 20px;text-align:center;">
                  <a href="?delete=<?= $e['EmployeID'] ?>" onclick="return confirm('Supprimer cet employé ?')" style="color:#c62828;font-size:1.3em;"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
</body>

</html>