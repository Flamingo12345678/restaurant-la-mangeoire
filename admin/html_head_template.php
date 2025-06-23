<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>La Mangeoire - Administration</title>
    
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- CSS Personnalisés -->
    <?php 
    // Détecter le niveau de répertoire pour les chemins relatifs
    $is_in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
    $asset_path = $is_in_admin_folder ? '../assets/' : 'assets/';
    
    // CSS de base
    $css_files = [
        'css/admin.css',
        'css/admin-sidebar.css',
        'css/admin-responsive.css',
        'css/admin-animations.css',
        'css/admin-inline-fixes.css'
    ];
    
    // Ajouter CSS spécifiques selon la page
    if (isset($additional_css) && is_array($additional_css)) {
        $css_files = array_merge($css_files, $additional_css);
    }
    
    // Inclure tous les CSS
    foreach ($css_files as $css_file) {
        echo '<link rel="stylesheet" href="' . $asset_path . $css_file . '">' . "\n    ";
    }
    ?>
    
    <!-- Meta pour optimisation mobile -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Prevent zoom on input focus (iOS) -->
    <style>
        @media screen and (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }
        
        /* Amélioration responsive globale */
        * {
            box-sizing: border-box;
        }
        
        body {
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Responsive containers */
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 5px;
                padding-right: 5px;
            }
        }
        
        /* Responsive text */
        @media (max-width: 768px) {
            h1 { font-size: 1.8rem !important; }
            h2 { font-size: 1.5rem !important; }
            h3 { font-size: 1.3rem !important; }
            h4 { font-size: 1.1rem !important; }
            h5 { font-size: 1rem !important; }
            h6 { font-size: 0.9rem !important; }
        }
        
        @media (max-width: 480px) {
            h1 { font-size: 1.5rem !important; }
            h2 { font-size: 1.3rem !important; }
            h3 { font-size: 1.1rem !important; }
            h4 { font-size: 1rem !important; }
            h5 { font-size: 0.9rem !important; }
            h6 { font-size: 0.8rem !important; }
        }
        
        /* Responsive tables */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table td, .table th {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }
        }
        
        /* Responsive buttons */
        @media (max-width: 768px) {
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .btn-lg {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
        }
        
        /* Responsive modals */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .modal-content {
                border-radius: 0.5rem;
            }
        }
        
        /* Force single row for stats cards */
        .stats-row {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 0.5rem;
        }
        
        .stats-card {
            flex: 1 1 25% !important;
            min-width: 0 !important;
        }
        
        @media (max-width: 768px) {
            .stats-row {
                gap: 0.25rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-row {
                gap: 0.125rem;
            }
        }
    </style>
</head>
<body>
    <?php
    // Protection contre l'inclusion directe
    if (!defined('INCLUDED_IN_PAGE')) {
        die('Ce fichier ne peut pas être appelé directement.');
    }
    ?>
    
    <!-- Contenu principal avec padding responsive -->
    <main class="main-content" style="padding: 1rem;">
        <!-- Inclure sidebar et navigation -->
        <?php
        // Inclure seulement la sidebar, pas le header complet
        require_once 'check_admin_access.php';
        
        // Détecter le niveau de répertoire pour les chemins relatifs
        $is_in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
        $asset_path = $is_in_admin_folder ? '../assets/' : 'assets/';
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
              <li><a href="<?php echo $admin_prefix; ?>index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
              <li><a href="<?php echo $root_prefix; ?>admin-messages.php"><i class="bi bi-chat-dots"></i> Messages</a></li>
              <li><a href="<?php echo $admin_prefix; ?>commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
              <li><a href="<?php echo $admin_prefix; ?>menus.php"><i class="bi bi-menu-button-wide"></i> Menus</a></li>
              <li><a href="<?php echo $admin_prefix; ?>tables.php"><i class="bi bi-table"></i> Tables</a></li>
              <li><a href="<?php echo $root_prefix; ?>employes.php"><i class="bi bi-people"></i> Employés</a></li>
              <li><a href="<?php echo $admin_prefix; ?>administrateurs.php"><i class="bi bi-person-gear"></i> Administrateurs</a></li>
              <li><a href="<?php echo $root_prefix; ?>deconnexion.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
            </ul>
          </nav>
        </div>
        
        <div class="container-fluid">
