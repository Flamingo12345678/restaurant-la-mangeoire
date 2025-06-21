<?php

require_once 'check_admin_access.php';
require_once '../db_connexion.php';
require_once '../includes/common.php';

// Gestion de l'ajout d'un client
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['prenom'], $_POST['email'])) {
  $nom = trim($_POST['nom']);
  $prenom = trim($_POST['prenom']);
  $email = validate_email(trim($_POST['email'])) ? trim($_POST['email']) : '';
  $tel = trim($_POST['telephone'] ?? '');
  if ($nom && $prenom && $email) {
    $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$nom, $prenom, $email, $tel]);
    if ($result) {
      set_message('Client ajouté avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('Erreur lors de l\'ajout.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  } else {
    set_message('Champs invalides.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}
// Suppression d'un client
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM Clients WHERE ClientID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    set_message('Client supprimé.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    set_message('Erreur lors de la suppression.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
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

// Gestion centralisée des messages
// function set_message($msg, $type = 'success')
// {
//   $_SESSION['flash_message'] = [
//     'text' => $msg,
//     'type' => $type
//   ];
// }
// function display_message()
// {
//   if (!empty($_SESSION['flash_message'])) {
//     $type = $_SESSION['flash_message']['type'] === 'success' ? 'alert-success' : 'alert-error';
//     $text = htmlspecialchars($_SESSION['flash_message']['text']);
//     echo "<div class='alert $type'><i class='bi " .
//       ($_SESSION['flash_message']['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle') .
//       "'></i> $text</div>";
//     unset($_SESSION['flash_message']);
//   }
// }
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Clients - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Styles spécifiques pour la page clients sur mobile */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .table-responsive-wrapper {
        margin: 0 -15px;
        width: calc(100% + 30px);
        border-radius: 0;
      }

      .admin-table th:nth-child(1),
      .admin-table td:nth-child(1) {
        display: none;
      }

      .admin-table th,
      .admin-table td {
        padding: 10px 8px;
        font-size: 0.9rem;
      }

      .admin-table th:nth-child(4),
      .admin-table td:nth-child(4) {
        max-width: 120px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }

    @media (max-width: 480px) {

      .admin-table th:nth-child(5),
      .admin-table td:nth-child(5) {
        display: none;
      }
    }
  </style>
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Clients";

  // Indiquer que ce fichier est inclus dans une page
  define('INCLUDED_IN_PAGE', true);
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <div class="admin-section-container">
      <h2 class="admin-section-title">Gestion des clients</h2>
    </div>
    <?php display_message(); ?>
    <div class="form-section">
      <h3 class="section-title">Ajouter un client</h3>
      <form method="post" class="form-grid">
        <div class="form-group">
          <input type="text" name="nom" placeholder="Nom" required>
        </div>
        <div class="form-group">
          <input type="text" name="prenom" placeholder="Prénom" required>
        </div>
        <div class="form-group">
          <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
          <input type="text" name="telephone" placeholder="Téléphone" required>
        </div>
        <div class="form-group admin-form-group-full">
          <button type="submit" class="submit-btn">Ajouter le client</button>
        </div>
      </form>
    </div>
    <h3 class="section-title admin-subsection-title">Liste des clients</h3>
    <div class="table-responsive-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['ClientID']) ?></td>
              <td><?= htmlspecialchars($c['Nom']) ?></td>
              <td><?= htmlspecialchars($c['Prenom']) ?></td>
              <td><?= htmlspecialchars($c['Email']) ?></td>
              <td><?= htmlspecialchars($c['Telephone']) ?></td>
              <td class="action-cell">
                <a href="?delete=<?= $c['ClientID'] ?>" onclick="return confirm('Supprimer ce client ?')" class="action-icon"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div> <!-- Fermeture du content-wrapper -->

  <?php
  include 'footer_template.php';
  ?>
</body>

</html>