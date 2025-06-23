<?php

require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';
$message = '';

// Ajout d'un client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  // Vérification du token CSRF
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = $_POST['email'] ?? '';
    $tel = trim($_POST['telephone'] ?? '');
    // Validation centralisée
    $valid = validate_nom($nom) && validate_prenom($prenom) && validate_email($email) && validate_telephone($tel);
    if ($valid) {
      $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, ?, ?, ?)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$nom, $prenom, $email, $tel]);
      set_message('✅ Client ajouté avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
    }
  }
}
// Suppression d'un client sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $id = intval($_POST['delete_client']);
    // Vérification d'existence du client
    $check = $pdo->prepare("SELECT COUNT(*) FROM Clients WHERE ClientID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Ce client n’existe pas ou a déjà été supprimé.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $pdo->prepare("DELETE FROM Clients WHERE ClientID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Client supprimé avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}
// Modification d'un client (édition)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_client'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
  } else {
    $id = intval($_POST['edit_client']);
    // Vérification d'existence du client
    $check = $pdo->prepare("SELECT COUNT(*) FROM Clients WHERE ClientID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Ce client n’existe pas ou a déjà été supprimé.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = $_POST['email'] ?? '';
    $tel = trim($_POST['telephone'] ?? '');
    $valid = validate_nom($nom) && validate_prenom($prenom) && validate_email($email) && validate_telephone($tel);
    if ($valid) {
      $sql = "UPDATE Clients SET Nom=?, Prenom=?, Email=?, Telephone=? WHERE ClientID=?";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$nom, $prenom, $email, $tel, $id]);
      set_message('✏️ Client modifié avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
    }
  }
}
// Pagination
$clients_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $clients_per_page;
$total_clients = $pdo->query("SELECT COUNT(*) FROM Clients")->fetchColumn();
$total_pages = ceil($total_clients / $clients_per_page);
$clients = $pdo->query("SELECT * FROM Clients ORDER BY ClientID DESC LIMIT $clients_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Clients</title>
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
  <h1>Clients</h1>
  <a href="admin/index.php">Retour admin</a>
  <form method="post" id="clientForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="text" name="nom" id="nom" placeholder="Nom" required>
    <input type="text" name="prenom" id="prenom" placeholder="Prénom" required>
    <input type="email" name="email" id="email" placeholder="Email" required>
    <input type="text" name="telephone" id="telephone" placeholder="Téléphone" required>
    <div id="form-error" class="form-error hidden"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('clientForm');
  if (!form) return;

  const nom = form.querySelector('[name="nom"]');
  const prenom = form.querySelector('[name="prenom"]');
  const email = form.querySelector('[name="email"]');
  const telephone = form.querySelector('[name="telephone"]');
  const errorDiv = document.getElementById('form-error');

  function validateNom(v) { return v.trim().length > 1; }
  function validatePrenom(v) { return v.trim().length > 1; }
  function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  function validateTelephone(v) { return v.trim().length >= 8; }

  function checkField(el, validate) {
    const valid = validate(el.value);
    el.classList.toggle('input-error', !valid);
    return valid;
  }

  function validateAll() {
    let ok = true;
    ok &= checkField(nom, validateNom);
    ok &= checkField(prenom, validatePrenom);
    ok &= checkField(email, validateEmail);
    ok &= checkField(telephone, validateTelephone);
    return !!ok;
  }

  [nom, prenom, email, telephone].forEach((el, i) => {
    const validators = [validateNom, validatePrenom, validateEmail, validateTelephone];
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
      <th>Prénom</th>
      <th>Email</th>
      <th>Téléphone</th>
      <th>Action</th>
    </tr>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['ClientID']) ?></td>
        <td><?= htmlspecialchars($c['Nom']) ?></td>
        <td><?= htmlspecialchars($c['Prenom']) ?></td>
        <td><?= htmlspecialchars($c['Email']) ?></td>
        <td><?= htmlspecialchars($c['Telephone']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Supprimer ce client ?')">
            <input type="hidden" name="delete_client" value="<?= $c['ClientID'] ?>">
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