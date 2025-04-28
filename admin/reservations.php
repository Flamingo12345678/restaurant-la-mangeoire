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
  $sql = "DELETE FROM Reservations WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Réservation supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}

// --- Récupération de la liste des réservations ---
$reservations = [];
$sql = "SELECT * FROM Reservations ORDER BY id DESC";
try {
  $stmt = $conn->query($sql);
  if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $reservations[] = $row;
    }
  }
} catch (PDOException $e) {
  $message = 'Erreur lors de la récupération des réservations.';
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
  <?php if ($message): ?><div><?= $message ?></div><?php endif; ?>

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
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['nom_client']) ?></td>
          <td><?= htmlspecialchars($r['email_client']) ?></td>
          <td><?= htmlspecialchars($r['DateReservation']) ?></td>
          <td><?= htmlspecialchars($r['statut']) ?></td>
          <!-- Lien pour supprimer la réservation (avec confirmation JS) -->
          <td><a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Supprimer cette réservation ?');" style="color:#c62828;font-weight:bold;">Supprimer</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>

</html>