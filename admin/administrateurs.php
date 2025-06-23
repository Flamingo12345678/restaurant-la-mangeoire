<?php

require_once 'check_admin_access.php';
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

// Définir le titre de la page
$page_title = "Gestion des Administrateurs";

// CSS supplémentaires spécifiques à cette page
$additional_css = [
    'css/admin-messages.css'
];

require_once 'header_template.php';
?>
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
<td class="actions-column">
<button type="button" class="admin-action-btn edit-action" data-color="blue" title="Modifier" 
                        onclick="openEditModal(<?php echo $admin['AdminID']; ?>, '<?php echo htmlspecialchars(addslashes($admin['Email'])); ?>', '<?php echo htmlspecialchars(addslashes($admin['Nom'])); ?>', '<?php echo htmlspecialchars(addslashes($admin['Prenom'])); ?>', '<?php echo $admin['Role']; ?>')">
<i class="bi bi-pencil"></i>
</button>
<button type="button" class="admin-action-btn reset-action" data-color="orange" title="Réinitialiser le mot de passe"
                        onclick="openResetPasswordModal(<?php echo $admin['AdminID']; ?>, '<?php echo htmlspecialchars(addslashes($admin['Email'])); ?>')">
<i class="bi bi-key"></i>
</button>
<?php if ($admin['AdminID'] != ($_SESSION['admin_id'] ?? 0)): ?>
<button type="button" class="admin-action-btn delete-action" data-color="red" title="Supprimer"
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
<div class="modal-body">
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
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('addAdminModal')">Annuler</button>
<button type="submit" class="btn btn-primary">Ajouter</button>
</div>
</form>
</div>
</div>
</div>
<!-- Modal Modification Administrateur -->
<div id="editAdminModal" class="modal">
<div class="modal-content">
<span class="close-modal" onclick="closeModal('editAdminModal')">&times;</span>
<div class="modal-header">
<h3 class="modal-title"><i class="bi bi-pencil"></i> Modifier un administrateur</h3>
</div>
<div class="modal-body">
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
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('editAdminModal')">Annuler</button>
<button type="submit" class="btn btn-primary">Modifier</button>
</div>
</form>
</div>
</div>
</div>
<!-- Modal Réinitialisation Mot de passe -->
<div id="resetPasswordModal" class="modal">
<div class="modal-content">
<span class="close-modal" onclick="closeModal('resetPasswordModal')">&times;</span>
<div class="modal-header">
<h3 class="modal-title"><i class="bi bi-key"></i> Réinitialiser le mot de passe</h3>
</div>
<div class="modal-body">
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
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">Annuler</button>
<button type="submit" class="btn btn-primary">Réinitialiser</button>
</div>
</form>
</div>
</div>
</div>
<!-- Modal Suppression Administrateur -->
<div id="deleteAdminModal" class="modal">
<div class="modal-content">
<span class="close-modal" onclick="closeModal('deleteAdminModal')">&times;</span>
<div class="modal-header">
<h3 class="modal-title"><i class="bi bi-trash"></i> Supprimer un administrateur</h3>
</div>
<div class="modal-body">
<p>Êtes-vous sûr de vouloir supprimer l'administrateur <strong id="delete_admin_email"></strong> ?</p>
<p class="text-danger">Cette action est irréversible.</p>
<form method="POST" action="administrateurs.php" id="deleteAdminForm">
<input type="hidden" name="action" value="supprimer">
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
<input type="hidden" name="admin_id" id="delete_admin_id">
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('deleteAdminModal')">Annuler</button>
<button type="submit" class="btn btn-danger">Supprimer</button>
</div>
</form>
</div>
</div>
</div>
<!-- Champ caché pour stocker l'ID de l'admin actuel -->
<input type="hidden" id="current_admin_id" value="<?php echo isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0; ?>">
<?php require_once 'footer_template.php'; ?>