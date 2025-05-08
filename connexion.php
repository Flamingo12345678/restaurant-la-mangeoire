<?php
require_once __DIR__ . '/includes/common.php';
require_once 'db_connexion.php';

// Si l'utilisateur est déjà connecté, redirection vers la page de compte
if (isset($_SESSION['user_id'])) {
  header('Location: mon-compte.php');
  exit;
} elseif (isset($_SESSION['admin'])) {
  header('Location: admin/index.php');
  exit;
}

// Initialisation des variables
$email = '';
$message = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identifiant = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  // Normaliser l'identifiant pour comparaison
  $email = filter_var($identifiant, FILTER_SANITIZE_EMAIL);

  // Validation des entrées
  if (empty($identifiant) || empty($password)) {
    $message = "Veuillez renseigner votre identifiant et votre mot de passe.";
  } else {
    // Vérifier si c'est l'admin spécial avec nom d'utilisateur "Admin"
    if ($identifiant === 'Admin' && $password === 'D@@mso_237*') {
      // Authentification administrateur réussie
      session_regenerate_id(true);
      $_SESSION['admin'] = true;
      $_SESSION['admin_id'] = 0; // ID spécial
      $_SESSION['admin_role'] = 'superadmin';
      $_SESSION['admin_nom'] = 'Administrateur';
      $_SESSION['admin_prenom'] = 'Principal';
      $_SESSION['admin_email'] = 'admin@mangeoire.fr';

      header('Location: admin/index.php');
      exit;
    }

    // Vérifier d'abord si c'est un administrateur standard (par email)
    $stmt = $conn->prepare("SELECT AdminID, Email, MotDePasse, Nom, Prenom, Role FROM Administrateurs WHERE Email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['MotDePasse'])) {
      // Authentification administrateur réussie
      session_regenerate_id(true);
      $_SESSION['admin'] = true;
      $_SESSION['admin_id'] = $admin['AdminID'];
      $_SESSION['admin_role'] = $admin['Role'];
      $_SESSION['admin_nom'] = $admin['Nom'];
      $_SESSION['admin_prenom'] = $admin['Prenom'];
      $_SESSION['admin_email'] = $admin['Email'];
      header('Location: admin/index.php');
      exit;
    } else {
      // Sinon, recherche de l'utilisateur dans la base de données
      $stmt = $conn->prepare("SELECT UtilisateurID, Email, MotDePasse, Nom, Prenom FROM Utilisateurs WHERE Email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Vérification du mot de passe
      if ($user && password_verify($password, $user['MotDePasse'])) {
        // Création de la session utilisateur
        $_SESSION['user_id'] = $user['UtilisateurID'];
        $_SESSION['user_email'] = $user['Email'];
        $_SESSION['user_nom'] = $user['Nom'];
        $_SESSION['user_prenom'] = $user['Prenom'];

        // Migration du panier temporaire vers le panier en base de données
        if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
          foreach ($_SESSION['panier'] as $item) {
            // Vérifier si cet article existe déjà dans le panier de l'utilisateur
            $stmt = $conn->prepare("SELECT PanierID, Quantite FROM Panier WHERE UtilisateurID = ? AND MenuID = ?");
            $stmt->execute([$user['UtilisateurID'], $item['MenuID']]);
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_item) {
              // Mettre à jour la quantité si l'article existe déjà
              $new_quantity = $existing_item['Quantite'] + $item['Quantite'];
              $stmt = $conn->prepare("UPDATE Panier SET Quantite = ? WHERE PanierID = ?");
              $stmt->execute([$new_quantity, $existing_item['PanierID']]);
            } else {
              // Ajouter le nouvel article au panier
              $stmt = $conn->prepare("INSERT INTO Panier (UtilisateurID, MenuID, Quantite) VALUES (?, ?, ?)");
              $stmt->execute([$user['UtilisateurID'], $item['MenuID'], $item['Quantite']]);
            }
          }

          // Vider le panier temporaire
          unset($_SESSION['panier']);
        }

        // Redirection vers la page d'accueil ou la page précédente
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);

        header("Location: $redirect");
        exit;
      } else {
        $message = "Identifiants incorrects. Veuillez réessayer.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - La Mangeoire</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    }

    body {
      background: var(--bg-color);
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      background: var(--card-bg);
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      padding: 40px 30px;
      min-width: 340px;
      max-width: 380px;
      width: 90%;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .login-card .logo {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      margin-bottom: 24px;
      object-fit: cover;
      border: 3px solid #ffe6e6;
      padding: 5px;
      background-color: #fff;
    }

    .login-card h1 {
      font-size: 1.8rem;
      color: var(--primary-color);
      margin-bottom: 24px;
      font-weight: 600;
    }

    .login-card .message {
      color: #c62828;
      background: #fdeaea;
      border-radius: 8px;
      padding: 10px;
      margin-bottom: 15px;
      width: 100%;
      text-align: center;
      font-size: 0.9rem;
    }

    .login-card form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .login-card .input-group {
      position: relative;
      width: 100%;
    }

    .login-card .input-group i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
      font-size: 1.1rem;
    }

    .login-card input {
      padding: 12px 16px 12px 45px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      font-size: 0.95rem;
      width: 100%;
    }

    .login-card input:focus {
      border: 1px solid var(--primary-color);
      outline: none;
    }

    .login-card button {
      background: var(--primary-color);
      color: #fff;
      font-weight: 500;
      font-size: 1rem;
      border: none;
      border-radius: 8px;
      padding: 12px 0;
      margin-top: 10px;
      cursor: pointer;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s;
    }

    .login-card button:hover {
      background: var(--primary-dark);
    }

    .btn-retour {
      position: absolute;
      top: 15px;
      left: 15px;
      color: var(--primary-color);
      text-decoration: none;
      display: flex;
      align-items: center;
      font-size: 0.9rem;
    }

    .btn-retour i {
      margin-right: 5px;
    }

    @media (max-width: 576px) {
      .login-card {
        min-width: 290px;
        max-width: 100%;
        padding: 30px 20px;
        margin: 0 15px;
      }

      .login-card h1 {
        font-size: 1.5rem;
      }

      .login-card .logo {
        width: 60px;
        height: 60px;
      }
    }
  </style>
</head>

<body>
  <a href="index.php" class="btn-retour">
    <i class="bi bi-arrow-left-circle"></i> <span>Retour au site public</span>
  </a>

  <div class="login-card">
    <img src="assets/img/favcon.jpeg" alt="Logo" class="logo">
    <h1>Connexion admin</h1>

    <?php if ($message): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="connexion.php">
      <div class="input-group">
        <i class="bi bi-person"></i>
        <input type="text" name="email" placeholder="Nom d'utilisateur ou Email" value="<?= htmlspecialchars($identifiant ?? '') ?>" required autofocus>
      </div>

      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>

      <button type="submit">
        <i class="bi bi-box-arrow-in-right" style="margin-right:8px;"></i>Connexion
      </button>

      <div style="margin-top: 15px; text-align: center; font-size: 0.9rem;">
        <a href="mot-de-passe-oublie.php" style="color: var(--primary-color);">Mot de passe oublié ?</a>
      </div>

      <div style="margin-top: 10px; text-align: center; font-size: 0.9rem;">
        <p>Pas encore de compte ? <a href="inscription.php" style="color: var(--primary-color);">Inscrivez-vous</a></p>
      </div>
    </form>
  </div>
</body>

</html>