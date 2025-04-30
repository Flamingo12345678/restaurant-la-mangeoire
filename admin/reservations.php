<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
$message = '';
// Traitement de l'ajout d'une réservation
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['nom_client'], $_POST['email_client'], $_POST['DateReservation'])
) {
  // Calcul du nombre de places déjà réservées à venir
  $now = date('Y-m-d H:i:s');
  $sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$now]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_reserves = intval($row['total_reserves']);

  // Calcul du nombre total de places disponibles dans la salle
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $conn->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  $people = intval($_POST['nb_personnes'] ?? 0);

  // Vérification de la capacité maximale
  if ($total_reserves + $people > $total_places) {
    $message = "<span class='alert alert-error'>Impossible d'enregistrer la réservation : la capacité maximale de la salle serait dépassée.</span>";
  } else {
    // Récupération et validation des champs du formulaire
    $nom = trim($_POST['nom_client']);
    $email = filter_var($_POST['email_client'], FILTER_VALIDATE_EMAIL);
    $date = $_POST['DateReservation'];
    $statut = trim($_POST['statut'] ?? 'Réservée');
    $nb_personnes = intval($_POST['nb_personnes'] ?? 1);
    if ($nom && $email && $date && $nb_personnes > 0) {
      // Insertion de la réservation en base de données
      $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut, nb_personnes) VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $result = $stmt->execute([$nom, $email, $date, $statut, $nb_personnes]);
      if ($result) {
        $message = 'Réservation ajoutée.';
      } else {
        $message = 'Erreur lors de l\'ajout.';
      }
    } else {
      $message = 'Champs invalides.';
    }
  }
}

// Suppression d'une réservation
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  // Libérer toutes les tables associées à la réservation supprimée (multi-tables)
  $sql = "SELECT TableID FROM ReservationTables WHERE ReservationID = ?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
  if ($tables) {
    foreach ($tables as $table_id) {
      $sql = "UPDATE TablesRestaurant SET Statut = 'Libre' WHERE TableID = ?";
      $stmt2 = $conn->prepare($sql);
      $stmt2->execute([$table_id]);
    }
    // Supprimer les associations dans ReservationTables
    $sql = "DELETE FROM ReservationTables WHERE ReservationID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
  }
  // Suppression de la réservation
  $sql = "DELETE FROM Reservations WHERE ReservationID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Réservation supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}

// Traitement de la modification d'une réservation (édition inline)
if (isset($_POST['edit_id'], $_POST['edit_nom_client'], $_POST['edit_email_client'], $_POST['edit_DateReservation'])) {
  $edit_id = intval($_POST['edit_id']);
  $edit_nom = trim($_POST['edit_nom_client']);
  $edit_email = filter_var($_POST['edit_email_client'], FILTER_VALIDATE_EMAIL);
  $edit_date = $_POST['edit_DateReservation'];
  $edit_statut = trim($_POST['edit_statut'] ?? 'Réservée');
  $edit_nb_personnes = intval($_POST['edit_nb_personnes'] ?? 1);
  // Par défaut, le statut ne peut être que Réservée ou Annulée
  if ($edit_statut !== 'Annulée') {
    $edit_statut = 'Réservée';
  }
  // Vérification de la capacité maximale lors de la modification
  $now = date('Y-m-d H:i:s');
  $sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ? AND ReservationID != ?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$now, $edit_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_reserves = intval($row['total_reserves']);
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $conn->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  if ($total_reserves + $edit_nb_personnes > $total_places) {
    $message = "<span class='alert alert-error'>Impossible de modifier la réservation : la capacité maximale de la salle serait dépassée.</span>";
  } elseif ($edit_nom && $edit_email && $edit_date && $edit_nb_personnes > 0) {
    // Mise à jour de la réservation
    $sql = "UPDATE Reservations SET nom_client = ?, email_client = ?, DateReservation = ?, Statut = ?, nb_personnes = ? WHERE ReservationID = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$edit_nom, $edit_email, $edit_date, $edit_statut, $edit_nb_personnes, $edit_id]);
    // Si la réservation passe à Annulée, libérer toutes les tables associées (multi-tables)
    if ($edit_statut === 'Annulée') {
      $sql = "SELECT TableID FROM ReservationTables WHERE ReservationID = ?";
      $stmt2 = $conn->prepare($sql);
      $stmt2->execute([$edit_id]);
      $tables = $stmt2->fetchAll(PDO::FETCH_COLUMN);
      if ($tables) {
        foreach ($tables as $table_id) {
          $sql = "UPDATE TablesRestaurant SET Statut = 'Libre' WHERE TableID = ?";
          $stmt3 = $conn->prepare($sql);
          $stmt3->execute([$table_id]);
        }
        // Supprimer les associations dans ReservationTables
        $sql = "DELETE FROM ReservationTables WHERE ReservationID = ?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->execute([$edit_id]);
      }
    }
    if ($result) {
      $message = 'Réservation modifiée.';
      header('Location: reservations.php');
      exit;
    } else {
      $message = 'Erreur lors de la modification.';
    }
  } else {
    $message = 'Champs invalides pour la modification.';
  }
}

// Récupération de la liste des réservations pour affichage
$reservations = [];
$sql = "SELECT * FROM Reservations ORDER BY ReservationID DESC";
try {
  $stmt = $conn->query($sql);
  if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $reservations[] = $row;
    }
  }
} catch (PDOException $e) {
  $message = 'Erreur lors de la récupération des réservations : ' . $e->getMessage();
}

// Libération automatique des tables dont la réservation est terminée (à chaque chargement de la page admin)
try {
  $now = date('Y-m-d H:i:s');
  $sql = "SELECT t.TableID
          FROM TablesRestaurant t
          LEFT JOIN Reservations r ON t.TableID = r.TableID AND r.Statut = 'Réservée' AND r.DateReservation > ?
          WHERE t.Statut = 'Réservée'
          GROUP BY t.TableID
          HAVING COUNT(r.ReservationID) = 0";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$now]);
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
  if ($tables) {
    $in = implode(',', array_fill(0, count($tables), '?'));
    $sql = "UPDATE TablesRestaurant SET Statut = 'Libre' WHERE TableID IN ($in)";
    $stmt2 = $conn->prepare($sql);
    $stmt2->execute($tables);
  }
} catch (PDOException $e) {
  // Optionnel : log ou message d'erreur admin
}

// Toujours recalculer les statistiques avant l'affichage
$now = date('Y-m-d H:i:s');
// Correction du calcul : on ne compte que les réservations à venir (date future ou maintenant, statut Réservée)
$sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$now]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_reserves = intval($row['total_reserves']);
// Nombre total de places disponibles
$sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
$stmt = $conn->query($sql);
$total_places = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_places = intval($row['total_places']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Réservations</title>
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
    <div class="logo">Reservations</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
        <li><a href="clients.php"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
        <li><a href="employes.php"><i class="bi bi-person-badge"></i> Employés</a></li>
        <li><a href="menus.php"><i class="bi bi-list"></i> Menus</a></li>
        <li><a href="paiements.php"><i class="bi bi-credit-card"></i> Paiements</a></li>
        <li><a href="reservations.php" class="active"><i class="bi bi-calendar-check"></i> Réservations</a></li>
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
      <h2 style="margin-bottom:32px;">Gestion des réservations</h2>
      <!-- Cartes statistiques -->
      <div class="dashboard-cards" style="display:flex;gap:32px;margin-bottom:32px;flex-wrap:wrap;">
        <div class="dashboard-card" style="background:#fff;border-radius:18px;box-shadow:0 2px 8px #0001;padding:28px 32px;min-width:200px;flex:1 1 200px;display:flex;flex-direction:column;align-items:flex-start;margin-bottom:12px;">
          <div class="card-title" style="font-size:1.1rem;color:#757575;margin-bottom:8px;">Total réservations</div>
          <div class="card-value" style="font-size:2rem;font-weight:bold;color:#1a237e;">
            <?php echo count($reservations); ?>
          </div>
        </div>
        <div class="dashboard-card" style="background:#fff;border-radius:18px;box-shadow:0 2px 8px #0001;padding:28px 32px;min-width:200px;flex:1 1 200px;display:flex;flex-direction:column;align-items:flex-start;margin-bottom:12px;">
          <div class="card-title" style="font-size:1.1rem;color:#757575;margin-bottom:8px;">Places réservées à venir</div>
          <div class="card-value" style="font-size:2rem;font-weight:bold;color:#1a237e;">
            <?php echo isset($total_reserves) ? $total_reserves : 0; ?>
          </div>
        </div>
        <div class="dashboard-card" style="background:#fff;border-radius:18px;box-shadow:0 2px 8px #0001;padding:28px 32px;min-width:200px;flex:1 1 200px;display:flex;flex-direction:column;align-items:flex-start;margin-bottom:12px;">
          <div class="card-title" style="font-size:1.1rem;color:#757575;margin-bottom:8px;">Places totales</div>
          <div class="card-value" style="font-size:2rem;font-weight:bold;color:#1a237e;">
            <?php echo isset($total_places) ? $total_places : 0; ?>
          </div>
        </div>
      </div>
      <!-- Formulaire d'ajout de réservation -->
      <div class="form-section" style="background:#fff;border-radius:12px;box-shadow:0 2px 8px #0001;padding:32px;margin-bottom:32px;max-width:500px;">
        <form method="post">
          <input type="text" name="nom_client" placeholder="Nom du client" required style="margin-bottom:16px;width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e0e0e0;font-size:1rem;">
          <input type="email" name="email_client" placeholder="Email du client" required style="margin-bottom:16px;width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e0e0e0;font-size:1rem;">
          <input type="datetime-local" name="DateReservation" placeholder="Date de réservation" required style="margin-bottom:16px;width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e0e0e0;font-size:1rem;">
          <input type="number" name="nb_personnes" placeholder="Nombre de personnes" required style="margin-bottom:16px;width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e0e0e0;font-size:1rem;">
          <select name="statut" style="margin-bottom:16px;width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e0e0e0;font-size:1rem;">
            <option value="Réservée">Réservée</option>
            <option value="Annulée">Annulée</option>
          </select>
          <button type="submit" style="margin-bottom:16px;width:100%;padding:10px 14px;border-radius:8px;background:#1a237e;color:#fff;font-weight:bold;border:none;cursor:pointer;transition:background 0.2s;">Ajouter</button>
        </form>
      </div>
      <!-- Tableau des réservations -->
      <table class="admin-table" style="border-collapse:collapse;width:100%;margin-top:1em;background:#fff;box-shadow:0 2px 8px #0001;border-radius:12px;overflow:hidden;">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Nb pers.</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['ReservationID']) ?></td>
              <td><?= htmlspecialchars($r['nom_client']) ?></td>
              <td><?= htmlspecialchars($r['email_client']) ?></td>
              <td><?= htmlspecialchars($r['DateReservation']) ?></td>
              <td><?= isset($r['statut']) ? htmlspecialchars($r['statut']) : '' ?></td>
              <td><?= htmlspecialchars($r['nb_personnes']) ?></td>
              <td>
                <a href="?delete=<?= $r['ReservationID'] ?>" onclick="return confirm('Supprimer cette réservation ?')"><i class="bi bi-trash"></i></a>
                <a href="#" class="edit-btn" data-id="<?= $r['ReservationID'] ?>" data-nom="<?= htmlspecialchars($r['nom_client']) ?>" data-email="<?= htmlspecialchars($r['email_client']) ?>" data-date="<?= htmlspecialchars($r['DateReservation']) ?>" data-statut="<?= isset($r['statut']) ? htmlspecialchars($r['statut']) : '' ?>" data-nb="<?= htmlspecialchars($r['nb_personnes']) ?>" style="margin-left:8px;"><i class="bi bi-pencil"></i></a>
              </td>
            </tr>
            <!-- Formulaire d'édition inline -->
            <tr class="edit-row" id="edit-row-<?= $r['ReservationID'] ?>" style="display:none;background:#e3f2fd;">
              <td colspan="7">
                <form method="post" class="edit-form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                  <input type="hidden" name="edit_id" value="<?= $r['ReservationID'] ?>">
                  <input type="text" name="edit_nom_client" value="<?= htmlspecialchars($r['nom_client']) ?>" required placeholder="Nom" style="padding:8px 10px;border-radius:6px;border:1px solid #e0e0e0;">
                  <input type="email" name="edit_email_client" value="<?= htmlspecialchars($r['email_client']) ?>" required placeholder="Email" style="padding:8px 10px;border-radius:6px;border:1px solid #e0e0e0;">
                  <input type="datetime-local" name="edit_DateReservation" value="<?= str_replace(' ', 'T', htmlspecialchars($r['DateReservation'])) ?>" required style="padding:8px 10px;border-radius:6px;border:1px solid #e0e0e0;">
                  <input type="number" name="edit_nb_personnes" value="<?= htmlspecialchars($r['nb_personnes']) ?>" required placeholder="Nb pers." style="width:90px;padding:8px 10px;border-radius:6px;border:1px solid #e0e0e0;">
                  <select name="edit_statut" style="padding:8px 10px;border-radius:6px;border:1px solid #e0e0e0;">
                    <option value="Réservée" <?= (isset($r['statut']) && $r['statut'] === 'Réservée') ? 'selected' : '' ?>>Réservée</option>
                    <option value="Annulée" <?= (isset($r['statut']) && $r['statut'] === 'Annulée') ? 'selected' : '' ?>>Annulée</option>
                  </select>
                  <button type="submit" style="background:#1a237e;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-weight:bold;">Enregistrer</button>
                  <button type="button" class="cancel-edit" style="background:#eee;color:#1a237e;border:none;border-radius:6px;padding:8px 18px;font-weight:bold;">Annuler</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <script>
        // Affichage du formulaire d'édition inline
        document.querySelectorAll('.edit-btn').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.edit-row').forEach(function(row) {
              row.style.display = 'none';
            });
            var id = btn.getAttribute('data-id');
            var row = document.getElementById('edit-row-' + id);
            if (row) row.style.display = '';
          });
        });
        document.querySelectorAll('.cancel-edit').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            btn.closest('tr').style.display = 'none';
          });
        });
      </script>
    </div>
  </div>
</body>

</html>