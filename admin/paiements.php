<?php

require_once 'check_admin_access.php';
$message = '';
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';

// Define verify_csrf_token if not already defined
// Note: Using check_csrf_token is preferred for consistency with other files
if (!function_exists('check_csrf_token')) {
  function check_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['montant'], $_POST['date_paiement'], $_POST['mode_paiement'])) {
  // Check the payment type selected by the user
  $payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : 'reservation';
  
  // Check if reservation or commande ID is provided based on the payment type
  $reservation_id = ($payment_type === 'reservation' && !empty($_POST['reservation_id'])) ? intval($_POST['reservation_id']) : null;
  $commande_id = ($payment_type === 'commande' && !empty($_POST['commande_id'])) ? intval($_POST['commande_id']) : null;
  
  $montant = floatval($_POST['montant']);
  $date = $_POST['date_paiement'];
  $mode = trim($_POST['mode_paiement']);
  $numero_transaction = !empty($_POST['numero_transaction']) ? trim($_POST['numero_transaction']) : null;
  
  // Validate required fields based on payment type
  $valid = false;
  if ($payment_type === 'reservation') {
    $valid = ($reservation_id > 0);
    
    // Si le montant n'est pas spécifié, calculer le montant total des commandes pour cette réservation
    if ($valid && $montant <= 0) {
      // Inclure le fichier commandes.php pour utiliser la fonction get_total_commandes_by_reservation
      require_once 'commandes.php';
      $montant = get_total_commandes_by_reservation($pdo, $reservation_id);
      if ($montant <= 0) {
        $message = 'Aucune commande trouvée pour cette réservation ou montant total égal à zéro.';
        $valid = false;
      }
    }
  } elseif ($payment_type === 'commande') {
    $valid = ($commande_id > 0);
    
    // Si le montant n'est pas spécifié, récupérer le montant de la commande
    if ($valid && $montant <= 0) {
      $commande_sql = "SELECT COALESCE(MontantTotal, PrixUnitaire * Quantite) as Total FROM Commandes WHERE CommandeID = ?";
      $commande_stmt = $pdo->prepare($commande_sql);
      $commande_stmt->execute([$commande_id]);
      $montant = $commande_stmt->fetchColumn();
      
      if ($montant <= 0) {
        $message = 'Montant de la commande invalide ou égal à zéro.';
        $valid = false;
      }
    }
  }
  
  if ($valid && $montant > 0 && $date) {
    try {
      // Vérifier si c'est un paiement de type commande
      if ($payment_type === 'commande' && $commande_id > 0) {
        // Si c'est une commande, on doit d'abord récupérer la réservation associée (si elle existe)
        $res_query = "SELECT ReservationID FROM Commandes WHERE CommandeID = ?";
        $res_stmt = $pdo->prepare($res_query);
        $res_stmt->execute([$commande_id]);
        $reservation_from_commande = $res_stmt->fetchColumn();
        
        // Utiliser la ReservationID associée à la commande, ou rejeter si aucune réservation n'est liée
        if ($reservation_from_commande) {
          $reservation_id = $reservation_from_commande;
        } else {
          // La base de données requiert un ReservationID, alors lancez une erreur ou modifiez le schéma
          throw new Exception("La commande n'est pas associée à une réservation. Impossible d'ajouter le paiement.");
        }
      }
      
      // Vérifier que ReservationID n'est pas NULL
      if ($reservation_id === null) {
        throw new Exception("L'ID de réservation ne peut pas être nul.");
      }
      
      $sql = "INSERT INTO Paiements (ReservationID, Montant, DatePaiement, ModePaiement, TransactionID) 
              VALUES (?, ?, ?, ?, ?)";
      $stmt = $pdo->prepare($sql);
      $result = $stmt->execute([$reservation_id, $montant, $date, $mode, $numero_transaction]);
      
      if ($result) {
        // Si c'est un paiement pour une commande, mettre à jour le statut de la commande
        if ($commande_id > 0) {
          $update_sql = "UPDATE Commandes SET Statut = 'Payé', DatePaiement = ? WHERE CommandeID = ?";
          $update_stmt = $pdo->prepare($update_sql);
          $update_stmt->execute([$date, $commande_id]);
        }
        
        $message = 'Paiement ajouté avec succès.';
      } else {
        $message = 'Erreur lors de l\'ajout du paiement.';
      }
    } catch (Exception $e) {
      $message = 'Erreur: ' . $e->getMessage();
    }
  } else {
    $message = 'Veuillez spécifier une ' . ($payment_type === 'reservation' ? 'réservation' : 'commande') . ' valide et des informations de paiement correctes.';
  }
}
// Suppression sécurisée d'un paiement (POST + CSRF + contrôle d'accès)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_paiement_id'], $_POST['csrf_token'])) {
  if (!check_csrf_token($_POST['csrf_token'])) {
    set_message('Token CSRF invalide.', 'error');
    header('Location: paiements.php');
    exit;
  }
  $id = intval($_POST['delete_paiement_id']);
  $sql = "DELETE FROM Paiements WHERE PaiementID = ?";
  $stmt = $pdo->prepare($sql);
  if ($stmt) {
    if ($stmt->execute([$id])) {
      set_message('Paiement supprimé.', 'success');
    } else {
      set_message('Erreur lors de la suppression.', 'error');
    }
  } else {
    set_message('Erreur lors de la préparation.', 'error');
  }
  header('Location: paiements.php');
  exit;
}

// Gestion des filtres et de la pagination
require_once 'paiements_filter.php';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$paiements = [];
$total_pages = 0;

// Appliquer les filtres
applyPaymentFilters($pdo, $page, $per_page, $total_pages, $paiements);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Paiements - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Styles spécifiques pour la page paiements sur mobile */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .form-group[style*="grid-column: span"] {
        grid-column: 1 !important;
      }

      .table-responsive-wrapper {
        margin: 0 -15px;
        width: calc(100% + 30px);
        border-radius: 0;
      }

      .admin-table th:nth-child(1),
      .admin-table td:nth-child(1),
      .admin-table th:nth-child(2),
      .admin-table td:nth-child(2) {
        display: none;
      }

      .admin-table th,
      .admin-table td {
        padding: 10px 8px;
        font-size: 0.9rem;
      }

      .pagination {
        flex-wrap: wrap;
        justify-content: center;
      }

      .pagination a,
      .pagination strong {
        margin: 3px;
      }
    }

    /* Styles pour les références de réservation invalides */
    .invalid-reference {
      color: #e74c3c;
      text-decoration: line-through;
      cursor: help;
    }
    
    .no-reference {
      color: #7f8c8d;
      font-style: italic;
    }
    
    .warning-row {
      background-color: #fff8e1 !important; /* Couleur d'avertissement subtile */
      border-left: 3px solid #f39c12 !important;
    }
    
    .warning-row:hover {
      background-color: #ffe8b3 !important;
    }

    @media (max-width: 480px) {

      .admin-table th:nth-child(4),
      .admin-table td:nth-child(4) {
        display: none;
      }
    }
  </style>
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Paiements";

  // Indiquer que ce fichier est inclus dans une page
  define('INCLUDED_IN_PAGE', true);
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <div style="background-color: #f9f9f9; border-radius: 5px; margin-bottom: 20px;">
      <h2 style="color: #222; font-size: 23px; margin-bottom: 20px; position: relative;">Gestion des paiements</h2>
    </div>

    <?php
    // Affichage des messages avec icônes
    if (!empty($_SESSION['flash_message'])) {
      $type = $_SESSION['flash_message']['type'] === 'success' ? 'alert-success' : 'alert-danger';
      $icon = $_SESSION['flash_message']['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
      $text = htmlspecialchars($_SESSION['flash_message']['text'] ?? '');
      echo "<div class='alert $type'><i class='bi $icon'></i> $text</div>";
      unset($_SESSION['flash_message']);
    }

    // Affichage des messages simples
    if (!empty($message)) {
      $type = strpos(strtolower($message), 'erreur') !== false ? 'alert-danger' : 'alert-success';
      $icon = strpos(strtolower($message), 'erreur') !== false ? 'bi-exclamation-triangle' : 'bi-check-circle';
      echo "<div class='alert $type'><i class='bi $icon'></i> " . htmlspecialchars($message ?? '') . "</div>";
    }
    ?>

    <!-- Formulaire d'ajout -->
    <div class="form-section">
      <h3 class="section-title">Ajouter un paiement</h3>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <!-- Type de paiement -->
        <div class="form-group" style="grid-column: 1 / -1;">
          <label>Type de paiement</label>
          <div style="display: flex; gap: 15px; margin-bottom: 10px;">
            <div>
              <input type="radio" id="type_reservation" name="payment_type" value="reservation" checked>
              <label for="type_reservation">Réservation</label>
            </div>
            <div>
              <input type="radio" id="type_commande" name="payment_type" value="commande">
              <label for="type_commande">Commande</label>
            </div>
          </div>
        </div>
        
        <!-- Champs pour le paiement d'une réservation -->
        <div class="form-group payment-reservation">
          <label for="reservation_id">ID Réservation</label>
          <input type="number" id="reservation_id" name="reservation_id" placeholder="ID Réservation">
        </div>
        
        <!-- Champs pour le paiement d'une commande -->
        <div class="form-group payment-commande" style="display: none;">
          <label for="commande_id">ID Commande</label>
          <input type="number" id="commande_id" name="commande_id" placeholder="ID Commande">
        </div>
        
        <!-- Champs communs -->
        <div class="form-group">
          <label for="montant">Montant (€)</label>
          <input type="number" id="montant" name="montant" placeholder="Montant" required step="0.01">
        </div>
        <div class="form-group">
          <label for="date_paiement">Date</label>
          <input type="date" id="date_paiement" name="date_paiement" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label for="mode_paiement">Mode de paiement</label>
          <select id="mode_paiement" name="mode_paiement" required>
            <option value="">Sélectionner...</option>
            <option value="Carte bancaire">Carte bancaire</option>
            <option value="Espèces">Espèces</option>
            <option value="Chèque">Chèque</option>
            <option value="Virement">Virement</option>
            <option value="PayPal">PayPal</option>
          </select>
        </div>
        <div class="form-group">
          <label for="numero_transaction">N° de transaction</label>
          <input type="text" id="numero_transaction" name="numero_transaction" placeholder="Numéro de transaction (optionnel)">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <button type="submit" class="submit-btn">Ajouter le paiement</button>
        </div>
      </form>
    </div>

    <!-- Tableau des paiements -->
    <h3 class="section-title" style="margin-top: 30px;">Liste des paiements</h3>
    
    <!-- Filtres pour les paiements -->
    <div class="filters-container" style="margin-bottom: 15px;">
      <form method="get" action="" class="filter-form" style="display: flex; gap: 10px; flex-wrap: wrap;">
        <div class="filter-group">
          <label for="filter_status">Statut:</label>
          <select id="filter_status" name="filter_status" onchange="this.form.submit()">
            <option value="all" <?= (!isset($_GET['filter_status']) || $_GET['filter_status'] === 'all') ? 'selected' : '' ?>>Tous</option>
            <option value="valid" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'valid') ? 'selected' : '' ?>>Réservations valides</option>
            <option value="invalid" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'invalid') ? 'selected' : '' ?>>Réservations invalides</option>
            <option value="no_res" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'no_res') ? 'selected' : '' ?>>Sans réservation</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="filter_date">Date:</label>
          <select id="filter_date" name="filter_date" onchange="this.form.submit()">
            <option value="all" <?= (!isset($_GET['filter_date']) || $_GET['filter_date'] === 'all') ? 'selected' : '' ?>>Toutes dates</option>
            <option value="today" <?= (isset($_GET['filter_date']) && $_GET['filter_date'] === 'today') ? 'selected' : '' ?>>Aujourd'hui</option>
            <option value="week" <?= (isset($_GET['filter_date']) && $_GET['filter_date'] === 'week') ? 'selected' : '' ?>>Cette semaine</option>
            <option value="month" <?= (isset($_GET['filter_date']) && $_GET['filter_date'] === 'month') ? 'selected' : '' ?>>Ce mois</option>
          </select>
        </div>
        
        <?php if (isset($_GET['filter_status']) || isset($_GET['filter_date'])): ?>
          <div class="filter-group">
            <a href="paiements.php" class="btn btn-secondary btn-sm" style="margin-top: 20px;">Réinitialiser les filtres</a>
          </div>
        <?php endif; ?>
        
        <div class="filter-group">
          <a href="fix_paiements.php" class="btn btn-warning btn-sm" style="margin-top: 20px;">
            <i class="bi bi-tools"></i> Corriger les paiements problématiques
          </a>
        </div>
      </form>
    </div>
    
    <div class="table-responsive-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Référence</th>
            <th>Client</th>
            <th>Montant</th>
            <th>Date</th>
            <th>Mode</th>
            <th>Transaction</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paiements as $p): ?>
            <?php 
              // Dans la version actuelle, tous les paiements sont liés à des réservations
              $isPaiementReservation = true;
              $isPaiementCommande = false;
              
              // Déterminer le type et la référence
              $type = 'Réservation';
              $reference = $p['ReservationID'];
              
              // Déterminer le nom du client
              if (!empty($p['nom_client'])) {
                // Si nous avons un nom dans la réservation
                $client = htmlspecialchars($p['nom_client'] ?? '');
              } elseif (!empty($p['NomClient']) && !empty($p['PrenomClient'])) {
                // Si nous avons un nom depuis la table Commandes
                $client = htmlspecialchars($p['PrenomClient'] ?? '') . ' ' . htmlspecialchars($p['NomClient'] ?? '');
              } else {
                // Aucune information client disponible
                $client = 'Client non identifié';
              }
            ?>
            <tr>
              <td><?= htmlspecialchars($p['PaiementID'] ?? '') ?></td>
              <td><?= $type ?></td>
              <td>
                <?php if (!empty($p['ReservationID']) && !empty($p['ResID'])): ?>
                <a href="reservations.php?id=<?= htmlspecialchars($p['ReservationID'] ?? '') ?>" title="Voir la réservation">
                  #<?= htmlspecialchars($p['ReservationID'] ?? '') ?>
                </a>
                <?php elseif (!empty($p['ReservationID'])): ?>
                <span class="invalid-reference" title="Réservation supprimée">#<?= htmlspecialchars($p['ReservationID'] ?? '') ?></span>
                <?php else: ?>
                <span class="no-reference">-</span>
                <?php endif; ?>
              </td>
              <td><?= $client ?></td>
              <td><strong><?= number_format((float)($p['Montant'] ?? 0), 2, ',', ' ') ?> €</strong></td>
              <td><?= isset($p['DatePaiement']) ? date('d/m/Y', strtotime($p['DatePaiement'])) : '-' ?></td>
              <td><?= htmlspecialchars($p['ModePaiement'] ?? 'Non spécifié') ?></td>
              <td><?= htmlspecialchars($p['TransactionID'] ?? '-') ?></td>
              <td class="action-cell">
                <form method="post" action="paiements.php" class="delete-form">
                  <input type="hidden" name="delete_paiement_id" value="<?= htmlspecialchars($p['PaiementID'] ?? '') ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                  <button type="submit" class="delete-btn" onclick="return confirm('Supprimer ce paiement ?')" title="Supprimer">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <strong class="active-page"><?= $i ?></strong>
          <?php else: ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php
  include 'footer_template.php';
  ?>

  <!-- JavaScript for payment form -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Marquer les lignes avec des paiements problématiques
      const paymentRows = document.querySelectorAll('.admin-table tbody tr');
      paymentRows.forEach(row => {
        // Si la ligne contient une référence invalide ou pas de référence
        if (row.querySelector('.invalid-reference') || row.querySelector('.no-reference')) {
          row.classList.add('warning-row');
          row.setAttribute('title', 'Ce paiement a une référence de réservation manquante ou invalide');
        }
      });
      
      // Payment type toggle
      const typeReservation = document.getElementById('type_reservation');
      const typeCommande = document.getElementById('type_commande');
      const reservationField = document.querySelector('.payment-reservation');
      const commandeField = document.querySelector('.payment-commande');
      const reservationInput = document.getElementById('reservation_id');
      const commandeInput = document.getElementById('commande_id');
      const montantInput = document.getElementById('montant');

      // Function to toggle fields based on payment type
      function togglePaymentFields() {
        if (typeReservation.checked) {
          reservationField.style.display = 'block';
          commandeField.style.display = 'none';
          reservationInput.required = true;
          commandeInput.required = false;
          commandeInput.value = '';
        } else if (typeCommande.checked) {
          reservationField.style.display = 'none';
          commandeField.style.display = 'block';
          reservationInput.required = false;
          commandeInput.required = true;
          reservationInput.value = '';
        }
      }

      // Attach event handlers
      if (typeReservation && typeCommande) {
        typeReservation.addEventListener('change', togglePaymentFields);
        typeCommande.addEventListener('change', togglePaymentFields);
        togglePaymentFields(); // Initial state
      }

      // Fonction pour récupérer le montant total des commandes d'une réservation
      function fetchReservationTotal() {
        const reservationId = reservationInput.value;
        if (!reservationId) return;

        // Créer un indicateur de chargement
        let loadingIndicator = document.getElementById('montant-loading');
        if (!loadingIndicator) {
          loadingIndicator = document.createElement('div');
          loadingIndicator.id = 'montant-loading';
          loadingIndicator.style.marginTop = '5px';
          loadingIndicator.style.fontSize = '0.9rem';
          loadingIndicator.style.color = '#666';
          montantInput.parentNode.appendChild(loadingIndicator);
        }
        loadingIndicator.textContent = 'Chargement...';

        // Effectuer une requête AJAX pour récupérer le montant total
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `get_reservation_total.php?reservation_id=${reservationId}`, true);
        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            loadingIndicator.textContent = '';
            if (xhr.status === 200) {
              try {
                const response = JSON.parse(xhr.responseText);
                if (response.success && response.total > 0) {
                  montantInput.value = response.total;
                  loadingIndicator.textContent = 'Montant récupéré automatiquement';
                  loadingIndicator.style.color = 'green';
                } else {
                  loadingIndicator.textContent = 'Aucun montant trouvé pour cette réservation';
                  loadingIndicator.style.color = '#ce1212';
                }
              } catch (e) {
                console.error('Erreur lors du traitement de la réponse:', e);
                loadingIndicator.textContent = 'Erreur lors de la récupération du montant';
                loadingIndicator.style.color = '#ce1212';
              }
            } else {
              loadingIndicator.textContent = 'Erreur lors de la récupération du montant';
              loadingIndicator.style.color = '#ce1212';
            }
          }
        };
        xhr.send();
      }

      // Fonction similaire pour les commandes
      function fetchCommandeTotal() {
        const commandeId = commandeInput.value;
        if (!commandeId) return;

        let loadingIndicator = document.getElementById('montant-loading');
        if (!loadingIndicator) {
          loadingIndicator = document.createElement('div');
          loadingIndicator.id = 'montant-loading';
          loadingIndicator.style.marginTop = '5px';
          loadingIndicator.style.fontSize = '0.9rem';
          loadingIndicator.style.color = '#666';
          montantInput.parentNode.appendChild(loadingIndicator);
        }
        loadingIndicator.textContent = 'Chargement...';

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `get_commande_total.php?commande_id=${commandeId}`, true);
        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            loadingIndicator.textContent = '';
            if (xhr.status === 200) {
              try {
                const response = JSON.parse(xhr.responseText);
                if (response.success && response.total > 0) {
                  montantInput.value = response.total;
                  loadingIndicator.textContent = 'Montant récupéré automatiquement';
                  loadingIndicator.style.color = 'green';
                } else {
                  loadingIndicator.textContent = 'Aucun montant trouvé pour cette commande';
                  loadingIndicator.style.color = '#ce1212';
                }
              } catch (e) {
                console.error('Erreur lors du traitement de la réponse:', e);
                loadingIndicator.textContent = 'Erreur lors de la récupération du montant';
                loadingIndicator.style.color = '#ce1212';
              }
            } else {
              loadingIndicator.textContent = 'Erreur lors de la récupération du montant';
              loadingIndicator.style.color = '#ce1212';
            }
          }
        };
        xhr.send();
      }

      // Attachement des événements
      if (reservationInput) {
        reservationInput.addEventListener('change', fetchReservationTotal);
        // Si une valeur est déjà sélectionnée
        if (reservationInput.value) {
          fetchReservationTotal();
        }
      }

      if (commandeInput) {
        commandeInput.addEventListener('change', fetchCommandeTotal);
        if (commandeInput.value) {
          fetchCommandeTotal();
        }
      }
    });
  </script>
    <script src="../assets/js/admin-sidebar.js"></script>
</body>

</html>