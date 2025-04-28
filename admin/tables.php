<?php
define('CAPACITE_SALLE', 100);

// --- Capacité totale de la salle ---
require_once '../db_connexion.php';
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
$message = '';
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
$places_restantes = CAPACITE_SALLE - $total_places;

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
  <?= $alerte ?>
  <div style="margin:1em 0; font-weight:bold; color:#1976d2;">
    Capacité totale de la salle : <span style="color:#c62828;">100 places strictement</span><br>
    Nombre de places utilisées (tables) : <span style="color:#c62828;"><?= $total_places ?></span><br>
    Nombre de personnes réservées à venir : <span style="color:#1976d2;"><?= $total_reserves ?></span><br>
    Places restantes (physiques) : <span style="color:<?= $places_restantes > 0 ? '#388e3c' : ($places_restantes == 0 ? '#1976d2' : '#c62828') ?>; font-weight:bold;">
      <?= $places_restantes == 0 ? 'Aucune (capacité maximale atteinte)' : $places_restantes ?>
    </span>
    <br>
    <span style="font-size:0.95em;color:#b01e28;">
      Vous devez organiser vos tables pour que la somme des capacités fasse <b>exactement</b> 100 places.<br>
      <b>Le nombre de personnes réservées ne doit jamais dépasser le nombre de places physiques.</b>
    </span>
  </div>
  <h2>Ajouter une table</h2>
  <form method="post" class="admin-form">
    <input type="number" name="numero" placeholder="Numéro de table *" required min="1">
    <input type="number" name="capacite" placeholder="Capacité *" min="1" required>
    <button type="submit">Ajouter</button>
  </form>
  <form method="post" style="margin-bottom:2em;">
    <input type="hidden" name="ajout_tables_types" value="1">
    <button type="submit" style="background:#1976d2;color:#fff;font-weight:bold;padding:0.7em 1.5em;border-radius:6px;border:none;cursor:pointer;">Ajouter différentes tailles de tables pour 100 personnes</button>
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
        <?php if (isset($_GET['edit']) && $_GET['edit'] == $t['TableID']): ?>
          <tr>
            <form method="post" class="admin-form">
              <td><input type="hidden" name="edit_table_id" value="<?= $t['TableID'] ?>"><?= htmlspecialchars($t['TableID']) ?></td>
              <td><input type="number" name="edit_numero" value="<?= htmlspecialchars($t['NumeroTable']) ?>" min="1" required style="width:70px;"></td>
              <td><input type="number" name="edit_capacite" value="<?= htmlspecialchars($t['Capacite']) ?>" min="1" required style="width:70px;"></td>
              <td>
                <button type="submit">Enregistrer</button>
                <a href="tables.php">Annuler</a>
              </td>
            </form>
          </tr>
        <?php else: ?>
          <tr>
            <td><?= htmlspecialchars($t['TableID']) ?></td>
            <td><?= htmlspecialchars($t['NumeroTable']) ?></td>
            <td><?= htmlspecialchars($t['Capacite']) ?></td>
            <td>
              <a href="?edit=<?= $t['TableID'] ?>" style="color:#1976d2;font-weight:bold;">Modifier</a> |
              <a href="?delete=<?= $t['TableID'] ?>" onclick="return confirm('Supprimer cette table ?');" style="color:#c62828;font-weight:bold;">Supprimer</a>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <h2>Statistiques d'occupation des tables</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Numéro</th>
        <th>Capacité</th>
        <th>Nombre de réservations à venir</th>
        <th>Personnes réservées à venir</th>
        <th>Taux d'occupation à venir (%)</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // On ne compte que les réservations à venir (date >= aujourd'hui)
      $now = date('Y-m-d H:i:s');
      $sql = "SELECT t.NumeroTable, t.Capacite, COUNT(r.ReservationID) AS nb_resa, COALESCE(SUM(r.nb_personnes),0) AS nb_pers
              FROM TablesRestaurant t
              LEFT JOIN Reservations r ON t.TableID = r.TableID AND r.Statut = 'Réservée' AND r.DateReservation >= ?
              GROUP BY t.TableID
              ORDER BY t.NumeroTable ASC";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$now]);
      $stats = $stmt->fetchAll();
      foreach ($stats as $s):
        $taux = $s['Capacite'] > 0 ? round(($s['nb_pers'] / $s['Capacite']) * 100, 1) : 0;
      ?>
        <tr>
          <td><?= htmlspecialchars($s['NumeroTable']) ?></td>
          <td><?= htmlspecialchars($s['Capacite']) ?></td>
          <td><?= htmlspecialchars($s['nb_resa']) ?></td>
          <td><?= htmlspecialchars($s['nb_pers']) ?></td>
          <td><?= $taux ?>%</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>