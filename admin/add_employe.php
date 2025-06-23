<?php

require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';
function log_admin_action($action, $details = '')
{
  $logfile = __DIR__ . '/../admin/admin_actions.log';
  $date = date('Y-m-d H:i:s');
  $user = $_SESSION['admin'] ?? 'inconnu';
  $entry = "[$date] [$user] $action $details\n";
  file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}
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
    log_admin_action('Tentative CSRF ajout employé');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $poste = trim($_POST['poste'] ?? '');
    $salaire = $_POST['salaire'] ?? '';
    $date_embauche = $_POST['date_embauche'] ?? '';
    $valid = validate_nom($nom) && validate_prenom($prenom) && validate_nom($poste, 50) && validate_salaire($salaire) && validate_date($date_embauche);
    if ($valid) {
      try {
        $sql = "INSERT INTO Employes (Nom, Prenom, Poste, Salaire, DateEmbauche) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$nom, $prenom, $poste, $salaire, $date_embauche]);
        if ($result) {
          set_message('Employé ajouté.');
          log_admin_action('Ajout employé', "Nom: $nom, Prénom: $prenom, Poste: $poste");
          header('Location: ' . $_SERVER['PHP_SELF']);
          exit;
        } else {
          set_message('Erreur lors de l\'ajout.', 'error');
          log_admin_action('Erreur ajout employé', "Nom: $nom, Prénom: $prenom, Poste: $poste");
          header('Location: ' . $_SERVER['PHP_SELF']);
          exit;
        }
      } catch (PDOException $e) {
        set_message('Erreur base de données.', 'error');
        log_admin_action('Erreur PDO ajout employé', 'PDOException');
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

// Gestion centralisée des messages - moved to common.php
// function set_message and display_message are now defined in common.php

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Ajouter un employé</title>
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
    <a href="employes.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter un employé</h1>
    <?php display_message(); ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="text" name="nom" placeholder="Nom" required maxlength="100">
      <input type="text" name="prenom" placeholder="Prénom" required maxlength="100">
      <input type="text" name="poste" placeholder="Poste" required maxlength="50">
      <input type="number" name="salaire" placeholder="Salaire" required min="0" step="0.01">
      <input type="date" name="date_embauche" placeholder="Date d'embauche" required>
      <button type="submit">Ajouter</button>
    </form>
  </div>
</body>

</html>