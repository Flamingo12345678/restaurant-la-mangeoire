<?php
require_once 'db_connexion.php';
require_once 'admin/utils.php';
$message = '';
// Ajout d'un paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $montant = floatval($_POST['montant'] ?? 0);
    $date = $_POST['date_paiement'] ?? '';
    $mode = trim($_POST['mode'] ?? '');
    if ($reservation_id > 0 && $montant > 0 && $date) {
      $sql = "INSERT INTO Paiements (ReservationID, Montant, DatePaiement, ModePaiement) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$reservation_id, $montant, $date, $mode]);
      set_message('Paiement ajouté.', 'success');
    } else {
      set_message('Champs invalides.', 'warning');
    }
  }
}
// Suppression d'un paiement
if (isset($_GET['delete'])) {
  if (!isset($_GET['csrf_token']) || !check_csrf_token($_GET['csrf_token'])) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Paiements WHERE PaiementID=?");
    $stmt->execute([$id]);
    set_message('Paiement supprimé.', 'success');
  }
}
// Liste des paiements
$paiements = $conn->query("SELECT * FROM Paiements ORDER BY PaiementID DESC")->fetchAll();
$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Paiements</title>
</head>

<body>
  <h1>Paiements</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <form method="post" id="paiementForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="number" name="reservation_id" id="reservation_id" placeholder="ID réservation" required min="1">
    <input type="number" name="montant" id="montant" placeholder="Montant" step="0.01" min="0" required>
    <input type="date" name="date_paiement" id="date_paiement" required>
    <input type="text" name="mode" id="mode" placeholder="Mode de paiement">
    <div id="form-error" style="color:#c62828; font-weight:bold; display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.getElementById('paiementForm').addEventListener('submit', function(e) {
      var reservation_id = document.getElementById('reservation_id').value;
      var montant = document.getElementById('montant').value;
      var date_paiement = document.getElementById('date_paiement').value;
      var error = '';
      if (!reservation_id || isNaN(reservation_id) || parseInt(reservation_id) < 1) {
        error = 'ID réservation invalide.';
        document.getElementById('reservation_id').focus();
      } else if (!montant || isNaN(montant) || parseFloat(montant) <= 0) {
        error = 'Montant invalide.';
        document.getElementById('montant').focus();
      } else if (!date_paiement) {
        error = 'Veuillez choisir une date.';
        document.getElementById('date_paiement').focus();
      }
      if (error) {
        e.preventDefault();
        document.getElementById('form-error').textContent = error;
        document.getElementById('form-error').style.display = 'block';
        return false;
      } else {
        document.getElementById('form-error').style.display = 'none';
      }
    });
  </script>
  <table border="1" cellpadding="5">
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
        <td><a href="?delete=<?= $p['PaiementID'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Supprimer ce paiement ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>