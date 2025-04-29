<?php
require_once 'admin/utils.php';
require_once 'db_connexion.php';
$message = '';
// Ajout d'un client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  // Vérification CSRF
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $tel = trim($_POST['telephone'] ?? '');
    if ($nom && $prenom && $email) {
      $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$nom, $prenom, $email, $tel]);
      set_message('Client ajouté.', 'success');
    } else {
      set_message('Champs invalides.', 'warning');
    }
  }
}
// Suppression d'un client
if (isset($_GET['delete'])) {
  // Vérification CSRF via token GET
  if (!isset($_GET['csrf_token']) || !check_csrf_token($_GET['csrf_token'])) {
    set_message('Erreur de sécurité (CSRF).', 'danger');
  } else {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Clients WHERE ClientID=?");
    $stmt->execute([$id]);
    set_message('Client supprimé.', 'success');
  }
}
// Liste des clients
$clients = $conn->query("SELECT * FROM Clients ORDER BY ClientID DESC")->fetchAll();
$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Clients</title>
</head>

<body>
  <h1>Clients</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php $flash = get_message();
  if ($flash): ?>
    <p style="color:<?= $flash['type'] === 'success' ? '#217a3c' : '#c62828' ?>;font-weight:bold;"> <?= e($flash['text']) ?> </p>
  <?php endif; ?>
  <form method="post" id="clientForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
    <input type="text" name="nom" id="nom" placeholder="Nom" required>
    <input type="text" name="prenom" id="prenom" placeholder="Prénom" required>
    <input type="email" name="email" id="email" placeholder="Email" required>
    <input type="text" name="telephone" id="telephone" placeholder="Téléphone">
    <div id="form-error" style="color:#c62828; font-weight:bold; display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.getElementById('clientForm').addEventListener('submit', function(e) {
      var nom = document.getElementById('nom').value.trim();
      var prenom = document.getElementById('prenom').value.trim();
      var email = document.getElementById('email').value.trim();
      var emailRegex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      var error = '';
      if (!nom) {
        error = 'Veuillez saisir le nom.';
        document.getElementById('nom').focus();
      } else if (!prenom) {
        error = 'Veuillez saisir le prénom.';
        document.getElementById('prenom').focus();
      } else if (!email || !emailRegex.test(email)) {
        error = 'Veuillez saisir un email valide.';
        document.getElementById('email').focus();
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
      <th>Prénom</th>
      <th>Email</th>
      <th>Téléphone</th>
      <th>Action</th>
    </tr>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['ClientID']) ?></td>
        <td><?= htmlspecialchars($c['Nom']) ?></td>
        <td><?= htmlspecialchars($c['Prenom']) ?></td>
        <td><?= htmlspecialchars($c['Email']) ?></td>
        <td><?= htmlspecialchars($c['Telephone']) ?></td>
        <td><a href="?delete=<?= $c['ClientID'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Supprimer ce client ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>