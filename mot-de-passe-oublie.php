<?php
require_once __DIR__ . '/includes/common.php';
require_once 'db_connexion.php';

// Initialisation des variables
$email = '';
$message = '';
$success = false;

// Traitement du formulaire de demande de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

  // Vérifier si l'email existe
  if (validate_email($email)) {
    $stmt = $conn->prepare("SELECT UtilisateurID, Email, Nom, Prenom FROM Utilisateurs WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
      // Générer un token unique de réinitialisation
      $token = bin2hex(random_bytes(32));
      $expiration = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiration dans 1 heure

      // Enregistrer le token dans la base de données
      $stmt = $conn->prepare("
                INSERT INTO ReinitialisationMotDePasse (UtilisateurID, Token, DateExpiration) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE Token = VALUES(Token), DateExpiration = VALUES(DateExpiration)
            ");
      $stmt->execute([$user['UtilisateurID'], $token, $expiration]);

      // Construire le lien de réinitialisation
      $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reinitialiser-mot-de-passe.php?token=" . $token;

      // Envoyer l'email (simulé pour l'instant)
      $to = $user['Email'];
      $subject = "Réinitialisation de votre mot de passe - La Mangeoire";
      $message_email = "Bonjour " . $user['Prenom'] . " " . $user['Nom'] . ",\n\n";
      $message_email .= "Vous avez demandé à réinitialiser votre mot de passe sur le site de La Mangeoire.\n\n";
      $message_email .= "Pour définir un nouveau mot de passe, veuillez cliquer sur le lien suivant :\n";
      $message_email .= $reset_link . "\n\n";
      $message_email .= "Ce lien expirera dans 1 heure.\n\n";
      $message_email .= "Si vous n'avez pas demandé à réinitialiser votre mot de passe, veuillez ignorer cet email.\n\n";
      $message_email .= "Cordialement,\nL'équipe de La Mangeoire";

      // En environnement de production, décommenter la ligne suivante :
      // mail($to, $subject, $message_email);

      // Pour le développement, on simule l'envoi
      $success = true;

      // Journaliser la demande de réinitialisation
      error_log("Demande de réinitialisation de mot de passe pour l'utilisateur " . $user['UtilisateurID'] . " (" . $user['Email'] . ")");
    } else {
      // Ne pas indiquer si l'email existe ou non pour des raisons de sécurité
      $success = true;
    }
  } else {
    $message = "L'adresse email n'est pas valide.";
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié - La Mangeoire</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/account.css">
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
</head>

<body>
  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center me-auto me-lg-0">
        <h1>La Mangeoire<span>.</span></h1>
      </a>

      <nav id="navbar" class="navbar">
        <ul>
          <li><a href="index.php">Accueil</a></li>
          <li><a href="#about">À propos</a></li>
          <li><a href="#menu">Menu</a></li>
          <li><a href="#events">Événements</a></li>
          <li><a href="#chefs">Chefs</a></li>
          <li><a href="#gallery">Galerie</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><a href="connexion.php">Connexion</a></li>
        </ul>
      </nav>

      <a class="btn-book-a-table" href="#book-a-table">Réserver une table</a>
      <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
      <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>
    </div>
  </header>
  <!-- End Header -->

  <main id="main">
    <section class="reset-password-section">
      <div class="container" data-aos="fade-up">
        <div class="section-header">
          <h2>Mot de passe oublié</h2>
          <p>Récupérez l'accès à votre <span>compte</span></p>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-6">
            <?php display_message(); ?>

            <?php if ($message): ?>
              <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="alert alert-success">
                <p>Si l'adresse email que vous avez saisie correspond à un compte existant, vous recevrez un email contenant les instructions pour réinitialiser votre mot de passe.</p>
                <p>Veuillez vérifier votre boîte de réception (et éventuellement vos spams).</p>
              </div>
              <div class="text-center mt-4">
                <a href="connexion.php" class="btn-book-a-table">Retour à la connexion</a>
              </div>
            <?php else: ?>
              <form method="POST" action="mot-de-passe-oublie.php" class="php-email-form">
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" name="email" value="<?= $email ?>" required>
                  <small class="text-muted">Entrez l'adresse email associée à votre compte</small>
                </div>

                <div class="text-center mt-4">
                  <button type="submit" class="btn-book-a-table">Réinitialiser mon mot de passe</button>
                </div>

                <div class="text-center mt-3">
                  <p><a href="connexion.php">Retour à la connexion</a></p>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright">
        &copy; Copyright <strong><span>La Mangeoire</span></strong>. Tous droits réservés
      </div>
    </div>
  </footer>
  <!-- End Footer -->

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <!-- Validation JS File -->
  <script src="assets/js/auth-validation.js"></script>
</body>

</html>