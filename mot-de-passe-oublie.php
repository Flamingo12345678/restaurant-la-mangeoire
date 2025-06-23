<?php
require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/db_connexion.php';

// Initialisation des variables
$email = '';
$message = '';
$success = false;

// Traitement du formulaire de demande de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

  // Vérifier si l'email existe
  if (validate_email($email)) {
    $stmt = $pdo->prepare("SELECT ClientID, Email, Nom, Prenom FROM Clients WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
      $token = bin2hex(random_bytes(32));
      $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

      $stmt = $pdo->prepare("
        INSERT INTO ReinitialisationMotDePasse (ClientID, Token, DateExpiration) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE Token = VALUES(Token), DateExpiration = VALUES(DateExpiration)
      ");
      $stmt->execute([$user['ClientID'], $token, $expiration]);

      $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reinitialiser-mot-de-passe.php?token=" . $token;

      $to = $user['Email'];
      $subject = "Réinitialisation de votre mot de passe - La Mangeoire";
      $message_email = "Bonjour " . $user['Prenom'] . " " . $user['Nom'] . ",\n\n";
      $message_email .= "Vous avez demandé à réinitialiser votre mot de passe sur le site de La Mangeoire.\n\n";
      $message_email .= "Pour définir un nouveau mot de passe, veuillez cliquer sur le lien suivant :\n";
      $message_email .= $reset_link . "\n\n";
      $message_email .= "Ce lien expirera dans 1 heure.\n\n";
      $message_email .= "Si vous n'avez pas demandé à réinitialiser votre mot de passe, veuillez ignorer cet email.\n\n";
      $message_email .= "Cordialement,\nL'équipe de La Mangeoire";

      // mail($to, $subject, $message_email);

      $success = true;
      error_log("Demande de réinitialisation de mot de passe pour le client " . $user['ClientID'] . " (" . $user['Email'] . ")");
    } else {
      $success = true;
    }
  } else {
    $message = "L'adresse email n'est pas valide.";
  }
}
?>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
