<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';

// Définition du titre de la page pour le template
$page_title = "Commandes";

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['menu_id'], $_POST['quantite'])) {
  $reservation_id = intval($_POST['reservation_id']);
  $menu_id = intval($_POST['menu_id']);
  $quantite = intval($_POST['quantite']);
  if ($reservation_id > 0 && $menu_id > 0 && $quantite > 0) {
    $sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$reservation_id, $menu_id, $quantite]);
    if ($result) {
      set_message('Commande ajoutée avec succès.', 'success');
      header('Location: commandes.php');
      exit;
    } else {
      set_message('Erreur lors de l\'ajout.', 'error');
      header('Location: commandes.php');
      exit;
    }
  } else {
    set_message('Champs invalides.', 'error');
    header('Location: commandes.php');
    exit;
  }
}
// Suppression sécurisée d'une commande (POST + CSRF + contrôle d'accès)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_commande_id'], $_POST['csrf_token'])) {
  if (!check_csrf_token($_POST['csrf_token'])) {
    set_message('Token CSRF invalide.', 'error');
    header('Location: commandes.php');
    exit;
  }
  $id = intval($_POST['delete_commande_id']);
  $sql = "DELETE FROM Commandes WHERE CommandeID = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if ($stmt->execute([$id])) {
      set_message('Commande supprimée.', 'success');
    } else {
      set_message('Erreur lors de la suppression.', 'error');
    }
  } else {
    set_message('Erreur lors de la préparation.', 'error');
  }
  header('Location: commandes.php');
  exit;
}

// Pagination
$commandes_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $commandes_per_page;
$total_commandes = $conn->query("SELECT COUNT(*) FROM Commandes")->fetchColumn();
$total_pages = ceil($total_commandes / $commandes_per_page);
$commandes = $conn->query("SELECT * FROM Commandes ORDER BY CommandeID DESC LIMIT $commandes_per_page OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Commandes - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Commandes";

  // Indiquer que ce fichier est inclus dans une page
  define('INCLUDED_IN_PAGE', true);
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <div style="background-color: #f9f9f9; border-radius: 5px;">
      <h2 style="color: #222; font-size: 23px; margin-bottom: 50px; position: relative;">Gestion des commandes
      </h2>
    </div>

    <?php display_message(); ?>

    <!-- Formulaire d'ajout -->
    <div class="form-section">
      <h3 class="section-title">Ajouter une commande</h3>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <div class="form-group" style="display: flex; gap: 15px; width: 100%;">
            <input type="number" name="reservation_id" placeholder="ID Réservation" required style="flex: 1;">
            <input type="number" name="menu_id" placeholder="ID Menu" required style="flex: 1;">
            <input type="number" name="quantite" placeholder="Quantité" required style="flex: 1;">
          </div>
    </div>
          
        <div class="form-group" style="grid-column: span 4;">
          <button type="submit" class="submit-btn">Ajouter</button>
        </div>
      </form>

    <!-- Tableau des commandes - caché sur mobile pour simplifier l'affichage -->
    <div style="width: 100%; overflow-x: auto; margin-top: 30px;">
      <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: white; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <thead>
          <tr>
            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">ID</th>
            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Réservation</th>
            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Menu</th>
            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Quantité</th>
            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $c): ?>
            <tr style="transition: background-color 0.2s ease;">
              <td style="padding: 15px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($c['CommandeID']) ?></td>
              <td style="padding: 15px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($c['ReservationID']) ?></td>
              <td style="padding: 15px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($c['MenuID']) ?></td>
              <td style="padding: 15px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($c['Quantite']) ?></td>
              <td style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">
                <form method="post" action="" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="delete_commande_id" value="<?= htmlspecialchars($c['CommandeID']) ?>">
                  <button type="submit" style="background: none; border: none; color: #ce1212; cursor: pointer; font-size: 1.1rem;" onclick="return confirm('Supprimer cette commande ?')" title="Supprimer">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($commandes)): ?>
            <tr>
              <td colspan="5" style="padding: 20px; text-align: center;">Aucune commande trouvée</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pages > 1): ?>
      <div style="display: flex; justify-content: center; margin-top: 25px; gap: 5px;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <span style="display: inline-block; padding: 8px 12px; background-color: #ce1212; color: white; border-radius: 3px; font-weight: bold;"><?= $i ?></span>
          <?php else: ?>
            <a href="?page=<?= $i ?>" style="display: inline-block; padding: 8px 12px; background-color: white; color: #333; text-decoration: none; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php
  include 'footer_template.php';
  ?>
</body>

</html>