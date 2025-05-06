<?php
require_once '../db_connexion.php';
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();

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
    set_message('Impossible d\'enregistrer la réservation : la capacité maximale de la salle serait dépassée.', 'error');
  } else {
    // Récupération et validation des champs du formulaire
    $nom = trim($_POST['nom_client']);
    $email = validate_email($_POST['email_client']) ? $_POST['email_client'] : '';
    $date = $_POST['DateReservation'];
    $statut = trim($_POST['statut'] ?? 'Réservée');
    $nb_personnes = intval($_POST['nb_personnes'] ?? 1);
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    if ($nom && $email && $date && $nb_personnes > 0) {
      // Recherche du client par email
      $sql = "SELECT ClientID FROM Clients WHERE Email = ?";
      $stmt_client = $conn->prepare($sql);
      $stmt_client->execute([$email]);
      $client = $stmt_client->fetch(PDO::FETCH_ASSOC);
      if ($client) {
        $client_id = $client['ClientID'];
      } else {
        // Ajout du client s'il n'existe pas
        $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, '', ?, ?)";
        $stmt_insert = $conn->prepare($sql);
        $stmt_insert->execute([$nom, $email, $telephone]);
        $client_id = $conn->lastInsertId();
      }
      // Insertion de la réservation en base de données avec ClientID
      $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut, nb_personnes, ClientID, telephone) VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $result = $stmt->execute([$nom, $email, $date, $statut, $nb_personnes, $client_id, $telephone]);
      if ($result) {
        // Récupérer l'ID de la réservation insérée
        $reservation_id = $conn->lastInsertId();
        // Association automatique des tables à la réservation et mise à jour du statut
        if (!empty($_POST['table_ids']) && is_array($_POST['table_ids'])) {
          foreach ($_POST['table_ids'] as $table_id) {
            $table_id = intval($table_id);
            $sql = "INSERT INTO ReservationTables (ReservationID, TableID, nb_places) VALUES (?, ?, ?)";
            $stmt_assoc = $conn->prepare($sql);
            $stmt_assoc->execute([$reservation_id, $table_id, 0]); // 0 ou nombre de places attribuées si connu
            $sql = "UPDATE TablesRestaurant SET Statut = 'Réservée' WHERE TableID = ?";
            $stmt_update = $conn->prepare($sql);
            $stmt_update->execute([$table_id]);
          }
        }
        set_message('Réservation ajoutée avec succès.', 'success');
        header('Location: reservations.php');
        exit;
      } else {
        set_message('Erreur lors de l\'ajout de la réservation.', 'error');
        header('Location: reservations.php');
        exit;
      }
    } else {
      set_message('Champs invalides pour la réservation.', 'error');
      header('Location: reservations.php');
      exit;
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
    set_message('Réservation supprimée avec succès.', 'success');
  } else {
    set_message('Erreur lors de la suppression de la réservation.', 'error');
  }
  header('Location: reservations.php');
  exit;
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
      set_message('Réservation modifiée avec succès.', 'success');
      header('Location: reservations.php');
      exit;
    } else {
      set_message('Erreur lors de la modification de la réservation.', 'error');
      header('Location: reservations.php');
      exit;
    }
  } else {
    set_message('Champs invalides pour la modification.', 'error');
    header('Location: reservations.php');
    exit;
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
  <title>Reservations - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="../assets/css/reservation-tables.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Reservations";

  // Indiquer que ce fichier est inclus dans une page
  define('INCLUDED_IN_PAGE', true);
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <div style="background-color: #f9f9f9; border-radius: 5px;">
      <h2 style="color: #222; font-size: 23px; margin-bottom: 50px; position: relative;">Gestion des reservations
      </h2>
    </div>
    <?php display_message(); ?> <!-- Ajout de cette ligne pour afficher les messages -->
    
    <!-- Cartes statistiques -->
    <div class="dashboard-cards">
      <div class="dashboard-card">
        <div class="card-title">Total réservations</div>
        <div class="card-value">
          <?php echo count($reservations); ?>
        </div>
      </div>
      <div class="dashboard-card">
        <div class="card-title">Places réservées à venir</div>
        <div class="card-value">
          <?php echo isset($total_reserves) ? $total_reserves : 0; ?>
        </div>
      </div>
      <div class="dashboard-card">
        <div class="card-title">Places totales</div>
        <div class="card-value">
          <?php echo isset($total_places) ? $total_places : 0; ?>
        </div>
      </div>
    </div>

    <!-- Formulaire d'ajout de réservation -->
    <h3 class="section-title">Ajouter une réservation</h3>
    <div class="form-section">
      <form method="post" class="form-grid">
        <div class="form-group">
          <input type="text" name="nom_client" placeholder="Nom du client" required>
        </div>
        <div class="form-group">
          <input type="email" name="email_client" placeholder="Email du client" required>
        </div>
        <div class="form-group">
          <input type="datetime-local" name="DateReservation" placeholder="Date de réservation" required>
        </div>
        <div class="form-group">
          <input type="number" name="nb_personnes" placeholder="Nombre de personnes" required>
        </div>
        <div class="form-group">
          <input type="text" name="telephone" placeholder="Téléphone du client" required>
        </div>
        <div class="form-group">
          <select name="statut">
            <option value="Réservée">Réservée</option>
            <option value="Annulée">Annulée</option>
          </select>
        </div>

        <!-- Sélection des tables disponibles -->
        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label">Tables disponibles :</label>
          <div class="tables-container">
            <?php
            // Récupérer les tables disponibles
            $sql = "SELECT * FROM TablesRestaurant WHERE Statut = 'Libre' ORDER BY Capacite ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $tables_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($tables_disponibles) {
              foreach ($tables_disponibles as $table) {
                echo '<div class="table-card">';
                $label = isset($table['NomTable']) ? $table['NomTable'] : (isset($table['NumeroTable']) ? 'Table ' . $table['NumeroTable'] : 'Table ' . $table['TableID']);
                echo '<div class="table-title">' . htmlspecialchars($label) . '</div>';
                echo '<div class="table-capacity">Capacité : ' . intval($table['Capacite']) . ' pers.</div>';
                echo '<input type="checkbox" name="table_ids[]" value="' . intval($table['TableID']) . '" class="table-checkbox">';
                echo '</div>';
              }
            } else {
              echo '<div class="alert alert-info">Aucune table disponible.</div>';
            }
            ?>
          </div>
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
          <button type="submit" class="submit-btn">Ajouter la réservation</button>
        </div>
      </form>
    </div>

    <!-- Tableau des réservations -->
    <div class="table-responsive-wrapper">
      <table class="admin-table">
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
              <td>
                <span class="status-indicator <?= isset($r['statut']) && strtolower($r['statut']) === 'réservée' ? 'status-reserved' : 'status-cancelled' ?>">
                  <?= isset($r['statut']) ? htmlspecialchars($r['statut']) : '' ?>
                </span>
              </td>
              <td><?= htmlspecialchars($r['nb_personnes']) ?></td>
              <td class="action-cell">
                <a href="?delete=<?= $r['ReservationID'] ?>" onclick="return confirm('Supprimer cette réservation ?')" class="action-icon"><i class="bi bi-trash"></i></a>
                <a href="#" class="edit-btn action-icon" data-id="<?= $r['ReservationID'] ?>"><i class="bi bi-pencil"></i></a>
              </td>
            </tr>
            <!-- Formulaire d'édition inline -->
            <tr class="edit-row" id="edit-row-<?= $r['ReservationID'] ?>" style="display:none;">
              <td colspan="7">
                <form method="post" class="edit-form form-grid">
                  <input type="hidden" name="edit_id" value="<?= $r['ReservationID'] ?>">
                  <div class="form-group">
                    <input type="text" name="edit_nom_client" value="<?= htmlspecialchars($r['nom_client']) ?>" required placeholder="Nom">
                  </div>
                  <div class="form-group">
                    <input type="email" name="edit_email_client" value="<?= htmlspecialchars($r['email_client']) ?>" required placeholder="Email">
                  </div>
                  <div class="form-group">
                    <input type="datetime-local" name="edit_DateReservation" value="<?= str_replace(' ', 'T', htmlspecialchars($r['DateReservation'])) ?>" required>
                  </div>
                  <div class="form-group">
                    <input type="number" name="edit_nb_personnes" value="<?= htmlspecialchars($r['nb_personnes']) ?>" required placeholder="Nb pers.">
                  </div>
                  <div class="form-group">
                    <select name="edit_statut">
                      <option value="Réservée" <?= (isset($r['statut']) && $r['statut'] === 'Réservée') ? 'selected' : '' ?>>Réservée</option>
                      <option value="Annulée" <?= (isset($r['statut']) && $r['statut'] === 'Annulée') ? 'selected' : '' ?>>Annulée</option>
                    </select>
                  </div>
                  <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="submit-btn">Enregistrer</button>
                    <button type="button" class="cancel-edit btn-secondary">Annuler</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <script>
      // Affichage du formulaire d'édition inline
      document.addEventListener('DOMContentLoaded', function() {
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

        // Rendre les cartes de table cliquables pour activer la checkbox
        document.querySelectorAll('.table-card').forEach(function(card) {
          card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
              const checkbox = this.querySelector('input[type="checkbox"]');
              checkbox.checked = !checkbox.checked;

              if (checkbox.checked) {
                this.classList.add('selected');
              } else {
                this.classList.remove('selected');
              }
            }
          });
        });

        // Mettre à jour l'état visuel des cartes selon l'état des checkboxes
        document.querySelectorAll('.table-checkbox').forEach(function(checkbox) {
          checkbox.addEventListener('change', function() {
            const card = this.closest('.table-card');
            if (this.checked) {
              card.classList.add('selected');
            } else {
              card.classList.remove('selected');
            }
          });
        });
      });
    </script>
  </div>

  <?php
  include 'footer_template.php';
  ?>
</body>

</html>