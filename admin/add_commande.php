require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
require_once '../db_connexion.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
$message = 'Erreur de sécurité (CSRF).';
log_admin_action('Tentative CSRF ajout commande');
} else {
$reservation_id = intval($_POST['reservation_id'] ?? 0);
$menu_id = intval($_POST['menu_id'] ?? 0);
$quantite = $_POST['quantite'] ?? '';
$valid = validate_quantite($quantite) && $reservation_id > 0 && $menu_id > 0;
if ($valid) {
try {
$sql = "INSERT INTO Commandes (ReservationID, MenuID, Quantite) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$reservation_id, $menu_id, $quantite]);
if ($result) {
$message = 'Commande ajoutée.';
log_admin_action('Ajout commande', "ReservationID: $reservation_id, MenuID: $menu_id, Quantité: $quantite");
} else {
$message = 'Erreur lors de l\'ajout.';
log_admin_action('Erreur ajout commande', "ReservationID: $reservation_id, MenuID: $menu_id, Quantité: $quantite");
}
} catch (PDOException $e) {
$message = 'Erreur base de données.';
log_admin_action('Erreur PDO ajout commande', 'PDOException');
}
} else {
$message = 'Champs invalides.';
}
}
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Ajouter une commande</title>
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

    .alert-danger {
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
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="form-container">
    <a href="commandes.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter une commande</h1>
    <?php if ($message): ?>
      <div class="alert <?= strpos($message, 'ajoutée') !== false ? 'alert-success' : 'alert-danger' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <form method="post" autocomplete="off" id="addCommandeForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="number" name="reservation_id" id="reservation_id" placeholder="ID réservation" required min="1">
      <input type="number" name="menu_id" id="menu_id" placeholder="ID menu" required min="1">
      <input type="number" name="quantite" id="quantite" placeholder="Quantité" required min="1">
      <div id="form-error" class="alert alert-danger" style="display:none;"></div>
      <button type="submit">Ajouter</button>
    </form>
    <script>
      (function() {
        const form = document.getElementById('addCommandeForm');
        const reservation_id = document.getElementById('reservation_id');
        const menu_id = document.getElementById('menu_id');
        const quantite = document.getElementById('quantite');
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
          if (field === menu_id && (menu_id.value === '' || isNaN(menu_id.value) || parseInt(menu_id.value) < 1)) {
            showError('ID menu invalide.');
            return false;
          }
          if (field === quantite && (quantite.value === '' || isNaN(quantite.value) || parseInt(quantite.value) < 1)) {
            showError('Quantité invalide.');
            return false;
          }
          clearError();
          return true;
        }
        [reservation_id, menu_id, quantite].forEach(input => {
          input.addEventListener('input', function() {
            validateField(this);
          });
          input.addEventListener('blur', function() {
            validateField(this);
          });
        });
        form.addEventListener('submit', function(e) {
          if (!validateField(reservation_id) || !validateField(menu_id) || !validateField(quantite)) {
            e.preventDefault();
            return false;
          }
          clearError();
        });
      })();
    </script>
  </div>
</body>

</html>