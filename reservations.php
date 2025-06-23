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
    $check = $pdo->prepare("SELECT COUNT(*) FROM Reservations WHERE ReservationID=?");
    $check->execute([$edit_id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette réservation n’existe pas.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $pdo->prepare("UPDATE Reservations SET Statut=? WHERE ReservationID=?");
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
    $check = $pdo->prepare("SELECT COUNT(*) FROM Reservations WHERE ReservationID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cette réservation n’existe pas ou a déjà été supprimée.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $pdo->prepare("DELETE FROM Reservations WHERE ReservationID=?");
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
    $telephone = $_POST['telephone'] ?? '';
    $message = trim($_POST['message'] ?? '');
    $date = $_POST['DateReservation'] ?? '';
    $statut = trim($_POST['statut'] ?? 'Réservée');
    $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;
    $clientID = isset($_POST['ClientID']) && !empty($_POST['ClientID']) ? intval($_POST['ClientID']) : null;
    
    $valid = validate_nom($nom) && validate_email($email) && validate_date(substr($date, 0, 10)) && $nb_personnes > 0;
    if ($valid) {
      $sql = "INSERT INTO Reservations (ClientID, nom_client, email_client, telephone, message, DateReservation, Statut, nb_personnes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$clientID, $nom, $email, $telephone, $message, $date, $statut, $nb_personnes]);
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
$total_reservations = $pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
$total_pages = ceil($total_reservations / $reservations_per_page);
$reservations = $pdo->query("SELECT * FROM Reservations ORDER BY ReservationID DESC LIMIT $reservations_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réservations - Restaurant La Mangeoire</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      margin: 0;
      padding: 20px;
      background-color: #f5f5f5;
      line-height: 1.6;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 {
      color: #333;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #007cba;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      padding: 8px 16px;
      background-color: #6c757d;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      transition: background-color 0.3s;
    }
    .back-link:hover {
      background-color: #5a6268;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
    }
    tr:hover {
      background-color: #f8f9fa;
    }
    .select-status {
      padding: 4px 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
      background-color: white;
    }
    .btn-link {
      background: #dc3545;
      color: white;
      border: none;
      padding: 4px 8px;
      border-radius: 3px;
      cursor: pointer;
      font-size: 12px;
    }
    .btn-link:hover {
      background: #c82333;
    }
    .pagination {
      margin-top: 20px;
      text-align: center;
    }
    .pagination-link {
      display: inline-block;
      padding: 8px 12px;
      margin: 0 2px;
      text-decoration: none;
      border: 1px solid #ddd;
      border-radius: 4px;
      color: #007cba;
    }
    .pagination-link:hover {
      background-color: #e9ecef;
    }
    .pagination-current {
      display: inline-block;
      padding: 8px 12px;
      margin: 0 2px;
      background-color: #007cba;
      color: white;
      border-radius: 4px;
    }
    .input-error {
      border-color: #dc3545 !important;
      background-color: #f8d7da !important;
    }
    .form-error {
      color: #dc3545;
      font-weight: bold;
      margin: 10px 0;
    }
    .message {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    .message.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .message.success {
      background-color: #d1edff;
      color: #0c5460;
      border: 1px solid #b8daff;
    }
    @media (max-width: 768px) {
      body { padding: 10px; }
      .container { padding: 15px; }
      table { 
        font-size: 12px; 
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }
      th, td { padding: 6px 4px; }
      td[style*="max-width"] {
        max-width: 120px !important;
      }
    }
  </style>
</head>

<body>
  <div class="container">
  <?php display_message(); ?>
  <h1>Gestion des Réservations</h1>
  <a href="admin/index.php" class="back-link">← Retour au Dashboard Admin</a>
  
  <!-- Statistiques rapides -->
  <?php
  $stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn(),
    'reservees' => $pdo->query("SELECT COUNT(*) FROM Reservations WHERE Statut = 'Réservée'")->fetchColumn(),
    'annulees' => $pdo->query("SELECT COUNT(*) FROM Reservations WHERE Statut = 'Annulée'")->fetchColumn(),
    'aujourdhui' => $pdo->query("SELECT COUNT(*) FROM Reservations WHERE DATE(DateReservation) = CURDATE()")->fetchColumn(),
  ];
  ?>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
      <h3 style="margin: 0 0 5px 0; color: #1976d2;">Total</h3>
      <p style="margin: 0; font-size: 24px; font-weight: bold;"><?= $stats['total'] ?></p>
    </div>
    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;">
      <h3 style="margin: 0 0 5px 0; color: #388e3c;">Réservées</h3>
      <p style="margin: 0; font-size: 24px; font-weight: bold;">✅ <?= $stats['reservees'] ?></p>
    </div>
    <div style="background: #ffebee; padding: 15px; border-radius: 8px; text-align: center;">
      <h3 style="margin: 0 0 5px 0; color: #d32f2f;">Annulées</h3>
      <p style="margin: 0; font-size: 24px; font-weight: bold;">❌ <?= $stats['annulees'] ?></p>
    </div>
    <div style="background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center;">
      <h3 style="margin: 0 0 5px 0; color: #f57c00;">Aujourd'hui</h3>
      <p style="margin: 0; font-size: 24px; font-weight: bold;">📅 <?= $stats['aujourdhui'] ?></p>
    </div>
  </div>
  <form method="post" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
    <h3 style="margin-top: 0; color: #333;">Ajouter une nouvelle réservation</h3>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nom du client *</label>
        <input type="text" name="nom_client" placeholder="Nom complet du client" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
      </div>
      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email *</label>
        <input type="email" name="email_client" placeholder="email@exemple.com" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
      </div>
      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Téléphone</label>
        <input type="tel" name="telephone" placeholder="01 23 45 67 89" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
      </div>
      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Date et heure *</label>
        <input type="datetime-local" name="DateReservation" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
      </div>
      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre de personnes *</label>
        <input type="number" name="nb_personnes" placeholder="2" min="1" max="20" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
      </div>
      <div>
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">ID Client (optionnel)</label>
        <input type="number" name="ClientID" placeholder="Laisser vide si nouveau client" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
      </div>
    </div>
    <div style="margin-bottom: 15px;">
      <label style="display: block; margin-bottom: 5px; font-weight: bold;">Message ou demande spéciale (optionnel)</label>
      <textarea name="message" placeholder="Allergie, occasion spéciale, demande particulière..." rows="3" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical; font-family: inherit;"></textarea>
    </div>
    <div style="margin-bottom: 15px;">
      <label style="display: block; margin-bottom: 5px; font-weight: bold;">Statut</label>
      <select name="statut" style="width: 200px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <option value="Réservée" selected>Réservée</option>
        <option value="Annulée">Annulée</option>
      </select>
    </div>
    <button type="submit" name="ajouter" style="padding: 12px 25px; background-color: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">✅ Ajouter la Réservation</button>
  </form>
  <table border="1" cellpadding="5" style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f5f5f5;">
      <th>ID Résa</th>
      <th>ID Client</th>
      <th>Nom</th>
      <th>Email</th>
      <th>Téléphone</th>
      <th>Nb Personnes</th>
      <th>Date & Heure</th>
      <th>Message</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($reservations as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['ReservationID']) ?></strong></td>
        <td><?= htmlspecialchars($r['ClientID'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($r['nom_client'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['email_client'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['telephone'] ?? 'Non renseigné') ?></td>
        <td><span style="padding: 2px 6px; background: #e3f2fd; border-radius: 3px;"><?= htmlspecialchars($r['nb_personnes'] ?? '1') ?></span></td>
        <td><?= htmlspecialchars($r['DateReservation'] ?? '') ?></td>
        <td style="max-width: 200px;">
          <?php if (!empty($r['message'])): ?>
            <div style="max-height: 60px; overflow-y: auto; font-size: 13px; line-height: 1.3;">
              💬 <?= htmlspecialchars($r['message']) ?>
            </div>
          <?php else: ?>
            <span style="color: #999; font-style: italic;">Aucun message</span>
          <?php endif; ?>
        </td>
        <td>
          <form method="post" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="edit_id" value="<?= $r['ReservationID'] ?>">
            <select name="edit_statut" onchange="this.form.submit()" class="select-status">
              <option value="Réservée" <?= ($r['Statut'] === 'Réservée') ? 'selected' : '' ?>>✅ Réservée</option>
              <option value="Annulée" <?= ($r['Statut'] === 'Annulée') ? 'selected' : '' ?>>❌ Annulée</option>
            </select>
          </form>
        </td>
        <td>
          <form method="post" class="inline-form" onsubmit="return confirm('⚠️ Supprimer définitivement cette réservation ?\\n\\nClient: <?= htmlspecialchars($r['nom_client']) ?>\\nDate: <?= htmlspecialchars($r['DateReservation']) ?>');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="delete_reservation" value="<?= $r['ReservationID'] ?>">
            <button type="submit" class="btn-link">🗑️ Supprimer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>    </table>
  </div>
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
          <strong class="pagination-current">[<?= $i ?>]</strong>
        <?php else: ?>
          <a href="?page=<?= $i ?>" class="pagination-link">[<?= $i ?>]</a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
  </div>

  <script>
document.addEventListener('DOMContentLoaded', function() {
  // Validation en temps réel du formulaire
  const form = document.querySelector('form[method="post"]');
  if (!form) return;

  const nom = form.querySelector('[name="nom_client"]');
  const email = form.querySelector('[name="email_client"]');
  const date = form.querySelector('[name="DateReservation"]');
  const nb = form.querySelector('[name="nb_personnes"]');
  const tel = form.querySelector('[name="telephone"]');

  function validateNom(v) { return v.trim().length >= 2; }
  function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  function validateDate(v) { 
    if (!v) return false;
    const selectedDate = new Date(v);
    const now = new Date();
    return selectedDate > now; // La réservation doit être dans le futur
  }
  function validateNb(v) { 
    const num = Number(v);
    return num >= 1 && num <= 20; 
  }
  function validateTel(v) {
    // Téléphone optionnel, mais si renseigné doit avoir au moins 10 chiffres
    if (!v.trim()) return true;
    return v.replace(/[^\d]/g, '').length >= 10;
  }

  function checkField(el, validate) {
    const valid = validate(el.value);
    el.style.borderColor = valid ? '#28a745' : '#dc3545';
    el.style.backgroundColor = valid ? '#f8fff8' : '#fff5f5';
    return valid;
  }

  // Validation en temps réel
  if (nom) nom.addEventListener('input', () => checkField(nom, validateNom));
  if (email) email.addEventListener('input', () => checkField(email, validateEmail));
  if (date) date.addEventListener('input', () => checkField(date, validateDate));
  if (nb) nb.addEventListener('input', () => checkField(nb, validateNb));
  if (tel) tel.addEventListener('input', () => checkField(tel, validateTel));

  // Validation avant soumission
  form.addEventListener('submit', function(e) {
    let ok = true;
    if (nom) ok &= checkField(nom, validateNom);
    if (email) ok &= checkField(email, validateEmail);
    if (date) ok &= checkField(date, validateDate);
    if (nb) ok &= checkField(nb, validateNb);
    if (tel) ok &= checkField(tel, validateTel);
    
    if (!ok) {
      e.preventDefault();
      alert('⚠️ Merci de corriger les champs invalides avant de continuer.');
      
      // Focus sur le premier champ invalide
      const invalidField = form.querySelector('input[style*="border-color: rgb(220, 53, 69)"]');
      if (invalidField) invalidField.focus();
    }
  });

  // Préremplir la date avec demain à 19h par défaut
  if (date && !date.value) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(19, 0);
    date.value = tomorrow.toISOString().slice(0, 16);
  }
});
</script>
</body>

</html>