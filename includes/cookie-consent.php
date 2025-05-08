<?php
/**
 * Gestion du consentement des cookies côté serveur
 * Ce fichier doit être inclus au début de chaque page
 */

// Fonction pour vérifier si un cookie existe
function cookie_exists($name) {
    return isset($_COOKIE[$name]);
}

// Fonction pour vérifier si le consentement aux cookies a été donné
function has_cookie_consent() {
    return cookie_exists('cookie_consent');
}

// Fonction pour vérifier si les cookies analytiques sont acceptés
function analytics_cookies_accepted() {
    return cookie_exists('analytics_cookies') && $_COOKIE['analytics_cookies'] === 'true';
}

// Fonction pour vérifier si les cookies marketing sont acceptés
function marketing_cookies_accepted() {
    return cookie_exists('marketing_cookies') && $_COOKIE['marketing_cookies'] === 'true';
}

// Fonction pour inclure les scripts de tracking uniquement si consentement donné
function include_tracking_scripts() {
    ob_start();
    
    if (analytics_cookies_accepted()) {
        // Inclure Google Analytics ou autres outils d'analyse
        ?>
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-XXXXXXXX-X"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'UA-XXXXXXXX-X');
        </script>
        <?php
    }
    
    if (marketing_cookies_accepted()) {
        // Inclure des scripts marketing
        ?>
        <!-- Scripts marketing -->
        <!-- Par exemple, Facebook Pixel Code, etc. -->
        <?php
    }
    
    return ob_get_clean();
}

// À intégrer dans le footer pour permettre de modifier les préférences
function cookie_preferences_link() {
    return '<a class="cookie-preferences-link" onclick="showCookiePreferences()">Gérer mes préférences de cookies</a>';
}
?>
