<?php
require_once 'admin/utils.php';
require_once 'db_connexion.php';
$message = '';
// Ajout d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $nom = trim($_POST['nom_client'] ?? '');
    $email = filter_var($_POST['email_client'] ?? '', FILTER_VALIDATE_EMAIL);
    $date = $_POST['DateReservation'] ?? '';
    $statut = trim($_POST['statut'] ?? 'Réservée');
    if ($nom && $email && $date) {
      $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$nom, $email, $date, $statut]);
      set_message('Réservation ajoutée.', 'success');
    } else {
      set_message('Champs invalides.', 'warning');
    }
  }
}
if (isset($_GET['delete'])) {
  if (!isset($_GET['csrf_token']) || !check_csrf_token($_GET['csrf_token'])) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Reservations WHERE id=?");
    $stmt->execute([$id]);
    set_message('Réservation supprimée.', 'success');
  }
}
$reservations = $conn->query("SELECT * FROM Reservations ORDER BY id DESC")->fetchAll();
$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Réservations</title>
</head>

<body>
  <h1>Réservations</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php $flash = get_message();
  if ($flash): ?>
    <p style="color:<?= $flash['type'] === 'success' ? '#217a3c' : '#c62828' ?>;font-weight:bold;"> <?= e($flash['text']) ?> </p>
    <script>
      setTimeout(function() {
        window.location.href = 'reservations.php';
      }, 3000);
    </script>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
    <input type="text" name="nom_client" placeholder="Nom du client" required>
    <input type="email" name="email_client" placeholder="Email du client" required>
    <input type="datetime-local" name="DateReservation" required>
    <input type="text" name="statut" placeholder="Statut (Réservée/Annulée)">
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
        <td><?= htmlspecialchars($r['id']) ?></td>
        <td><?= htmlspecialchars($r['nom_client']) ?></td>
        <td><?= htmlspecialchars($r['email_client']) ?></td>
        <td><?= htmlspecialchars($r['DateReservation']) ?></td>
        <td><?= htmlspecialchars($r['statut']) ?></td>
        <td><a href="?delete=<?= $r['id'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Supprimer cette réservation ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>