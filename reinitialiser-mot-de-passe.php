<?php
require_once __DIR__ . '/includes/common.php';
require_once 'db_connexion.php';

// Initialisation des variables
$message = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_id = null;
$success = false;

// Vérifier si le token est valide
if (!empty($token)) {
  $stmt = $conn->prepare("
        SELECT r.UtilisateurID, u.Email 
        FROM ReinitialisationMotDePasse r
        JOIN Utilisateurs u ON r.UtilisateurID = u.UtilisateurID
        WHERE r.Token = ? AND r.DateExpiration > NOW()
    ");
  $stmt->execute([$token]);
  $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($reset_data) {
    $valid_token = true;
    $user_id = $reset_data['UtilisateurID'];
    $user_email = $reset_data['Email'];
  } else {
    $message = "Ce lien de réinitialisation est invalide ou a expiré. Veuillez faire une nouvelle demande.";
  }
}

// Traitement du formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  // Validation du nouveau mot de passe
  $erreurs = [];

  if (!validate_password_strength($new_password)) {
    $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.";
  }

  if ($new_password !== $confirm_password) {
    $erreurs[] = "Les mots de passe ne correspondent pas.";
  }

  // Si pas d'erreurs, mettre à jour le mot de passe
  if (empty($erreurs)) {
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Utilisateurs SET MotDePasse = ? WHERE UtilisateurID = ?");

    if ($stmt->execute([$password_hash, $user_id])) {
      // Supprimer le token de réinitialisation
      $stmt = $conn->prepare("DELETE FROM ReinitialisationMotDePasse WHERE UtilisateurID = ?");
      $stmt->execute([$user_id]);

      $success = true;

      // Journaliser la réinitialisation
      error_log("Mot de passe réinitialisé pour l'utilisateur " . $user_id);
    } else {
      $message = "Une erreur est survenue lors de la réinitialisation de votre mot de passe. Veuillez réessayer.";
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
  <title>Réinitialisation de mot de passe - La Mangeoire</title>
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

      <a class="btn-book-a-table" href="reserver-table.php">Réserver une table</a>
      <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
      <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>
    </div>
  </header>
  <!-- End Header -->

  <main id="main">
    <section class="reset-password-section">
      <div class="container" data-aos="fade-up">
        <div class="section-header">
          <h2>Réinitialisation de mot de passe</h2>
          <p>Définissez un nouveau <span>mot de passe</span></p>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-6">
            <?php display_message(); ?>

            <?php if ($message): ?>
              <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="alert alert-success">
                <p>Votre mot de passe a été réinitialisé avec succès.</p>
                <p>Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
              </div>
              <div class="text-center mt-4">
                <a href="connexion.php" class="btn-book-a-table">Se connecter</a>
              </div>
            <?php elseif ($valid_token): ?>
              <form method="POST" action="reinitialiser-mot-de-passe.php?token=<?= $token ?>" class="php-email-form">
                <div class="mb-3">
                  <label for="new_password" class="form-label">Nouveau mot de passe</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                  <small class="text-muted">Au moins 8 caractères, une majuscule, une minuscule et un chiffre</small>
                </div>

                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="text-center mt-4">
                  <button type="submit" class="btn-book-a-table">Réinitialiser mon mot de passe</button>
                </div>
              </form>
            <?php else: ?>
              <div class="alert alert-warning">
                <p>Le lien de réinitialisation est invalide ou a expiré.</p>
                <p>Veuillez faire une nouvelle demande de réinitialisation de mot de passe.</p>
              </div>
              <div class="text-center mt-4">
                <a href="mot-de-passe-oublie.php" class="btn-book-a-table">Nouvelle demande</a>
              </div>
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