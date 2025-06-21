<?php

require_once 'check_admin_access.php';
$message = '';
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();
require_once '../db_connexion.php';


$message = '';


// Contrôle de droits strict : seuls les superadmins peuvent ajouter
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
  header('Location: index.php?error=forbidden');
  exit;
}

// Génération du token CSRF si besoin
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Vérification du token CSRF
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF ajout paiement');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $montant = $_POST['montant'] ?? '';
    $date = $_POST['date_paiement'] ?? '';
    $methode = trim($_POST['methode'] ?? '');
    $valid = validate_prix($montant) && validate_date($date) && validate_nom($methode, 50);
    if ($valid) {
      try {
        $sql = "INSERT INTO Paiements (ReservationID, Montant, DatePaiement, ModePaiement) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$reservation_id, $montant, $date, $mode]);
        if ($result) {
          set_message('Paiement ajouté.');
          log_admin_action('Ajout paiement', "ReservationID: $reservation_id, Montant: $montant, Date: $date");
          header('Location: ' . $_SERVER['PHP_SELF']);
          exit;
        } else {
          set_message('Erreur lors de l\'ajout.', 'error');
          log_admin_action('Erreur ajout paiement', "ReservationID: $reservation_id, Montant: $montant, Date: $date");
          header('Location: ' . $_SERVER['PHP_SELF']);
          exit;
        }
      } catch (PDOException $e) {
        set_message('Erreur base de données.', 'error');
        log_admin_action('Erreur PDO ajout paiement', 'PDOException');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      }
    } else {
      set_message('Champs invalides.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Ajouter un paiement</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .form-container {
      max-width: 400px;
      margin: 40px auto;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
      padding: 2rem 2.5rem 2.5rem 2.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .form-container h1 {
      margin-bottom: 1.5rem;
      color: #b01e28;
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
    }

    .form-container form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .form-container input {
      padding: 0.7rem 1rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: border 0.2s;
    }

    .form-container input:focus {
      border-color: #b01e28;
      outline: none;
    }

    .form-container button {
      background: #b01e28;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0.8rem 0;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .form-container button:hover {
      background: #8c181f;
    }

    .alert {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.8rem 1rem;
      border-radius: 6px;
      font-size: 1rem;
      text-align: center;
    }

    .alert-success {
      background: #e6f9ed;
      color: #217a3c;
      border: 1px solid #b6e2c7;
    }

    .alert-error {
      background: #fdeaea;
      color: #b01e28;
      border: 1px solid #f5c2c7;
    }

    .back-link {
      display: inline-block;
      margin-bottom: 1.5rem;
      color: #b01e28;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: #8c181f;
      text-decoration: underline;
    }
  </style>
  <link rel="stylesheet" href="../assets/css/admin-inline-fixes.css">
</head>

<body class="admin-body">
  <div class="form-container">
    <a href="paiements.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter un paiement</h1>
    <?php display_message(); ?>
    <form method="post" autocomplete="off" id="addPaiementForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="number" name="reservation_id" id="reservation_id" placeholder="ID réservation *" required min="1">
      <input type="number" name="montant" id="montant" placeholder="Montant *" step="0.01" min="0" required>
      <input type="date" name="date_paiement" id="date_paiement" placeholder="Date de paiement *" required>
      <input type="text" name="mode" id="mode" placeholder="Mode de paiement" maxlength="50">
      <div id="form-error" class="alert alert-error admin-form-error"></div>
      <button type="submit">Ajouter</button>
    </form>
    <script>
      (function() {
        const form = document.getElementById('addPaiementForm');
        const reservation_id = document.getElementById('reservation_id');
        const montant = document.getElementById('montant');
        const date_paiement = document.getElementById('date_paiement');
        const mode = document.getElementById('mode');
        const errorDiv = document.getElementById('form-error');

        function showError(msg) {
          errorDiv.textContent = msg;
          errorDiv.style.display = 'block';
        }

        function clearError() {
          errorDiv.textContent = '';
          errorDiv.style.display = 'none';
        }

        function validateField(field) {
          if (field === reservation_id && (reservation_id.value === '' || isNaN(reservation_id.value) || parseInt(reservation_id.value) < 1)) {
            showError('ID réservation invalide.');
            return false;
          }
          if (field === montant && (montant.value === '' || isNaN(montant.value) || parseFloat(montant.value) < 0)) {
            showError('Montant invalide.');
            return false;
          }
          if (field === date_paiement && date_paiement.value === '') {
            showError('Veuillez choisir une date de paiement.');
            return false;
          }
          if (field === mode && mode.value.trim() === '') {
            showError('Veuillez saisir le mode de paiement.');
            return false;
          }
          clearError();
          return true;
        }
        [reservation_id, montant, date_paiement, mode].forEach(input => {
          input.addEventListener('input', function() {
            validateField(this);
          });
          input.addEventListener('blur', function() {
            validateField(this);
          });
        });
        form.addEventListener('submit', function(e) {
          if (!validateField(reservation_id) || !validateField(montant) || !validateField(date_paiement) || !validateField(mode)) {
            e.preventDefault();
            return false;
          }
          clearError();
        });
      })();
    </script>
    <script src="../assets/js/admin-remove-inline-styles.js"></script>
  </div>
</body>

</html>