<?php
session_start();
// Authentification simple (à renforcer en production)
$admin_username = 'admin';
// Mot de passe par défaut : 'admin' (à changer après la première connexion)
$admin_password_hash = password_hash('admin', PASSWORD_DEFAULT);

if (isset($_POST['username'], $_POST['password'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  // Vérification de l'identifiant et du mot de passe
  if ($username === $admin_username && password_verify($password, $admin_password_hash)) {
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    header('Location: index.php');
    exit();
  } else {
    $error = 'Identifiants incorrects';
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Connexion Admin</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background: #f6f8fb;
      font-family: 'Inter', 'Roboto', Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px 0 #2342a41a;
      padding: 48px 36px 36px 36px;
      min-width: 340px;
      max-width: 380px;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .login-card .logo {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      margin-bottom: 18px;
      object-fit: cover;
      box-shadow: 0 2px 8px #0001;
    }

    .login-card h1 {
      font-size: 2rem;
      color: #1a237e;
      margin-bottom: 18px;
      font-family: 'Inter', Arial, sans-serif;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .login-card form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .login-card input {
      padding: 12px 16px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      font-size: 1rem;
      background: #f5f7fa;
      transition: border 0.2s;
    }

    .login-card input:focus {
      border: 1.5px solid #2342a4;
      outline: none;
    }

    .login-card button {
      background: #1a237e;
      color: #fff;
      font-weight: bold;
      font-size: 1.1em;
      border: none;
      border-radius: 8px;
      padding: 14px 0;
      margin-top: 8px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .login-card button:hover {
      background: #283593;
    }

    .login-card .error {
      color: #c62828;
      background: #fdeaea;
      border: 1px solid #f5c2c7;
      border-radius: 8px;
      padding: 10px 0;
      margin-bottom: 10px;
      width: 100%;
      text-align: center;
      font-weight: 500;
    }
  </style>
</head>

<body>
  <div class="login-card">
    <img src="../assets/img/favcon.jpeg" alt="Logo" class="logo">
    <h1>Connexion admin</h1>
    <?php if (isset($error)) echo '<div class="error">' . $error . '</div>'; ?>
    <form method="post" autocomplete="off">
      <input type="text" name="username" placeholder="Nom d'utilisateur" required autofocus>
      <input type="password" name="password" placeholder="Mot de passe" required>
      <button type="submit"><i class="bi bi-box-arrow-in-right" style="margin-right:8px;"></i>Connexion</button>
    </form>
  </div>
</body>

</html>