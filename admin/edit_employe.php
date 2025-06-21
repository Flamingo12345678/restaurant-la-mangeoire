require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
set_message('Erreur de sécurité (CSRF).', 'error');
log_admin_action('Tentative CSRF modification employé');
} else {
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$poste = trim($_POST['poste'] ?? '');
$salaire = floatval($_POST['salaire'] ?? 0);
$date_embauche = $_POST['date_embauche'] ?? '';
// Validation stricte
if ($nom && $prenom && $poste && $salaire > 0 && $date_embauche && mb_strlen($nom) <= 100 && mb_strlen($prenom) <=100 && mb_strlen($poste) <=50) {
  try {
  $sql="UPDATE Employes SET Nom=?, Prenom=?, Poste=?, Salaire=?, DateEmbauche=? WHERE EmployeID=?" ;
  $stmt=$conn->prepare($sql);
  $result = $stmt->execute([$nom, $prenom, $poste, $salaire, $date_embauche, $id]);
  if ($result) {
  set_message('Employé modifié.');
  log_admin_action('Modification employé', "ID: $id, Nom: $nom, Prénom: $prenom, Poste: $poste");
  } else {
  set_message('Erreur lors de la modification.', 'error');
  log_admin_action('Erreur modification employé', "ID: $id, Nom: $nom, Prénom: $prenom, Poste: $poste");
  }
  } catch (PDOException $e) {
  set_message('Erreur base de données.', 'error');
  log_admin_action('Erreur PDO modification employé', 'PDOException');
  }
  } else {
  set_message('Champs invalides.', 'error');
  }
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
  exit;
  }
  // Récupération de l'employé avec PDO
  $sql = "SELECT * FROM Employes WHERE EmployeID=?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);
  $employe = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
  $employe = null;
  }
  ?>
  <!DOCTYPE html>
  <html lang="fr">

  <head>
    <meta charset="UTF-8">
    <title>Modifier un employé</title>
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
    <div class="admin-nav">Administration – Modifier un employé</div>
    <div class="form-container">
      <a href="employes.php" class="back-link">&larr; Retour à la liste</a>
      <h1>Modifier un employé</h1>
      <?php display_message(); ?>
      <?php if ($employe): ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="text" name="nom" value="<?= htmlspecialchars($employe['Nom'] ?? '') ?>" placeholder="Nom" required maxlength="100">
          <input type="text" name="prenom" value="<?= htmlspecialchars($employe['Prenom'] ?? '') ?>" placeholder="Prénom" required maxlength="100">
          <input type="text" name="poste" value="<?= htmlspecialchars($employe['Poste'] ?? '') ?>" placeholder="Poste" required maxlength="50">
          <input type="number" name="salaire" value="<?= htmlspecialchars($employe['Salaire'] ?? '') ?>" placeholder="Salaire" required min="0" step="0.01">
          <input type="date" name="date_embauche" value="<?= htmlspecialchars($employe['DateEmbauche'] ?? '') ?>" placeholder="Date d'embauche" required>
          <div id="form-error" class="alert alert-error" style="display:none;"></div>
          <button type="submit">Enregistrer</button>
        </form>
      <?php else: ?>
        <div class="alert alert-error">Employé introuvable.</div>
      <?php endif; ?>
    </div>
  </body>

  <script>
    // Validation en temps réel pour le formulaire d'édition d'employé
    (function() {
      const form = document.querySelector('.form-container form');
      if (!form) return;
      const nom = form.querySelector('input[name="nom"]');
      const prenom = form.querySelector('input[name="prenom"]');
      const poste = form.querySelector('input[name="poste"]');
      const salaire = form.querySelector('input[name="salaire"]');
      const date_embauche = form.querySelector('input[name="date_embauche"]');
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
        if (field === nom && nom.value.trim() === '') {
          showError('Veuillez saisir le nom.');
          return false;
        }
        if (field === prenom && prenom.value.trim() === '') {
          showError('Veuillez saisir le prénom.');
          return false;
        }
        if (field === poste && poste.value.trim() === '') {
          showError('Veuillez saisir le poste.');
          return false;
        }
        if (field === salaire && (salaire.value === '' || isNaN(salaire.value) || parseFloat(salaire.value) < 0)) {
          showError('Salaire invalide.');
          return false;
        }
        if (field === date_embauche && date_embauche.value === '') {
          showError("Veuillez choisir une date d'embauche.");
          return false;
        }
        clearError();
        return true;
      }
      [nom, prenom, poste, salaire, date_embauche].forEach(input => {
        input.addEventListener('input', function() {
          validateField(this);
        });
        input.addEventListener('blur', function() {
          validateField(this);
        });
      });
      form.addEventListener('submit', function(e) {
        if (!validateField(nom) || !validateField(prenom) || !validateField(poste) || !validateField(salaire) || !validateField(date_embauche)) {
          e.preventDefault();
          return false;
        }
        clearError();
      });
    })();
  </script>

  </html>