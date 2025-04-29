<?php
require_once 'db_connexion.php';
$message = '';
// Ajout d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  $reservation_id = intval($_POST['reservation_id'] ?? 0);
  $menu_id = intval($_POST['menu_id'] ?? 0);
  $quantite = intval($_POST['quantite'] ?? 0);
  if ($reservation_id > 0 && $menu_id > 0 && $quantite > 0) {
    $sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reservation_id, $menu_id, $quantite]);
    $message = 'Commande ajoutée.';
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression d'une commande
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM Commandes WHERE CommandeID=?");
  $stmt->execute([$id]);
  $message = 'Commande supprimée.';
}
// Liste des commandes
$commandes = $conn->query("SELECT * FROM Commandes ORDER BY CommandeID DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Commandes</title>
</head>

<body>
  <h1>Commandes</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <form method="post" id="commandeForm" autocomplete="off" novalidate>
    <input type="number" name="reservation_id" id="reservation_id" placeholder="ID réservation" required min="1">
    <input type="number" name="menu_id" id="menu_id" placeholder="ID menu" required min="1">
    <input type="number" name="quantite" id="quantite" placeholder="Quantité" required min="1">
    <div id="form-error" style="color:#c62828; font-weight:bold; display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.getElementById('commandeForm').addEventListener('submit', function(e) {
      var reservation_id = document.getElementById('reservation_id').value;
      var menu_id = document.getElementById('menu_id').value;
      var quantite = document.getElementById('quantite').value;
      var error = '';
      if (!reservation_id || isNaN(reservation_id) || parseInt(reservation_id) < 1) {
        error = 'ID réservation invalide.';
        document.getElementById('reservation_id').focus();
      } else if (!menu_id || isNaN(menu_id) || parseInt(menu_id) < 1) {
        error = 'ID menu invalide.';
        document.getElementById('menu_id').focus();
      } else if (!quantite || isNaN(quantite) || parseInt(quantite) < 1) {
        error = 'Quantité invalide.';
        document.getElementById('quantite').focus();
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
        <td><a href="?delete=<?= $c['CommandeID'] ?>" onclick="return confirm('Supprimer cette commande ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>