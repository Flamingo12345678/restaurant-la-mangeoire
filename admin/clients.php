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
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="logo">Clients</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
        <li><a href="clients.php" class="active"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
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
      <div style="padding:40px;">
        <h2 style="margin-bottom:32px;">Gestion des clients</h2>
        <?php if ($message) echo $message; ?>
        <div style="max-width:100%;margin-bottom:40px;background:#fff;border-radius:16px;box-shadow:0 2px 12px #0001;padding:32px 32px 24px 32px;">
          <form method="post" class="form-section" style="background:none;box-shadow:none;padding:0;margin-bottom:0;display:flex;gap:24px;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;">
            <input type="text" name="nom" placeholder="Nom" required style="flex:1 1 180px;min-width:120px;margin-bottom:0;">
            <input type="text" name="prenom" placeholder="Prénom" required style="flex:1 1 180px;min-width:120px;margin-bottom:0;">
            <input type="email" name="email" placeholder="Email" required style="flex:1 1 220px;min-width:140px;margin-bottom:0;">
            <input type="text" name="telephone" placeholder="Téléphone" style="flex:1 1 160px;min-width:120px;margin-bottom:0;">
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
                <th style="padding:18px 20px;">Email</th>
                <th style="padding:18px 20px;">Téléphone</th>
                <th style="padding:18px 20px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($clients as $c): ?>
                <tr style="background:#fff;">
                  <td style="padding:16px 20px;"><?= htmlspecialchars($c['ClientID']) ?></td>
                  <td style="padding:16px 20px;"><?= htmlspecialchars($c['Nom']) ?></td>
                  <td style="padding:16px 20px;"><?= htmlspecialchars($c['Prenom']) ?></td>
                  <td style="padding:16px 20px;"><?= htmlspecialchars($c['Email']) ?></td>
                  <td style="padding:16px 20px;"><?= htmlspecialchars($c['Telephone']) ?></td>
                  <td style="padding:16px 20px;text-align:center;">
                    <a href="?delete=<?= $c['ClientID'] ?>" onclick="return confirm('Supprimer ce client ?')" style="color:#c62828;font-size:1.3em;"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
</body>

</html>