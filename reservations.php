<?php

require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';
$message = '';

// Modification du statut d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_statut'], $_POST['edit_id'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $edit_id = intval($_POST['edit_id']);
    $edit_statut = ($_POST['edit_statut'] === 'Annulée') ? 'Annulée' : 'Réservée';
    $check = $conn->prepare("SELECT COUNT(*) FROM Reservations WHERE ReservationID=?");
    $check->execute([$edit_id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette réservation n’existe pas.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $conn->prepare("UPDATE Reservations SET statut=? WHERE ReservationID=?");
    $stmt->execute([$edit_statut, $edit_id]);
    set_message('✅ Statut de la réservation modifié.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Suppression d'une réservation sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $id = intval($_POST['delete_reservation']);
    // Vérification d'existence de la réservation
    $check = $conn->prepare("SELECT COUNT(*) FROM Reservations WHERE ReservationID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette réservation n’existe pas ou a déjà été supprimée.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $conn->prepare("DELETE FROM Reservations WHERE ReservationID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Réservation supprimée avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Ajout d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $nom = trim($_POST['nom_client'] ?? '');
    $email = $_POST['email_client'] ?? '';
    $date = $_POST['DateReservation'] ?? '';
    $statut = trim($_POST['statut'] ?? 'Réservée');
    $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;
    $valid = validate_nom($nom) && validate_email($email) && validate_date(substr($date, 0, 10)) && $nb_personnes > 0;
    if ($valid) {
      $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut, nb_personnes) VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$nom, $email, $date, $statut, $nb_personnes]);
      set_message('✅ Réservation ajoutée avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}

// Pagination
$reservations_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $reservations_per_page;
$total_reservations = $conn->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
$total_pages = ceil($total_reservations / $reservations_per_page);
$reservations = $conn->query("SELECT * FROM Reservations ORDER BY ReservationID DESC LIMIT $reservations_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Réservations</title>
</head>

<body>
  <?php display_message(); ?>
  <h1>Réservations</h1>
  <a href="admin/index.php">Retour admin</a>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="text" name="nom_client" placeholder="Nom du client" required>
    <input type="email" name="email_client" placeholder="Email du client" required>
    <input type="datetime-local" name="DateReservation" required>
    <input type="number" name="nb_personnes" placeholder="Nombre de personnes" min="1" required>
    <input type="text" name="statut" placeholder="Statut (Réservée/Annulée)">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <table borde="1" cellpadding="5">
    <tr>
      <th>ID</th>
      <th>Nom</th>
      <th>Email</th>
      <th>Date</th>
      <th>Statut</th>
      <th>Action</th>
    </tr>
    <?php foreach ($reservations as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['ReservationID']) ?></td>
        <td><?= htmlspecialchars($r['nom_client']) ?></td>
        <td><?= htmlspecialchars($r['email_client']) ?></td>
        <td><?= htmlspecialchars($r['DateReservation']) ?></td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="edit_id" value="<?= $r['ReservationID'] ?>">
            <select name="edit_statut" onchange="this.form.submit()" style="padding:2px 6px;">
              <option value="Réservée" <?= ($r['statut'] === 'Réservée') ? 'selected' : '' ?>>Réservée</option>
              <option value="Annulée" <?= ($r['statut'] === 'Annulée') ? 'selected' : '' ?>>Annulée</option>
            </select>
          </form>
        </td>
        <td>
          <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette réservation ?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="delete_reservation" value="<?= $r['ReservationID'] ?>">
            <button type="submit" style="background:none;border:none;color:#b01e28;cursor:pointer;">Supprimer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if ($total_pages > 1): ?>
    <div class="pagination" style="margin:20px 0;">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
          <strong style="margin:0 5px; color:#1976d2;">[<?= $i ?>]</strong>
        <?php else: ?>
          <a href="?page=<?= $i ?>" style="margin:0 5px;">[<?= $i ?>]</a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('form[method="post"]:not([style])'); // cible le formulaire d'ajout
  if (!form) return;

  const nom = form.querySelector('[name="nom_client"]');
  const email = form.querySelector('[name="email_client"]');
  const date = form.querySelector('[name="DateReservation"]');
  const nb = form.querySelector('[name="nb_personnes"]');

  function validateNom(v) { return v.trim().length > 1; }
  function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  function validateDate(v) { return !!v; }
  function validateNb(v) { return Number(v) > 0; }

  function checkField(el, validate) {
    const valid = validate(el.value);
    el.classList.toggle('input-error', !valid);
    return valid;
  }

  [nom, email, date, nb].forEach((el, i) => {
    if (!el) return;
    const validators = [validateNom, validateEmail, validateDate, validateNb];
    el.addEventListener('input', () => checkField(el, validators[i]));
    el.addEventListener('blur', () => checkField(el, validators[i]));
  });

  form.addEventListener('submit', function(e) {
    let ok = true;
    ok &= checkField(nom, validateNom);
    ok &= checkField(email, validateEmail);
    ok &= checkField(date, validateDate);
    ok &= checkField(nb, validateNb);
    if (!ok) {
      e.preventDefault();
      let msg = form.querySelector('.form-error');
      if (!msg) {
        msg = document.createElement('div');
        msg.className = 'form-error';
        msg.style.color = 'red';
        msg.style.margin = '10px 0';
        form.prepend(msg);
      }
      msg.textContent = "Merci de corriger les champs invalides.";
    }
  });
});
</script>
</body>

</html>