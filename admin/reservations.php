<?php
// --- Démarrage de la session et vérification de l'authentification admin ---
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}

// --- Connexion à la base de données ---
require_once '../db_connexion.php';

// --- Initialisation du message d'information ---
$message = '';

// --- Traitement du formulaire d'ajout de réservation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_client'], $_POST['email_client'], $_POST['DateReservation'])) {
  $nom = trim($_POST['nom_client']);
  $email = filter_var($_POST['email_client'], FILTER_VALIDATE_EMAIL);
  $date = $_POST['DateReservation'];
  $statut = trim($_POST['statut'] ?? 'Réservée');
  if ($nom && $email && $date) {
    // Préparation et exécution de la requête d'insertion
    $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$nom, $email, $date, $statut]);
    if ($result) {
      $message = 'Réservation ajoutée.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}

// --- Suppression d'une réservation si demandé via GET ---
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Reservations WHERE ReservationID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Réservation supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}

// --- Traitement du formulaire de modification de réservation ---
if (isset($_POST['edit_id'], $_POST['edit_nom_client'], $_POST['edit_email_client'], $_POST['edit_DateReservation'])) {
  $edit_id = intval($_POST['edit_id']);
  $edit_nom = trim($_POST['edit_nom_client']);
  $edit_email = filter_var($_POST['edit_email_client'], FILTER_VALIDATE_EMAIL);
  $edit_date = $_POST['edit_DateReservation'];
  $edit_statut = trim($_POST['edit_statut'] ?? 'Réservée');
  // Correction : forcer la valeur à 'Réservée' ou 'Annulée' uniquement
  if ($edit_statut !== 'Annulée') {
    $edit_statut = 'Réservée';
  }
  if ($edit_nom && $edit_email && $edit_date) {
    $sql = "UPDATE Reservations SET nom_client = ?, email_client = ?, DateReservation = ?, Statut = ? WHERE ReservationID = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$edit_nom, $edit_email, $edit_date, $edit_statut, $edit_id]);
    if ($result) {
      $message = 'Réservation modifiée.';
      // Redirection pour réinitialiser l'état du formulaire d'édition
      header('Location: reservations.php');
      exit;
    } else {
      $message = 'Erreur lors de la modification.';
    }
  } else {
    $message = 'Champs invalides pour la modification.';
  }
}

// --- Récupération de la liste des réservations ---
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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Réservations</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    /* --- Styles pour la page d'administration des réservations --- */
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
  <h1>Réservations</h1>
  <a href="index.php">&larr; Retour admin</a>
  <!-- Affichage du message d'information (succès ou erreur) -->
  <?php if (!empty($message)): ?><div><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <!-- Formulaire d'ajout d'une nouvelle réservation -->
  <h2>Ajouter une réservation</h2>
  <form method="post" class="admin-form">
    <input type="text" name="nom_client" placeholder="Nom du client *" required maxlength="255">
    <input type="email" name="email_client" placeholder="Email du client *" required maxlength="255">
    <input type="datetime-local" name="DateReservation" placeholder="Date et heure *" required>
    <input type="text" name="statut" placeholder="Statut (Réservée/Annulée)" maxlength="50" value="Réservée">
    <button type="submit">Ajouter</button>
  </form>

  <!-- Tableau affichant la liste des réservations existantes -->
  <h2>Liste des réservations</h2>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Email</th>
        <th>Date</th>
        <th>Statut</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reservations as $r): ?>
        <?php if (isset($_GET['edit']) && $_GET['edit'] == $r['ReservationID']): ?>
          <tr>
            <form method="post" class="admin-form">
              <td><input type="hidden" name="edit_id" value="<?= $r['ReservationID'] ?>"><?= htmlspecialchars($r['ReservationID']) ?></td>
              <td><input type="text" name="edit_nom_client" value="<?= htmlspecialchars($r['nom_client']) ?>" required maxlength="255"></td>
              <td><input type="email" name="edit_email_client" value="<?= htmlspecialchars($r['email_client']) ?>" required maxlength="255"></td>
              <td><input type="datetime-local" name="edit_DateReservation" value="<?= date('Y-m-d\TH:i', strtotime($r['DateReservation'])) ?>" required></td>
              <td><input type="text" name="edit_statut" value="<?= htmlspecialchars($r['statut'] ?? '') ?>" maxlength="50"></td>
              <td>
                <button type="submit">Enregistrer</button>
                <a href="reservations.php">Annuler</a>
              </td>
            </form>
          </tr>
        <?php else: ?>
          <tr>
            <td><?= htmlspecialchars($r['ReservationID']) ?></td>
            <td><?= htmlspecialchars($r['nom_client']) ?></td>
            <td><?= htmlspecialchars($r['email_client']) ?></td>
            <td><?= htmlspecialchars($r['DateReservation']) ?></td>
            <td><?= htmlspecialchars($r['statut'] ?? '') ?></td>
            <td>
              <a href="?edit=<?= $r['ReservationID'] ?>" style="color:#1976d2;font-weight:bold;">Modifier</a> |
              <a href="?delete=<?= $r['ReservationID'] ?>" onclick="return confirm('Supprimer cette réservation ?');" style="color:#c62828;font-weight:bold;">Supprimer</a>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>