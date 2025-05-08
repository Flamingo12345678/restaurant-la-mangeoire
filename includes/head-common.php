<?php
/**
 * Fichier commun pour les éléments à inclure dans la balise <head> des pages
 * Ce fichier ajoute les styles et scripts nécessaires au système de gestion des cookies
 */

// Ajouter les styles CSS pour le système de gestion des cookies
function add_cookie_consent_head_elements() {
    ob_start();
    ?>
    <!-- Styles pour le système de gestion des cookies -->
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <?php
    return ob_get_clean();
}

// Ajouter les scripts JS pour le système de gestion des cookies (à placer avant la fermeture de body)
function add_cookie_consent_scripts() {
    ob_start();
    ?>
    <!-- Scripts pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
    <?php
    // Inclure les scripts de tracking si le consentement a été donné
    if (function_exists('include_tracking_scripts')) {
        echo include_tracking_scripts();
    }
    return ob_get_clean();
}

// Récupérer le chemin des assets par rapport au dossier actuel
function get_assets_path() {
    $script_path = $_SERVER['SCRIPT_FILENAME'];
    $base_path = realpath(__DIR__ . '/..');
    $depth = substr_count(str_replace($base_path, '', $script_path), '/');
    
    $prefix = '';
    for ($i = 0; $i < $depth; $i++) {
        $prefix .= '../';
    }
    
    return $prefix;
}

// Ajouter les styles CSS avec le bon chemin relatif
function add_cookie_consent_styles_with_path() {
    $path = get_assets_path();
    ob_start();
    ?>
    <!-- Styles pour le système de gestion des cookies -->
    <link rel="stylesheet" href="<?php echo $path; ?>assets/css/cookie-consent.css">
    <?php
    return ob_get_clean();
}

// Ajouter les scripts JS avec le bon chemin relatif
function add_cookie_consent_scripts_with_path() {
    $path = get_assets_path();
    ob_start();
    ?>
    <!-- Scripts pour le système de gestion des cookies -->
    <script src="<?php echo $path; ?>assets/js/cookie-consent.js"></script>
    <?php
    // Inclure les scripts de tracking si le consentement a été donné
    if (function_exists('include_tracking_scripts')) {
        echo include_tracking_scripts();
    }
    return ob_get_clean();
}
?>
