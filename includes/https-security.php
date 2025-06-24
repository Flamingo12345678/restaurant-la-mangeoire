<?php
/**
 * Configuration de sécurité HTTPS pour Restaurant La Mangeoire
 * 
 * Ce fichier configure les paramètres de sécurité pour forcer HTTPS
 * et sécuriser l'application web
 */

// Vérifier si ce fichier est inclus dans un contexte approprié
if (!defined('HTTPS_CONFIG_LOADED')) {
    define('HTTPS_CONFIG_LOADED', true);
}

/**
 * Fonction pour forcer HTTPS
 */
function forceHTTPS() {
    // Vérifier si on est déjà en HTTPS
    $isHTTPS = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
    
    if (!$isHTTPS) {
        // Construire l'URL HTTPS
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // Redirection permanente vers HTTPS
        header('HTTP/1.1 301 Moved Permanently');
        header("Location: $redirectURL");
        exit();
    }
}

/**
 * Configuration des en-têtes de sécurité HTTPS
 */
function setSecurityHeaders() {
    // Vérifier que les en-têtes ne sont pas déjà envoyés
    if (!headers_sent()) {
        // HSTS - Forcer HTTPS pour les futures requêtes
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        
        // Protection contre le clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Empêcher la détection automatique du type MIME
        header('X-Content-Type-Options: nosniff');
        
        // Protection XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Politique de référent
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (ajustez selon vos besoins)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'";
        header("Content-Security-Policy: $csp");
    }
}

/**
 * Configuration des cookies sécurisés
 */
function configureSecureCookies() {
    // Configurer les paramètres uniquement si aucune session n'est active ET si les en-têtes ne sont pas envoyés
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        // Alternative avec session_set_cookie_params (plus moderne et plus sûre)
        session_set_cookie_params([
            'lifetime' => 0, // Session expire à la fermeture du navigateur
            'path' => '/',
            'domain' => '',
            'secure' => true,      // HTTPS uniquement
            'httponly' => true,    // Pas d'accès JavaScript
            'samesite' => 'Strict' // Protection CSRF
        ]);
        
        // Configuration des paramètres de session supplémentaires si possible
        if (function_exists('ini_set')) {
            ini_set('session.use_strict_mode', '1');   // Mode strict pour les sessions
        }
    }
}

/**
 * Fonction pour démarrer une session sécurisée
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurer les cookies sécurisés avant de démarrer
        configureSecureCookies();
        
        // Démarrer la session
        if (!session_start()) {
            error_log("Erreur lors du démarrage de la session sécurisée");
            return false;
        }
        
        // Régénérer l'ID de session pour éviter la fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        
        return true;
    }
    return true; // Session déjà active
}

/**
 * Vérifier si la connexion est sécurisée
 */
function isSecureConnection() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
}

/**
 * Obtenir l'URL de base sécurisée
 */
function getSecureBaseURL() {
    $protocol = 'https://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    
    // Nettoyer le chemin
    $path = $scriptName === '/' ? '' : $scriptName;
    
    return $protocol . $host . $path;
}

/**
 * Générer une URL sécurisée
 */
function secureUrl($path = '') {
    $baseUrl = getSecureBaseURL();
    $path = ltrim($path, '/');
    
    return $baseUrl . ($path ? '/' . $path : '');
}

/**
 * Initialisation automatique de la sécurité HTTPS
 */
function initHTTPSSecurity() {
    // Forcer HTTPS (décommentez cette ligne en production)
     forceHTTPS();
    
    // Configurer les en-têtes de sécurité
    setSecurityHeaders();
    
    // Configurer les cookies sécurisés seulement si aucune session n'est active
    if (session_status() === PHP_SESSION_NONE) {
        configureSecureCookies();
    } else {
        // Session déjà active, on peut seulement logger un avertissement
        if (defined('DEBUG_HTTPS') && constant('DEBUG_HTTPS')) {
            error_log("HTTPS Security: Session déjà active, impossible de configurer les cookies");
        }
    }
}

/**
 * Alternative pour configurer la sécurité avant toute session
 * À appeler en tout début de script avant session_start()
 */
function initHTTPSSecurityEarly() {
    // Forcer HTTPS (décommentez cette ligne en production)
    // forceHTTPS();
    
    // Configurer les en-têtes de sécurité
    setSecurityHeaders();
    
    // Configurer les cookies sécurisés
    configureSecureCookies();
    
    return true;
}

// Auto-initialisation si ce fichier est inclus
if (!defined('HTTPS_SECURITY_MANUAL_INIT')) {
    initHTTPSSecurity();
}

// Constantes utiles
define('IS_HTTPS', isSecureConnection());
define('SECURE_BASE_URL', getSecureBaseURL());

// Log pour debug (à supprimer en production)
if (defined('DEBUG_HTTPS') && constant('DEBUG_HTTPS')) {
    error_log("HTTPS Security Config - Is HTTPS: " . (IS_HTTPS ? 'YES' : 'NO'));
    error_log("HTTPS Security Config - Base URL: " . SECURE_BASE_URL);
}
?>
