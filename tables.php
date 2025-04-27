<?php
require_once 'db_connexion.php';
$message = '';
// Ajout d'une table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  $numero = intval($_POST['numero'] ?? 0);
  $places = intval($_POST['places'] ?? 0);
  if ($numero > 0 && $places > 0) {
    $sql = "INSERT INTO Tables (NumeroTable, NbPlaces) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$numero, $places]);
    $message = 'Table ajoutée.';
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression d'une table
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM Tables WHERE TableID=?");
  $stmt->execute([$id]);
  $message = 'Table supprimée.';
}
// Liste des tables
$tables = $conn->query("SELECT * FROM Tables ORDER BY TableID DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Tables</title>
</head>

<body>
  <h1>Tables</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <form method="post">
    <input type="number" name="numero" placeholder="Numéro de table" required>
    <input type="number" name="places" placeholder="Nombre de places" required>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <table border="1" cellpadding="5">
    <tr>
      <th>ID</th>
      <th>Numéro</th>
      <th>Places</th>
      <th>Action</th>
    </tr>
    <?php foreach ($tables as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['TableID']) ?></td>
        <td><?= htmlspecialchars($t['NumeroTable']) ?></td>
        <td><?= htmlspecialchars($t['NbPlaces']) ?></td>
        <td><a href="?delete=<?= $t['TableID'] ?>" onclick="return confirm('Supprimer cette table ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>