<?php
// Ce fichier contient le header HTML complet et responsive pour toutes les pages admin
// Il remplace et améliore l'ancien système de templates

// Protection contre l'inclusion directe
if (!defined('INCLUDED_IN_PAGE')) {
    die('Ce fichier ne peut pas être appelé directement.');
}

require_once 'check_admin_access.php';

// Détecter le niveau de répertoire pour les chemins relatifs
$is_in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
$asset_path = $is_in_admin_folder ? '../assets/' : 'assets/';

// Définir le titre par défaut si non défini
if (!isset($page_title)) {
    $page_title = "Administration";
}
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
    
    <!-- CSS Admin de base -->
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin.css">
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-sidebar.css">
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-responsive.css">
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-animations.css">
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin-inline-fixes.css">
    
    <?php
    // Inclure CSS additionnels spécifiques à la page si définis
    if (isset($additional_css) && is_array($additional_css)) {
        foreach ($additional_css as $css_file) {
            // Nettoyer le chemin du CSS
            $clean_css_path = str_replace('assets/', '', $css_file);
            echo '<link rel="stylesheet" href="' . $asset_path . $clean_css_path . '">' . "\n    ";
        }
    }
    ?>
    
    <!-- Meta pour optimisation mobile -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- CSS inline pour optimisations critiques -->
    <style>
        /* Optimisations critiques pour le rendu */
        body {
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            font-size: 16px; /* Évite le zoom iOS */
        }
        
        /* Force les stats cards en ligne horizontale - CRITIQUE */
        .admin-messages .row.g-4,
        .stats-row {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 0.5rem;
        }
        
        .admin-messages .row.g-4 > .col-md-3,
        .stats-card {
            flex: 1 1 25% !important;
            min-width: 0 !important;
            max-width: 25% !important;
        }
        
        /* Responsive mobile immédiat */
        @media (max-width: 768px) {
            .admin-messages .row.g-4,
            .stats-row {
                gap: 0.25rem;
            }
            
            .admin-messages .stats-card {
                padding: 0.75rem 0.25rem !important;
                min-height: 120px !important;
            }
            
            .admin-messages .stats-card .display-4 {
                font-size: 1.5rem !important;
            }
            
            .admin-messages .stats-card h3 {
                font-size: 1rem !important;
            }
            
            .admin-messages .stats-card p {
                font-size: 0.75rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .admin-messages .row.g-4,
            .stats-row {
                gap: 0.125rem;
            }
            
            .admin-messages .stats-card {
                padding: 0.5rem 0.125rem !important;
                min-height: 100px !important;
            }
            
            .admin-messages .stats-card .display-4 {
                font-size: 1.2rem !important;
            }
            
            .admin-messages .stats-card h3 {
                font-size: 0.85rem !important;
            }
            
            .admin-messages .stats-card p {
                font-size: 0.65rem !important;
            }
        }
        
        /* Prévenir le zoom sur les inputs */
        input, select, textarea {
            font-size: 16px !important;
        }
        
        /* Optimisation tactile */
        @media (pointer: coarse) {
            .btn, .form-control, .form-select {
                min-height: 44px;
            }
        }
    </style>
</head>
<body>
    <!-- Bouton burger avec animation -->
    <button id="admin-burger-btn" class="admin-burger-btn" aria-label="Ouvrir le menu">
        <i class="bi bi-list"></i>
    </button>

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

    <!-- Contenu principal avec adaptation responsive -->
    <main class="admin-main-content" style="margin-left: 0; padding: 1rem; transition: margin-left 0.3s ease;">
        <div class="container-fluid">
            <?php
            // Définir les scripts JavaScript communs à toutes les pages admin
            $common_scripts = array(
                $asset_path . 'vendor/bootstrap/js/bootstrap.bundle.min.js',
                $asset_path . 'js/main.js',
                $asset_path . 'js/admin-sidebar.js',
                $asset_path . 'js/admin-modals.js',
                $asset_path . 'js/harmonize-admin-styles.js'
            );
            ?>
            
            <!-- Ajuster la marge pour la sidebar sur desktop -->
            <style>
                @media (min-width: 992px) {
                    .admin-main-content {
                        margin-left: 250px !important;
                    }
                }
                
                @media (max-width: 991px) {
                    .admin-main-content {
                        margin-left: 0 !important;
                        padding: 0.75rem !important;
                    }
                }
            </style>
