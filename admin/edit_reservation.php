require_once __DIR__ . '/../includes/common.php';
$message = '';
require_superadmin();
require_once '../db_connexion.php';
// Contrôle de droits strict : seuls les superadmins peuvent modifier
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
header('Location: index.php?error=forbidden');
exit;
}

// Génération du token CSRF si besoin
if (empty($_SESSION['csrf_token'])) {
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
set_message('Erreur de sécurité (CSRF).', 'error');
log_admin_action('Tentative CSRF modification réservation');
} else {
$nom = trim($_POST['nom_client'] ?? '');
$email = validate_email($_POST['email_client'] ?? '') ? $_POST['email_client'] : '';
$date = $_POST['DateReservation'] ?? '';
$statut = trim($_POST['statut'] ?? 'Réservée');
// Validation stricte
if ($nom && $email && $date && mb_strlen($nom) <= 100 && mb_strlen($email) <=100 && mb_strlen($statut) <=50) {
  try {
  $sql="UPDATE Reservations SET nom_client=?, email_client=?, DateReservation=?, statut=? WHERE id=?" ;
  $stmt=$conn->prepare($sql);
  $result = $stmt->execute([$nom, $email, $date, $statut, $id]);
  if ($result) {
  // Libérer toutes les anciennes tables associées
  $sql = "SELECT TableID FROM ReservationTables WHERE ReservationID = ?";
  $stmt_old = $conn->prepare($sql);
  $stmt_old->execute([$id]);
  $old_tables = $stmt_old->fetchAll(PDO::FETCH_COLUMN);
  if ($old_tables) {
  foreach ($old_tables as $table_id) {
  $sql = "UPDATE TablesRestaurant SET Statut = 'Libre' WHERE TableID = ?";
  $stmt_update = $conn->prepare($sql);
  $stmt_update->execute([$table_id]);
  }
  $sql = "DELETE FROM ReservationTables WHERE ReservationID = ?";
  $stmt_del = $conn->prepare($sql);
  $stmt_del->execute([$id]);
  }
  // Associer les nouvelles tables sélectionnées
  if (!empty($_POST['table_ids']) && is_array($_POST['table_ids'])) {
  foreach ($_POST['table_ids'] as $table_id) {
  $table_id = intval($table_id);
  $sql = "INSERT INTO ReservationTables (ReservationID, TableID, nb_places) VALUES (?, ?, ?)";
  $stmt_assoc = $conn->prepare($sql);
  $stmt_assoc->execute([$id, $table_id, 0]);
  $sql = "UPDATE TablesRestaurant SET Statut = 'Réservée' WHERE TableID = ?";
  $stmt_update = $conn->prepare($sql);
  $stmt_update->execute([$table_id]);
  }
  }
  set_message('Réservation modifiée.');
  log_admin_action('Modification réservation', "ID: $id, Nom: $nom, Email: $email, Date: $date");
  } else {
  set_message('Erreur lors de la modification.', 'error');
  log_admin_action('Erreur modification réservation', "ID: $id, Nom: $nom, Email: $email, Date: $date");
  }
  } catch (PDOException $e) {
  set_message('Erreur base de données.', 'error');
  log_admin_action('Erreur PDO modification réservation', 'PDOException');
  }
  } else {
  set_message('Champs invalides.', 'error');
  }
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
  exit;
  }
  // Récupération de la réservation avec PDO
  $sql = "SELECT * FROM Reservations WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);
  $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
  $reservation = null;
  }
  ?>
  <!DOCTYPE html>
  <html lang="fr">

  <head>
    <meta charset="UTF-8">
    <title>Modifier une réservation</title>
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
    <div class="admin-nav">Administration – Modifier une réservation</div>
    <div class="form-container">
      <a href="reservations.php" class="back-link">&larr; Retour à la liste</a>
      <h1>Modifier une réservation</h1>
      <?php display_message(); ?>
      <?php if ($reservation): ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="text" name="nom_client" value="<?= htmlspecialchars($reservation['nom_client'] ?? '') ?>" placeholder="Nom du client" required maxlength="100">
          <input type="email" name="email_client" value="<?= htmlspecialchars($reservation['email_client'] ?? '') ?>" placeholder="Email du client" required maxlength="100">
          <input type="datetime-local" name="DateReservation" value="<?= htmlspecialchars($reservation['DateReservation'] ?? '') ?>" required>
          <input type="text" name="statut" value="<?= htmlspecialchars($reservation['statut'] ?? '') ?>" placeholder="Statut (Réservée/Annulée)" maxlength="50">
          <div id="form-error" class="alert alert-error" style="display:none;"></div>
          <button type="submit">Enregistrer</button>
        </form>
      <?php else: ?>
        <div class="alert alert-error">Réservation introuvable.</div>
      <?php endif; ?>
    </div>
  </body>

  <script>
    // Validation en temps réel pour le formulaire d'édition de réservation
    (function() {
      const form = document.querySelector('.form-container form');
      if (!form) return;
      const nom = form.querySelector('input[name="nom_client"]');
      const email = form.querySelector('input[name="email_client"]');
      const date = form.querySelector('input[name="DateReservation"]');
      const statut = form.querySelector('input[name="statut"]');
      const errorDiv = document.getElementById('form-error');

      function validateEmail(val) {
        return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val);
      }

      function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.style.display = 'block';
      }

      function clearError() {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
      }

      function validateField(field) {
        if (field === nom && nom.value.trim() === '') {
          showError('Veuillez saisir le nom du client.');
          return false;
        }
        if (field === email && (email.value.trim() === '' || !validateEmail(email.value))) {
          showError('Veuillez saisir un email valide.');
          return false;
        }
        if (field === date && date.value === '') {
          showError('Veuillez choisir une date.');
          return false;
        }
        if (field === statut && statut.value && !['Réservée', 'Annulée'].includes(statut.value)) {
          showError('Statut invalide.');
          return false;
        }
        clearError();
        return true;
      }
      [nom, email, date, statut].forEach(input => {
        input.addEventListener('input', function() {
          validateField(this);
        });
        input.addEventListener('blur', function() {
          validateField(this);
        });
      });
      form.addEventListener('submit', function(e) {
        if (!validateField(nom) || !validateField(email) || !validateField(date) || !validateField(statut)) {
          e.preventDefault();
          return false;
        }
        clearError();
      });
    })();
  </script>

  </html>