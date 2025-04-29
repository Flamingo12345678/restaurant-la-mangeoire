<?php
<<<<<<< HEAD
=======
define('CAPACITE_SALLE', 100);
require_once '../db_connexion.php';
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
<<<<<<< HEAD
require_once '../db_connexion.php';
=======
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero'], $_POST['capacite'])) {
  $numero = intval($_POST['numero']);
  $capacite = intval($_POST['capacite']);
<<<<<<< HEAD
  if ($numero > 0 && $capacite > 0) {
    $sql = "INSERT INTO TablesRestaurant (NumeroTable, Capacite) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$numero, $capacite]);
    if ($result) {
      $message = 'Table ajoutée.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
=======
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
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
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
<<<<<<< HEAD
=======
if (isset($_POST['ajout_tables_types'])) {
  // Définition des types de tables (exemple : 10x2p, 8x4p, 4x6p, 2x8p)
  $types = [2 => 10, 4 => 8, 6 => 4, 8 => 2];
  // Calcul du prochain numéro de table
  $sql = "SELECT MAX(NumeroTable) AS maxnum, SUM(Capacite) AS total_places FROM TablesRestaurant";
  $maxnum = 0;
  $total_places = 0;
  $stmt = $conn->query($sql);
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxnum = intval($row['maxnum']);
    $total_places = intval($row['total_places']);
  }
  $places_restantes = CAPACITE_SALLE - $total_places;
  $inserted = 0;
  foreach ($types as $capacite => $nb) {
    for ($i = 0; $i < $nb; $i++) {
      if ($capacite > $places_restantes) {
        $message = "$inserted tables ajoutées. Capacité maximale atteinte, arrêt de l'ajout.";
        break 2;
      }
      $maxnum++;
      $sql = "INSERT INTO TablesRestaurant (NumeroTable, Capacite) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      if ($stmt->execute([$maxnum, $capacite])) {
        $inserted++;
        $places_restantes -= $capacite;
      }
    }
  }
  // Après ajout automatique, vérifier la cohérence avec les réservations à venir
  $now = date('Y-m-d H:i:s');
  $sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$now]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_reserves = intval($row['total_reserves']);
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $conn->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  if ($total_reserves > $total_places) {
    $message = "Attention : Il y a plus de personnes réservées ($total_reserves) que de places disponibles ($total_places) !";
  } elseif (!$message) {
    $message = "$inserted tables ajoutées automatiquement (2, 4, 6, 8 places) pour une capacité totale de 100 personnes.";
  }
}

// --- Calcul du nombre de places utilisées et cohérence avec les réservations ---
// 1. Calculer le nombre de places utilisées par les tables
$sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
$stmt = $conn->query($sql);
$total_places = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_places = intval($row['total_places']);
}
// Capacité maximale de la salle
$cap_max = defined('CAPACITE_SALLE') ? CAPACITE_SALLE : 100;
$places_restantes = max(0, $cap_max - $total_places);

// 2. Calculer le nombre de personnes réservées à venir (toutes tables confondues)
$now = date('Y-m-d H:i:s');
$sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$now]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_reserves = intval($row['total_reserves']);

// 3. Vérifier la cohérence :
$alerte = '';
if ($total_reserves > $total_places) {
  $alerte = "<div style='color:#c62828;font-weight:bold;'>Attention : Il y a plus de personnes réservées ($total_reserves) que de places disponibles ($total_places) !</div>";
}

// Suppression automatique des tables si la capacité totale dépasse 100
if ($total_places > CAPACITE_SALLE) {
  // On supprime les tables les plus récemment ajoutées jusqu'à atteindre 100 places
  $places_a_supprimer = $total_places - CAPACITE_SALLE;
  $sql = "SELECT TableID, Capacite FROM TablesRestaurant ORDER BY TableID DESC";
  $stmt = $conn->query($sql);
  $tables_to_remove = [];
  $somme = 0;
  if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $tables_to_remove[] = $row;
      $somme += $row['Capacite'];
      if ($somme >= $places_a_supprimer) break;
    }
  }
  foreach ($tables_to_remove as $t) {
    $conn->prepare("DELETE FROM TablesRestaurant WHERE TableID = ?")->execute([$t['TableID']]);
    $places_a_supprimer -= $t['Capacite'];
    if ($places_a_supprimer <= 0) break;
  }
  // Recalculer le total après suppression
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $conn->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  $places_restantes = CAPACITE_SALLE - $total_places;
  $message = "Des tables ont été supprimées automatiquement pour respecter la capacité maximale de 100 places.";
}

>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
$tables = [];
$sql = "SELECT * FROM TablesRestaurant ORDER BY TableID DESC";
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
<<<<<<< HEAD
  <style>
    .success-message {
      color: #2e7d32;
      font-weight: bold;
    }

    .error-message {
      color: #c62828;
      font-weight: bold;
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
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
    }

    .admin-table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 1em;
      background: #fff;
      box-shadow: 0 2px 8px #0001;
<<<<<<< HEAD
=======
      border-radius: 12px;
      overflow: hidden;
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
    }

    .admin-table th,
    .admin-table td {
<<<<<<< HEAD
      border: 1px solid #ddd;
      padding: 8px 12px;
=======
      border: 1px solid #e0e0e0;
      padding: 12px 16px;
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
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
<<<<<<< HEAD
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
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
    }
  </style>
</head>

<body>
<<<<<<< HEAD
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
=======
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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (isset($tables)) foreach ($tables as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['TableID']) ?></td>
              <td><?= htmlspecialchars($t['NumeroTable']) ?></td>
              <td><?= htmlspecialchars($t['Capacite']) ?></td>
              <td><a href="?delete=<?= $t['TableID'] ?>" onclick="return confirm('Supprimer cette table ?')"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
</body>

</html>