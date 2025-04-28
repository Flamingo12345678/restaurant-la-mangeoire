<?php
require_once 'db_connexion.php';
$message = '';
// Ajout d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  $nom = trim($_POST['nom_client'] ?? '');
  $email = filter_var($_POST['email_client'] ?? '', FILTER_VALIDATE_EMAIL);
  $date = $_POST['DateReservation'] ?? '';
  $statut = trim($_POST['statut'] ?? 'Réservée');
  if ($nom && $email && $date) {
    $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom, $email, $date, $statut]);
    $message = 'Réservation ajoutée.';
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression d'une réservation
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM Reservations WHERE id=?");
  $stmt->execute([$id]);
  $message = 'Réservation supprimée.';
}
// Liste des réservations
$reservations = $conn->query("SELECT * FROM Reservations ORDER BY id DESC")->fetchAll();
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
  <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <form method="post">
    <input type="text" name="nom_client" placeholder="Nom du client" required>
    <input type="email" name="email_client" placeholder="Email du client" required>
    <input type="datetime-local" name="DateReservation" required>
    <input type="text" name="statut" placeholder="Statut (Réservée/Annulée)">
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <table border="1" cellpadding="5">
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
        <td><a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Supprimer cette réservation ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>