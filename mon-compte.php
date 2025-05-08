<?php
require_once __DIR__ . '/includes/common.php';
require_once 'db_connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
  // Sauvegarder l'URL actuelle pour rediriger après connexion
  $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
  header('Location: connexion.php');
  exit;
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM Utilisateurs WHERE UtilisateurID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  // Si l'utilisateur n'existe pas (compte supprimé par exemple)
  session_destroy();
  header('Location: connexion.php');
  exit;
}

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
  $prenom = htmlspecialchars(trim($_POST['prenom'] ?? ''));
  $telephone = htmlspecialchars(trim($_POST['telephone'] ?? ''));
  $adresse = htmlspecialchars(trim($_POST['adresse'] ?? ''));
  $code_postal = htmlspecialchars(trim($_POST['code_postal'] ?? ''));
  $ville = htmlspecialchars(trim($_POST['ville'] ?? ''));

  // Validation des données
  $erreurs = [];

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

  // Si aucune erreur, on met à jour les informations
  if (empty($erreurs)) {
    $sql = "UPDATE Utilisateurs SET Nom = ?, Prenom = ?, Telephone = ?, Adresse = ?, CodePostal = ?, Ville = ? WHERE UtilisateurID = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nom, $prenom, $telephone, $adresse, $code_postal, $ville, $user_id])) {
      // Mise à jour des informations de session
      $_SESSION['user_nom'] = $nom;
      $_SESSION['user_prenom'] = $prenom;

      set_message("Vos informations ont été mises à jour avec succès.", "success");
      header("Location: mon-compte.php");
      exit;
    } else {
      set_message("Une erreur est survenue lors de la mise à jour de vos informations.", "error");
    }
  } else {
    set_message(implode("<br>", $erreurs), "error");
  }
}

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $current_password = $_POST['current_password'] ?? '';
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  // Validation des données
  $erreurs = [];

  // Vérifier le mot de passe actuel
  if (!password_verify($current_password, $user['MotDePasse'])) {
    $erreurs[] = "Le mot de passe actuel est incorrect.";
  }

  // Vérifier le nouveau mot de passe
  if (!validate_password_strength($new_password)) {
    $erreurs[] = "Le nouveau mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.";
  }

  // Vérifier que les deux mots de passe correspondent
  if ($new_password !== $confirm_password) {
    $erreurs[] = "Les nouveaux mots de passe ne correspondent pas.";
  }

  // Si aucune erreur, on met à jour le mot de passe
  if (empty($erreurs)) {
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE Utilisateurs SET MotDePasse = ? WHERE UtilisateurID = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$password_hash, $user_id])) {
      set_message("Votre mot de passe a été modifié avec succès.", "success");
      header("Location: mon-compte.php");
      exit;
    } else {
      set_message("Une erreur est survenue lors de la modification de votre mot de passe.", "error");
    }
  } else {
    set_message(implode("<br>", $erreurs), "error");
  }
}

// Traitement de la suppression du compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
  $password = $_POST['password_delete'] ?? '';

  // Vérifier le mot de passe
  if (password_verify($password, $user['MotDePasse'])) {
    // Supprimer le compte
    $sql = "DELETE FROM Utilisateurs WHERE UtilisateurID = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$user_id])) {
      // Détruire la session
      session_destroy();

      // Rediriger vers la page d'accueil avec un message
      session_start();
      set_message("Votre compte a été supprimé avec succès.", "success");
      header("Location: index.php");
      exit;
    } else {
      set_message("Une erreur est survenue lors de la suppression de votre compte.", "error");
    }
  } else {
    set_message("Mot de passe incorrect. La suppression du compte a échoué.", "error");
  }
}

// Récupérer les commandes de l'utilisateur
$stmt = $conn->prepare("
    SELECT c.CommandeID, c.ReservationID, DATE_FORMAT(r.DateReservation, '%d/%m/%Y') as Date, 
    SUM(m.Prix * c.Quantite) as Total, 
    GROUP_CONCAT(CONCAT(m.NomItem, ' (', c.Quantite, ')') SEPARATOR ', ') as Details,
    MAX(COALESCE(p.Statut, 'Non payé')) as StatutPaiement
    FROM Commandes c
    JOIN Reservations r ON c.ReservationID = r.ReservationID
    JOIN Menus m ON c.MenuID = m.MenuID
    LEFT JOIN Paiements p ON c.ReservationID = p.ReservationID
    WHERE c.UtilisateurID = ?
    GROUP BY c.CommandeID, c.ReservationID, r.DateReservation
    ORDER BY r.DateReservation DESC
");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les cartes bancaires de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM CartesBancaires WHERE UtilisateurID = ? ORDER BY EstDefaut DESC");
$stmt->execute([$user_id]);
$cartes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Compte - La Mangeoire</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/account.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #ce1212;
      --primary-dark: #951010;
      --accent-color: #ce1212;
      --contrast-color: #ffffff;
      --bg-color: #f4f7fa;
      --card-bg: #ffffff;
      --text-color: #333;
      --border-color: #e0e0e0;
      --text-muted: #777;
    }

    /* Styles spécifiques à la page mon-compte */
    body {
      background-color: #f2f4f8;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      padding: 40px 0;
    }

    .account-container {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      padding: 30px;
      position: relative;
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

    h1,
    h2,
    h3 {
      text-align: center;
      color: var(--primary-color);
    }

    h1 {
      font-size: 24px;
      margin-bottom: 5px;
      font-weight: 600;
    }

    h2 {
      font-size: 20px;
      margin-bottom: 20px;
      color: var(--primary-color);
    }

    .welcome-text {
      text-align: center;
      margin-bottom: 25px;
      color: var(--text-muted);
      font-size: 16px;
    }

    .welcome-text span {
      font-weight: 600;
      color: var(--text-color);
    }

    /* Navigation par onglets */
    .account-tabs {
      display: flex;
      flex-wrap: wrap;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 25px;
      justify-content: center;
    }

    .account-tabs button {
      background: none;
      border: none;
      padding: 12px 20px;
      font-weight: 500;
      color: var(--text-muted);
      cursor: pointer;
      margin-right: 10px;
      position: relative;
      transition: all 0.3s ease;
      font-size: 15px;
      text-transform: none;
    }

    .account-tabs button:hover {
      color: var(--primary-color);
      background-color: rgba(206, 18, 18, 0.05);
    }

    .account-tabs button.active {
      color: var(--primary-color);
      font-weight: 600;
    }

    .account-tabs button.active::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: var(--primary-color);
    }

    .tab-content {
      padding: 10px 0;
    }

    .tab-pane {
      display: none;
    }

    .tab-pane.active {
      display: block;
    }

    /* Tableaux */
    .table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .table th,
    .table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    .table th {
      font-weight: 600;
      color: var(--text-color);
      background-color: #f8f9fa;
      position: sticky;
      top: 0;
    }

    .table tr:hover {
      background-color: rgba(0, 0, 0, 0.02);
    }

    .form-group {
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
      border: 1px solid var(--border-color);
      font-size: 14px;
    }

    input:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 2px rgba(206, 18, 18, 0.1);
    }

    /* Boutons d'action */
    .action-btn {
      width: 100%;
      padding: 12px;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      margin-top: 10px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
      text-align: center;
      text-decoration: none;
      display: inline-block;
    }

    .action-btn:hover {
      background-color: var(--primary-dark);
      color: white;
      text-decoration: none;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(206, 18, 18, 0.2);
    }

    .action-btn:active {
      transform: translateY(0);
      box-shadow: 0 2px 4px rgba(206, 18, 18, 0.2);
    }

    .btn-danger {
      background-color: #dc3545;
    }

    .btn-danger:hover {
      background-color: #bb2d3b;
    }

    /* Disposition des cartes */
    .form-row {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
    }

    /* Badges pour les statuts */
    .badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
      text-align: center;
    }

    .badge-success {
      background-color: #28a745;
      color: white;
    }

    .badge-warning {
      background-color: #ffc107;
      color: #212529;
    }

    /* Liens dans les tableaux */
    .table-link {
      color: #0d6efd;
      text-decoration: none;
      margin-right: 8px;
      font-weight: 500;
      transition: all 0.2s ease;
      display: inline-block;
    }

    .table-link:hover {
      color: #0a58ca;
      transform: translateY(-1px);
    }

    /* Messages d'alerte */
    .message {
      color: #c62828;
      background: #fdeaea;
      border-radius: 6px;
      padding: 12px 15px;
      margin-bottom: 20px;
      width: 100%;
      text-align: center;
      font-size: 14px;
      border-left: 4px solid #c62828;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .message.success {
      color: #28a745;
      background: #e8f4ee;
      border-left: 4px solid #28a745;
    }

    .table {
      width: 100%;
      margin-bottom: 20px;
      border-collapse: collapse;
    }

    .table th,
    .table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    .table th {
      font-weight: 600;
      color: var(--text-color);
      background-color: #f8f9fa;
    }

    /* Copyright */
    .copyright {
      text-align: center;
      font-size: 12px;
      color: #777;
      margin-top: 25px;
      padding-top: 15px;
      border-top: 1px solid #eee;
      width: 100%;
      position: relative;
      bottom: 0;
      left: 0;
      right: 0;
    }

    .copyright span {
      font-weight: 600;
      color: var(--primary-color);
    }

    /* Animations */
    .tab-pane {
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .tab-pane.active {
      opacity: 1;
      transform: translateY(0);
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Media queries */
    @media (max-width: 768px) {
      .account-container {
        max-width: 95%;
        padding: 20px 15px;
        margin-top: 20px;
        margin-bottom: 20px;
      }

      .account-tabs {
        justify-content: center;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 5px;
      }

      .account-tabs button {
        padding: 8px 12px;
        margin-right: 0;
        font-size: 13px;
        white-space: nowrap;
      }

      .btn-retour span {
        display: none;
      }

      .form-row {
        flex-direction: column;
        gap: 0;
      }

      h1 {
        font-size: 22px;
      }

      h2 {
        font-size: 18px;
      }
    }
  </style>
</head>

<body>
  <a href="index.php" class="btn-retour">
    <i class="bi bi-arrow-left-circle"></i> <span>Retour à l'accueil</span>
  </a>

  <div class="account-container">
    <div class="logo-container">
      <img src="assets/img/favcon.jpeg" alt="Logo La Mangeoire">
    </div>

    <h1>Mon Compte</h1>
    <p class="welcome-text">Bienvenue, <span><?= htmlspecialchars($user['Prenom']) ?> <?= htmlspecialchars($user['Nom']) ?></span></p>

    <?php if (isset($_SESSION['message'])): ?>
      <div class="message <?= $_SESSION['message_type'] === 'success' ? 'success' : '' ?>">
        <?= $_SESSION['message'] ?>
      </div>
      <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Onglets du compte -->
    <div class="account-tabs">
      <button id="tab-profile" class="active">Profil</button>
      <button id="tab-orders">Mes commandes</button>
      <button id="tab-cards">Mes cartes bancaires</button>
      <button id="tab-password">Changer mot de passe</button>
      <button id="tab-delete">Supprimer compte</button>
    </div>

    <!-- Contenu des onglets -->
    <div class="tab-content">
      <!-- Onglet Profil -->
      <div class="tab-pane active" id="profile">
        <h2>Informations personnelles</h2>
        <form method="post" action="mon-compte.php">
          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label for="nom">Nom</label>
              <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['Nom']) ?>" required>
            </div>
            <div class="form-group" style="flex:1;">
              <label for="prenom">Prénom</label>
              <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['Prenom']) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" value="<?= htmlspecialchars($user['Email']) ?>" readonly style="background-color: #f9f9f9;">
            <small style="display: block; margin-top: 5px; color: #777;">L'email ne peut pas être modifié</small>
          </div>
          <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($user['Telephone']) ?>">
          </div>
          <div class="form-group">
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($user['Adresse']) ?>">
          </div>
          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($user['CodePostal']) ?>">
            </div>
            <div class="form-group" style="flex:2;">
              <label for="ville">Ville</label>
              <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($user['Ville']) ?>">
            </div>
          </div>
          <button type="submit" name="update_profile" class="action-btn">Mettre à jour le profil</button>
        </form>
      </div>

      <!-- Onglet Commandes -->
      <div class="tab-pane" id="orders">
        <h2>Historique des commandes</h2>
        <?php if (empty($commandes)): ?>
          <p style="text-align: center; margin: 20px 0;">Vous n'avez pas encore passé de commande.</p>
        <?php else: ?>
          <div style="overflow-x: auto;">
            <table class="table">
              <thead>
                <tr>
                  <th>N° Commande</th>
                  <th>Date</th>
                  <th>Détails</th>
                  <th>Total</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($commandes as $commande): ?>
                  <tr>
                    <td>#<?= $commande['CommandeID'] ?></td>
                    <td><?= $commande['Date'] ?></td>
                    <td><?= strlen($commande['Details']) > 50 ? substr(htmlspecialchars($commande['Details']), 0, 50) . '...' : htmlspecialchars($commande['Details']) ?></td>
                    <td><?= number_format($commande['Total'], 2, ',', ' ') ?> €</td>
                    <td>
                      <?php if ($commande['StatutPaiement'] === 'Confirme'): ?>
                        <span class="badge badge-success">Payé</span>
                      <?php elseif ($commande['StatutPaiement'] === 'En attente'): ?>
                        <span class="badge badge-warning">En attente</span>
                      <?php elseif ($commande['StatutPaiement'] === 'Refuse'): ?>
                        <span class="badge" style="background-color: #dc3545; color: white;">Refusé</span>
                      <?php elseif ($commande['StatutPaiement'] === 'Annule'): ?>
                        <span class="badge" style="background-color: #6c757d; color: white;">Annulé</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Non payé</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="detail-commande.php?id=<?= $commande['CommandeID'] ?>" class="table-link">Détails</a>
                      <?php if ($commande['StatutPaiement'] === 'Non payé' || $commande['StatutPaiement'] === 'Refuse'): ?>
                        <a href="payer-commande.php?id=<?= $commande['CommandeID'] ?>" class="table-link">Payer</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div> <!-- Onglet Cartes bancaires -->
      <div class="tab-pane" id="cards">
        <h2>Mes cartes de paiement</h2>
        <?php if (empty($cartes)): ?>
          <p style="text-align: center; margin: 20px 0;">Vous n'avez pas encore enregistré de carte bancaire.</p>
        <?php else: ?>
          <?php foreach ($cartes as $carte): ?>
            <div class="payment-card <?= $carte['EstDefaut'] ? 'default' : '' ?>">
              <?php if ($carte['EstDefaut']): ?>
                <div class="default-badge">Par défaut</div>
              <?php endif; ?>
              <div style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                <div>
                  <strong>Numéro:</strong> **** **** **** <?= substr($carte['NumeroCarte'], -4) ?> <br>
                  <strong>Titulaire:</strong> <?= htmlspecialchars($carte['NomTitulaire']) ?>
                </div>
                <div>
                  <strong>Expiration:</strong> <?= htmlspecialchars($carte['MoisExpiration']) ?>/<?= htmlspecialchars($carte['AnneeExpiration']) ?> <br>
                  <div class="card-actions">
                    <?php if (!$carte['EstDefaut']): ?>
                      <a href="definir-carte-defaut.php?id=<?= $carte['CarteID'] ?>" class="action-btn" style="background-color: var(--primary-color); color: white; display: inline-block; padding: 5px 12px; margin: 0;">Définir par défaut</a>
                    <?php endif; ?>
                    <a href="supprimer-carte.php?id=<?= $carte['CarteID'] ?>" class="action-btn btn-danger" style="display: inline-block; padding: 5px 12px; margin: 0;">Supprimer</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
          <a href="ajouter-carte.php" class="action-btn" style="display: inline-block; max-width: 300px;">Ajouter une carte</a>
        </div>
      </div>

      <!-- Onglet Mot de passe -->
      <div class="tab-pane" id="password">
        <h2>Changer mon mot de passe</h2>
        <form method="post" action="mon-compte.php">
          <div class="form-group">
            <label for="current_password">Mot de passe actuel</label>
            <input type="password" id="current_password" name="current_password" required>
          </div>
          <div class="form-group">
            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" id="new_password" name="new_password" required>
            <small style="display: block; margin-top: 5px; color: #777;">Au moins 8 caractères, une majuscule, une minuscule et un chiffre</small>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>
          <button type="submit" name="change_password" class="action-btn">Changer le mot de passe</button>
        </form>
      </div>

      <!-- Onglet Supprimer compte -->
      <div class="tab-pane" id="delete">
        <h2 style="color: #dc3545;">Supprimer mon compte</h2>
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px;">
          <p style="margin: 0; color: #856404;"><i class="bi bi-exclamation-triangle-fill" style="margin-right: 10px;"></i>Attention : Cette action est irréversible. Toutes vos données personnelles seront supprimées.</p>
        </div>

        <form method="post" action="mon-compte.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.');">
          <div class="form-group">
            <label for="password_delete">Veuillez saisir votre mot de passe pour confirmer</label>
            <input type="password" id="password_delete" name="password_delete" required>
          </div>
          <button type="submit" name="delete_account" class="action-btn btn-danger">Supprimer mon compte</button>
        </form>
      </div>
    </div>

    <div class="copyright">
      &copy; Copyright <span>La Mangeoire</span>. Tous droits réservés
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Gestion des onglets
      const tabs = document.querySelectorAll('.account-tabs button');
      const tabPanes = document.querySelectorAll('.tab-pane');

      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Retirer la classe active de tous les onglets et panneaux
          tabs.forEach(t => t.classList.remove('active'));
          tabPanes.forEach(p => p.classList.remove('active'));

          // Ajouter la classe active à l'onglet cliqué
          this.classList.add('active');

          // Afficher le panneau correspondant
          const tabId = this.id.replace('tab-', '');
          document.getElementById(tabId).classList.add('active');
        });
      });

      // Validation du mot de passe de confirmation
      const newPasswordField = document.getElementById('new_password');
      const confirmPasswordField = document.getElementById('confirm_password');

      if (confirmPasswordField && newPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
          if (this.value === newPasswordField.value) {
            this.style.borderColor = '#28a745';
          } else {
            this.style.borderColor = '#dc3545';
          }
        });

        newPasswordField.addEventListener('input', function() {
          if (confirmPasswordField.value && confirmPasswordField.value !== this.value) {
            confirmPasswordField.style.borderColor = '#dc3545';
          } else if (confirmPasswordField.value) {
            confirmPasswordField.style.borderColor = '#28a745';
          }
        });
      }
    });
   </script>
  </body>

  </html>