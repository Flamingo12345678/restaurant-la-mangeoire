<?php
/**
 * Configuration sécurisée des sessions PHP
 * Ce fichier configure les paramètres de session pour améliorer la sécurité
 */

// Éviter les erreurs si les headers ont déjà été envoyés
if (headers_sent()) {
    error_log("Warning: Headers already sent when trying to configure session");
    return;
}

// Configuration des paramètres de session pour améliorer la sécurité
if (session_status() === PHP_SESSION_NONE) {
    // Paramètres de sécurité pour les cookies de session
    ini_set('session.cookie_httponly', 1); // Empêche l'accès aux cookies via JavaScript
    ini_set('session.use_only_cookies', 1); // Utilise uniquement les cookies pour les sessions
    ini_set('session.cookie_secure', 0); // À mettre à 1 en production avec HTTPS
    ini_set('session.cookie_samesite', 'Lax'); // Protection CSRF
    
    // Paramètres de durée de vie de la session
    ini_set('session.gc_maxlifetime', 3600); // 1 heure
    ini_set('session.cookie_lifetime', 0); // Cookie expire à la fermeture du navigateur
    
    // Paramètres de régénération de l'ID de session
    ini_set('session.use_strict_mode', 1); // Mode strict pour les IDs de session
}

/**
 * Démarre une session sécurisée si elle n'est pas déjà démarrée
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Démarrer la session
        if (!session_start()) {
            error_log("Failed to start session");
            return false;
        }
        
        // Régénérer l'ID de session périodiquement pour éviter les attaques de fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        return true;
    }
    return true;
}

/**
 * Nettoie et détruit une session de manière sécurisée
 */
function destroy_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Nettoyer toutes les variables de session
        $_SESSION = array();
        
        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
    }
}

/**
 * Valide et nettoie l'ID de session
 */
function validate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Vérifier la validité de l'IP (optionnel, peut causer des problèmes avec les proxies)
        $current_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $current_ip) {
            // L'IP a changé, possibilité de session hijacking
            error_log("Session IP mismatch: " . $_SESSION['user_ip'] . " vs " . $current_ip);
            // Pour le moment, on log juste l'erreur sans détruire la session
            // En production, vous pourriez vouloir détruire la session
        }
        
        // Stocker l'IP lors de la première connexion
        if (!isset($_SESSION['user_ip'])) {
            $_SESSION['user_ip'] = $current_ip;
        }
        
        // Vérifier le User-Agent (optionnel)
        $current_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'cli';
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $current_agent) {
            error_log("Session User-Agent mismatch");
            // Même approche que pour l'IP
        }
        
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $current_agent;
        }
    }
}

// Démarrer automatiquement la session sécurisée
start_secure_session();
validate_session();
?>
