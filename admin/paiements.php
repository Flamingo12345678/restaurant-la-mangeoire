<?php
$message = '';
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['montant'], $_POST['date_paiement'])) {
  $reservation_id = intval($_POST['reservation_id']);
  $montant = floatval($_POST['montant']);
  $date = $_POST['date_paiement'];
  $mode = trim($_POST['mode'] ?? '');
  if ($reservation_id > 0 && $montant > 0 && $date) {
    $sql = "INSERT INTO Paiements (ReservationID, Montant, DatePaiement, ModePaiement) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$reservation_id, $montant, $date, $mode]);
    if ($result) {
      $message = 'Paiement ajouté.';
    } else {
      $message = 'Erreur lors de l\'ajout.';
    }
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression sécurisée d'un paiement (POST + CSRF + contrôle d'accès)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_paiement_id'], $_POST['csrf_token'])) {
  if (!verify_csrf_token($_POST['csrf_token'])) {
    set_message('Token CSRF invalide.', 'error');
    header('Location: paiements.php');
    exit;
  }
  $id = intval($_POST['delete_paiement_id']);
  $sql = "DELETE FROM Paiements WHERE PaiementID = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if ($stmt->execute([$id])) {
      set_message('Paiement supprimé.', 'success');
    } else {
      set_message('Erreur lors de la suppression.', 'error');
    }
  } else {
    set_message('Erreur lors de la préparation.', 'error');
  }
  header('Location: paiements.php');
  exit;
}
// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total_sql = "SELECT COUNT(*) FROM Paiements";
$total_paiements = $conn->query($total_sql)->fetchColumn();
$total_pages = ceil($total_paiements / $per_page);
$paiements = [];
$sql = "SELECT * FROM Paiements ORDER BY PaiementID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $paiements[] = $row;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Paiements - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Styles spécifiques pour la page paiements sur mobile */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .form-group[style*="grid-column: span"] {
        grid-column: 1 !important;
      }

      .table-responsive-wrapper {
        margin: 0 -15px;
        width: calc(100% + 30px);
        border-radius: 0;
      }

      .admin-table th:nth-child(1),
      .admin-table td:nth-child(1),
      .admin-table th:nth-child(2),
      .admin-table td:nth-child(2) {
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
    }
  </style>
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Paiements";

  // Indiquer que ce fichier est inclus dans une page
  define('INCLUDED_IN_PAGE', true);
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <div style="background-color: #f9f9f9; border-radius: 5px; margin-bottom: 20px;">
      <h2 style="color: #222; font-size: 23px; margin-bottom: 20px; position: relative;">Gestion des paiements</h2>
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

    <!-- Formulaire d'ajout -->
    <div class="form-section">
      <h3 class="section-title">Ajouter un paiement</h3>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="form-group">
          <label for="reservation_id">ID Réservation</label>
          <input type="number" id="reservation_id" name="reservation_id" placeholder="ID Réservation" required>
        </div>
        <div class="form-group">
          <label for="montant">Montant (€)</label>
          <input type="number" id="montant" name="montant" placeholder="Montant" required step="0.01">
        </div>
        <div class="form-group">
          <label for="date_paiement">Date</label>
          <input type="date" id="date_paiement" name="date_paiement" placeholder="Date de paiement" required>
        </div>
        <div class="form-group">
          <label for="mode">Mode de paiement</label>
          <input type="text" id="mode" name="mode" placeholder="Ex: CB, Espèces, Chèque...">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <button type="submit" class="submit-btn">Ajouter le paiement</button>
        </div>
      </form>
    </div>

    <!-- Tableau des paiements -->
    <h3 class="section-title" style="margin-top: 30px;">Liste des paiements</h3>
    <div class="table-responsive-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Réservation</th>
            <th>Montant</th>
            <th>Date</th>
            <th>Mode</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paiements as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['PaiementID']) ?></td>
              <td><?= htmlspecialchars($p['ReservationID']) ?></td>
              <td><strong><?= number_format(htmlspecialchars($p['Montant']), 2, ',', ' ') ?> €</strong></td>
              <td><?= date('d/m/Y', strtotime($p['DatePaiement'])) ?></td>
              <td><?= htmlspecialchars($p['ModePaiement'] ?: 'Non spécifié') ?></td>
              <td class="action-cell">
                <form method="post" action="paiements.php" class="delete-form">
                  <input type="hidden" name="delete_paiement_id" value="<?= htmlspecialchars($p['PaiementID']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                  <button type="submit" class="delete-btn" onclick="return confirm('Supprimer ce paiement ?')" title="Supprimer">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <strong class="active-page"><?= $i ?></strong>
          <?php else: ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php
  include 'footer_template.php';
  ?>
</body>

</html>