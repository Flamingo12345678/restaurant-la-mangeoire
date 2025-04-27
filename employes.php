<?php
require_once 'db_connexion.php';
$message = '';
// Ajout d'un employé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  $nom = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $poste = trim($_POST['poste'] ?? '');
  $salaire = floatval($_POST['salaire'] ?? 0);
  $date_embauche = $_POST['date_embauche'] ?? '';
  if ($nom && $prenom && $poste && $salaire > 0 && $date_embauche) {
    $sql = "INSERT INTO Employes (Nom, Prenom, Poste, Salaire, DateEmbauche) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom, $prenom, $poste, $salaire, $date_embauche]);
    $message = 'Employé ajouté.';
  } else {
    $message = 'Champs invalides.';
  }
}
// Suppression d'un employé
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM Employes WHERE EmployeID=?");
  $stmt->execute([$id]);
  $message = 'Employé supprimé.';
}
// Liste des employés
$employes = $conn->query("SELECT * FROM Employes ORDER BY EmployeID DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Employés</title>
</head>

<body>
  <h1>Employés</h1>
  <a href="admin/index.php">Retour admin</a>
  <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <form method="post" id="employeForm" autocomplete="off" novalidate>
    <input type="text" name="nom" id="nom" placeholder="Nom" required>
    <input type="text" name="prenom" id="prenom" placeholder="Prénom" required>
    <input type="text" name="poste" id="poste" placeholder="Poste" required>
    <input type="number" name="salaire" id="salaire" placeholder="Salaire" step="0.01" min="0" required>
    <input type="date" name="date_embauche" id="date_embauche" required>
    <div id="form-error" style="color:#c62828; font-weight:bold; display:none;"></div>
    <button type="submit" name="ajouter">Ajouter</button>
  </form>
  <script>
    document.getElementById('employeForm').addEventListener('submit', function(e) {
      var nom = document.getElementById('nom').value.trim();
      var prenom = document.getElementById('prenom').value.trim();
      var poste = document.getElementById('poste').value.trim();
      var salaire = document.getElementById('salaire').value;
      var date_embauche = document.getElementById('date_embauche').value;
      var error = '';
      if (!nom) {
        error = 'Veuillez saisir le nom.';
        document.getElementById('nom').focus();
      } else if (!prenom) {
        error = 'Veuillez saisir le prénom.';
        document.getElementById('prenom').focus();
      } else if (!poste) {
        error = 'Veuillez saisir le poste.';
        document.getElementById('poste').focus();
      } else if (!salaire || isNaN(salaire) || parseFloat(salaire) <= 0) {
        error = 'Salaire invalide.';
        document.getElementById('salaire').focus();
      } else if (!date_embauche) {
        error = 'Veuillez choisir une date d\'embauche.';
        document.getElementById('date_embauche').focus();
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
      <th>Poste</th>
      <th>Salaire</th>
      <th>Date embauche</th>
      <th>Action</th>
    </tr>
    <?php foreach ($employes as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['EmployeID']) ?></td>
        <td><?= htmlspecialchars($e['Nom']) ?></td>
        <td><?= htmlspecialchars($e['Prenom']) ?></td>
        <td><?= htmlspecialchars($e['Poste']) ?></td>
        <td><?= htmlspecialchars($e['Salaire']) ?></td>
        <td><?= htmlspecialchars($e['DateEmbauche']) ?></td>
        <td><a href="?delete=<?= $e['EmployeID'] ?>" onclick="return confirm('Supprimer cet employé ?')">Supprimer</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>