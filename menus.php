<?php
require_once 'db_connexion.php';
$message = '';
// Ajout d'un menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  $nom = trim($_POST['nom'] ?? '');
  $prix = floatval($_POST['prix'] ?? 0);
  if ($nom && $prix > 0) {
    $sql = "INSERT INTO Menus (NomItem, Prix) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom, $prix]);
    $message = 'Menu ajouté.';
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression d'un menu
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM Menus WHERE MenuID=?");
  $stmt->execute([$id]);
  $message = 'Menu supprimé.';
}
// Liste des menus
$menus = $conn->query("SELECT * FROM Menus ORDER BY MenuID DESC")->fetchAll();
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
        <td><a href="?delete=<?= $m['MenuID'] ?>" onclick="return confirm('Supprimer ce menu ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>