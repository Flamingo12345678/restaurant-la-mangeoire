<?php
/**
 * Utilitaires de sécurité pour la partie administration
 * Ce fichier contient les fonctions et vérifications de sécurité pour l'accès admin
 */

// Éviter l'inclusion directe
if (!defined('SECURE_ACCESS') && basename($_SERVER['PHP_SELF']) === 'security_utils.php') {
    http_response_code(403);
    die('Accès direct non autorisé');
}

// Configuration de session sécurisée
require_once __DIR__ . '/../../includes/session_config.php';

// Inclure les fonctions communes pour avoir accès aux fonctions de validation
require_once __DIR__ . '/../../includes/common.php';

/**
 * Vérifie si l'utilisateur actuel est un administrateur
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['admin_id']) && 
           isset($_SESSION['user_type']) && 
           $_SESSION['user_type'] === 'admin';
}

/**
 * Vérifie si l'utilisateur actuel est un employé
 * @return bool
 */
function is_employee() {
    return isset($_SESSION['employe_id']) && 
           isset($_SESSION['user_type']) && 
           $_SESSION['user_type'] === 'employe';
}

/**
 * Vérifie si l'utilisateur a accès à l'administration (admin ou employé)
 * @return bool
 */
function has_admin_access() {
    return is_admin() || is_employee();
}

/**
 * Force la redirection vers la page de connexion si l'utilisateur n'est pas connecté
 * @param string $redirect_to URL de redirection après connexion
 */
function require_admin_login($redirect_to = null) {
    if (!has_admin_access()) {
        if ($redirect_to) {
            $_SESSION['redirect_after_login'] = $redirect_to;
        }
        header('Location: ' . get_admin_login_url());
        exit;
    }
}

/**
 * Force la redirection vers la page de connexion si l'utilisateur n'est pas admin
 */
function require_admin_only() {
    if (!is_admin()) {
        header('Location: ' . get_admin_login_url());
        exit;
    }
}

/**
 * Force la redirection si l'utilisateur n'est pas un super administrateur
 */
function require_superadmin() {
    if (!is_admin()) {
        header('Location: ' . get_admin_login_url());
        exit;
    }
    
    // Vérifier le rôle de super administrateur en session
    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
        log_unauthorized_access("Tentative d'accès superadmin sans les droits", __FILE__);
        header('Location: index.php?error=insufficient_privileges');
        exit;
    }
}

/**
 * Obtient l'URL de connexion admin appropriée
 * @return string
 */
function get_admin_login_url() {
    // Vérifier si REQUEST_URI est défini (pas en CLI)
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Si on est dans le dossier admin
    if (strpos($request_uri, '/admin/') !== false) {
        return './login.php';
    }
    // Si on est à la racine
    return 'admin/login.php';
}

/**
 * Génère un token CSRF pour les formulaires
 * @return string
 */
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité d'un token CSRF
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Échappe les données pour l'affichage HTML
 * @param mixed $data
 * @return mixed
 */
function escape_html($data) {
    if (is_array($data)) {
        return array_map('escape_html', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoie et valide une entrée utilisateur
 * @param string $input
 * @param int $max_length
 * @return string
 */
function sanitize_input($input, $max_length = 255) {
    $input = trim($input);
    $input = stripslashes($input);
    if ($max_length > 0 && strlen($input) > $max_length) {
        $input = substr($input, 0, $max_length);
    }
    return $input;
}

/**
 * Enregistre une tentative de connexion suspecte
 * @param string $type Type de tentative (login, access_denied, etc.)
 * @param string $details Détails supplémentaires
 */
function log_security_event($type, $details = '') {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_entry = "[{$timestamp}] {$type} - IP: {$ip} - User-Agent: {$user_agent}";
    if ($details) {
        $log_entry .= " - Details: {$details}";
    }
    $log_entry .= "\n";
    
    // Log dans un fichier de sécurité
    $log_file = __DIR__ . '/../../security.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Vérifie les tentatives de brute force
 * @param string $identifier Identifiant unique (IP, email, etc.)
 * @param int $max_attempts Nombre maximum de tentatives
 * @param int $window_minutes Fenêtre de temps en minutes
 * @return bool true si le nombre de tentatives est dépassé
 */
function is_rate_limited($identifier, $max_attempts = 5, $window_minutes = 15) {
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Nettoyer les tentatives anciennes
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window_minutes) {
        return $now - $timestamp < ($window_minutes * 60);
    });
    
    // Vérifier si la limite est atteinte
    if (count($_SESSION[$key]) >= $max_attempts) {
        return true;
    }
    
    // Ajouter la tentative actuelle
    $_SESSION[$key][] = $now;
    
    return false;
}

/**
 * Vérifie si une IP est sur liste noire
 * @param string $ip IP à vérifier (par défaut l'IP actuelle)
 * @return bool
 */
function is_ip_blacklisted($ip = null) {
    if ($ip === null) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    // Pour le moment, utilisons le système de rate limiting
    return is_rate_limited('ip_' . $ip, 10, 30); // 10 tentatives max en 30 minutes
}

/**
 * Ajoute une IP à la liste noire temporaire
 * @param string $ip
 * @param int $duration_minutes
 */
function blacklist_ip($ip = null, $duration_minutes = 60) {
    if ($ip === null) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    $key = 'blacklist_' . md5($ip);
    $_SESSION[$key] = time() + ($duration_minutes * 60);
    
    log_security_event('ip_blacklisted', "IP {$ip} blacklisted for {$duration_minutes} minutes");
}

/**
 * Réinitialise les tentatives de connexion échouées pour un utilisateur
 * @param string $email
 * @param string $ip
 * @param string $type Type d'utilisateur (admin, employe, client)
 */
function reset_failed_login_attempts($email, $ip = null, $type = 'client') {
    if ($ip === null) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    // Réinitialiser le rate limiting pour cet email
    $email_key = 'rate_limit_' . md5('email_' . $email);
    if (isset($_SESSION[$email_key])) {
        unset($_SESSION[$email_key]);
    }
    
    // Réinitialiser le rate limiting pour cette IP
    $ip_key = 'rate_limit_' . md5('ip_' . $ip);
    if (isset($_SESSION[$ip_key])) {
        unset($_SESSION[$ip_key]);
    }
    
    log_security_event('login_success', "Type: {$type}, Email: {$email}, IP: {$ip}");
}

/**
 * Enregistre une tentative de connexion échouée
 * @param string $email
 * @param string $ip
 * @param string $type Type d'utilisateur (admin, employe, client)
 * @param string $reason Raison de l'échec
 */
function record_failed_login_attempt($email, $ip = null, $type = 'client', $reason = 'invalid_credentials') {
    if ($ip === null) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    // Enregistrer la tentative pour cet email
    $email_key = 'rate_limit_' . md5('email_' . $email);
    if (!isset($_SESSION[$email_key])) {
        $_SESSION[$email_key] = [];
    }
    $_SESSION[$email_key][] = time();
    
    // Enregistrer la tentative pour cette IP
    $ip_key = 'rate_limit_' . md5('ip_' . $ip);
    if (!isset($_SESSION[$ip_key])) {
        $_SESSION[$ip_key] = [];
    }
    $_SESSION[$ip_key][] = time();
    
    log_security_event('login_failed', "Type: {$type}, Email: {$email}, IP: {$ip}, Reason: {$reason}");
    
    // Vérifier si on doit bloquer cette IP
    if (is_rate_limited('ip_' . $ip, 5, 15)) {
        blacklist_ip($ip, 30);
    }
}

/**
 * Vérifie si un email est bloqué temporairement
 * @param string $email
 * @return bool
 */
function is_email_rate_limited($email) {
    return is_rate_limited('email_' . $email, 3, 10); // 3 tentatives max en 10 minutes
}

/**
 * Vérifie et enregistre les tentatives de connexion échouées
 * @param string $email
 * @param string $type Type d'utilisateur (admin, employe, client)
 */
function check_failed_login_attempts($email, $type = 'client') {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    record_failed_login_attempt($email, $ip, $type, 'invalid_password');
}

/**
 * Enregistre un accès non autorisé ou une tentative suspecte
 * @param string $message Message à enregistrer
 * @param string $file Fichier où l'événement s'est produit
 * @param array $context Contexte supplémentaire
 */
function log_unauthorized_access($message, $file = '', $context = []) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_entry = "[{$timestamp}] UNAUTHORIZED_ACCESS - {$message}";
    if ($file) {
        $log_entry .= " - File: " . basename($file);
    }
    $log_entry .= " - IP: {$ip} - User-Agent: {$user_agent}";
    
    if (!empty($context)) {
        $log_entry .= " - Context: " . json_encode($context);
    }
    
    $log_entry .= "\n";
    
    // Log dans le fichier de sécurité
    $log_file = __DIR__ . '/../../security.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Auto-vérification si pas défini autrement
if (!defined('NO_AUTO_SECURITY_CHECK')) {
    // Cette vérification peut être désactivée en définissant NO_AUTO_SECURITY_CHECK avant l'inclusion
    // Exemple: define('NO_AUTO_SECURITY_CHECK', true); require_once 'security_utils.php';
}
?>
