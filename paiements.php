<?php

require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';
$message = '';

// Ajout d'un paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $reservation_id = $_POST['reservation_id'] ?? '';
    $montant = $_POST['montant'] ?? '';
    $date = $_POST['date_paiement'] ?? '';
    $mode = trim($_POST['mode'] ?? '');
    $valid = validate_numero_table($reservation_id) && validate_prix($montant) && validate_date($date);
    if ($valid) {
      $sql = "INSERT INTO Paiements (ReservationID, Montant, DatePaiement, ModePaiement) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$reservation_id, $montant, $date, $mode]);
      set_message('✅ Paiement ajouté avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}
// Suppression d'un paiement sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_paiement'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $id = intval($_POST['delete_paiement']);
    // Vérification d'existence du paiement
    $check = $conn->prepare("SELECT COUNT(*) FROM Paiements WHERE PaiementID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Ce paiement n’existe pas ou a déjà été supprimé.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $conn->prepare("DELETE FROM Paiements WHERE PaiementID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Paiement supprimé avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}
// Pagination
$paiements_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $paiements_per_page;
$total_paiements = $conn->query("SELECT COUNT(*) FROM Paiements")->fetchColumn();
$total_pages = ceil($total_paiements / $paiements_per_page);
$paiements = $conn->query("SELECT * FROM Paiements ORDER BY PaiementID DESC LIMIT $paiements_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Paiements</title>
  <style>
    .admin-table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 20px;
    }

    .admin-table th,
    .admin-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
    }

    .admin-table th {
      background: #f5f5f5;
    }

    .delete-btn {
      background: none;
      border: none;
      color: #1976d2;
      cursor: pointer;
      font-size: 1em;
    }

    .pagination {
      margin: 20px 0;
    }

    .pagination a,
    .pagination strong {
      margin: 0 5px;
      text-decoration: none;
    }

    .pagination strong {
      color: #1976d2;
    }

    .input-error {
      border: 1px solid red !important;
    }

    .form-error {
      color: #c62828;
      font-weight: bold;
      margin: 10px 0;
    }
  </style>
</head>

<body>
  <?php display_message(); ?>
  <h1>Paiements</h1>
  <a href="admin/index.php">Retour admin</a>
  <form method="post" id="paiementForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="number" name="reservation_id" id="reservation_id" placeholder="ID réservation" required min="1">
    <input type="number" name="montant" id="montant" placeholder="Montant" step="0.01" min="0" required>
    <input type="date" name="date_paiement" id="date_paiement" required>
    <input type="text" name="mode" id="mode" placeholder="Mode de paiement">
    <div id="form-error" class="form-error" style="display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('paiementForm');
      if (!form) return;
      const reservation_id = form.querySelector('[name="reservation_id"]');
      const montant = form.querySelector('[name="montant"]');
      const date_paiement = form.querySelector('[name="date_paiement"]');
      const errorDiv = document.getElementById('form-error');

      function validateReservationId(v) {
        return v && !isNaN(v) && Number(v) > 0;
      }

      function validateMontant(v) {
        return v && !isNaN(v) && Number(v) > 0;
      }

      function validateDate(v) {
        return !!v;
      }

      function checkField(el, validate) {
        const valid = validate(el.value);
        el.classList.toggle('input-error', !valid);
        return valid;
      }

      function validateAll() {
        let ok = true;
        ok &= checkField(reservation_id, validateReservationId);
        ok &= checkField(montant, validateMontant);
        ok &= checkField(date_paiement, validateDate);
        return !!ok;
      }

      [reservation_id, montant, date_paiement].forEach((el, i) => {
        const validators = [validateReservationId, validateMontant, validateDate];
        el.addEventListener('input', () => {
          checkField(el, validators[i]);
          if (validateAll()) {
            errorDiv.style.display = 'none';
          }
        });
        el.addEventListener('blur', () => checkField(el, validators[i]));
      });

      form.addEventListener('submit', function(e) {
        if (!validateAll()) {
          e.preventDefault();
          errorDiv.textContent = "Merci de corriger les champs invalides.";
          errorDiv.style.display = 'block';
          return false;
        } else {
          errorDiv.style.display = 'none';
        }
      });
    });
  </script>
  <table class="admin-table">
    <tr>
      <th>ID</th>
      <th>Réservation</th>
      <th>Montant</th>
      <th>Date</th>
      <th>Mode</th>
      <th>Action</th>
    </tr>
    <?php foreach ($paiements as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['PaiementID']) ?></td>
        <td><?= htmlspecialchars($p['ReservationID']) ?></td>
        <td><?= htmlspecialchars($p['Montant']) ?></td>
        <td><?= htmlspecialchars($p['DatePaiement']) ?></td>
        <td><?= htmlspecialchars($p['ModePaiement']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Supprimer ce paiement ?')">
            <input type="hidden" name="delete_paiement" value="<?= $p['PaiementID'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" class="delete-btn">Supprimer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
          <strong>[<?= $i ?>]</strong>
        <?php else: ?>
          <a href="?page=<?= $i ?>">[<?= $i ?>]</a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</body>

</html>