<?php
require_once __DIR__ . '/db_connexion.php';
require_once __DIR__ . '/includes/common.php';

// Initialisation des variables
$message = '';
$success = false;
$admin_email = '';

// Vérifier si la page est appelée depuis une session admin valide ou par un paramètre spécial de sécurité 
// (ou en mode développement local)
$is_dev_mode = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');
$is_from_admin = (isset($_SESSION['admin']) && $_SESSION['admin'] === true);
$has_security_token = (isset($_GET['token']) && $_GET['token'] === 'c7e3a4b9f2d1'); // Token de sécurité

// Rediriger si l'accès n'est pas autorisé et qu'on n'est pas en développement
if (!$is_from_admin && !$has_security_token && !$is_dev_mode) {
  header('Location: admin/login.php');
  exit;
}

// Vérifier si l'email est fourni
if (isset($_GET['email'])) {
  $admin_email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
}

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $admin_email = filter_var($_POST['admin_email'], FILTER_SANITIZE_EMAIL);
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  // Validation des données
  $erreurs = [];

  // Vérifier si l'email existe
  $stmt = $conn->prepare("SELECT AdminID FROM Administrateurs WHERE Email = ?");
  $stmt->execute([$admin_email]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$admin) {
    $erreurs[] = "Cette adresse email n'est pas associée à un compte administrateur.";
  }

  // Utiliser la fonction de validation du mot de passe définie dans common.php
  if (!validate_password_strength($new_password)) {
    $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.";
  }

  // Vérifier que les deux mots de passe correspondent
  if ($new_password !== $confirm_password) {
    $erreurs[] = "Les nouveaux mots de passe ne correspondent pas.";
  }

  // Si aucune erreur, on met à jour le mot de passe
  if (empty($erreurs)) {
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Administrateurs SET MotDePasse = ? WHERE Email = ?");

    if ($stmt->execute([$password_hash, $admin_email])) {
      $success = true;
      $message = "Votre mot de passe a été modifié avec succès.";
    } else {
      $message = "Une erreur est survenue lors de la modification de votre mot de passe.";
    }
  } else {
    $message = implode("<br>", $erreurs);
  }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Réinitialisation du mot de passe administrateur - La Mangeoire</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Styles pour correspondre à l'image fournie */
    body {
      background-color: #f2f4f8;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    }

    /* Styles supplémentaires pour un meilleur positionnement */
    .reset-container {
      margin-top: 30px;
    }

    .reset-container {
      width: 100%;
      max-width: 450px;
      margin: 0 auto;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      position: relative;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .logo-container {
      text-align: center;
      position: relative;
      width: 80px;
      height: 80px;
      margin: 15px auto 30px;
    }

    .logo-container img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid rgba(255, 182, 182, 0.2);
      background-color: #000;
      box-shadow: 0 3px 15px rgba(206, 18, 18, 0.2);
    }

    .logo-container::before {
      content: '';
      position: absolute;
      top: -5px;
      left: -5px;
      right: -5px;
      bottom: -5px;
      background: rgba(255, 182, 182, 0.1);
      border-radius: 50%;
    }

    .form-container {
      padding: 20px 30px 30px;
    }

    .form-title {
      text-align: center;
      color: #ce1212;
      font-size: 24px;
      font-weight: 600;
      margin: 0 0 25px;
      letter-spacing: 0.5px;
    }

    .form-title+p {
      text-align: center;
      margin-bottom: 20px;
      color: #666;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 7px;
      font-size: 14px;
      color: #555;
    }

    .form-control {
      width: 100%;
      padding: 15px;
      padding-left: 45px;
      /* Padding initial avec icône */
      border: 1px solid #e1e1e1;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }

    /* Classe pour ajuster le padding quand l'icône est cachée */
    .form-control.no-icon {
      padding-left: 15px;
      /* Padding réduit quand pas d'icône */
      transition: padding 0.3s ease;
    }

    .form-control::placeholder {
      color: #bbb;
      font-size: 13px;
      padding: 20px;
    }

    .form-control:focus {
      border-color: #ce1212;
      outline: none;
      box-shadow: 0 0 0 3px rgba(206, 18, 18, 0.1);
      background-color: #fff;
    }

    .form-control-wrap {
      position: relative;
      width: 100%;
      display: flex;
      align-items: center;

    }

    .input-icon-container {
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      display: flex;
      align-items: center;
      width: 45px;
      justify-content: center;
      z-index: 2;
      transition: opacity 0.3s;
    }

    .input-icon {
      color: #888;
      transition: all 0.3s;
      font-size: 16px;
    }

    .form-control:focus~.input-icon-container .input-icon {
      color: #ce1212;
    }

    .input-icon-container.hidden {
      opacity: 0;
    }

    .password-requirements {
      font-size: 12px;
      color: #888;
      margin-top: 6px;
      padding-left: 2px;
    }

    .btn-submit {
      width: 100%;
      padding: 14px 20px;
      background-color: #ce1212;
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-submit:hover {
      background-color: #b01010;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(206, 18, 18, 0.2);
    }

    .btn-submit i {
      margin-right: 8px;
    }

    .alert {
      padding: 12px 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-size: 14px;
      display: flex;
      align-items: center;
    }

    .alert-danger {
      background-color: #fff5f5;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }

    .alert-success {
      background-color: #f0fff4;
      border-left: 4px solid #28a745;
      color: #155724;
    }

    .alert i {
      margin-right: 10px;
      font-size: 16px;
    }

    .text-center {
      text-align: center;
    }

    .success-container {
      text-align: center;
      padding: 20px;
    }

    .success-icon {
      color: #28a745;
      font-size: 48px;
      margin-bottom: 15px;
      animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.1);
      }

      100% {
        transform: scale(1);
      }
    }

    .success-heading {
      font-size: 20px;
      color: #28a745;
      margin-bottom: 10px;
    }

    .success-message {
      color: #555;
      margin-bottom: 25px;
      font-size: 14px;
      line-height: 1.6;
    }

    .btn i {
      margin-right: 8px;
      font-size: 18px;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: 0.5s;
    }

    .btn:hover::before {
      left: 100%;
    }

    /* Nous pouvons enlever ces styles car ils sont définis dans admin.css */
    /* Les classes .alert, .alert-danger et .alert-success sont déjà dans le CSS commun */

    .alert-icon {
      margin-right: 10px;
      display: inline-block;
    }

    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 30px 0;
      color: #6c757d;
      font-size: 14px;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid #eaeaea;
    }

    .divider::before {
      margin-right: 15px;
    }

    .divider::after {
      margin-left: 15px;
    }

    .footer-link {
      text-align: center;
      margin-top: 25px;
    }

    .back-link {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 15px;
      font-weight: 500;
      transition: var(--transition);
    }

    .back-link:hover {
      text-decoration: underline;
      color: var(--primary-dark);
    }

    .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo img {
      max-width: 80px;
      border-radius: 50%;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .password-strength-meter {
      height: 5px;
      width: 100%;
      background-color: #f1f1f1;
      position: relative;
      margin-top: 8px;
      border-radius: 2px;
      overflow: hidden;
    }

    .password-strength-meter-progress {
      height: 100%;
      border-radius: 2px;
      transition: var(--transition);
      width: 0;
    }

    .password-strength-meter-progress.weak {
      background: #ff4b4b;
      width: 25%;
    }

    .password-strength-meter-progress.medium {
      background: #ffa64b;
      width: 50%;
    }

    .password-strength-meter-progress.strong {
      background: #80cc28;
      width: 75%;
    }

    .password-strength-meter-progress.very-strong {
      background: #2eb92e;
      width: 100%;
    }

    .password-strength-text {
      text-align: right;
      font-size: 12px;
      margin-top: 5px;
      font-weight: 500;
    }

    .password-strength-text.weak {
      color: #ff4b4b;
    }

    .password-strength-text.medium {
      color: #ffa64b;
    }

    .password-strength-text.strong {
      color: #80cc28;
    }

    .password-strength-text.very-strong {
      color: #2eb92e;
    }

    /* Animations */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-in {
      animation: fadeIn 0.5s ease forwards;
    }

    @media (max-width: 768px) {
      .reset-container {
        margin: 20px;
        max-width: none;
      }
    }

    .alert-success {
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }

    .password-requirements {
      font-size: 12px;
      color: #6c757d;
      margin-top: 5px;
    }

    .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo img {
      max-width: 100px;
    }
  </style>
</head>

<body>
  <div class="reset-container">
    <div class="logo-container">
      <img src="assets/img/favcon.jpeg" alt="Logo La Mangeoire">
    </div>

    <div class="form-container">
      <h1 class="form-title">Réinitialiser le mot de passe</h1>
      <p>Veuillez saisir vos informations pour réinitialiser votre mot de passe</p>

      <?php if ($message): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
          <i class="bi <?php echo $success ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
        <form method="POST" action="reset_admin_password.php">
          <div class="form-group">
            <label for="admin_email">Nom d'utilisateur</label>
            <div class="form-control-wrap">
              <span class="input-icon-container"><i class="bi bi-person input-icon"></i></span>
              <input type="email" id="admin_email" name="admin_email" class="form-control" placeholder="<?php echo htmlspecialchars($admin_email ?: 'nom.utilisateur@lamangeoire.fr'); ?>" value="<?php echo htmlspecialchars($admin_email); ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label for="new_password">Mot de passe</label>
            <div class="form-control-wrap">
              <span class="input-icon-container"><i class="bi bi-lock input-icon"></i></span>
              <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Mot de passe" required>
            </div>
            <div class="password-requirements">
              Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.
            </div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe</label>
            <div class="form-control-wrap">
              <span class="input-icon-container"><i class="bi bi-lock-fill input-icon"></i></span>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirmez le mot de passe" required>
            </div>
          </div>

          <button type="submit" class="btn-submit">
            <i class="bi bi-key-fill"></i> Changer le mot de passe
          </button>
        </form>
      <?php else: ?>
        <div class="text-center">
          <div class="success-icon">
            <i class="bi bi-check-circle-fill" style="font-size: 48px; color: #28a745;"></i>
          </div>
          <h3 style="margin: 15px 0; color: #28a745;">Mot de passe modifié avec succès!</h3>
          <p style="margin-bottom: 25px;">Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
          <a href="admin/login.php" class="btn-reset">
            <i class="bi bi-box-arrow-in-right"></i> Se connecter à l'interface d'administration
          </a>
        </div>
      <?php endif; ?>
      <p class="text-center" style="margin-top: 20px;">
        <a href="index.html" class="back-link">Retour à l'accueil</a>
      </p>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const newPasswordField = document.getElementById('new_password');
      const confirmPasswordField = document.getElementById('confirm_password');
      const formTitle = document.querySelector('.form-title'); // Effet de focus sur les champs
      const formControls = document.querySelectorAll('.form-control');
      formControls.forEach(input => {
        // Au chargement, vérifier si le champ a déjà une valeur
        if (input.value) {
          const iconContainer = input.parentNode.querySelector('.input-icon-container');
          if (iconContainer) iconContainer.classList.add('hidden'); // Cacher l'icône si le champ a une valeur
          input.classList.add('no-icon'); // Ajouter la classe pour ajuster le padding
        }

        // Lorsqu'on commence à saisir du texte
        input.addEventListener('input', function() {
          const iconContainer = this.parentNode.querySelector('.input-icon-container');
          if (iconContainer) {
            if (this.value) {
              iconContainer.classList.add('hidden'); // Cacher l'icône quand on saisit du texte
              this.classList.add('no-icon'); // Ajouter la classe pour ajuster le padding
            } else {
              iconContainer.classList.remove('hidden'); // Réafficher l'icône si le champ est vide
              this.classList.remove('no-icon'); // Retirer la classe quand l'icône est visible
            }
          }
        });

        // Lorsque le champ prend le focus
        input.addEventListener('focus', function() {
          const iconContainer = this.parentNode.querySelector('.input-icon-container');
          const icon = iconContainer ? iconContainer.querySelector('.input-icon') : null;
          if (icon && !this.value) icon.style.color = '#ce1212';
        });

        // Lorsque le champ perd le focus
        input.addEventListener('blur', function() {
          const iconContainer = this.parentNode.querySelector('.input-icon-container');
          const icon = iconContainer ? iconContainer.querySelector('.input-icon') : null;
          if (icon && !this.value) icon.style.color = '#888';
        });
      });

      // Vérification de la correspondance des mots de passe
      if (newPasswordField && confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
          if (this.value === newPasswordField.value) {
            this.style.borderColor = '#28a745';
          } else {
            this.style.borderColor = '#dc3545';
          }
        });
      }

      // Animation d'entrée du titre
      if (formTitle) {
        formTitle.style.opacity = '0';
        formTitle.style.transform = 'translateY(-10px)';

        setTimeout(() => {
          formTitle.style.transition = 'all 0.5s ease';
          formTitle.style.opacity = '1';
          formTitle.style.transform = 'translateY(0)';
        }, 100);
      }

      // Animation de fade-in pour les champs du formulaire
      const formElements = document.querySelectorAll('.form-group, .btn-submit');
      formElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(10px)';

        setTimeout(() => {
          el.style.transition = 'all 0.3s ease';
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }, 200 + (index * 100));
      });
    });
  </script>
</body>

</html>