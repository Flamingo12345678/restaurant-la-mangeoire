<?php
/**
 * Vérification d'accès administrateur
 * Ce fichier vérifie que l'utilisateur est bien connecté et autorisé à accéder à l'interface d'administration
 */

// Éviter l'inclusion directe
if (basename($_SERVER['PHP_SELF']) === 'check_admin_access.php') {
    http_response_code(403);
    die('Accès direct non autorisé');
}

// Inclure les utilitaires de sécurité si pas déjà fait
if (!function_exists('has_admin_access')) {
    require_once __DIR__ . '/includes/security_utils.php';
}

// Inclure la connexion à la base de données si pas déjà fait
if (!isset($pdo)) {
    require_once __DIR__ . '/../db_connexion.php';
}

/**
 * Vérifie l'accès admin et redirige si nécessaire
 * @param bool $require_admin_only Si true, seuls les admins sont autorisés (pas les employés)
 * @param string $redirect_url URL de redirection personnalisée
 */
function check_admin_access($require_admin_only = false, $redirect_url = null) {
    // Démarrer la session si nécessaire
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vérifier si l'utilisateur est connecté et a les droits appropriés
    if ($require_admin_only) {
        if (!is_admin()) {
            // Seuls les admins sont autorisés
            if (!$redirect_url) {
                $redirect_url = get_admin_login_url() . '?admin=1&error=admin_required';
            }
            
            // Enregistrer la page demandée pour redirection après connexion (seulement si REQUEST_URI existe)
            if (isset($_SERVER['REQUEST_URI'])) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            
            log_unauthorized_access("Tentative d'accès admin sans droits suffisants", __FILE__);
            header("Location: $redirect_url");
            exit;
        }
    } else {
        if (!has_admin_access()) {
            // Ni admin ni employé
            if (!$redirect_url) {
                $redirect_url = get_admin_login_url() . '?admin=1&error=access_required';
            }
            
            // Enregistrer la page demandée pour redirection après connexion (seulement si REQUEST_URI existe)
            if (isset($_SERVER['REQUEST_URI'])) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            
            log_unauthorized_access("Tentative d'accès admin sans authentification", __FILE__);
            header("Location: $redirect_url");
            exit;
        }
    }
    
    // Vérifier la validité de la session
    validate_session();
    
    // Mettre à jour l'heure de dernière activité
    $_SESSION['last_activity'] = time();
    
    // Régénérer l'ID de session périodiquement
    if (!isset($_SESSION['last_regenerate'])) {
        $_SESSION['last_regenerate'] = time();
    } elseif (time() - $_SESSION['last_regenerate'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regenerate'] = time();
    }
}

/**
 * Obtient les informations de l'utilisateur connecté
 * @return array|null
 */
function get_current_admin_user() {
    if (is_admin()) {
        return [
            'id' => $_SESSION['admin_id'],
            'nom' => $_SESSION['admin_nom'],
            'prenom' => $_SESSION['admin_prenom'],
            'email' => $_SESSION['admin_email'],
            'type' => 'admin'
        ];
    } elseif (is_employee()) {
        return [
            'id' => $_SESSION['employe_id'],
            'nom' => $_SESSION['employe_nom'] ?? '',
            'prenom' => $_SESSION['employe_prenom'] ?? '',
            'email' => $_SESSION['employe_email'] ?? '',
            'type' => 'employe'
        ];
    }
    
    return null;
}

/**
 * Vérifie si l'utilisateur a l'autorisation pour une action spécifique
 * @param string $action L'action à vérifier (ex: 'manage_users', 'view_reports', etc.)
 * @return bool
 */
function has_permission($action) {
    $user = get_current_admin_user();
    
    if (!$user) {
        return false;
    }
    
    // Les admins ont toutes les permissions
    if ($user['type'] === 'admin') {
        return true;
    }
    
    // Pour les employés, définir des permissions spécifiques
    // Cette logique peut être étendue selon les besoins
    $employee_permissions = [
        'view_orders' => true,
        'manage_orders' => true,
        'view_reservations' => true,
        'manage_reservations' => true,
        'view_clients' => true,
        'view_menus' => true,
        'view_reports' => false,  // Seuls les admins peuvent voir les rapports
        'manage_users' => false,  // Seuls les admins peuvent gérer les utilisateurs
        'manage_payments' => false, // Seuls les admins peuvent gérer les paiements
    ];
    
    return isset($employee_permissions[$action]) ? $employee_permissions[$action] : false;
}

// Exécution automatique de la vérification d'accès
// Peut être désactivée en définissant NO_AUTO_ACCESS_CHECK avant l'inclusion
// Ne s'exécute pas si appelé depuis header_template.php (car la vérification a déjà été faite)
if (!defined('NO_AUTO_ACCESS_CHECK') && !defined('INCLUDED_IN_PAGE')) {
    check_admin_access();
}
?>
