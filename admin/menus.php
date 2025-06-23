<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';
// Gestion de l'ajout d'un menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['prix'])) {
  $nom = trim($_POST['nom']);
  $prix = floatval($_POST['prix']);
  if ($nom && $prix > 0) {
    $sql = "INSERT INTO Menus (NomItem, Prix) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      if ($stmt->execute([$nom, $prix])) {
        set_message('Menu ajouté avec succès.', 'success');
      } else {
        set_message('Erreur lors de l\'ajout.', 'error');
      }
    } else {
      set_message('Erreur lors de la préparation.', 'error');
    }
  } else {
    set_message('Champs invalides.', 'error');
  }
  // Rediriger pour éviter la soumission multiple du formulaire
  header('Location: menus.php');
  exit;
}
// Suppression sécurisée d'un menu (POST + CSRF + contrôle d'accès)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_menu_id'], $_POST['csrf_token'])) {
  if (!check_csrf_token($_POST['csrf_token'])) {
    set_message('Token CSRF invalide.', 'error');
    header('Location: menus.php');
    exit;
  }
  $id = intval($_POST['delete_menu_id']);
  $sql = "DELETE FROM Menus WHERE MenuID = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if ($stmt->execute([$id])) {
      set_message('Menu supprimé.', 'success');
    } else {
      set_message('Erreur lors de la suppression.', 'error');
    }
  } else {
    set_message('Erreur lors de la préparation.', 'error');
  }
  header('Location: menus.php');
  exit;
}
// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
// Compter le total
$total_sql = "SELECT COUNT(*) FROM Menus";
$total_menus = $conn->query($total_sql)->fetchColumn();
$total_pages = ceil($total_menus / $per_page);
$menus = [];
$sql = "SELECT * FROM Menus ORDER BY MenuID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $menus[] = $row;
}

// Définir le titre de la page
$page_title = "Gestion des Menus";

// CSS supplémentaires spécifiques à cette page
$additional_css = [
    'css/admin-messages.css'
];

// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);
require_once 'header_template.php';
?>
<!-- Contenu spécifique de la page -->
<div style="padding:20px;">
<h2>Gestion des menus</h2>
<?php display_message(); ?>
<!-- Formulaire d'ajout -->
<form method="post" class="form-section">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="text" name="nom" placeholder="Nom du menu" required>
<input type="number" name="prix" placeholder="Prix" required step="0.01">
<button type="submit">Ajouter</button>
</form>
<!-- Tableau des menus -->
<div class="table-responsive-wrapper">
<table class="admin-table">
<thead>
<tr>
<th>ID</th>
<th>Nom</th>
<th>Prix</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($menus as $m): ?>
<tr>
<td><?= htmlspecialchars($m['MenuID']) ?></td>
<td><?= htmlspecialchars($m['NomItem']) ?></td>
<td><?= htmlspecialchars($m['Prix']) ?></td>
<td class="action-cell">
<form method="post" action="menus.php" class="delete-form">
<input type="hidden" name="delete_menu_id" value="<?= htmlspecialchars($m['MenuID']) ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<button type="submit" class="delete-btn" onclick="return confirm('Supprimer ce menu ?')" title="Supprimer">
<i class="bi bi-trash"></i>
</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if ($total_pages > 1): ?>
<div class="pagination">
<?php for ($i = 1; $i <= $total_pages; $i++): ?>
<?php if ($i == $page): ?>
<strong class="active-page">[<?= $i ?>]</strong>
<?php else: ?>
<a href="?page=<?= $i ?>">[<?= $i ?>]</a>
<?php endif; ?>
<?php endfor; ?>
</div>
<?php endif; ?>
</div>
<?php
  require_once 'footer_template.php';
  ?>