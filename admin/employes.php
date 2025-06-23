<?php
/**
 * Point d'entrée sécurisé pour la gestion des employés
 */

// Activer la mise en tampon de sortie pour éviter les erreurs "headers already sent"
if (ob_get_level() == 0) {
    ob_start();
}

// Inclure la configuration de session avant toute chose
require_once __DIR__ . '/../includes/session_config.php';

// S'assurer que la session est active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Définir la constante de sécurité pour gestion_employes.php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Définir la constante pour header_template.php
if (!defined('INCLUDED_IN_PAGE')) {
    define('INCLUDED_IN_PAGE', true);
}

// Inclure les fichiers de sécurité et d'authentification
require_once 'check_admin_access.php';
require_once __DIR__ . '/../includes/common.php';

// Vérifier que l'utilisateur est bien un administrateur
require_admin();

// Générer un token CSRF pour les formulaires
generate_csrf_token();

// Inclusion de la connexion à la base de données
require_once __DIR__ . '/../db_connexion.php';

// Récupérer les informations de l'administrateur actuel
try {
    $query = "SELECT Role FROM Administrateurs WHERE AdminID = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Stocker le rôle pour l'utiliser dans l'interface
    $admin_role = $admin ? $admin['Role'] : 'admin';
} catch (PDOException $e) {
    die("Erreur lors de la récupération du rôle administrateur: " . $e->getMessage());
}

// Traitement de la création, modification ou suppression d'employés
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Vérification du jeton CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité : jeton CSRF invalide.";
        } else {
            // Création d'un nouvel employé
            if ($_POST['action'] === 'create') {
                $nom = trim($_POST['nom']);
                $prenom = trim($_POST['prenom']);
                $email = trim($_POST['email']);
                $telephone = trim($_POST['telephone'] ?? '');
                $poste = trim($_POST['poste']);
                $date_embauche = $_POST['date_embauche'];
                $salaire = isset($_POST['salaire']) ? floatval($_POST['salaire']) : 0;
                $password = trim($_POST['password']);
                $status = isset($_POST['status']) ? 'Actif' : 'Inactif';

                // Validation des données
                if (empty($nom) || empty($prenom) || empty($poste) || empty($date_embauche) || empty($password)) {
                    $error = "Les champs nom, prénom, poste, date d'embauche et mot de passe sont obligatoires.";
                } else {
                    try {
                        // Hachage du mot de passe
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Requête d'insertion
                        $query = "INSERT INTO Employes (Nom, Prenom, Email, Telephone, Poste, DateEmbauche, Salaire, MotDePasse, Status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$nom, $prenom, $email, $telephone, $poste, $date_embauche, $salaire, $password_hash, $status]);
                        
                        $message = "L'employé a été ajouté avec succès.";
                    } catch (PDOException $e) {
                        $error = "Erreur lors de l'ajout de l'employé : " . $e->getMessage();
                    }
                }
            }
            
            // Mise à jour d'un employé
            else if ($_POST['action'] === 'update' && isset($_POST['id'])) {
                $id = $_POST['id'];
                $nom = trim($_POST['nom']);
                $prenom = trim($_POST['prenom']);
                $email = trim($_POST['email']);
                $telephone = trim($_POST['telephone'] ?? '');
                $poste = trim($_POST['poste']);
                $date_embauche = $_POST['date_embauche'];
                $salaire = isset($_POST['salaire']) ? floatval($_POST['salaire']) : 0;
                $password = trim($_POST['password'] ?? '');
                $status = isset($_POST['status']) ? 'Actif' : 'Inactif';

                // Validation des données
                if (empty($nom) || empty($prenom) || empty($poste) || empty($date_embauche)) {
                    $error = "Les champs nom, prénom, poste et date d'embauche sont obligatoires.";
                } else {
                    try {
                        // Préparer la requête de mise à jour
                        if (!empty($password)) {
                            // Si un nouveau mot de passe est fourni, le hacher et l'inclure dans la mise à jour
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $query = "UPDATE Employes SET Nom = ?, Prenom = ?, Email = ?, Telephone = ?, Poste = ?, 
                                    DateEmbauche = ?, Salaire = ?, MotDePasse = ?, Status = ? WHERE EmployeID = ?";
                            $params = [$nom, $prenom, $email, $telephone, $poste, $date_embauche, $salaire, $password_hash, $status, $id];
                        } else {
                            // Si aucun mot de passe n'est fourni, ne pas mettre à jour ce champ
                            $query = "UPDATE Employes SET Nom = ?, Prenom = ?, Email = ?, Telephone = ?, Poste = ?, 
                                    DateEmbauche = ?, Salaire = ?, Status = ? WHERE EmployeID = ?";
                            $params = [$nom, $prenom, $email, $telephone, $poste, $date_embauche, $salaire, $status, $id];
                        }
                        
                        // Exécuter la requête
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        
                        $message = "L'employé a été mis à jour avec succès.";
                    } catch (PDOException $e) {
                        $error = "Erreur lors de la mise à jour de l'employé : " . $e->getMessage();
                    }
                }
            }
            
            // Suppression d'un employé
            else if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
                $id = $_POST['id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM Employes WHERE EmployeID = ?");
                    $stmt->execute([$id]);
                    
                    $message = "L'employé a été supprimé avec succès.";
                } catch (PDOException $e) {
                    $error = "Erreur lors de la suppression de l'employé : " . $e->getMessage();
                }
            }
        }
    }
}

// Pagination
$employes_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $employes_per_page;
$total_employes = $pdo->query("SELECT COUNT(*) FROM Employes")->fetchColumn();
$total_pages = ceil($total_employes / $employes_per_page);

// Récupérer les employés pour la pagination
$query = "SELECT * FROM Employes ORDER BY Nom ASC, Prenom ASC LIMIT $employes_per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute();
$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Employes - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="../assets/css/employes-table.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .content-wrapper {
      padding: 30px;
      background-color: #fff;
      min-height: calc(100vh - 60px);
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    /* Supprimer complètement le modal qui apparaît par erreur */
    body > div[id="Confirmer la suppression"],
    body > div.modal.fade,
    body > div.modal-backdrop {
      display: none !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
      z-index: -9999 !important;
    }
    
    /* Nouvelle interface style */
    h1 {
      font-size: 24px;
      margin-bottom: 30px;
      color: #212529;
      font-weight: 600;
    }
    
    h2 {
      font-size: 20px;
      margin-bottom: 20px;
      color: #495057;
      font-weight: 500;
    }
    
    .container {
      max-width: 100%;
      padding: 20px;
      background-color: #f8f9fa;
      border-radius: 8px;
    }
    
    .add-section {
      margin-bottom: 40px;
    }
    
    .form-container {
      display: flex;
      margin-top: 20px;
    }
    
    .content-message {
      padding: 30px;
      background-color: #f9f9f9;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      text-align: center;
    }
    
    .info-panel {
      padding: 20px;
      background-color: #ffebeb;
      border-left: 4px solid #f03e3e;
      border-radius: 4px;
      color: #333;
      text-align: left;
    }
    
    .info-panel p {
      margin: 10px 0;
    }
    
    .info-panel i {
      color: #f03e3e;
      margin-right: 8px;
    }
    
    .add-button {
      display: inline-flex;
      align-items: center;
      background-color: #28a745;
      color: white;
      padding: 10px 16px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .add-button:hover {
      background-color: #218838;
      color: white;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
    }
    
    /* Nouveau style pour le bouton Mettre à jour qui correspond à la capture d'écran */
    .refresh-button {
      display: inline-flex;
      align-items: center;
      background-color: #6c757d;
      color: white;
      padding: 10px 16px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
    }
    
    .add-button i {
      margin-right: 8px;
    }
    
    .update-button {
      display: inline-flex;
      align-items: center;
      background-color: #6c757d;
      color: white;
      padding: 8px 14px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
      margin-right: 10px;
      font-size: 0.9rem;
    }
    
    .update-button:hover {
      background-color: #5a6268;
      color: white;
    }
    
    .update-button i {
      margin-right: 8px;
    }
    
    .table-container {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      margin-bottom: 30px;
    }
    
    .data-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .data-table th {
      background-color: #fff1f1;
      color: #495057;
      font-weight: 500;
      text-align: left;
      padding: 15px 10px;
      font-size: 0.7rem;
      letter-spacing: 0.5px;
      border-bottom: 1px solid #e9ecef;
      text-transform: uppercase;
    }
    
    .data-table td {
      padding: 15px 10px;
      border-bottom: 1px solid #f0f0f0;
      vertical-align: middle;
      color: #495057;
      font-size: 0.85rem;
    }
    
    .data-table tr:hover {
      background-color: #f8f9fa;
    }
    
    .data-table tr:last-child td {
      border-bottom: none;
    }
    
    /* Style de la table qui correspond exactement à la capture d'écran */
    .data-table tr:nth-child(odd) {
      background-color: #fff;
    }
    
    .data-table tr:nth-child(even) {
      background-color: #fff8f8;
    }
    
    .actions-cell {
      white-space: nowrap;
    }
    
    .edit-btn {
      color: #1d6adb;
      margin-right: 15px;
      text-decoration: none;
      padding: 5px;
      border-radius: 3px;
      transition: background-color 0.2s;
    }
    
    .edit-btn:hover {
      background-color: rgba(29, 106, 219, 0.1);
    }
    
    .delete-btn {
      color: #b01e28;
      background: none;
      border: none;
      cursor: pointer;
      padding: 5px;
      border-radius: 3px;
      transition: background-color 0.2s;
    }
    
    .delete-btn:hover {
      background-color: rgba(176, 30, 40, 0.1);
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 30px;
      font-size: 0.85em;
      font-weight: 500;
      text-align: center;
    }
    
    .status-badge.active {
      background-color: #ffe6e6;
      color: #ff4d4d;
      padding: 5px 12px;
      font-size: 0.7rem;
      border-radius: 50px;
    }
    
    .status-badge.inactive {
      background-color: #f8d7da;
      color: #dc3545;
      font-size: 0.7rem;
      border-radius: 50px;
      padding: 5px 12px;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      padding: 15px 0;
    }
    
    .page-link {
      padding: 8px 12px;
      margin: 0 5px;
      border-radius: 4px;
      color: #495057;
      text-decoration: none;
      border: 1px solid #dee2e6;
      transition: background-color 0.2s, color 0.2s;
    }
    
    .page-link:hover {
      background-color: #e9ecef;
    }
    
    .current-page {
      padding: 8px 12px;
      margin: 0 5px;
      background-color: #28a745;
      color: white;
      border-radius: 4px;
      border: 1px solid #28a745;
    }
    
    /* Styles spécifiques pour la page employés sur mobile */
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .form-group {
        margin-bottom: 15px;
      }

      .table-responsive-wrapper {
        margin: 0 -15px;
        width: calc(100% + 30px);
        border-radius: 0;
      }

      .admin-table th:nth-child(1),
      .admin-table td:nth-child(1),
      .admin-table th:nth-child(6),
      .admin-table td:nth-child(6) {
        display: none;
      }

      .admin-table th,
      .admin-table td {
        padding: 10px 8px;
        font-size: 0.9rem;
      }

      .pagination {
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 20px;
      }

      .pagination a,
      .pagination strong {
        margin: 3px;
      }
    }

    @media (max-width: 480px) {

      .admin-table th:nth-child(4),
      .admin-table td:nth-child(4) {
        display: none;
      }

      .admin-table th:nth-child(5),
      .admin-table td:nth-child(5) {
        max-width: 80px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
  </style>
</head>

<body>
  <?php
  // Définir le titre de la page
  $page_title = "Gestion des employés";

  // Utiliser le header_template (INCLUDED_IN_PAGE est déjà défini en haut du fichier)
  include 'header_template.php';
  ?>

  <!-- Contenu spécifique de la page -->    
  <div class="content-wrapper">
    <h1>Gestion des employés</h1>
    <div class="breadcrumb">
      <a href="index.php">Tableau de bord</a> &gt; Gestion des employés
    </div>

    <?php
    // Affichage des messages flash
    display_message();

    // Affichage des messages d'erreur internes
    if (!empty($error)) {
      echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle'></i> " . htmlspecialchars($error) . "</div>";
    }
    
    // Affichage des messages de succès internes
    if (!empty($message)) {
      echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> " . htmlspecialchars($message) . "</div>";
    }
    ?>

    <!-- Section principale -->
    <div class="container">
      <div class="add-section">
        <div class="section-header">
          <h2>Liste des employés</h2>
          <div>
            <a href="update_employes_table.php" class="refresh-button" title="Mettre à jour la structure de la table"><i class="bi bi-arrow-repeat"></i> Mettre à jour</a>
            <button type="button" class="add-button" id="showAddEmployeForm"><i class="bi bi-plus-circle"></i> Ajouter un employé</button>
          </div>
        </div>
      </div>
      
      <!-- Formulaire d'ajout d'employé (caché par défaut) -->
      <div id="addEmployeForm" class="admin-hidden-form">
        <div class="admin-form-container">
          <h3 class="admin-form-title">Ajouter un nouvel employé</h3>
          <form method="post" action="" class="employe-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
              <div class="form-group">
                <label for="nom">Nom <span style="color: red;">*</span></label>
                <input type="text" name="nom" id="nom" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="prenom">Prénom <span style="color: red;">*</span></label>
                <input type="text" name="prenom" id="prenom" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control">
              </div>
              <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" name="telephone" id="telephone" class="form-control">
              </div>
              <div class="form-group">
                <label for="poste">Poste <span style="color: red;">*</span></label>
                <input type="text" name="poste" id="poste" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="date_embauche">Date d'embauche <span style="color: red;">*</span></label>
                <input type="date" name="date_embauche" id="date_embauche" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="salaire">Salaire <span style="color: red;">*</span></label>
                <input type="number" name="salaire" id="salaire" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="password">Mot de passe <span style="color: red;">*</span></label>
                <input type="password" name="password" id="password" class="form-control" required>
              </div>
              <div class="form-group" style="display: flex; align-items: center;">
                <input type="checkbox" name="status" id="status" checked> 
                <label for="status" style="margin-left: 10px;">Actif</label>
              </div>
            </div>
            <div style="text-align: right; margin-top: 20px;">
              <button type="button" id="cancelAddEmploye" class="btn btn-secondary" style="background-color: #6c757d; color: white; padding: 8px 12px; border: none; border-radius: 4px; margin-right: 10px; cursor: pointer;">Annuler</button>
              <button type="submit" class="btn btn-primary" style="background-color: #28a745; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer;">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>NOM</th>
              <th>PRÉNOM</th>
              <th>EMAIL</th>
              <th>TÉLÉPHONE</th>
              <th>POSTE</th>
              <th>SALAIRE</th>
              <th>DATE EMBAUCHE</th>
              <th>DERNIÈRE CONNEXION</th>
              <th>STATUT</th>
              <th style="text-align: right; padding-right: 20px;">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employes as $e): ?>
            <tr>
              <td><?= htmlspecialchars($e['EmployeID']) ?></td>
              <td><?= htmlspecialchars($e['Nom']) ?></td>
              <td><?= htmlspecialchars($e['Prenom']) ?></td>
              <td><?= isset($e['Email']) ? htmlspecialchars($e['Email']) : '—' ?></td>
              <td><?= isset($e['Telephone']) ? htmlspecialchars($e['Telephone']) : '—' ?></td>
              <td><?= htmlspecialchars($e['Poste']) ?></td>
              <td><?= number_format(htmlspecialchars($e['Salaire']), 0, '', ' ') ?> XAF</td>
              <td><?= date('d/m/Y', strtotime($e['DateEmbauche'])) ?></td>
              <td><?= isset($e['DerniereConnexion']) && $e['DerniereConnexion'] ? date('d/m/Y H:i', strtotime($e['DerniereConnexion'])) : '—' ?></td>
              <td><span class="status-badge <?= isset($e['Status']) && $e['Status'] == 'Actif' ? 'active' : 'inactive' ?>"><?= isset($e['Status']) ? htmlspecialchars($e['Status']) : 'Actif' ?></span></td>
              <td class="actions-cell">
                <a href="modifier_employe.php?id=<?= $e['EmployeID'] ?>" class="edit-btn" title="Modifier"><i class="bi bi-pencil"></i></a>
                <form method="post" action="" class="delete-form" style="display: inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="id" value="<?= $e['EmployeID'] ?>">
                  <button type="submit" class="delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')" title="Supprimer"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page-link">&laquo; Précédent</a>
          <?php endif; ?>

          <?php
          // Afficher max 5 pages avec la page actuelle au centre si possible
          $start_page = max(1, min($page - 2, $total_pages - 4));
          $end_page = min($total_pages, max(5, $page + 2));
          $start_page = max(1, min($start_page, $total_pages - ($end_page - $start_page)));

          for ($i = $start_page; $i <= $end_page; $i++):
          ?>
            <?php if ($i == $page): ?>
              <strong class="current-page"><?= $i ?></strong>
            <?php else: ?>
              <a href="?page=<?= $i ?>" class="page-link"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="page-link">Suivant &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      </div>
    </div>
  </div>

  <?php
  include 'footer_template.php';
  ?>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Gestion du formulaire d'ajout d'employé
      const showAddButton = document.getElementById('showAddEmployeForm');
      const addForm = document.getElementById('addEmployeForm');
      const cancelButton = document.getElementById('cancelAddEmploye');
      
      if (showAddButton && addForm && cancelButton) {
        showAddButton.addEventListener('click', function() {
          addForm.style.display = 'block';
          addForm.classList.add('show');
        });
        
        cancelButton.addEventListener('click', function() {
          addForm.style.display = 'none';
        });
      }
      
      // Ajouter une animation aux badges de statut
      const statusBadges = document.querySelectorAll('.status-badge');
      statusBadges.forEach(function(badge) {
        badge.style.transition = 'transform 0.2s ease';
        badge.addEventListener('mouseover', function() {
          this.style.transform = 'scale(1.05)';
        });
        badge.addEventListener('mouseout', function() {
          this.style.transform = 'scale(1)';
        });
      });
      
      // Ajouter une confirmation avant la suppression d'un employé
      const deleteForms = document.querySelectorAll('.delete-form');
      deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
          if (!confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')) {
            e.preventDefault();
            return false;
          }
        });
      });
    });
  </script>
    <script src="../assets/js/admin-sidebar.js"></script>
</body>

</html>