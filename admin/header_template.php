<?php

require_once 'check_admin_access.php';
// Ce fichier contient le header commun pour toutes les pages admin
// Il doit être inclus au début de chaque page après l'ouverture des balises <body>

// Protection contre l'inclusion directe
if (!defined('INCLUDED_IN_PAGE')) {
  die('Ce fichier ne peut pas être appelé directement.');
}

// Ajouter les feuilles de style globales pour l'interface d'administration
echo '<link rel="stylesheet" href="../assets/css/admin.css">';
echo '<link rel="stylesheet" href="../assets/css/admin-unified.css">';
echo '<link rel="stylesheet" href="../assets/css/admin-animations.css">';
echo '<link rel="stylesheet" href="../assets/css/admin-inline-fixes.css">';
echo '<link rel="stylesheet" href="../assets/css/employes-admin.css">';

// Définir les scripts JavaScript communs à toutes les pages admin
$common_scripts = array(
  '../assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
  '../assets/js/main.js',
  '../assets/js/admin-modals.js',
  '../assets/js/harmonize-admin-styles.js'
);
?>
<!-- Bouton burger avec animation -->
<button id="admin-burger-btn" class="admin-burger-btn" aria-label="Ouvrir le menu"><i class="bi bi-list"></i></button>

<!-- Sidebar avec un design modernisé -->
<div id="admin-sidebar" class="admin-sidebar">
  <div class="logo">La Mangeoire</div>
  <nav>
    <ul>
      <li><a href="index.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'index.php') ? 'class="active"' : ''; ?>><i class="bi bi-house"></i> Tableau de bord</a></li>
      <li><a href="clients.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'clients.php') ? 'class="active"' : ''; ?>><i class="bi bi-people"></i> Clients</a></li>
      <li><a href="commandes.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'commandes.php') ? 'class="active"' : ''; ?>><i class="bi bi-basket"></i> Commandes</a></li>
      <li><a href="menus.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'menus.php') ? 'class="active"' : ''; ?>><i class="bi bi-book"></i> Menus</a></li>
      <li><a href="reservations.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'reservations.php') ? 'class="active"' : ''; ?>><i class="bi bi-calendar-check"></i> Réservations</a></li>
      <li><a href="tables.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'tables.php') ? 'class="active"' : ''; ?>><i class="bi bi-table"></i> Tables</a></li>
      <li><a href="employes.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'gestion_employes.php' || basename($_SERVER['SCRIPT_NAME']) == 'employes.php' || basename($_SERVER['SCRIPT_NAME']) == 'acces_employes_direct.php' || basename($_SERVER['SCRIPT_NAME']) == 'modifier_employe.php') ? 'class="active"' : ''; ?>><i class="bi bi-person-badge"></i> Employés</a></li>
      <li><a href="paiements.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'paiements.php') ? 'class="active"' : ''; ?>><i class="bi bi-credit-card"></i> Paiements</a></li>
      
      <!-- Section Administration, visible uniquement pour les superadmins -->
      <?php
      // Vérifier le rôle depuis la session
      $is_superadmin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
      
      if ($is_superadmin): 
      ?>
      <li class="nav-section"><span>Administration</span></li>
      <li><a href="administrateurs.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'administrateurs.php') ? 'class="active"' : ''; ?>><i class="bi bi-shield-lock"></i> Administrateurs</a></li>
      <li><a href="activity_log.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'activity_log.php') ? 'class="active"' : ''; ?>><i class="bi bi-journal-text"></i> Logs d'activité</a></li>
      <?php endif; ?>
      
      <li class="nav-section"><span>Navigation</span></li>
      <li><a href="../index.html"><i class="bi bi-arrow-left-circle"></i> Retour au site</a></li>
      <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
    </ul>
  </nav>
</div>

<!-- Overlay pour mobile -->
<div id="admin-sidebar-overlay"></div>

<!-- Contenu principal -->
<div class="admin-main-content">
  <header class="admin-header">
    <div class="admin-header-center">
      <h1 class="sitename"><?php echo isset($page_title) ? $page_title : 'Administration'; ?></h1>
    </div>
    <div class="admin-header-right">
      <img src="../assets/img/favcon.jpeg" alt="Logo" class="admin-logo">
    </div>
  </header>

  <!-- Le contenu spécifique de la page sera inclus ici -->