<?php

require_once 'check_admin_access.php';
// Ce fichier contient le header commun et COMPLET pour toutes les pages admin
// Il gère automatiquement la structure HTML responsive

// Protection contre l'inclusion directe
if (!defined('INCLUDED_IN_PAGE')) {
  die('Ce fichier ne peut pas être appelé directement.');
}

// Détecter le niveau de répertoire pour les chemins relatifs
$is_in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
$asset_path = $is_in_admin_folder ? '../assets/' : 'assets/';

// Définir le titre par défaut si non défini
if (!isset($page_title)) {
    $page_title = "Administration";
}

// Vérifier si nous devons inclure la structure HTML complète
$need_html_structure = !headers_sent() && !ob_get_level();

if ($need_html_structure) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($page_title); ?> - La Mangeoire Administration</title>
    
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
<?php } ?>

<!-- CSS Admin de base - toujours inclus -->
<link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin.css">
<link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-sidebar.css">
<link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-responsive.css">
<link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-animations.css">
<link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-inline-fixes.css">

<?php
// Inclure CSS additionnels spécifiques à la page si définis
if (isset($additional_css) && is_array($additional_css)) {
    foreach ($additional_css as $css_file) {
        $clean_css_path = str_replace('assets/', '', $css_file);
        echo '<link rel="stylesheet" href="' . $asset_path . $clean_css_path . '">' . "\n";
    }
}

if ($need_html_structure) {
?>
    
    <!-- Optimisations critiques pour mobile -->
    <style>
        body {
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            font-size: 16px;
        }
        
        /* Stats cards TOUJOURS en ligne horizontale */
        .admin-messages .row.g-4, .stats-row {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 0.5rem;
        }
        
        .admin-messages .row.g-4 > .col-md-3, .stats-card {
            flex: 1 1 25% !important;
            min-width: 0 !important;
            max-width: 25% !important;
        }
        input, select, textarea { font-size: 16px !important; }
        
        @media (pointer: coarse) {
            .btn, .form-control, .form-select { min-height: 44px; }
        }
    </style>
    
    <?php
    // Inclure scripts JavaScript spécifiques dans le head si définis
    if (isset($head_js) && is_array($head_js)) {
        foreach ($head_js as $js_file) {
            echo '<script src="' . $js_file . '"></script>' . "\n    ";
        }
    }
    
    // Inclure CSS spécifiques à la page si définis
    if (isset($additional_css) && is_array($additional_css)) {
        foreach ($additional_css as $css_file) {
            echo '<link rel="stylesheet" href="' . $css_file . '">' . "\n    ";
        }
    }
    ?>
</head>
<body>
<?php } ?>
?>
<!-- Bouton burger avec animation -->
<button id="admin-burger-btn" class="admin-burger-btn" aria-label="Ouvrir le menu"><i class="bi bi-list"></i></button>

<!-- Sidebar avec un design modernisé -->
<div id="admin-sidebar" class="admin-sidebar">
  <div class="logo">La Mangeoire</div>
  <nav>
    <ul>
      <?php
      // Définir les chemins selon le répertoire courant
      $admin_prefix = $is_in_admin_folder ? '' : 'admin/';
      $root_prefix = $is_in_admin_folder ? '../' : '';
      ?>
      <li><a href="<?php echo $admin_prefix; ?>index.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'index.php') ? 'class="active"' : ''; ?>><i class="bi bi-house"></i> Tableau de bord</a></li>
      <li><a href="<?php echo $admin_prefix; ?>clients.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'clients.php') ? 'class="active"' : ''; ?>><i class="bi bi-people"></i> Clients</a></li>
      <li><a href="<?php echo $admin_prefix; ?>commandes.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'commandes.php') ? 'class="active"' : ''; ?>><i class="bi bi-basket"></i> Commandes</a></li>
      <li><a href="<?php echo $admin_prefix; ?>menus.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'menus.php') ? 'class="active"' : ''; ?>><i class="bi bi-book"></i> Menus</a></li>
      <li><a href="<?php echo $admin_prefix; ?>reservations.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'reservations.php') ? 'class="active"' : ''; ?>><i class="bi bi-calendar-check"></i> Réservations</a></li>
      <li><a href="<?php echo $admin_prefix; ?>tables.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'tables.php') ? 'class="active"' : ''; ?>><i class="bi bi-table"></i> Tables</a></li>
      <li><a href="<?php echo $admin_prefix; ?>employes.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'employes.php') ? 'class="active"' : ''; ?>><i class="bi bi-person-badge"></i> Employés</a></li>
      <li><a href="<?php echo $admin_prefix; ?>paiements.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'paiements.php') ? 'class="active"' : ''; ?>><i class="bi bi-credit-card"></i> Paiements</a></li>
      <li><a href="<?php echo $root_prefix; ?>admin-messages.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'admin-messages.php') ? 'class="active"' : ''; ?>><i class="bi bi-envelope"></i> Messages</a></li>
      
      <!-- Section Administration, visible uniquement pour les superadmins -->
      <?php
      // Vérifier le rôle depuis la session
      $is_superadmin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
      
      if ($is_superadmin): 
      ?>
      <li class="nav-section"><span>Administration</span></li>
      <li><a href="<?php echo $root_prefix; ?>dashboard-admin.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'dashboard-admin.php') ? 'class="active"' : ''; ?>><i class="bi bi-speedometer2"></i> Dashboard Système</a></li>
      <li><a href="<?php echo $admin_prefix; ?>administrateurs.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'administrateurs.php') ? 'class="active"' : ''; ?>><i class="bi bi-shield-lock"></i> Administrateurs</a></li>
      <li><a href="<?php echo $admin_prefix; ?>activity_log.php" <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'activity_log.php') ? 'class="active"' : ''; ?>><i class="bi bi-journal-text"></i> Logs d'activité</a></li>
      <?php endif; ?>
      
      <li class="nav-section"><span>Navigation</span></li>
      <li><a href="<?php echo $root_prefix; ?>index.php"><i class="bi bi-arrow-left-circle"></i> Retour au site</a></li>
      <li><a href="<?php echo $admin_prefix; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
    </ul>
  </nav>
</div>

<!-- Overlay pour mobile -->
<div id="admin-sidebar-overlay" class="admin-sidebar-overlay"></div>

<?php 
// Détecter si c'est une page qui utilise déjà sa propre structure
$current_page = basename($_SERVER['SCRIPT_NAME']);
$pages_with_own_structure = []; // Plus de pages avec structure personnalisée - toutes utilisent le wrapper standard
$use_main_content_wrapper = !in_array($current_page, $pages_with_own_structure);
?>

<?php if ($use_main_content_wrapper): ?>
<!-- Contenu principal avec wrapper -->
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
<?php else: ?>
<!-- Pour les pages avec structure personnalisée, on ne met que le header minimal -->
<script>
// Assurer la cohérence de la sidebar sur toutes les pages
document.addEventListener('DOMContentLoaded', function() {
    // Code pour la cohérence de l'interface
    const burger = document.getElementById('admin-burger-btn');
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');
    
    if (burger && sidebar) {
        // Gestion du menu burger
        burger.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            
            // Changer l'icône du bouton
            const icon = burger.querySelector('i');
            if (icon) {
                icon.className = sidebar.classList.contains('open') ? 'bi bi-x-lg' : 'bi bi-list';
            }
        });
        
        // Fermeture par overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            
            // Remettre l'icône burger
            const icon = burger.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-list';
            }
        });
    }
});
</script>
<?php endif; ?>