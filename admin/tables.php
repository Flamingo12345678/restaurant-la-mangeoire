<?php
define('CAPACITE_SALLE', 100);
require_once '../db_connexion.php';
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
$message = '';

// Initialisation par défaut pour éviter les warnings
$alerte = '';
$total_places = 0;
$places_restantes = 0;
$total_reserves = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero'], $_POST['capacite'])) {
  $numero = intval($_POST['numero']);
  $capacite = intval($_POST['capacite']);
  // Vérifier la capacité restante avant ajout
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $conn->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  $places_restantes = CAPACITE_SALLE - $total_places;
  if ($numero > 0 && $capacite > 0) {
    if ($capacite > $places_restantes) {
      $message = 'Impossible d\'ajouter cette table : capacité maximale de la salle atteinte.';
    } else {
      $sql = "INSERT INTO TablesRestaurant (NumeroTable, Capacite) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      $result = $stmt->execute([$numero, $capacite]);
      if ($result) {
        $message = 'Table ajoutée.';
      } else {
        $message = 'Erreur lors de l\'ajout.';
      }
    }
  } else {
    $message = 'Champs invalides.';
  }
}
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM TablesRestaurant WHERE TableID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Table supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}
$tables = [];
$sql = "SELECT * FROM TablesRestaurant ORDER BY TableID DESC";
$stmt = $conn->query($sql);
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[] = $row;
  }
}

// Calcul dynamique des statistiques pour les cards
$sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
$stmt = $conn->query($sql);
$total_places = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_places = intval($row['total_places']);
}
// Correction : places restantes = capacité totale de la salle - personnes réservées à venir
$now = date('Y-m-d H:i:s');
$sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
$stmt = $conn->query($sql);
$total_places = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_places = intval($row['total_places']);
}
$sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$now]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_reserves = intval($row['total_reserves']);
$places_restantes = $total_places - $total_reserves;
// Card : nombre de tables disponibles (statut 'Libre')
$sql = "SELECT COUNT(*) AS nb_libres FROM TablesRestaurant WHERE Statut = 'Libre'";
$stmt = $conn->query($sql);
$nb_tables_libres = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $nb_tables_libres = intval($row['nb_libres']);
}
// Card : nombre de tables disponibles (statut 'Libre') et détail par type
$sql = "SELECT Capacite, COUNT(*) AS nb FROM TablesRestaurant WHERE Statut = 'Libre' GROUP BY Capacite ORDER BY Capacite ASC";
$stmt = $conn->query($sql);
$types_tables_libres = [];
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $types_tables_libres[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Tables</title>
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

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff;
      padding: 24px 40px 24px 40px;
      box-shadow: 0 2px 8px #0001;
      position: sticky;
      top: 0;
      z-index: 5;
    }

    .topbar .search input {
      border: 1px solid #e0e0e0;
      border-radius: 24px;
      padding: 8px 20px;
      font-size: 1rem;
      background: #f5f5f5;
      outline: none;
      width: 220px;
      transition: box-shadow 0.2s;
    }

    .topbar .icons {
      display: flex;
      align-items: center;
      gap: 18px;
    }

    .topbar .icons img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #eee;
      object-fit: cover;
      border: 2px solid #e0e0e0;
    }

    .dashboard-cards {
      display: flex;
      gap: 32px;
      margin-bottom: 32px;
      flex-wrap: wrap;
    }

    .dashboard-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 2px 8px #0001;
      padding: 28px 32px;
      min-width: 200px;
      flex: 1 1 200px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 12px;
    }

    .dashboard-card .card-title {
      font-size: 1.1rem;
      color: #757575;
      margin-bottom: 8px;
    }

    .dashboard-card .card-value {
      font-size: 2rem;
      font-weight: bold;
      color: #1a237e;
    }

    .admin-table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 1em;
      background: #fff;
      box-shadow: 0 2px 8px #0001;
      border-radius: 12px;
      overflow: hidden;
    }

    .admin-table th,
    .admin-table td {
      border: 1px solid #e0e0e0;
      padding: 12px 16px;
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
      background: #e3f2fd;
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

      .dashboard-cards {
        flex-direction: column;
        gap: 16px;
      }
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="logo">Tables</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
        <li><a href="clients.php"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
        <li><a href="employes.php"><i class="bi bi-person-badge"></i> Employés</a></li>
        <li><a href="menus.php"><i class="bi bi-list"></i> Menus</a></li>
        <li><a href="paiements.php"><i class="bi bi-credit-card"></i> Paiements</a></li>
        <li><a href="reservations.php"><i class="bi bi-calendar-check"></i> Réservations</a></li>
        <li><a href="tables.php" class="active"><i class="bi bi-table"></i> Tables</a></li>
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
      <h2 style="margin-bottom:32px;">Gestion des tables</h2>
      <?php if ($alerte) echo $alerte; ?>
      <?php if ($message) echo '<div class="success-message">' . $message . '</div>'; ?>
      <div class="dashboard-cards">
        <div class="dashboard-card">
          <div class="card-title">Places totales</div>
          <div class="card-value"><?php echo $total_places; ?></div>
        </div>
        <div class="dashboard-card">
          <div class="card-title">Places restantes</div>
          <div class="card-value"><?php echo $places_restantes; ?></div>
        </div>
        <div class="dashboard-card">
          <div class="card-title">Personnes réservées à venir</div>
          <div class="card-value"><?php echo $total_reserves; ?></div>
        </div>
        <div class="dashboard-card">
          <div class="card-title">Tables disponibles</div>
          <div class="card-value"><?php echo $nb_tables_libres; ?></div>
          <?php if (!empty($types_tables_libres)): ?>
            <div style="font-size:1em;color:#444;margin-top:8px;">
              <?php foreach ($types_tables_libres as $t): ?>
                <div><?= $t['nb'] ?> table<?= $t['nb'] > 1 ? 's' : '' ?> de <?= $t['Capacite'] ?> place<?= $t['Capacite'] > 1 ? 's' : '' ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-section">
        <form method="post">
          <input type="number" name="numero" placeholder="Numéro de table" required>
          <input type="number" name="capacite" placeholder="Capacité" required>
          <button type="submit">Ajouter</button>
          <button type="submit" name="ajout_tables_types">Ajout auto (2,4,6,8 places)</button>
        </form>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Numéro</th>
            <th>Capacité</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (isset($tables)) foreach ($tables as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['TableID']) ?></td>
              <td><?= htmlspecialchars($t['NumeroTable']) ?></td>
              <td><?= htmlspecialchars($t['Capacite']) ?></td>
              <td>
                <?php if (isset($t['Statut'])): ?>
                  <span style="font-weight:bold;color:<?= $t['Statut'] === 'Réservée' ? '#b01e28' : '#217a3c' ?>;">
                    <?= htmlspecialchars($t['Statut']) ?>
                  </span>
                <?php else: ?>
                  <span style="color:#757575;">-</span>
                <?php endif; ?>
              </td>
              <td><a href="?delete=<?= $t['TableID'] ?>" onclick="return confirm('Supprimer cette table ?')"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>