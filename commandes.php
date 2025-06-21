<?php

require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';
$message = '';

// Ajout d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $reservation_id = $_POST['reservation_id'] ?? '';
    $menu_id = $_POST['menu_id'] ?? '';
    $quantite = $_POST['quantite'] ?? '';
    $valid = validate_quantite($quantite) && validate_numero_table($reservation_id) && validate_numero_table($menu_id);
    if ($valid) {
      $sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite, DateCommande, Statut) VALUES (?, ?, ?, NOW(), 'En attente')";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$reservation_id, $menu_id, $quantite]);
      set_message('✅ Commande ajoutée avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
    }
  }
}
// Suppression d'une commande sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_commande'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $id = intval($_POST['delete_commande']);
    // Vérification d'existence de la commande
    $check = $conn->prepare("SELECT COUNT(*) FROM Commandes WHERE CommandeID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette commande n’existe pas ou a déjà été supprimée.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $conn->prepare("DELETE FROM Commandes WHERE CommandeID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Commande supprimée avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}
// Pagination
$commandes_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $commandes_per_page;
$total_commandes = $conn->query("SELECT COUNT(*) FROM Commandes")->fetchColumn();
$total_pages = ceil($total_commandes / $commandes_per_page);
$commandes = $conn->query("SELECT * FROM Commandes ORDER BY CommandeID DESC LIMIT $commandes_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Commandes</title>
  <style>
    .admin-table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    .admin-table th { background: #f5f5f5; }
    .delete-btn { background: none; border: none; color: #b01e28; cursor: pointer; font-size: 1em; }
    .pagination { margin: 20px 0; }
    .pagination a, .pagination strong { margin: 0 5px; text-decoration: none; }
    .pagination strong { color: #1976d2; }
    .input-error { border: 1px solid red !important; }
    .form-error { color: #c62828; font-weight: bold; margin: 10px 0; }
  </style>
</head>

<body>
  <?php display_message(); ?>
  <h1>Commandes</h1>
  <a href="admin/index.php">Retour admin</a>
  <form method="post" id="commandeForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="number" name="reservation_id" id="reservation_id" placeholder="ID réservation" required min="1">
    <input type="number" name="menu_id" id="menu_id" placeholder="ID menu" required min="1">
    <input type="number" name="quantite" id="quantite" placeholder="Quantité" required min="1">
    <div id="form-error" class="form-error hidden"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('commandeForm');
      if (!form) return;
      const reservation_id = form.querySelector('[name="reservation_id"]');
      const menu_id = form.querySelector('[name="menu_id"]');
      const quantite = form.querySelector('[name="quantite"]');
      const errorDiv = document.getElementById('form-error');

      function validateId(v) { return v && !isNaN(v) && Number(v) > 0; }
      function validateQuantite(v) { return v && !isNaN(v) && Number(v) > 0; }

      function checkField(el, validate) {
        const valid = validate(el.value);
        el.classList.toggle('input-error', !valid);
        return valid;
      }

      function validateAll() {
        let ok = true;
        ok &= checkField(reservation_id, validateId);
        ok &= checkField(menu_id, validateId);
        ok &= checkField(quantite, validateQuantite);
        return !!ok;
      }

      [reservation_id, menu_id, quantite].forEach((el, i) => {
        const validators = [validateId, validateId, validateQuantite];
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
      <th>Menu</th>
      <th>Quantité</th>
      <th>Action</th>
    </tr>
    <?php foreach ($commandes as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['CommandeID']) ?></td>
        <td><?= htmlspecialchars($c['ReservationID']) ?></td>
        <td><?= htmlspecialchars($c['MenuID']) ?></td>
        <td><?= htmlspecialchars($c['Quantite']) ?></td>
        <td>
          <form method="post" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="delete_commande" value="<?= $c['CommandeID'] ?>">
            <button type="submit" class="delete-btn" onclick="return confirm('Supprimer cette commande ?')">Supprimer</button>
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