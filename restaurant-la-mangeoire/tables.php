<?php
require_once 'admin/utils.php';
require_once 'db_connexion.php';
$message = '';
// Ajout d'une table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $numero = intval($_POST['numero'] ?? 0);
    $places = intval($_POST['places'] ?? 0);
    if ($numero > 0 && $places > 0) {
      $sql = "INSERT INTO Tables (NumeroTable, NbPlaces) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$numero, $places]);
      set_message('Table ajoutée.', 'success');
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
    $stmt = $conn->prepare("DELETE FROM Tables WHERE TableID=?");
    $stmt->execute([$id]);
    set_message('Table supprimée.', 'success');
  }
}
$tables = $conn->query("SELECT * FROM Tables ORDER BY TableID DESC")->fetchAll();
$csrf_token = get_csrf_token();
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
  <?php $flash = get_message();
  if ($flash): ?>
    <p style="color:<?= $flash['type'] === 'success' ? '#217a3c' : '#c62828' ?>;font-weight:bold;"> <?= e($flash['text']) ?> </p>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
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
        <td><a href="?delete=<?= $t['TableID'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Supprimer cette table ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>