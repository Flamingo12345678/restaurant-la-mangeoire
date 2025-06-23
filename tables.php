<?php
// filepath: /Users/flamingo/Documents/Site Web/restaurant-la-mangeoire/tables.php
require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';

// Modification du statut d'une table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_statut_table'], $_POST['edit_table_id'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $edit_id = intval($_POST['edit_table_id']);
    $edit_statut = in_array($_POST['edit_statut_table'], ['Libre', 'Réservée', 'Maintenance']) ? $_POST['edit_statut_table'] : 'Libre';
    $check = $pdo->prepare("SELECT COUNT(*) FROM TablesRestaurant WHERE TableID=?");
    $check->execute([$edit_id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette table n’existe pas.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $pdo->prepare("UPDATE TablesRestaurant SET Statut=? WHERE TableID=?");
    $stmt->execute([$edit_statut, $edit_id]);
    set_message('✅ Statut de la table modifié.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Ajout d'une table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $numero = $_POST['numero'] ?? '';
    $places = $_POST['places'] ?? '';
    $valid = validate_numero_table($numero) && validate_places($places);
    if ($valid) {
      $sql = "INSERT INTO TablesRestaurant (NumeroTable, Capacite) VALUES (?, ?)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$numero, $places]);
      set_message('✅ Table ajoutée avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}

// Suppression d'une table sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $id = intval($_POST['delete_table']);
    // Vérification d'existence de la table
    $check = $pdo->prepare("SELECT COUNT(*) FROM TablesRestaurant WHERE TableID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette table n’existe pas ou a déjà été supprimée.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $pdo->prepare("DELETE FROM TablesRestaurant WHERE TableID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Table supprimée avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Pagination
$tables_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $tables_per_page;
$total_tables = $pdo->query("SELECT COUNT(*) FROM TablesRestaurant")->fetchColumn();
$total_pages = ceil($total_tables / $tables_per_page);
$tables = $pdo->query("SELECT * FROM TablesRestaurant ORDER BY TableID DESC LIMIT $tables_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tables</title>
  <style>
    .admin-table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    .admin-table th { background: #f5f5f5; }
    .delete-btn { background: none; border: none; color: #b01e28; cursor: pointer; font-size: 1.1em; }
    .statut-select { padding: 2px 6px; }
    .pagination { margin: 20px 0; }
    .pagination a, .pagination strong { margin: 0 5px; text-decoration: none; }
    .pagination strong { color: #1976d2; }
    .input-error { border: 1px solid red !important; }
    .form-error { color: #c62828; font-weight: bold; margin: 10px 0; }
  </style>
</head>
<body>
  <?php display_message(); ?>
  <h1>Tables</h1>
  <a href="admin/index.php">Retour admin</a>
  <form method="post" id="tableForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="number" name="numero" id="numero" placeholder="Numéro de table" required>
    <input type="number" name="places" id="places" placeholder="Nombre de places" required>
    <div id="form-error" class="form-error" style="display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <table class="admin-table">
    <tr>
      <th>ID</th>
      <th>Numéro</th>
      <th>Places</th>
      <th>Statut</th>
      <th>Suppression</th>
    </tr>
    <?php foreach ($tables as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['TableID']) ?></td>
        <td><?= htmlspecialchars($t['NumeroTable']) ?></td>
        <td><?= htmlspecialchars($t['Capacite']) ?></td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="edit_table_id" value="<?= $t['TableID'] ?>">
            <select name="edit_statut_table" class="statut-select" onchange="this.form.submit()">
              <option value="Libre" <?= (isset($t['Statut']) && $t['Statut'] === 'Libre') ? 'selected' : '' ?>>Libre</option>
              <option value="Réservée" <?= (isset($t['Statut']) && $t['Statut'] === 'Réservée') ? 'selected' : '' ?>>Réservée</option>
              <option value="Maintenance" <?= (isset($t['Statut']) && $t['Statut'] === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
            </select>
          </form>
        </td>
        <td>
          <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette table ?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="delete_table" value="<?= $t['TableID'] ?>">
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
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('tableForm');
    if (!form) return;
    const numero = form.querySelector('[name="numero"]');
    const places = form.querySelector('[name="places"]');
    const errorDiv = document.getElementById('form-error');

    function validateNumero(v) { return v && !isNaN(v) && Number(v) > 0; }
    function validatePlaces(v) { return v && !isNaN(v) && Number(v) > 0; }

    function checkField(el, validate) {
      const valid = validate(el.value);
      el.classList.toggle('input-error', !valid);
      return valid;
    }

    function validateAll() {
      let ok = true;
      ok &= checkField(numero, validateNumero);
      ok &= checkField(places, validatePlaces);
      return !!ok;
    }

    [numero, places].forEach((el, i) => {
      const validators = [validateNumero, validatePlaces];
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
</body>
</html>