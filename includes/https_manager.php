<?php
/**
 * Configuration HTTPS pour La Mangeoire
 * Forcer HTTPS et sécuriser les connexions
 */

class HTTPSManager {
    
    /**
     * Vérifie si la connexion est en HTTPS
     */
    public static function isHTTPS() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false)
        );
    }
    
    /**
     * Force la redirection vers HTTPS
     */
    public static function forceHTTPS() {
        if (!self::isHTTPS()) {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect_url", true, 301);
            header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
            exit();
        }
    }
    
    /**
     * Ajoute les headers de sécurité
     */
    public static function addSecurityHeaders() {
        if (self::isHTTPS()) {
            header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: DENY");
            header("X-XSS-Protection: 1; mode=block");
            header("Referrer-Policy: strict-origin-when-cross-origin");
            header("Content-Security-Policy: default-src 'self' https://js.stripe.com https://api.stripe.com https://www.paypal.com https://www.sandbox.paypal.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https://js.stripe.com https://www.paypal.com https://www.sandbox.paypal.com;");
        }
    }
    
    /**
     * Vérifie si l'environnement est prêt pour les paiements
     */
    public static function isPaymentReady() {
        // Vérifier les clés Stripe
        $stripe_public = getenv('STRIPE_PUBLISHABLE_KEY') ?: getenv('STRIPE_PUBLIC_KEY') ?: ($_ENV['STRIPE_PUBLISHABLE_KEY'] ?? $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
        $stripe_secret = getenv('STRIPE_SECRET_KEY') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? '');
        
        return self::isHTTPS() && !empty($stripe_public) && !empty($stripe_secret);
    }
    
    /**
     * Obtient l'URL complète avec HTTPS
     */
    public static function getSecureURL($path = '') {
        $protocol = self::isHTTPS() ? 'https' : 'https'; // Force toujours HTTPS
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        return $protocol . '://' . $host . '/' . ltrim($path, '/');
    }
    
    /**
     * Configure l'environnement sécurisé
     */
    public static function setupSecureEnvironment() {
        // Force HTTPS si configuré
        $force_https = getenv('FORCE_HTTPS') === 'true' || 
                      (isset($_ENV['FORCE_HTTPS']) && $_ENV['FORCE_HTTPS'] === 'true');
        
        if ($force_https) {
            self::forceHTTPS();
        }
        
        // Ajoute les headers de sécurité
        self::addSecurityHeaders();
        
        // Configure la session sécurisée
        if (self::isHTTPS()) {
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
        }
    }
    
    /**
     * Affiche un message d'erreur si HTTPS n'est pas disponible
     */
    public static function checkHTTPSOrDie() {
        if (!self::isHTTPS()) {
            // Nettoyer le buffer de sortie si nécessaire
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Essayer de définir le code de réponse
            if (!headers_sent()) {
                http_response_code(426); // Upgrade Required
            }
            
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Connexion sécurisée requise</title>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
                    .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .error-icon { font-size: 4rem; color: #dc3545; margin-bottom: 20px; }
                    h1 { color: #dc3545; margin-bottom: 20px; }
                    p { color: #6c757d; line-height: 1.6; margin-bottom: 30px; }
                    .btn { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">🔒</div>
                    <h1>Connexion sécurisée requise</h1>
                    <p>Pour des raisons de sécurité, les paiements ne peuvent être traités que via une connexion HTTPS sécurisée.</p>
                    <p>Veuillez accéder au site via <strong>https://</strong> pour effectuer vos achats en toute sécurité.</p>
                    <a href="<?php echo self::getSecureURL(); ?>" class="btn">Accéder au site sécurisé</a>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
    }
}

// Auto-configuration si ce fichier est inclus
if (!defined('HTTPS_MANAGER_NO_AUTO')) {
    // Charger les variables d'environnement si le fichier .env existe
    if (file_exists(__DIR__ . '/../.env')) {
        $env_file = __DIR__ . '/../.env';
        if (is_readable($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (!array_key_exists($key, $_ENV)) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
    }
    
    HTTPSManager::setupSecureEnvironment();
}
?>
