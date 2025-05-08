<?php
require_once __DIR__ . '/includes/common.php';
require_once 'db_connexion.php';

// Initialisation des variables
$message = '';
$email = '';
$nom = '';
$prenom = '';
$telephone = '';
$adresse = '';
$code_postal = '';
$ville = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Récupération et nettoyage des données
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
  $prenom = htmlspecialchars(trim($_POST['prenom'] ?? ''));
  $telephone = htmlspecialchars(trim($_POST['telephone'] ?? ''));
  $adresse = htmlspecialchars(trim($_POST['adresse'] ?? ''));
  $code_postal = htmlspecialchars(trim($_POST['code_postal'] ?? ''));
  $ville = htmlspecialchars(trim($_POST['ville'] ?? ''));

  // Validation des données
  $erreurs = [];

  if (!validate_email($email)) {
    $erreurs[] = "L'adresse email n'est pas valide.";
  }

  if (!validate_password_strength($password)) {
    $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.";
  } elseif ($password !== $confirm_password) {
    $erreurs[] = "Les mots de passe ne correspondent pas.";
  }

  if (!validate_nom($nom)) {
    $erreurs[] = "Le nom n'est pas valide.";
  }

  if (!validate_prenom($prenom)) {
    $erreurs[] = "Le prénom n'est pas valide.";
  }

  if ($telephone && !validate_telephone($telephone)) {
    $erreurs[] = "Le numéro de téléphone n'est pas valide.";
  }

  if ($code_postal && !validate_code_postal($code_postal)) {
    $erreurs[] = "Le code postal n'est pas valide.";
  }

  // Vérifier si l'email existe déjà
  $stmt = $conn->prepare("SELECT COUNT(*) FROM Utilisateurs WHERE Email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetchColumn() > 0) {
    $erreurs[] = "Cette adresse email est déjà utilisée. Veuillez utiliser une autre adresse ou vous connecter.";
  }

  // Si aucune erreur, on peut créer le compte
  if (empty($erreurs)) {
    try {
      // Hachage du mot de passe
      $password_hash = password_hash($password, PASSWORD_DEFAULT);

      // Insertion dans la base de données
      $sql = "INSERT INTO Utilisateurs (Email, MotDePasse, Nom, Prenom, Telephone, Adresse, CodePostal, Ville) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$email, $password_hash, $nom, $prenom, $telephone, $adresse, $code_postal, $ville]);

      // Récupérer l'ID de l'utilisateur nouvellement créé
      $user_id = $conn->lastInsertId();

      // Migration du panier temporaire vers la base de données si l'inscription réussit
      if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
        foreach ($_SESSION['panier'] as $item) {
          $stmt = $conn->prepare("INSERT INTO Panier (UtilisateurID, MenuID, Quantite) VALUES (?, ?, ?)");
          $stmt->execute([$user_id, $item['MenuID'], $item['Quantite']]);
        }

        // Vider le panier temporaire
        unset($_SESSION['panier']);
      }

      // Connexion automatique de l'utilisateur après inscription
      $_SESSION['user_id'] = $user_id;
      $_SESSION['user_email'] = $email;
      $_SESSION['user_nom'] = $nom;
      $_SESSION['user_prenom'] = $prenom;

      // Redirection vers la page principale avec un message de succès
      set_message("Votre compte a été créé avec succès. Bienvenue chez La Mangeoire !", "success");
      header("Location: index.php");
      exit;
    } catch (PDOException $e) {
      $message = "Une erreur est survenue lors de la création de votre compte. Veuillez réessayer plus tard.";
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Créer un compte - La Mangeoire</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/cookie-consent.css">
  <style>
    :root {
      --primary-color: #ce1212;
      --primary-dark: #951010;
      --bg-color: #f4f7fa;
      --card-bg: #ffffff;
      --text-color: #333;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #f2f4f8;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    }

    .signup-container {
      width: 100%;
      max-width: 450px;
      margin: 0 auto;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      padding: 25px 30px;
    }

    .logo-container {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo-container img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid rgba(255, 182, 182, 0.2);
    }

    h1 {
      text-align: center;
      color: var(--primary-color);
      font-size: 24px;
      margin-bottom: 25px;
      font-weight: 600;
    }

    form {
      width: 100%;
    }

    .form-row {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
    }

    .form-group {
      flex: 1;
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      font-size: 14px;
      color: #555;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"] {
      width: 100%;
      padding: 10px 12px;
      border-radius: 4px;
      border: 1px solid #e0e0e0;
      font-size: 14px;
    }

    input:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 2px rgba(206, 18, 18, 0.1);
    }

    .password-hint {
      font-size: 12px;
      color: #777;
      margin-top: 4px;
      display: block;
    }

    .create-btn {
      width: 100%;
      padding: 12px;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      margin-top: 5px;
      margin-bottom: 15px;
    }

    .create-btn:hover {
      background-color: var(--primary-dark);
    }

    .login-link {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .login-link a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    .copyright {
      text-align: center;
      font-size: 12px;
      color: #777;
      margin-top: 20px;
    }

    .copyright span {
      font-weight: 600;
    }

    .message {
      color: #c62828;
      background: #fdeaea;
      border-radius: 4px;
      padding: 10px;
      margin-bottom: 15px;
      width: 100%;
      text-align: center;
      font-size: 14px;
    }

    @media (max-width: 576px) {
      .signup-container {
        max-width: 100%;
        margin: 0 15px;
        padding: 20px 15px;
      }

      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
</head>

<body>
  <div class="signup-container">
    <div class="logo-container">
      <img src="assets/img/favcon.jpeg" alt="Logo La Mangeoire">
    </div>

    <h1>Créer un compte</h1>

    <?php if ($message): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="inscription.php">
      <div class="form-row">
        <div class="form-group">
          <label for="nom">Nom *</label>
          <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
        </div>
        <div class="form-group">
          <label for="prenom">Prénom *</label>
          <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
      </div>

      <div class="form-group">
        <label for="password">Mot de passe *</label>
        <input type="password" id="password" name="password" required>
        <span class="password-hint">Au moins 8 caractères, une majuscule, une minuscule et un chiffre</span>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirmer le mot de passe *</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>

      <div class="form-group">
        <label for="telephone">Téléphone</label>
        <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone) ?>">
      </div>

      <div class="form-group">
        <label for="adresse">Adresse</label>
        <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($adresse) ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="code_postal">Code postal</label>
          <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($code_postal) ?>">
        </div>
        <div class="form-group">
          <label for="ville">Ville</label>
          <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($ville) ?>">
        </div>
      </div>

      <button type="submit" class="create-btn">Créer mon compte</button>

      <div class="login-link">
        <p>Déjà inscrit ? <a href="connexion.php">Connectez-vous</a></p>
      </div>
    </form>

    <p class="copyright">© Copyright <span>La Mangeoire</span>. Tous droits réservés</p>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Validation du mot de passe
      const passwordField = document.getElementById('password');
      const confirmPasswordField = document.getElementById('confirm_password');

      confirmPasswordField.addEventListener('input', function() {
        if (this.value === passwordField.value) {
          this.style.borderColor = '#28a745';
        } else {
          this.style.borderColor = '#dc3545';
        }
      });

      // Validation des entrées
      const form = document.querySelector('form');
      form.addEventListener('submit', function(e) {
        // Cette fonction peut être utilisée pour ajouter une validation côté client
        // Les validations principales sont effectuées côté serveur
      });
    });
  </script>
  
  <!-- Script pour le système de gestion des cookies -->
  <script src="assets/js/cookie-consent.js"></script>
</body>

</html>