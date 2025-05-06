<?php

require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['prenom'], $_POST['poste'], $_POST['salaire'])) {
  $nom = trim($_POST['nom']);
  $prenom = trim($_POST['prenom']);
  $poste = trim($_POST['poste']);
  $salaire = floatval($_POST['salaire']);
  $date = $_POST['date_embauche'] ?? date('Y-m-d');
  if ($nom && $prenom && $poste && $salaire > 0) {
    $sql = "INSERT INTO Employes (Nom, Prenom, Poste, Salaire, DateEmbauche) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$nom, $prenom, $poste, $salaire, $date]);
    if ($result) {
      $message = 'Employé ajouté.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression d'un employé (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employe'], $_POST['csrf_token'])) {
  require_admin();
  if (!check_csrf_token($_POST['csrf_token'])) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
  $id = intval($_POST['delete_employe']);
  $sql = "DELETE FROM Employes WHERE EmployeID = ?";
  $stmt = $conn->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    set_message('Employé supprimé.');
  } else {
    set_message('Erreur lors de la suppression.', 'error');
  }
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

// Pagination
$employes_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $employes_per_page;
$total_employes = $conn->query("SELECT COUNT(*) FROM Employes")->fetchColumn();
$total_pages = ceil($total_employes / $employes_per_page);
$employes = $conn->query("SELECT * FROM Employes ORDER BY EmployeID DESC LIMIT $employes_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Employes - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Styles spécifiques pour la page employés sur mobile */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .form-group {
        margin-bottom: 15px;
      }

      .table-responsive-wrapper {
        margin: 0 -15px;
        width: calc(100% + 30px);
        border-radius: 0;
      }

      .admin-table th:nth-child(1),
      .admin-table td:nth-child(1),
      .admin-table th:nth-child(6),
      .admin-table td:nth-child(6) {
        display: none;
      }

      .admin-table th,
      .admin-table td {
        padding: 10px 8px;
        font-size: 0.9rem;
      }

      .pagination {
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 20px;
      }

      .pagination a,
      .pagination strong {
        margin: 3px;
      }
    }

    @media (max-width: 480px) {

      .admin-table th:nth-child(4),
      .admin-table td:nth-child(4) {
        display: none;
      }

      .admin-table th:nth-child(5),
      .admin-table td:nth-child(5) {
        max-width: 80px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
  </style>
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Employes";

  // Indiquer que ce fichier est inclus dans une page
  define('INCLUDED_IN_PAGE', true);
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <!-- Titre de la page -->
    <div style="background-color: #f9f9f9; border-radius: 5px; margin-bottom: 20px;">
      <h2 style="color: #222; font-size: 23px; margin-bottom: 20px; position: relative;">Gestion des employés</h2>
    </div>

    <?php
    // Affichage des messages avec icônes
    if (!empty($_SESSION['flash_message'])) {
      $type = $_SESSION['flash_message']['type'] === 'success' ? 'alert-success' : 'alert-error';
      $icon = $_SESSION['flash_message']['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
      $text = htmlspecialchars($_SESSION['flash_message']['text']);
      echo "<div class='alert $type'><i class='bi $icon'></i> $text</div>";
      unset($_SESSION['flash_message']);
    }

    // Affichage des messages simples
    if (!empty($message)) {
      $type = strpos(strtolower($message), 'erreur') !== false ? 'alert-error' : 'alert-success';
      $icon = strpos(strtolower($message), 'erreur') !== false ? 'bi-exclamation-triangle' : 'bi-check-circle';
      echo "<div class='alert $type'><i class='bi $icon'></i> " . htmlspecialchars($message) . "</div>";
    }
    ?>
    <div class="form-section">
      <h3 class="section-title">Ajouter un employé</h3>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-group">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" placeholder="Nom" required>
        </div>
        <div class="form-group">
          <label for="prenom">Prénom</label>
          <input type="text" id="prenom" name="prenom" placeholder="Prénom" required>
        </div>
        <div class="form-group">
          <label for="poste">Poste</label>
          <input type="text" id="poste" name="poste" placeholder="Poste" required>
        </div>
        <div class="form-group">
          <label for="salaire">Salaire (€)</label>
          <input type="number" id="salaire" name="salaire" placeholder="Salaire" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label for="date_embauche">Date d'embauche</label>
          <input type="date" id="date_embauche" name="date_embauche" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <button type="submit" class="submit-btn">Ajouter l'employé</button>
        </div>
      </form>
    </div>
    <h3 class="section-title" style="margin-top: 30px;">Liste des employés</h3>
    <div class="table-responsive-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Poste</th>
            <th>Salaire</th>
            <th>Date embauche</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employes as $e): ?>
            <tr>
              <td><?= htmlspecialchars($e['EmployeID']) ?></td>
              <td><?= htmlspecialchars($e['Nom']) ?></td>
              <td><?= htmlspecialchars($e['Prenom']) ?></td>
              <td><?= htmlspecialchars($e['Poste']) ?></td>
              <td><strong><?= number_format(htmlspecialchars($e['Salaire']), 2, ',', ' ') ?> €</strong></td>
              <td><?= date('d/m/Y', strtotime($e['DateEmbauche'])) ?></td>
              <td class="action-cell">
                <form method="post" action="" class="delete-form">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="delete_employe" value="<?= $e['EmployeID'] ?>">
                  <button type="submit" class="delete-btn" onclick="return confirm('Supprimer cet employé ?')"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Précédent</a>
          <?php endif; ?>

          <?php
          // Afficher max 5 pages avec la page actuelle au centre si possible
          $start_page = max(1, min($page - 2, $total_pages - 4));
          $end_page = min($total_pages, max(5, $page + 2));
          $start_page = max(1, min($start_page, $total_pages - ($end_page - $start_page)));

          for ($i = $start_page; $i <= $end_page; $i++):
          ?>
            <?php if ($i == $page): ?>
              <strong class="active-page"><?= $i ?></strong>
            <?php else: ?>
              <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Suivant &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php
  include 'footer_template.php';
  ?>
</body>

</html>