<?php
require_once __DIR__ . '/../includes/common.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';

// Définir le titre de la page
$page_title = "Gestion des administrateurs";
// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);

// Traitement de l'ajout d'un administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF ajout administrateur');
  } else {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $role = ($_POST['role'] ?? '') === 'superadmin' ? 'superadmin' : 'admin';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation des champs
    $errors = [];
    if (!validate_email($email)) {
      $errors[] = "L'adresse email n'est pas valide.";
    }
    if (!validate_nom($nom)) {
      $errors[] = "Le nom n'est pas valide.";
    }
    if (!validate_prenom($prenom)) {
      $errors[] = "Le prénom n'est pas valide.";
    }
    if (!validate_password_strength($password)) {
      $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.";
    }
    if ($password !== $confirm_password) {
      $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Administrateurs WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
      $errors[] = "Cette adresse email est déjà utilisée.";
    }

    if (empty($errors)) {
      // Hachage du mot de passe
      $password_hash = password_hash($password, PASSWORD_DEFAULT);

      // Insertion de l'administrateur
      try {
        $stmt = $conn->prepare("INSERT INTO Administrateurs (Email, MotDePasse, Nom, Prenom, Role) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$email, $password_hash, $nom, $prenom, $role]);

        if ($result) {
          set_message("L'administrateur a été ajouté avec succès.");
          log_admin_action("Ajout administrateur", "Email: $email, Nom: $nom, Prénom: $prenom, Rôle: $role");
          header("Location: administrateurs.php");
          exit;
        } else {
          set_message("Une erreur est survenue lors de l'ajout de l'administrateur.", "error");
        }
      } catch (PDOException $e) {
        set_message("Erreur de base de données : " . $e->getMessage(), "error");
        log_admin_action("Erreur ajout administrateur", $e->getMessage());
      }
    } else {
      set_message(implode("<br>", $errors), "error");
    }
  }
}

// Traitement de la modification d'un administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF modification administrateur');
  } else {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $role = ($_POST['role'] ?? '') === 'superadmin' ? 'superadmin' : 'admin';

    // Protection contre la modification de son propre rôle
    if ($admin_id === intval($_SESSION['admin_id'] ?? 0)) {
      $role = 'superadmin'; // Forcer le rôle superadmin pour l'utilisateur actuel
    }

    // Validation des champs
    $errors = [];
    if (!validate_email($email)) {
      $errors[] = "L'adresse email n'est pas valide.";
    }
    if (!validate_nom($nom)) {
      $errors[] = "Le nom n'est pas valide.";
    }
    if (!validate_prenom($prenom)) {
      $errors[] = "Le prénom n'est pas valide.";
    }

    // Vérifier si l'email existe déjà pour un autre administrateur
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Administrateurs WHERE Email = ? AND AdminID != ?");
    $stmt->execute([$email, $admin_id]);
    if ($stmt->fetchColumn() > 0) {
      $errors[] = "Cette adresse email est déjà utilisée par un autre administrateur.";
    }

    if (empty($errors)) {
      try {
        $stmt = $conn->prepare("UPDATE Administrateurs SET Email = ?, Nom = ?, Prenom = ?, Role = ? WHERE AdminID = ?");
        $result = $stmt->execute([$email, $nom, $prenom, $role, $admin_id]);

        if ($result) {
          set_message("L'administrateur a été modifié avec succès.");
          log_admin_action("Modification administrateur", "ID: $admin_id, Email: $email, Nom: $nom, Prénom: $prenom, Rôle: $role");

          // Mise à jour de la session si l'utilisateur modifie ses propres informations
          if ($admin_id === intval($_SESSION['admin_id'] ?? 0)) {
            $_SESSION['admin_nom'] = $nom;
            $_SESSION['admin_prenom'] = $prenom;
            $_SESSION['admin_email'] = $email;
          }

          header("Location: administrateurs.php");
          exit;
        } else {
          set_message("Une erreur est survenue lors de la modification de l'administrateur.", "error");
        }
      } catch (PDOException $e) {
        set_message("Erreur de base de données : " . $e->getMessage(), "error");
        log_admin_action("Erreur modification administrateur", $e->getMessage());
      }
    } else {
      set_message(implode("<br>", $errors), "error");
    }
  }
}

// Traitement de la réinitialisation du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF réinitialisation mot de passe administrateur');
  } else {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation des champs
    $errors = [];
    if (!validate_password_strength($password)) {
      $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule et un chiffre.";
    }
    if ($password !== $confirm_password) {
      $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
      // Hachage du mot de passe
      $password_hash = password_hash($password, PASSWORD_DEFAULT);

      try {
        $stmt = $conn->prepare("UPDATE Administrateurs SET MotDePasse = ? WHERE AdminID = ?");
        $result = $stmt->execute([$password_hash, $admin_id]);

        if ($result) {
          set_message("Le mot de passe a été réinitialisé avec succès.");
          log_admin_action("Réinitialisation mot de passe administrateur", "ID: $admin_id");
          header("Location: administrateurs.php");
          exit;
        } else {
          set_message("Une erreur est survenue lors de la réinitialisation du mot de passe.", "error");
        }
      } catch (PDOException $e) {
        set_message("Erreur de base de données : " . $e->getMessage(), "error");
        log_admin_action("Erreur réinitialisation mot de passe administrateur", $e->getMessage());
      }
    } else {
      set_message(implode("<br>", $errors), "error");
    }
  }
}

// Traitement de la suppression d'un administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
  if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    set_message('Erreur de sécurité (CSRF).', 'error');
    log_admin_action('Tentative CSRF suppression administrateur');
  } else {
    $admin_id = intval($_POST['admin_id'] ?? 0);

    // Empêcher la suppression de son propre compte
    if ($admin_id === intval($_SESSION['admin_id'] ?? 0)) {
      set_message("Vous ne pouvez pas supprimer votre propre compte.", "error");
      header("Location: administrateurs.php");
      exit;
    }

    try {
      $stmt = $conn->prepare("DELETE FROM Administrateurs WHERE AdminID = ?");
      $result = $stmt->execute([$admin_id]);

      if ($result) {
        set_message("L'administrateur a été supprimé avec succès.");
        log_admin_action("Suppression administrateur", "ID: $admin_id");
        header("Location: administrateurs.php");
        exit;
      } else {
        set_message("Une erreur est survenue lors de la suppression de l'administrateur.", "error");
      }
    } catch (PDOException $e) {
      set_message("Erreur de base de données : " . $e->getMessage(), "error");
      log_admin_action("Erreur suppression administrateur", $e->getMessage());
    }
  }
}

// Récupération de la liste des administrateurs
$administrateurs = [];
try {
  $stmt = $conn->query("SELECT * FROM Administrateurs ORDER BY Role DESC, Nom ASC");
  $administrateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  set_message("Erreur lors de la récupération des administrateurs : " . $e->getMessage(), "error");
  log_admin_action("Erreur récupération administrateurs", $e->getMessage());
}
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
    .admin-card {
      margin-bottom: 30px;
    }

    .admin-role {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 8px;
    }

    .admin-role.superadmin {
      background-color: #ff4757;
      color: #fff;
    }

    .admin-role.admin {
      background-color: #1e90ff;
      color: #fff;
    }

    .admin-action-btn {
      margin-right: 5px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 80%;
      max-width: 600px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      animation: modalFadeIn 0.3s;
    }

    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .close-modal {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close-modal:hover {
      color: #000;
    }

    .modal-header {
      padding-bottom: 15px;
      margin-bottom: 15px;
      border-bottom: 1px solid #eee;
    }

    .modal-title {
      margin: 0;
      font-size: 1.5rem;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }

    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }

    .form-text {
      display: block;
      margin-top: 5px;
      font-size: 0.85rem;
      color: #666;
    }

    .btn-container {
      margin-top: 20px;
      text-align: right;
    }

    .current-user {
      background-color: #f8f9fa;
    }
  </style>
</head>

<body>
  <?php include 'header_template.php'; ?>

  <section class="admin-section">
    <div class="admin-container">
      <div class="admin-card">
        <div class="admin-card-header">
          <h2><i class="bi bi-people-fill"></i> Gestion des administrateurs</h2>
          <p>Gérez les utilisateurs ayant accès au panneau d'administration</p>
        </div>

        <div class="admin-card-body">
          <?php display_message(); ?>

          <div class="action-button-container">
            <button type="button" class="btn btn-primary" onclick="openModal('addAdminModal')">
              <i class="bi bi-person-plus"></i> Ajouter un administrateur
            </button>
          </div>

          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nom</th>
                  <th>Prénom</th>
                  <th>Email</th>
                  <th>Rôle</th>
                  <th>Date de création</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($administrateurs as $admin): ?>
                  <tr class="<?php echo (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $admin['AdminID']) ? 'current-user' : ''; ?>">
                    <td><?php echo $admin['AdminID']; ?></td>
                    <td><?php echo htmlspecialchars($admin['Nom']); ?></td>
                    <td><?php echo htmlspecialchars($admin['Prenom']); ?></td>
                    <td><?php echo htmlspecialchars($admin['Email']); ?></td>
                    <td>
                      <span class="admin-role <?php echo $admin['Role']; ?>">
                        <?php echo $admin['Role'] === 'superadmin' ? 'Super Admin' : 'Admin'; ?>
                      </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($admin['DateCreation'])); ?></td>
                    <td>
                      <button type="button" class="btn btn-sm btn-primary admin-action-btn"
                        onclick="openEditModal(<?php echo $admin['AdminID']; ?>, '<?php echo htmlspecialchars(addslashes($admin['Email'])); ?>', '<?php echo htmlspecialchars(addslashes($admin['Nom'])); ?>', '<?php echo htmlspecialchars(addslashes($admin['Prenom'])); ?>', '<?php echo $admin['Role']; ?>')">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-warning admin-action-btn"
                        onclick="openResetPasswordModal(<?php echo $admin['AdminID']; ?>, '<?php echo htmlspecialchars(addslashes($admin['Email'])); ?>')">
                        <i class="bi bi-key"></i>
                      </button>
                      <?php if ($admin['AdminID'] != ($_SESSION['admin_id'] ?? 0)): ?>
                        <button type="button" class="btn btn-sm btn-danger admin-action-btn"
                          onclick="openDeleteModal(<?php echo $admin['AdminID']; ?>, '<?php echo htmlspecialchars(addslashes($admin['Email'])); ?>')">
                          <i class="bi bi-trash"></i>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($administrateurs)): ?>
                  <tr>
                    <td colspan="7" class="text-center">Aucun administrateur trouvé.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal Ajout Administrateur -->
  <div id="addAdminModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeModal('addAdminModal')">&times;</span>
      <div class="modal-header">
        <h3 class="modal-title"><i class="bi bi-person-plus"></i> Ajouter un administrateur</h3>
      </div>
      <form method="POST" action="administrateurs.php" id="addAdminForm">
        <input type="hidden" name="action" value="ajouter">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="form-group">
          <label for="nom">Nom</label>
          <input type="text" class="form-control" id="nom" name="nom" required>
        </div>

        <div class="form-group">
          <label for="prenom">Prénom</label>
          <input type="text" class="form-control" id="prenom" name="prenom" required>
        </div>

        <div class="form-group">
          <label for="role">Rôle</label>
          <select class="form-control" id="role" name="role" required>
            <option value="admin">Administrateur</option>
            <option value="superadmin">Super Administrateur</option>
          </select>
          <small class="form-text">
            Les super administrateurs ont un accès complet à toutes les fonctionnalités.
          </small>
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" class="form-control" id="password" name="password" required>
          <small class="form-text">
            Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.
          </small>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirmer le mot de passe</label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="btn-container">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addAdminModal')">Annuler</button>
          <button type="submit" class="btn btn-primary">Ajouter</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Modification Administrateur -->
  <div id="editAdminModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeModal('editAdminModal')">&times;</span>
      <div class="modal-header">
        <h3 class="modal-title"><i class="bi bi-pencil"></i> Modifier un administrateur</h3>
      </div>
      <form method="POST" action="administrateurs.php" id="editAdminForm">
        <input type="hidden" name="action" value="modifier">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="admin_id" id="edit_admin_id">

        <div class="form-group">
          <label for="edit_email">Email</label>
          <input type="email" class="form-control" id="edit_email" name="email" required>
        </div>

        <div class="form-group">
          <label for="edit_nom">Nom</label>
          <input type="text" class="form-control" id="edit_nom" name="nom" required>
        </div>

        <div class="form-group">
          <label for="edit_prenom">Prénom</label>
          <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
        </div>

        <div class="form-group">
          <label for="edit_role">Rôle</label>
          <select class="form-control" id="edit_role" name="role" required>
            <option value="admin">Administrateur</option>
            <option value="superadmin">Super Administrateur</option>
          </select>
          <small class="form-text">
            Les super administrateurs ont un accès complet à toutes les fonctionnalités.
          </small>
        </div>

        <div class="btn-container">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editAdminModal')">Annuler</button>
          <button type="submit" class="btn btn-primary">Modifier</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Réinitialisation Mot de passe -->
  <div id="resetPasswordModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeModal('resetPasswordModal')">&times;</span>
      <div class="modal-header">
        <h3 class="modal-title"><i class="bi bi-key"></i> Réinitialiser le mot de passe</h3>
      </div>
      <p id="reset_password_email" class="mb-3"></p>
      <form method="POST" action="administrateurs.php" id="resetPasswordForm">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="admin_id" id="reset_admin_id">

        <div class="form-group">
          <label for="reset_password">Nouveau mot de passe</label>
          <input type="password" class="form-control" id="reset_password" name="password" required>
          <small class="form-text">
            Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.
          </small>
        </div>

        <div class="form-group">
          <label for="reset_confirm_password">Confirmer le mot de passe</label>
          <input type="password" class="form-control" id="reset_confirm_password" name="confirm_password" required>
        </div>

        <div class="btn-container">
          <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">Annuler</button>
          <button type="submit" class="btn btn-primary">Réinitialiser</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Suppression Administrateur -->
  <div id="deleteAdminModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeModal('deleteAdminModal')">&times;</span>
      <div class="modal-header">
        <h3 class="modal-title"><i class="bi bi-trash"></i> Supprimer un administrateur</h3>
      </div>
      <p>Êtes-vous sûr de vouloir supprimer l'administrateur <strong id="delete_admin_email"></strong> ?</p>
      <p class="text-danger">Cette action est irréversible.</p>

      <form method="POST" action="administrateurs.php" id="deleteAdminForm">
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="admin_id" id="delete_admin_id">

        <div class="btn-container">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteAdminModal')">Annuler</button>
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </div>
      </form>
    </div>
  </div>

  <?php include 'footer_template.php'; ?>

  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>
  <script>
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function openEditModal(id, email, nom, prenom, role) {
      document.getElementById('edit_admin_id').value = id;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_nom').value = nom;
      document.getElementById('edit_prenom').value = prenom;
      document.getElementById('edit_role').value = role;

      // Désactiver le champ de rôle si c'est l'utilisateur courant
      const currentUserId = <?php echo isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0; ?>;
      if (id === currentUserId) {
        document.getElementById('edit_role').disabled = true;
        document.getElementById('edit_role').value = 'superadmin';
      } else {
        document.getElementById('edit_role').disabled = false;
      }

      openModal('editAdminModal');
    }

    function openResetPasswordModal(id, email) {
      document.getElementById('reset_admin_id').value = id;
      document.getElementById('reset_password_email').textContent = `Réinitialisation du mot de passe pour: ${email}`;
      openModal('resetPasswordModal');
    }

    function openDeleteModal(id, email) {
      document.getElementById('delete_admin_id').value = id;
      document.getElementById('delete_admin_email').textContent = email;
      openModal('deleteAdminModal');
    }

    // Fermer les modales quand on clique en dehors
    window.onclick = function(event) {
      const modals = document.getElementsByClassName('modal');
      for (let i = 0; i < modals.length; i++) {
        if (event.target === modals[i]) {
          modals[i].style.display = 'none';
          document.body.style.overflow = 'auto';
        }
      }
    };

    // Validation des mots de passe
    document.addEventListener('DOMContentLoaded', function() {
      // Formulaire d'ajout
      const addPasswordField = document.getElementById('password');
      const addConfirmPasswordField = document.getElementById('confirm_password');

      if (addPasswordField && addConfirmPasswordField) {
        addConfirmPasswordField.addEventListener('input', function() {
          if (this.value === addPasswordField.value) {
            this.setCustomValidity('');
          } else {
            this.setCustomValidity('Les mots de passe ne correspondent pas');
          }
        });

        addPasswordField.addEventListener('input', function() {
          if (addConfirmPasswordField.value && this.value !== addConfirmPasswordField.value) {
            addConfirmPasswordField.setCustomValidity('Les mots de passe ne correspondent pas');
          } else {
            addConfirmPasswordField.setCustomValidity('');
          }
        });
      }

      // Formulaire de réinitialisation
      const resetPasswordField = document.getElementById('reset_password');
      const resetConfirmPasswordField = document.getElementById('reset_confirm_password');

      if (resetPasswordField && resetConfirmPasswordField) {
        resetConfirmPasswordField.addEventListener('input', function() {
          if (this.value === resetPasswordField.value) {
            this.setCustomValidity('');
          } else {
            this.setCustomValidity('Les mots de passe ne correspondent pas');
          }
        });

        resetPasswordField.addEventListener('input', function() {
          if (resetConfirmPasswordField.value && this.value !== resetConfirmPasswordField.value) {
            resetConfirmPasswordField.setCustomValidity('Les mots de passe ne correspondent pas');
          } else {
            resetConfirmPasswordField.setCustomValidity('');
          }
        });
      }
    });
  </script>
</body>

</html>