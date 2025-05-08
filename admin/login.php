<?php
// Redirection vers la nouvelle page de connexion unifiée
header("Location: ../connexion-unifiee.php");
exit;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Connexion Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #ce1212;
      --primary-dark: #951010;
      --primary-light: #ff5252;
      --dark-bg: #1e1e24;
      --light-bg: #f8fafc;
      --text-color: #333;
      --light-text: #fff;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: var(--light-bg);
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      padding: 15px;
    }

    .login-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      padding: 48px 36px 36px 36px;
      min-width: 340px;
      max-width: 380px;
      width: 90%;
      display: flex;
      flex-direction: column;
      animation: fadeIn 0.6s ease forwards;
      align-items: center;
      box-sizing: border-box;
    }

    .login-card .logo {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-bottom: 24px;
      object-fit: cover;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      animation: pulseAnimation 2s infinite;
      border: 3px solid white;
      padding: 5px;
      background-color: #ffd1d1;
    }

    .login-card h1 {
      font-size: 2rem;
      color: var(--primary-color);
      margin-bottom: 24px;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      letter-spacing: 0.5px;
      animation: fadeIn 0.8s ease forwards;
      animation-delay: 0.2s;
      opacity: 0;
    }

    .login-card form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 20px;
      animation: fadeIn 0.8s ease forwards;
      animation-delay: 0.4s;
      opacity: 0;
      box-sizing: border-box;
    }

    .login-card .input-group {
      position: relative;
    }

    .login-card .input-group i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
      font-size: 1.2rem;
    }

    .login-card input {
      padding: 14px 18px 14px 48px;
      border-radius: 12px;
      border: 1px solid #e0e0e0;
      font-size: 1rem;
      background: #f8fafc;
      transition: all 0.3s;
      width: 100%;
      box-sizing: border-box;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .login-card input:focus {
      border: 1px solid var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 3px rgba(206, 18, 18, 0.1);
      transform: translateY(-2px);
    }

    .login-card button {
      background: var(--primary-color);
      color: #fff;
      font-weight: 600;
      font-size: 1rem;
      border: none;
      border-radius: 12px;
      padding: 16px 0;
      box-shadow: 0 5px 15px rgba(206, 18, 18, 0.2);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      z-index: 1;
      width: 100%;
      box-sizing: border-box;
    }

    .login-card button::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 0;
      height: 100%;
      background: var(--primary-dark);
      transition: width 0.3s ease;
      z-index: -1;
      border-radius: 12px;
    }

    .login-card button:hover::before {
      width: 100%;
    }

    .login-card button {
      margin-top: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(206, 18, 18, 0.3);
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

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulseAnimation {
      0% {
        transform: scale(1);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }

      50% {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(206, 18, 18, 0.2);
      }

      100% {
        transform: scale(1);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }
    }

    /* Styles responsifs pour mobile */
    @media (max-width: 576px) {
      .login-card {
        min-width: 290px;
        max-width: 100%;
        padding: 32px 24px;
        margin: 0 15px;
        width: 100%;
      }

      .login-card h1 {
        font-size: 1.6rem;
      }

      .login-card .logo {
        width: 70px;
        height: 70px;
      }

      .login-card input {
        font-size: 0.95rem;
      }
    }
  </style>
</head>

<body>
  <a href="../index.html" class="btn-retour-public" style="position: absolute; top: 20px; left: 20px;">
    <i class="bi bi-arrow-left-circle"></i> <span>Retour au site public</span>
  </a>

  <div class="login-card">
    <img src="../assets/img/favcon.jpeg" alt="Logo" class="logo">
    <h1>Connexion admin</h1>
    <?php if (isset($error)) echo '<div class="error">' . $error . '</div>'; ?>
    <form method="post" autocomplete="off">
      <div class="input-group">
        <i class="bi bi-person"></i>
        <input type="text" name="username" placeholder="Nom d'utilisateur" required autofocus>
      </div>
      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>
      <button type="submit"><i class="bi bi-box-arrow-in-right" style="margin-right:8px;"></i>Connexion</button>
    </form>
  </div>

  <script src="../assets/js/admin-animations.js"></script>
</body>

</html>