<?php

/**
 * add_client.php
 *
 * Permet à un administrateur d'ajouter un nouveau client dans la base de données.
 * - Protection par session admin obligatoire.
 * - Validation des champs (nom, prénom, email).
 * - Utilisation de requêtes préparées pour éviter les injections SQL.
 * - Affiche un message de succès ou d'erreur.
 *
 * @auteur Projet La Mangeoire
 * @sécurité Accès restreint aux administrateurs via session.
 */


require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Vérification du token CSRF
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $message = 'Erreur de sécurité (CSRF).';
    log_admin_action('Tentative CSRF ajout client');
  } else {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = $_POST['email'] ?? '';
    $tel = trim($_POST['telephone'] ?? '');
    // Validation centralisée
    $valid = validate_nom($nom) && validate_prenom($prenom) && validate_email($email) && validate_telephone($tel);
    if ($valid) {
      try {
        $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$nom, $prenom, $email, $tel]);
        if ($result) {
          $message = 'Client ajouté avec succès.';
          log_admin_action('Ajout client', "Nom: $nom, Prénom: $prenom, Email: $email");
        } else {
          $message = 'Erreur lors de l\'ajout.';
          log_admin_action('Erreur ajout client', "Nom: $nom, Prénom: $prenom, Email: $email");
        }
      } catch (PDOException $e) {
        $message = 'Erreur base de données.';
        log_admin_action('Erreur PDO ajout client', 'PDOException');
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
  <title>Ajouter un client</title>
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
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="admin-nav">Administration – Ajouter un client</div>
  <div class="form-container">
    <a href="clients.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter un client</h1>
    <?php if ($message): ?>
      <div class="alert <?= strpos($message, 'succès') !== false ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <form method="post" autocomplete="off" id="addClientForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="text" name="nom" id="nom" placeholder="Nom" required maxlength="100">
      <input type="text" name="prenom" id="prenom" placeholder="Prénom" required maxlength="100">
      <input type="email" name="email" id="email" placeholder="Email" required maxlength="100">
      <input type="text" name="telephone" id="telephone" placeholder="Téléphone" maxlength="20">
      <div id="form-error" class="alert alert-error" style="display:none;"></div>
      <button type="submit">Ajouter</button>
    </form>
    <script>
      // Validation en temps réel pour le formulaire d'ajout de client
      (function() {
        const form = document.getElementById('addClientForm');
        const nom = document.getElementById('nom');
        const prenom = document.getElementById('prenom');
        const email = document.getElementById('email');
        const telephone = document.getElementById('telephone');
        const errorDiv = document.getElementById('form-error');

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
  </div>
</body>

</html>