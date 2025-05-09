<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';

// Définition du titre de la page pour le template
$page_title = "Commandes";

// Vérifier si les colonnes PrixUnitaire et MontantTotal existent
$checkColumn = function($columnName) use ($conn) {
    try {
        $stmt = $conn->prepare("SELECT $columnName FROM Commandes LIMIT 1");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column") !== false) {
            return false;
        }
        throw $e;
    }
};

$hasPrixUnitaire = $checkColumn('PrixUnitaire');
$hasMontantTotal = $checkColumn('MontantTotal');

// Récupérer la liste des réservations actives
$reservations = [];
try {
  $res_query = "SELECT ReservationID, DateReservation, nom_client, nb_personnes FROM Reservations WHERE Statut = 'Réservée' ORDER BY DateReservation DESC";
  $reservations = $conn->query($res_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  set_message('Erreur lors de la récupération des réservations : ' . $e->getMessage(), 'error');
}

// Récupérer la liste des menus disponibles
$menus = [];
try {
  $menu_query = "SELECT MenuID, NomItem, Prix FROM Menus ORDER BY NomItem";
  $menus = $conn->query($menu_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  set_message('Erreur lors de la récupération des menus : ' . $e->getMessage(), 'error');
}

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['menu_id'], $_POST['quantite'])) {
  $reservation_id = intval($_POST['reservation_id']);
  $menu_id = intval($_POST['menu_id']);
  $quantite = intval($_POST['quantite']);
  
  if ($reservation_id > 0 && $menu_id > 0 && $quantite > 0) {
    // Vérifier que la réservation existe avant d'insérer
    $check_res = $conn->prepare("SELECT ReservationID FROM Reservations WHERE ReservationID = ?");
    $check_res->execute([$reservation_id]);
    
    // Récupérer le prix du menu sélectionné
    $check_menu = $conn->prepare("SELECT Prix FROM Menus WHERE MenuID = ?");
    $check_menu->execute([$menu_id]);
    $menu_data = $check_menu->fetch(PDO::FETCH_ASSOC);
    
    if ($check_res->rowCount() > 0 && $menu_data) {
      $prix_unitaire = $menu_data['Prix'];
      $montant_total = $prix_unitaire * $quantite;
      
      try {
        // Vérifier si les colonnes PrixUnitaire et MontantTotal existent avant de les inclure dans l'insertion
        if ($hasPrixUnitaire && $hasMontantTotal) {
          $sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite, PrixUnitaire, MontantTotal) 
                  VALUES (?, ?, ?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $result = $stmt->execute([$reservation_id, $menu_id, $quantite, $prix_unitaire, $montant_total]);
        } else {
          // Fallback sans les colonnes de prix
          $sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite) VALUES (?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $result = $stmt->execute([$reservation_id, $menu_id, $quantite]);
        }
        
        if ($result) {
          set_message('Commande ajoutée avec succès.', 'success');
        } else {
          set_message('Erreur lors de l\'ajout.', 'error');
        }
      } catch (PDOException $e) {
        set_message('Erreur de base de données: ' . $e->getMessage(), 'error');
      }
    } else {
      set_message('Erreur: la réservation ou le menu sélectionné n\'existe pas.', 'error');
    }
    
    header('Location: commandes.php');
    exit;
  } else {
    set_message('Champs invalides.', 'error');
    header('Location: commandes.php');
    exit;
  }
}

// Suppression sécurisée d'une commande
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

// Requête adaptée en fonction des colonnes disponibles
$commandes_query = "
    SELECT c.CommandeID, c.ReservationID, c.MenuID, c.Quantite, 
           " . ($hasPrixUnitaire ? "c.PrixUnitaire, " : "") . 
           ($hasMontantTotal ? "c.MontantTotal, " : "") . "
           r.nom_client, r.DateReservation,
           m.NomItem as NomMenu, m.Prix as PrixMenu
    FROM Commandes c 
    LEFT JOIN Reservations r ON c.ReservationID = r.ReservationID
    LEFT JOIN Menus m ON c.MenuID = m.MenuID
    ORDER BY c.CommandeID DESC 
    LIMIT $commandes_per_page OFFSET $offset";

try {
    $commandes = $conn->query($commandes_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer le prix unitaire et le montant total à partir du prix du menu
    foreach ($commandes as $key => $commande) {
        $prix_unitaire = isset($commande['PrixUnitaire']) ? $commande['PrixUnitaire'] : $commande['PrixMenu'];
        $montant_total = $prix_unitaire * $commande['Quantite'];
        
        // Ajouter ces valeurs calculées aux résultats
        $commandes[$key]['PrixUnitaire'] = $prix_unitaire;
        $commandes[$key]['MontantTotal'] = $montant_total;
    }
} catch (PDOException $e) {
    set_message('Erreur lors de la récupération des commandes : ' . $e->getMessage(), 'error');
    $commandes = [];
}

// Fonction utilitaire pour obtenir le montant total des commandes par réservation
function get_total_commandes_by_reservation($conn, $reservation_id) {
  global $hasPrixUnitaire, $hasMontantTotal;
  
  if ($hasPrixUnitaire && $hasMontantTotal) {
    $sql = "SELECT COALESCE(SUM(COALESCE(MontantTotal, Quantite * PrixUnitaire)), 0) as Total
            FROM Commandes
            WHERE ReservationID = ?";
  } else {
    // Utiliser la table Menus pour calculer les totaux
    $sql = "SELECT SUM(m.Prix * c.Quantite) as Total
            FROM Commandes c
            JOIN Menus m ON c.MenuID = m.MenuID
            WHERE c.ReservationID = ?";
  }
  
  $stmt = $conn->prepare($sql);
  $stmt->execute([$reservation_id]);
  return $stmt->fetchColumn();
}
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
  <style>
    /* Styles spécifiques pour la page commandes sur mobile */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .table-responsive-wrapper {
        margin: 0 -15px;
        width: calc(100% + 30px);
        border-radius: 0;
      }

      .admin-table th:nth-child(1),
      .admin-table td:nth-child(1) {
        display: none;
      }

      .admin-table th,
      .admin-table td {
        padding: 10px 8px;
        font-size: 0.9rem;
      }

      .admin-table th:nth-child(2),
      .admin-table td:nth-child(2) {
        max-width: 80px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }

    @media (max-width: 480px) {
      .admin-table th:nth-child(6),
      .admin-table td:nth-child(6) {
        display: none;
      }
    }
  </style>
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
      <h2 style="color: #222; font-size: 23px; margin-bottom: 30px; position: relative;">Gestion des commandes</h2>
    </div>

    <?php display_message(); ?>

    <!-- Formulaire d'ajout -->
    <div class="form-section">
      <h1 style="color: #333; text-align: center; margin-bottom: 40px; font-size: 28px; font-weight: 600;">Ajouter une commande</h1>
      <form method="post" style="max-width: 1200px; margin: 0 auto;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        
        <div class="form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
          <div>
            <select name="reservation_id" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box;">
              <option value="">-- Sélectionner une réservation --</option>
              <?php foreach ($reservations as $reservation): ?>
                <option value="<?= htmlspecialchars($reservation['ReservationID']) ?>">
                  ID: <?= htmlspecialchars($reservation['ReservationID']) ?> - 
                  <?= htmlspecialchars($reservation['nom_client']) ?> - 
                  <?= date('d/m/Y H:i', strtotime($reservation['DateReservation'])) ?> - 
                  <?= htmlspecialchars($reservation['nb_personnes']) ?> pers.
                </option>
              <?php endforeach; ?>
              <?php if (empty($reservations)): ?>
                <option disabled>Aucune réservation active disponible</option>
              <?php endif; ?>
            </select>
          </div>
          
          <div>
            <select name="menu_id" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box;">
              <option value="">-- Sélectionner un menu --</option>
              <?php foreach ($menus as $menu): ?>
                <option value="<?= htmlspecialchars($menu['MenuID']) ?>">
                  <?= htmlspecialchars($menu['NomItem']) ?> - 
                  <?= number_format($menu['Prix'], 0, ',', ' ') ?> XAF
                </option>
              <?php endforeach; ?>
              <?php if (empty($menus)): ?>
                <option disabled>Aucun menu disponible</option>
              <?php endif; ?>
            </select>
          </div>
          
          <div>
            <input type="number" name="quantite" placeholder="Quantité" required min="1" value="1" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; box-sizing: border-box;">
          </div>
        </div>
        
        <div id="total-price-display" class="total-price-info" style="text-align: center; margin: 20px 0; font-size: 1.1rem; font-weight: bold;"></div>
        
        <div style="margin-top: 30px;">
          <button type="submit" style="background-color: #ae2012; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; transition: background-color 0.3s;">Ajouter la commande</button>
        </div>
      </form>
    </div>

    <!-- Tableau des commandes -->
    <h3 class="section-title" style="margin-top: 40px;">Liste des commandes</h3>
    <div class="table-responsive-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Client</th>
            <th>Menu</th>
            <th>Quantité</th>
            <th>Prix unitaire</th>
            <th>Montant total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['CommandeID']) ?></td>
              <td><?= isset($c['DateReservation']) ? date('d/m/Y H:i', strtotime($c['DateReservation'])) : 'N/A' ?></td>
              <td><?= htmlspecialchars($c['nom_client'] ?: 'N/A') ?></td>
              <td><?= htmlspecialchars($c['NomMenu'] ?: 'N/A') ?></td>
              <td><?= htmlspecialchars($c['Quantite']) ?></td>
              <td><?= number_format($c['PrixUnitaire'] ?? $c['PrixMenu'] ?? 0, 0, ',', ' ') ?> XAF</td>
              <td><?= number_format($c['MontantTotal'] ?? ($c['Quantite'] * ($c['PrixUnitaire'] ?? $c['PrixMenu'] ?? 0)), 0, ',', ' ') ?> XAF</td>
              <td class="action-cell">
                <form method="post" action="" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="delete_commande_id" value="<?= htmlspecialchars($c['CommandeID']) ?>">
                  <button type="submit" class="action-icon" onclick="return confirm('Supprimer cette commande ?')" title="Supprimer">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($commandes)): ?>
            <tr>
              <td colspan="8" class="text-center">Aucune commande trouvée</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <span class="page-item active"><?= $i ?></span>
          <?php else: ?>
            <a href="?page=<?= $i ?>" class="page-item"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php
  include 'footer_template.php';
  ?>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Éléments du formulaire
      const menuSelect = document.querySelector('select[name="menu_id"]');
      const quantiteInput = document.querySelector('input[name="quantite"]');
      const totalPriceDisplay = document.getElementById('total-price-display');
      
      // Prix des menus stockés dans un objet JavaScript
      const menuPrix = {};
      <?php foreach ($menus as $menu): ?>
      menuPrix[<?= $menu['MenuID'] ?>] = <?= $menu['Prix'] ?>;
      <?php endforeach; ?>
      
      // Fonction pour mettre à jour l'affichage du prix total estimé
      function updateTotalPrice() {
        const menuId = parseInt(menuSelect.value);
        const quantite = parseInt(quantiteInput.value) || 0;
        
        if (menuId && menuPrix[menuId]) {
          const prixUnitaire = menuPrix[menuId];
          const total = prixUnitaire * quantite;
          
          totalPriceDisplay.innerHTML = `
            <div>Prix unitaire: ${prixUnitaire.toLocaleString('fr-FR')} XAF</div>
            <div>Montant total: ${total.toLocaleString('fr-FR')} XAF</div>
          `;
          
          // Afficher le conteneur de prix total s'il est masqué
          totalPriceDisplay.style.display = 'block';
        } else {
          // Cacher le conteneur si aucun prix n'est disponible
          totalPriceDisplay.style.display = 'none';
        }
      }
      
      // Attachement des événements
      if (menuSelect && quantiteInput && totalPriceDisplay) {
        menuSelect.addEventListener('change', updateTotalPrice);
        quantiteInput.addEventListener('input', updateTotalPrice);
        
        // Mise à jour initiale si des valeurs sont déjà sélectionnées
        if (menuSelect.value) {
          updateTotalPrice();
        } else {
          // Cacher le conteneur au chargement initial s'il n'y a pas de menu sélectionné
          totalPriceDisplay.style.display = 'none';
        }
      }
    });
  </script>
</body>

</html>
