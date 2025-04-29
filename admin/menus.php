<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
<<<<<<< HEAD
<<<<<<< HEAD
require_once '../db_connexion.php'; // Assurez-vous que $conn est un objet mysqli
=======
require_once '../db_connexion.php';
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
=======
require_once '../db_connexion.php';
>>>>>>> nouvelle_modif_railway

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
<<<<<<< HEAD
<<<<<<< HEAD
  <title>Gestion des menus</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
=======
=======
>>>>>>> nouvelle_modif_railway
  <title>Menus</title>
  <link rel="stylesheet" href="../assets/css/main.css">
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
    }

<<<<<<< HEAD
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
=======
>>>>>>> nouvelle_modif_railway
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

<<<<<<< HEAD
<<<<<<< HEAD
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
=======
>>>>>>> nouvelle_modif_railway
    .form-section {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px #0001;
      padding: 32px;
      margin-bottom: 32px;
      max-width: 500px;
    }

    .form-section input,
    .form-section button {
      margin-bottom: 16px;
      width: 100%;
      padding: 10px 14px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      font-size: 1rem;
    }

    .form-section button {
      background: #1a237e;
      color: #fff;
      font-weight: bold;
      border: none;
      cursor: pointer;
      transition: background 0.2s;
    }

    .form-section button:hover {
      background: #283593;
<<<<<<< HEAD
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
=======
>>>>>>> nouvelle_modif_railway
    }
  </style>
</head>

<body>
<<<<<<< HEAD
<<<<<<< HEAD
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
=======
=======
>>>>>>> nouvelle_modif_railway
  <div class="sidebar">
    <div class="logo">Menus</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
        <li><a href="clients.php"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
        <li><a href="employes.php"><i class="bi bi-person-badge"></i> Employés</a></li>
        <li><a href="menus.php" class="active"><i class="bi bi-list"></i> Menus</a></li>
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
      <h2>Gestion des menus</h2>
      <?php if ($message) echo $message; ?>
      <!-- Formulaire d'ajout -->
      <form method="post" style="margin-bottom:24px;" class="form-section">
        <input type="text" name="nom" placeholder="Nom du menu" required>
        <input type="number" name="prix" placeholder="Prix" required step="0.01">
        <button type="submit">Ajouter</button>
      </form>
      <!-- Tableau des menus -->
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prix</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menus as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['MenuID']) ?></td>
              <td><?= htmlspecialchars($m['NomItem']) ?></td>
              <td><?= htmlspecialchars($m['Prix']) ?></td>
              <td><a href="?delete=<?= $m['MenuID'] ?>" onclick="return confirm('Supprimer ce menu ?')"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<<<<<<< HEAD
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
=======
>>>>>>> nouvelle_modif_railway
</body>

</html>