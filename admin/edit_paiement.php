require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$reservation_id = intval($_POST['reservation_id'] ?? 0);
$montant = floatval($_POST['montant'] ?? 0);
$date = $_POST['date_paiement'] ?? '';
$mode = trim($_POST['mode'] ?? '');
if ($reservation_id > 0 && $montant > 0 && $date) {
try {
$sql = "UPDATE Paiements SET ReservationID=?, Montant=?, DatePaiement=?, ModePaiement=? WHERE PaiementID=?";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$reservation_id, $montant, $date, $mode, $id]);
if ($result) {
set_message('Paiement modifié.');
log_admin_action('Modification paiement', "ID: $id, ReservationID: $reservation_id, Montant: $montant, Date: $date");
} else {
set_message('Erreur lors de la modification.', 'error');
log_admin_action('Erreur modification paiement', "ID: $id, ReservationID: $reservation_id, Montant: $montant, Date: $date");
}
} catch (PDOException $e) {
set_message('Erreur base de données.', 'error');
log_admin_action('Erreur PDO modification paiement', $e->getMessage());
}
} else {
set_message('Champs invalides.', 'error');
}
header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
exit;
}
$sql = "SELECT * FROM Paiements WHERE PaiementID=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$paiement = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
$paiement = null;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Modifier un paiement</title>
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

    .admin-nav {
      width: 100vw;
      background: #b01e28;
      color: #fff;
      padding: 0.7rem 0;
      margin-bottom: 2rem;
      text-align: center;
      font-weight: 600;
      letter-spacing: 1px;
    }
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="admin-nav">Administration – Modifier un paiement</div>
  <div class="form-container">
    <a href="paiements.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Modifier un paiement</h1>
    <?php display_message(); ?>
    <?php if ($paiement): ?>
      <form method="post" autocomplete="off">
        <input type="number" name="reservation_id" value="<?= htmlspecialchars($paiement['ReservationID']) ?>" placeholder="ID réservation" required>
        <input type="number" name="montant" value="<?= htmlspecialchars($paiement['Montant']) ?>" step="0.01" min="0" placeholder="Montant" required>
        <input type="date" name="date_paiement" value="<?= htmlspecialchars($paiement['DatePaiement']) ?>" required>
        <input type="text" name="mode" value="<?= htmlspecialchars($paiement['ModePaiement']) ?>" placeholder="Mode de paiement">
        <div id="form-error" class="alert alert-danger" style="display:none;"></div>
        <button type="submit">Enregistrer</button>
      </form>
    <?php else: ?>
      <div class="alert alert-danger">Paiement introuvable.</div>
    <?php endif; ?>
  </div>
</body>

<script>
  // Validation en temps réel pour le formulaire d'édition de paiement
  (function() {
    const form = document.querySelector('.form-container form');
    if (!form) return;
    const reservation_id = form.querySelector('input[name="reservation_id"]');
    const montant = form.querySelector('input[name="montant"]');
    const date_paiement = form.querySelector('input[name="date_paiement"]');
    const mode = form.querySelector('input[name="mode"]');
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

</html>