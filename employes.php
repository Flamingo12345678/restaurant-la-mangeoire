<?php
// Définir la constante pour l'inclusion du header
define('INCLUDED_IN_PAGE', true);

require_once __DIR__ . '/includes/common.php';
require_admin();
generate_csrf_token();
require_once 'db_connexion.php';

// Définir le titre de la page et les styles spécifiques
$page_title = "Gestion des Employés";
$additional_css = [
    'assets/css/admin-sidebar.css',
    'assets/css/employes-admin.css'
];

// Ajout d'un employé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $poste = trim($_POST['poste'] ?? '');
    $salaire = $_POST['salaire'] ?? '';
    $date_embauche = $_POST['date_embauche'] ?? '';
    $valid = validate_nom($nom) && validate_prenom($prenom) && validate_nom($poste, 50) && validate_salaire($salaire) && validate_date($date_embauche);
    if ($valid) {
      $sql = "INSERT INTO Employes (Nom, Prenom, Poste, Salaire, DateEmbauche) VALUES (?, ?, ?, ?, ?)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$nom, $prenom, $poste, $salaire, $date_embauche]);
      set_message('✅ Employé ajouté avec succès.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('❌ Un ou plusieurs champs sont invalides. Veuillez vérifier vos saisies.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}
// Suppression d'un employé sécurisée (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employe'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('❌ Erreur de sécurité (CSRF) : le formulaire a expiré ou est invalide.', 'error');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $id = intval($_POST['delete_employe']);
    // Vérification d'existence de l'employé
    $check = $pdo->prepare("SELECT COUNT(*) FROM Employes WHERE EmployeID=?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
      set_message('❌ Cet employé n’existe pas ou a déjà été supprimé.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    $stmt = $pdo->prepare("DELETE FROM Employes WHERE EmployeID=?");
    $stmt->execute([$id]);
    set_message('🗑️ Employé supprimé avec succès.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
}
// Pagination
$employes_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $employes_per_page;
$total_employes = $pdo->query("SELECT COUNT(*) FROM Employes")->fetchColumn();
$total_pages = ceil($total_employes / $employes_per_page);
$employes = $pdo->query("SELECT * FROM Employes ORDER BY EmployeID DESC LIMIT $employes_per_page OFFSET $offset")->fetchAll();

// CSS supplémentaires spécifiques à cette page
$additional_css = [
    'css/admin-messages.css'
];

define('INCLUDED_IN_PAGE', true);
require_once 'admin/header_template.php';
?>
<?php display_message(); ?>
<h1>Employés</h1>
<a href="admin/index.php">Retour admin</a>
<form method="post" id="employeForm" autocomplete="off" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="text" name="nom" id="nom" placeholder="Nom" required>
<input type="text" name="prenom" id="prenom" placeholder="Prénom" required>
<input type="text" name="poste" id="poste" placeholder="Poste" required>
<input type="number" name="salaire" id="salaire" placeholder="Salaire" step="0.01" min="0" required>
<input type="date" name="date_embauche" id="date_embauche" required>
<div id="form-error" class="form-error hidden"></div>
<button type="submit" name="ajouter">Ajouter</button>
</form>
<script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('employeForm');
      if (!form) return;
      const nom = form.querySelector('[name="nom"]');
      const prenom = form.querySelector('[name="prenom"]');
      const poste = form.querySelector('[name="poste"]');
      const salaire = form.querySelector('[name="salaire"]');
      const date_embauche = form.querySelector('[name="date_embauche"]');
      const errorDiv = document.getElementById('form-error');

      function validateNom(v) { return v.trim().length > 1; }
      function validatePrenom(v) { return v.trim().length > 1; }
      function validatePoste(v) { return v.trim().length > 1; }
      function validateSalaire(v) { return v && !isNaN(v) && parseFloat(v) >= 0; }
      function validateDate(v) { return !!v; }

      function checkField(el, validate) {
        const valid = validate(el.value);
        el.classList.toggle('input-error', !valid);
        return valid;
      }

      function validateAll() {
        let ok = true;
        ok &= checkField(nom, validateNom);
        ok &= checkField(prenom, validatePrenom);
        ok &= checkField(poste, validatePoste);
        ok &= checkField(salaire, validateSalaire);
        ok &= checkField(date_embauche, validateDate);
        return !!ok;
      }

      [nom, prenom, poste, salaire, date_embauche].forEach((el, i) => {
        const validators = [validateNom, validatePrenom, validatePoste, validateSalaire, validateDate];
        el.addEventListener('input', () => {
          checkField(el, validators[i]);
          if (validateAll()) {
            errorDiv.style.display = 'none';
          }
        });
        el.addEventListener('blur', () => checkField(el, validators[i]));
      });

      form.addEventListener('submit', function(e) {
        if (!validateAll()) {
          e.preventDefault();
          errorDiv.textContent = "Merci de corriger les champs invalides.";
          errorDiv.style.display = 'block';
          return false;
        } else {
          errorDiv.style.display = 'none';
        }
      });
    });
  </script>
<table class="admin-table">
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
<td>
<form method="post" onsubmit="return confirm('Supprimer cet employé ?')">
<input type="hidden" name="delete_employe" value="<?= $e['EmployeID'] ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<button type="submit" class="delete-btn">Supprimer</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php if ($total_pages > 1): ?>
<div class="pagination">
<?php for ($i = 1; $i <= $total_pages; $i++): ?>
<?php if ($i == $page): ?>
<strong>[<?= $i ?>]</strong>
<?php else: ?>
<a href="?page=<?= $i ?>">[<?= $i ?>]</a>
<?php endif; ?>
<?php endfor; ?>
</div>
<?php endif; ?>

<?php
// Inclure le footer admin harmonisé
require_once 'admin/footer_template.php';
?>