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
</head>

<body>
  <h1>Connexion administrateur</h1>
  <?php if (isset($error)) echo '<p style="color:red">' . $error . '</p>'; ?>
  <form method="post">
    <input type="text" name="username" placeholder="Nom d\'utilisateur" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>
    <button type="submit">Connexion</button>
  </form>
</body>

</html>