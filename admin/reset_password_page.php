<?php
// Ce script ajoute un lien vers la page de réinitialisation du mot de passe dans l'interface admin
require_once __DIR__ . '/../includes/common.php';
require_once '../db_connexion.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}

// Définir le titre de la page
$page_title = "Changer mon mot de passe";
define('INCLUDED_IN_PAGE', true);

// Récupérer l'email de l'administrateur connecté
$admin_email = $_SESSION['admin_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($page_title); ?> - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .password-hero {
      background: linear-gradient(135deg, #ce1212 0%, #9c0e0e 100%);
      border-radius: 15px;
      color: white;
      padding: 30px;
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(206, 18, 18, 0.2);
    }

    .password-hero::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 150px;
      height: 150px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      z-index: 0;
    }

    .password-hero::after {
      content: '';
      position: absolute;
      bottom: -30px;
      left: -30px;
      width: 100px;
      height: 100px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 50%;
      z-index: 0;
    }

    .password-hero h2 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 15px;
      position: relative;
      z-index: 1;
    }

    .password-hero p {
      font-size: 1.1rem;
      opacity: 0.9;
      position: relative;
      z-index: 1;
    }

    .feature-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      border-left: 5px solid #ce1212;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .feature-card h3 {
      color: #ce1212;
      font-size: 1.3rem;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }

    .feature-card h3 i {
      margin-right: 10px;
      background: #fff1f1;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: #ce1212;
    }

    .action-btn {
      background: #ce1212;
      color: white;
      border: none;
      padding: 15px 25px;
      border-radius: 50px;
      font-size: 1.1rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      box-shadow: 0 5px 15px rgba(206, 18, 18, 0.3);
    }

    .action-btn:hover {
      background: #b50f0f;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(206, 18, 18, 0.4);
    }

    .action-btn i {
      margin-right: 10px;
      font-size: 1.2rem;
    }

    .action-container {
      text-align: center;
      margin: 40px 0;
      position: relative;
    }

    .action-container::before {
      content: '';
      position: absolute;
      width: 80%;
      height: 1px;
      background: #f1f1f1;
      left: 10%;
      top: -20px;
    }

    .action-container::after {
      content: '';
      position: absolute;
      width: 80%;
      height: 1px;
      background: #f1f1f1;
      left: 10%;
      bottom: -20px;
    }

    .security-note {
      background: #fff8e1;
      border-radius: 12px;
      padding: 20px;
      position: relative;
      border-left: 5px solid #ffc107;
      margin-top: 30px;
    }

    .security-icon {
      position: absolute;
      top: -15px;
      left: 20px;
      background: #ffc107;
      color: white;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .steps-container {
      position: relative;
      padding-left: 50px;
      margin: 30px 0;
    }

    .step {
      position: relative;
      margin-bottom: 25px;
      padding-bottom: 5px;
    }

    .step-number {
      position: absolute;
      left: -50px;
      top: -5px;
      width: 35px;
      height: 35px;
      background: #ce1212;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }

    .step::after {
      content: '';
      position: absolute;
      left: -33px;
      top: 30px;
      width: 2px;
      height: calc(100% - 5px);
      background: #f1f1f1;
    }

    .step:last-child::after {
      display: none;
    }

    .step h4 {
      margin-bottom: 5px;
      color: #333;
    }

    .step p {
      color: #666;
      font-size: 0.9rem;
      margin-left: 5px;
    }

    .password-strength {
      display: flex;
      margin: 20px 0;
      align-items: center;
    }

    .strength-meter {
      height: 8px;
      flex-grow: 1;
      border-radius: 4px;
      background: #f1f1f1;
      margin-right: 15px;
      position: relative;
      overflow: hidden;
    }

    .strength-meter::after {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 75%;
      border-radius: 4px;
      background: linear-gradient(90deg, #ce1212, #ff4b4b);
    }

    .strength-text {
      font-weight: 600;
      color: #ce1212;
    }

    /* Animations */
    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(206, 18, 18, 0.4);
      }

      70% {
        box-shadow: 0 0 0 10px rgba(206, 18, 18, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(206, 18, 18, 0);
      }
    }

    .pulse {
      animation: pulse 2s infinite;
    }
  </style>
</head>

<body>
  <?php include 'header_template.php'; ?>

  <section class="admin-section">
    <div class="admin-container">
      <!-- Hero section -->
      <div class="password-hero">
        <h2><i class="bi bi-shield-lock"></i> Sécurisez votre compte</h2>
        <p>Un mot de passe fort est essentiel pour protéger votre compte administrateur et les données du restaurant. Utilisez notre nouvel outil pour créer un mot de passe robuste et sécurisé.</p>
      </div>

      <div class="row">
        <div class="col-md-5">
          <!-- Feature cards -->
          <div class="feature-card">
            <h3><i class="bi bi-shield-check"></i> Protection renforcée</h3>
            <p>Un mot de passe fort est votre première ligne de défense contre les accès non autorisés.</p>

            <div class="password-strength">
              <div class="strength-meter"></div>
              <span class="strength-text">Fort</span>
            </div>
          </div>

          <div class="feature-card">
            <h3><i class="bi bi-clock-history"></i> Mise à jour régulière</h3>
            <p>Il est recommandé de changer votre mot de passe tous les 3 mois pour une sécurité optimale.</p>
          </div>

          <div class="feature-card">
            <h3><i class="bi bi-fingerprint"></i> Authentification sécurisée</h3>
            <p>Votre mot de passe est stocké de manière cryptée pour garantir sa confidentialité.</p>
          </div>
        </div>

        <div class="col-md-7">
          <!-- Main content -->
          <div class="admin-card" style="border-top: 5px solid #ce1212;">
            <div class="admin-card-header">
              <h2><i class="bi bi-key"></i> Changer mon mot de passe</h2>
              <p>Suivez ces étapes simples pour modifier votre mot de passe</p>
            </div>

            <div class="admin-card-body">
              <!-- Steps -->
              <div class="steps-container">
                <div class="step">
                  <div class="step-number">1</div>
                  <h4>Accéder à l'outil</h4>
                  <p>Cliquez sur le bouton ci-dessous pour ouvrir l'interface de changement de mot de passe.</p>
                </div>

                <div class="step">
                  <div class="step-number">2</div>
                  <h4>Créer un mot de passe fort</h4>
                  <p>Utilisez une combinaison de lettres majuscules et minuscules, chiffres et symboles.</p>
                </div>

                <div class="step">
                  <div class="step-number">3</div>
                  <h4>Confirmer votre mot de passe</h4>
                  <p>Assurez-vous que les deux saisies correspondent pour éviter toute erreur.</p>
                </div>

                <div class="step">
                  <div class="step-number">4</div>
                  <h4>Enregistrer les modifications</h4>
                  <p>Cliquez sur le bouton "Changer le mot de passe" pour finaliser le processus.</p>
                </div>
              </div>

              <!-- Action button -->
              <div class="action-container">
                <a href="../reset_admin_password.php?email=<?php echo urlencode($admin_email); ?>&token=c7e3a4b9f2d1" class="action-btn pulse">
                  <i class="bi bi-shield-lock"></i> Modifier mon mot de passe
                </a>
              </div>

              <!-- Security note -->
              <div class="security-note">
                <div class="security-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <h4 style="margin-top: 5px;"><i class="bi bi-info-circle"></i> Information importante</h4>
                <p>Pour des raisons de sécurité, vous serez déconnecté après avoir changé votre mot de passe. Vous devrez vous reconnecter avec votre nouveau mot de passe.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer_template.php'; ?>

  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>
  <script>
    // Animation des éléments au chargement
    document.addEventListener('DOMContentLoaded', function() {
      const featureCards = document.querySelectorAll('.feature-card');

      featureCards.forEach((card, index) => {
        setTimeout(() => {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';

          setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, 100);
        }, index * 150);
      });

      // Focus sur le bouton d'action principal
      const actionBtn = document.querySelector('.action-btn');
      setTimeout(() => {
        actionBtn.classList.add('pulse');
      }, 1000);
    });
  </script>
</body>

</html>