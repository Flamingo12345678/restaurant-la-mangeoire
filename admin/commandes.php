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
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="logo">Commandes</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
        <li><a href="clients.php"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php" class="active"><i class="bi bi-basket"></i> Commandes</a></li>
        <li><a href="employes.php"><i class="bi bi-person-badge"></i> Employés</a></li>
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
      <h2>Gestion des commandes</h2>
      <?php if ($message) echo $message; ?>
      <!-- Formulaire d'ajout -->
      <form method="post" style="margin-bottom:24px;" class="form-section">
        <input type="number" name="reservation_id" placeholder="ID Réservation" required>
        <input type="number" name="menu_id" placeholder="ID Menu" required>
        <input type="number" name="quantite" placeholder="Quantité" required>
        <button type="submit">Ajouter</button>
      </form>
      <!-- Tableau des commandes -->
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Réservation</th>
            <th>Menu</th>
            <th>Quantité</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['CommandeID']) ?></td>
              <td><?= htmlspecialchars($c['ReservationID']) ?></td>
              <td><?= htmlspecialchars($c['MenuID']) ?></td>
              <td><?= htmlspecialchars($c['Quantite']) ?></td>
              <td><a href="?delete=<?= $c['CommandeID'] ?>" onclick="return confirm('Supprimer cette commande ?')"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>