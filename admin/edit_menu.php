$message = '';
require_once __DIR__ . '/../includes/common.php';
require_superadmin();
require_once '../db_connexion.php';
$message = '';
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
log_admin_action('Tentative CSRF modification menu');
} else {
$nom = trim($_POST['nom'] ?? '');
$description = trim($_POST['description'] ?? '');
$prix = floatval($_POST['prix'] ?? 0);
// Validation stricte
if ($nom && $prix > 0 && mb_strlen($nom) <= 100 && mb_strlen($description) <=255) {
  try {
  $sql="UPDATE Menus SET NomItem=?, Description=?, Prix=? WHERE MenuID=?" ;
  $stmt=$conn->prepare($sql);
  $result = $stmt->execute([$nom, $description, $prix, $id]);
  if ($result) {
  set_message('Menu modifié.');
  log_admin_action('Modification menu', "ID: $id, Nom: $nom, Prix: $prix");
  } else {
  set_message('Erreur lors de la modification.', 'error');
  log_admin_action('Erreur modification menu', "ID: $id, Nom: $nom, Prix: $prix");
  }
  } catch (PDOException $e) {
  set_message('Erreur base de données.', 'error');
  log_admin_action('Erreur PDO modification menu', 'PDOException');
  }
  } else {
  set_message('Champs invalides.', 'error');
  }
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
  exit;
  }
  $sql = "SELECT * FROM Menus WHERE MenuID=?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$id]);
  $menu = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
  $menu = null;
  }
  ?>
  <!DOCTYPE html>
  <html lang="fr">

  <head>
    <meta charset="UTF-8">
    <title>Modifier un menu</title>
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
    <div class="admin-nav">Administration – Modifier un menu</div>
    <div class="form-container">
      <a href="menus.php" class="back-link">&larr; Retour à la liste</a>
      <h1>Modifier un menu</h1>
      <?php display_message(); ?>
      <?php if ($menu): ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="text" name="nom" value="<?= htmlspecialchars($menu['NomItem'] ?? '') ?>" placeholder="Nom du menu" required maxlength="100">
          <input type="text" name="description" value="<?= htmlspecialchars($menu['Description'] ?? '') ?>" placeholder="Description" maxlength="255">
          <input type="number" name="prix" value="<?= htmlspecialchars($menu['Prix'] ?? '') ?>" placeholder="Prix" required min="0" step="0.01">
          <div id="form-error" class="alert alert-error" style="display:none;"></div>
          <button type="submit">Enregistrer</button>
        </form>
      <?php else: ?>
        <div class="alert alert-error">Menu introuvable.</div>
      <?php endif; ?>
    </div>
  </body>

  <script>
    // Validation en temps réel pour le formulaire d'édition de menu
    (function() {
      const form = document.querySelector('.form-container form');
      if (!form) return;
      const nom = form.querySelector('input[name="nom"]');
      const description = form.querySelector('input[name="description"]');
      const prix = form.querySelector('input[name="prix"]');
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
          showError('Veuillez saisir le nom du menu.');
          return false;
        }
        if (field === prix && (prix.value === '' || isNaN(prix.value) || parseFloat(prix.value) < 0)) {
          showError('Prix invalide.');
          return false;
        }
        if (field === description && description.value.length > 255) {
          showError('Description trop longue (255 caractères max).');
          return false;
        }
        clearError();
        return true;
      }
      [nom, description, prix].forEach(input => {
        input.addEventListener('input', function() {
          validateField(this);
        });
        input.addEventListener('blur', function() {
          validateField(this);
        });
      });
      form.addEventListener('submit', function(e) {
        if (!validateField(nom) || !validateField(description) || !validateField(prix)) {
          e.preventDefault();
          return false;
        }
        clearError();
      });
    })();
  </script>

  </html>