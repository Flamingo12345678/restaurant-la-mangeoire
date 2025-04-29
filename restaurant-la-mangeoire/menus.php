<?php
require_once 'db_connexion.php';
require_once 'admin/utils.php';
$message = '';
// Ajout d'un menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    if ($nom && $prix > 0) {
      $sql = "INSERT INTO Menus (NomItem, Prix) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$nom, $prix]);
      set_message('Menu ajouté.', 'success');
    } else {
      set_message('Champs invalides.', 'warning');
    }
  }
}
// Suppression d'un menu
if (isset($_GET['delete'])) {
  if (!isset($_GET['csrf_token']) || !check_csrf_token($_GET['csrf_token'])) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Menus WHERE MenuID=?");
    $stmt->execute([$id]);
    set_message('Menu supprimé.', 'success');
  }
}
// Liste des menus
$menus = $conn->query("SELECT * FROM Menus ORDER BY MenuID DESC")->fetchAll();
$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Menus</title>
</head>

<body>
  <h1>Menus</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <form method="post" id="menuForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="text" name="nom" id="nom" placeholder="Nom du menu" required>
    <input type="number" name="prix" id="prix" placeholder="Prix" step="0.01" min="0" required>
    <div id="form-error" style="color:#c62828; font-weight:bold; display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.getElementById('menuForm').addEventListener('submit', function(e) {
      var nom = document.getElementById('nom').value.trim();
      var prix = document.getElementById('prix').value;
      var error = '';
      if (!nom) {
        error = 'Veuillez saisir le nom du menu.';
        document.getElementById('nom').focus();
      } else if (!prix || isNaN(prix) || parseFloat(prix) <= 0) {
        error = 'Prix invalide.';
        document.getElementById('prix').focus();
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
      <th>Nom</th>
      <th>Prix</th>
      <th>Action</th>
    </tr>
    <?php foreach ($menus as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['MenuID']) ?></td>
        <td><?= htmlspecialchars($m['NomItem']) ?></td>
        <td><?= htmlspecialchars($m['Prix']) ?></td>
        <td><a href="?delete=<?= $m['MenuID'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Supprimer ce menu ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>