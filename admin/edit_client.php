require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
require_once '../db_connexion.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
set_message('Erreur de sécurité (CSRF).', 'error');
log_admin_action('Tentative CSRF modification client');
} else {
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$email = validate_email($_POST['email'] ?? '') ? $_POST['email'] : '';
$tel = trim($_POST['telephone'] ?? '');
// Vérifier l'existence du client avant modification
$check = $conn->prepare("SELECT COUNT(*) FROM Clients WHERE ClientID=?");
$check->execute([$id]);
if ($check->fetchColumn() == 0) {
set_message('Ce client n\'existe pas ou a déjà été supprimé.', 'error');
header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
exit;
}
// Validation stricte
if ($nom && $prenom && $email && mb_strlen($nom) <= 100 && mb_strlen($prenom) <=100 && mb_strlen($tel) <=20) {
  try {
  $sql="UPDATE Clients SET Nom=?, Prenom=?, Email=?, Telephone=? WHERE ClientID=?" ;
  $stmt=$conn->prepare($sql);
  $result = $stmt->execute([$nom, $prenom, $email, $tel, $id]);
  if ($result) {
  set_message('Client modifié.');
  log_admin_action('Modification client', "ID: $id, Nom: $nom, Prénom: $prenom, Email: $email");
  } else {
  set_message('Erreur lors de la modification.', 'error');
  log_admin_action('Erreur modification client', "ID: $id, Nom: $nom, Prénom: $prenom, Email: $email");
  }
  } catch (PDOException $e) {
  set_message('Erreur base de données.', 'error');
  log_admin_action('Erreur PDO modification client', 'PDOException');
  }
  } else {
  set_message('Champs invalides.', 'error');
  }
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
  exit;
  }
  // Récupération du client avec PDO (et non sqlsrv)
  $sql = "SELECT * FROM Clients WHERE ClientID=?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);
  $client = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
  $client = null;
  }
  ?>
  <!DOCTYPE html>
  <html lang="fr">

  <head>
    <meta charset="UTF-8">
    <title>Modifier un client</title>
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
      <a href="clients.php" class="back-link">&larr; Retour à la liste</a>
      <h1>Modifier un client</h1>
      <?php display_message(); ?>
      <?php if ($client): ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="text" name="nom" value="<?= htmlspecialchars($client['Nom'] ?? '') ?>" placeholder="Nom" required maxlength="100">
          <input type="text" name="prenom" value="<?= htmlspecialchars($client['Prenom'] ?? '') ?>" placeholder="Prénom" required maxlength="100">
          <input type="email" name="email" value="<?= htmlspecialchars($client['Email'] ?? '') ?>" placeholder="Email" required maxlength="100">
          <input type="text" name="telephone" value="<?= htmlspecialchars($client['Telephone'] ?? '') ?>" placeholder="Téléphone" maxlength="20">
          <button type="submit">Enregistrer</button>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Client introuvable.</div>
      <?php endif; ?>
    </div>
  </body>

  <script>
    // Validation en temps réel pour le formulaire d'édition de client
    (function() {
      const form = document.querySelector('.form-container form');
      if (!form) return;
      // Ajout d'un div pour afficher les erreurs si besoin
      let errorDiv = document.getElementById('form-error');
      if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'form-error';
        errorDiv.className = 'alert alert-danger';
        errorDiv.style.display = 'none';
        form.insertBefore(errorDiv, form.querySelector('button'));
      }
      const nom = form.querySelector('input[name="nom"]');
      const prenom = form.querySelector('input[name="prenom"]');
      const email = form.querySelector('input[name="email"]');
      const telephone = form.querySelector('input[name="telephone"]');

      function validateEmail(val) {
        return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val);
      }

      function validateTel(val) {
        return val === '' || /^[0-9 +().-]{6,20}$/.test(val);
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
          showError('Veuillez saisir le nom.');
          return false;
        }
        if (field === prenom && prenom.value.trim() === '') {
          showError('Veuillez saisir le prénom.');
          return false;
        }
        if (field === email && (email.value.trim() === '' || !validateEmail(email.value))) {
          showError('Veuillez saisir un email valide.');
          return false;
        }
        if (field === telephone && !validateTel(telephone.value)) {
          showError('Veuillez saisir un numéro de téléphone valide.');
          return false;
        }
        clearError();
        return true;
      }
      [nom, prenom, email, telephone].forEach(input => {
        input.addEventListener('input', function() {
          validateField(this);
        });
        input.addEventListener('blur', function() {
          validateField(this);
        });
      });
      form.addEventListener('submit', function(e) {
        if (!validateField(nom) || !validateField(prenom) || !validateField(email) || !validateField(telephone)) {
          e.preventDefault();
          return false;
        }
        clearError();
      });
    })();
  </script>

  </html>