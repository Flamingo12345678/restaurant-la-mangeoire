<?php
require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';

// Ajout d'un menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prix = $_POST['prix'] ?? '';
    $valid = validate_nom($nom) && validate_prix($prix);
    if ($valid) {
      $sql = "INSERT INTO Menus (NomItem, Prix) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$nom, $prix]);
      set_message('✅ Menu ajouté avec succès.');
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
    }
  }
}

// Suppression d'un menu sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_menu'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $id = intval($_POST['delete_menu']);
    // Vérification d'existence du menu
    $check = $conn->prepare("SELECT COUNT(*) FROM Menus WHERE MenuID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Ce menu n’existe pas ou a déjà été supprimé.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $conn->prepare("DELETE FROM Menus WHERE MenuID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Menu supprimé avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Pagination
$menus_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $menus_per_page;
$total_menus = $conn->query("SELECT COUNT(*) FROM Menus")->fetchColumn();
$total_pages = ceil($total_menus / $menus_per_page);
$menus = $conn->query("SELECT * FROM Menus ORDER BY MenuID DESC LIMIT $menus_per_page OFFSET $offset")->fetchAll();

// Removed duplicate function as it's already defined in common.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Menus</title>
  <style>
    .admin-table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    .admin-table th { background: #f5f5f5; }
    .delete-btn { background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1em; }
    .pagination { margin: 20px 0; }
    .pagination a, .pagination strong { margin: 0 5px; text-decoration: none; }
    .pagination strong { color: #1976d2; }
    .input-error { border: 1px solid red !important; }
    .form-error { color: #c62828; font-weight: bold; margin: 10px 0; }
  </style>
</head>
<body>
  <?php display_message(); ?>
  <h1>Menus</h1>
  <a href="admin/index.php">Retour admin</a>
  <form method="post" id="menuForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="text" name="nom" id="nom" placeholder="Nom du menu" required>
    <input type="number" name="prix" id="prix" placeholder="Prix" step="0.01" min="0" required>
    <div id="form-error" class="form-error" style="display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('menuForm');
      if (!form) return;
      const nom = form.querySelector('[name="nom"]');
      const prix = form.querySelector('[name="prix"]');
      const errorDiv = document.getElementById('form-error');

      function validateNom(v) { return v.trim().length > 1; }
      function validatePrix(v) { return v && !isNaN(v) && parseFloat(v) > 0; }

      function checkField(el, validate) {
        const valid = validate(el.value);
        el.classList.toggle('input-error', !valid);
        return valid;
      }

      function validateAll() {
        let ok = true;
        ok &= checkField(nom, validateNom);
        ok &= checkField(prix, validatePrix);
        return !!ok;
      }

      [nom, prix].forEach((el, i) => {
        const validators = [validateNom, validatePrix];
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
      <th>Nom</th>
      <th>Prix</th>
      <th>Action</th>
    </tr>
    <?php foreach ($menus as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['MenuID']) ?></td>
        <td><?= htmlspecialchars($m['NomItem']) ?></td>
        <td><?= htmlspecialchars($m['Prix']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Supprimer ce menu ?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="delete_menu" value="<?= $m['MenuID'] ?>">
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