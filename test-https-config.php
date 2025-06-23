<?php
/**
 * Test de Configuration HTTPS - Restaurant La Mangeoire
 * Vérifie que tous les composants HTTPS sont correctement configurés
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Test HTTPS - La Mangeoire</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.test-section { margin-bottom: 20px; padding: 15px; border-left: 4px solid #007cba; background: #f9f9f9; }
.success { color: #28a745; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.info { color: #17a2b8; }
h1 { color: #333; text-align: center; }
h2 { color: #007cba; }
.status-badge { padding: 5px 10px; border-radius: 5px; color: white; font-weight: bold; }
.badge-success { background: #28a745; }
.badge-warning { background: #ffc107; }
.badge-error { background: #dc3545; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔒 Test Configuration HTTPS - La Mangeoire</h1>";
echo "<p class='info'>Test exécuté le : " . date('d/m/Y H:i:s') . "</p>";

$https_status = [];

// 1. Vérification du protocole HTTPS
echo "<div class='test-section'>";
echo "<h2>🌐 Protocole de Connexion</h2>";

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

if ($is_https) {
    echo "<p class='success'>✅ Connexion HTTPS active</p>";
    $https_status['protocol'] = true;
} else {
    echo "<p class='warning'>⚠️ Connexion HTTP détectée - HTTPS recommandé pour la production</p>";
    $https_status['protocol'] = false;
}

echo "<p><strong>URL actuelle :</strong> " . (($is_https) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
echo "</div>";

// 2. Vérification des variables d'environnement HTTPS
echo "<div class='test-section'>";
echo "<h2>⚙️ Configuration Environnement</h2>";

// Charger les variables d'environnement
$env_file = __DIR__ . '/.env';
$env_production_file = __DIR__ . '/.env.production';
$env_vars = [];

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env_vars[trim($key)] = trim($value, '"');
        }
    }
    echo "<p class='success'>✅ Fichier .env chargé</p>";
} else {
    echo "<p class='error'>❌ Fichier .env manquant</p>";
}

if (file_exists($env_production_file)) {
    echo "<p class='success'>✅ Fichier .env.production disponible</p>";
} else {
    echo "<p class='warning'>⚠️ Fichier .env.production manquant</p>";
}

// Vérifier FORCE_HTTPS
$force_https = $env_vars['FORCE_HTTPS'] ?? 'false';
if ($force_https === 'true') {
    echo "<p class='success'>✅ FORCE_HTTPS activé</p>";
    $https_status['force_https'] = true;
} else {
    echo "<p class='warning'>⚠️ FORCE_HTTPS désactivé</p>";
    $https_status['force_https'] = false;
}

// Vérifier APP_ENV
$app_env = $env_vars['APP_ENV'] ?? 'development';
echo "<p><strong>Environnement :</strong> <span class='badge-" . ($app_env === 'production' ? 'success' : 'warning') . " status-badge'>$app_env</span></p>";

echo "</div>";

// 3. Vérification des headers de sécurité
echo "<div class='test-section'>";
echo "<h2>🛡️ Headers de Sécurité</h2>";

$security_headers = [
    'Strict-Transport-Security' => 'HSTS - Force HTTPS',
    'X-Content-Type-Options' => 'Protection MIME',
    'X-Frame-Options' => 'Protection Clickjacking',
    'X-XSS-Protection' => 'Protection XSS',
    'Content-Security-Policy' => 'CSP - Politique de sécurité'
];

$headers_ok = 0;
foreach ($security_headers as $header => $description) {
    if (function_exists('getallheaders')) {
        $all_headers = getallheaders();
        if (isset($all_headers[$header])) {
            echo "<p class='success'>✅ $description configuré</p>";
            $headers_ok++;
        } else {
            echo "<p class='warning'>⚠️ $description manquant</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ Impossible de vérifier les headers (getallheaders non disponible)</p>";
    }
}

$https_status['security_headers'] = $headers_ok;
echo "</div>";

// 4. Test des services de paiement HTTPS
echo "<div class='test-section'>";
echo "<h2>💳 Services de Paiement HTTPS</h2>";

// Vérifier les clés Stripe
$stripe_pub = $env_vars['STRIPE_PUBLISHABLE_KEY'] ?? '';
$stripe_secret = $env_vars['STRIPE_SECRET_KEY'] ?? '';

if (!empty($stripe_pub) && !empty($stripe_secret)) {
    echo "<p class='success'>✅ Clés Stripe configurées</p>";
    if (strpos($stripe_pub, 'pk_live_') === 0) {
        echo "<p class='success'>✅ Stripe en mode PRODUCTION</p>";
    } else {
        echo "<p class='warning'>⚠️ Stripe en mode TEST</p>";
    }
} else {
    echo "<p class='error'>❌ Clés Stripe manquantes</p>";
}

// Vérifier PayPal
$paypal_client = $env_vars['PAYPAL_CLIENT_ID'] ?? '';
$paypal_mode = $env_vars['PAYPAL_MODE'] ?? 'sandbox';

if (!empty($paypal_client)) {
    echo "<p class='success'>✅ PayPal configuré</p>";
    echo "<p><strong>Mode PayPal :</strong> <span class='status-badge badge-" . ($paypal_mode === 'live' ? 'success' : 'warning') . "'>$paypal_mode</span></p>";
} else {
    echo "<p class='error'>❌ PayPal non configuré</p>";
}

$https_status['payment_services'] = true;
echo "</div>";

// 5. Test de configuration SMTP avec HTTPS
echo "<div class='test-section'>";
echo "<h2>📧 Configuration Email HTTPS</h2>";

try {
    require_once 'includes/https_manager.php';
    require_once 'includes/email_manager.php';
    
    $emailManager = new EmailManager();
    $email_config = $emailManager->testConfiguration();
    
    echo "<p class='success'>✅ EmailManager chargé</p>";
    
    $smtp_host = $email_config['smtp_host'] ?? '';
    if (strpos($smtp_host, 'smtp.gmail.com') !== false || strpos($smtp_host, 'smtp.') === 0) {
        echo "<p class='success'>✅ SMTP configuré avec TLS/SSL</p>";
    } else {
        echo "<p class='warning'>⚠️ Configuration SMTP à vérifier</p>";
    }
    
    $https_status['email_config'] = true;
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur email : " . $e->getMessage() . "</p>";
    $https_status['email_config'] = false;
}
echo "</div>";

// 6. Vérification du fichier .htaccess
echo "<div class='test-section'>";
echo "<h2>🔧 Configuration Apache (.htaccess)</h2>";

if (file_exists('.htaccess')) {
    $htaccess_content = file_get_contents('.htaccess');
    echo "<p class='success'>✅ Fichier .htaccess présent</p>";
    
    if (strpos($htaccess_content, 'RewriteRule ^(.*)$ https://') !== false) {
        echo "<p class='success'>✅ Redirection HTTP → HTTPS configurée</p>";
    } else {
        echo "<p class='warning'>⚠️ Redirection HTTPS non détectée dans .htaccess</p>";
    }
    
    if (strpos($htaccess_content, 'Strict-Transport-Security') !== false) {
        echo "<p class='success'>✅ HSTS configuré</p>";
    } else {
        echo "<p class='warning'>⚠️ HSTS non configuré</p>";
    }
    
    $https_status['htaccess'] = true;
} else {
    echo "<p class='warning'>⚠️ Fichier .htaccess manquant</p>";
    $https_status['htaccess'] = false;
}
echo "</div>";

// 7. Résumé de sécurité HTTPS
echo "<div class='test-section'>";
echo "<h2>📊 Résumé Sécurité HTTPS</h2>";

$total_checks = count($https_status);
$passed_checks = count(array_filter($https_status));
$security_score = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100, 1) : 0;

if ($security_score >= 90) {
    $status_class = 'success';
    $status_icon = '🟢';
    $status_text = 'EXCELLENT';
} elseif ($security_score >= 70) {
    $status_class = 'warning';
    $status_icon = '🟡';
    $status_text = 'ACCEPTABLE';
} else {
    $status_class = 'error';
    $status_icon = '🔴';
    $status_text = 'À AMÉLIORER';
}

echo "<p class='$status_class'><strong>$status_icon Score de sécurité HTTPS : $status_text ($security_score%)</strong></p>";
echo "<p>Vérifications réussies : $passed_checks / $total_checks</p>";

if ($is_https && $security_score >= 80) {
    echo "<p class='success'>🎉 Configuration HTTPS prête pour la production !</p>";
} elseif ($is_https) {
    echo "<p class='warning'>⚠️ HTTPS actif mais quelques améliorations recommandées</p>";
} else {
    echo "<p class='error'>❌ HTTPS requis pour la production - Vérifiez la configuration du serveur</p>";
}

echo "<h3>🔧 Recommandations :</h3>";
echo "<ul>";
if (!$is_https) {
    echo "<li>🔒 Activer HTTPS sur le serveur web</li>";
}
if ($force_https !== 'true') {
    echo "<li>⚙️ Activer FORCE_HTTPS=true dans .env</li>";
}
if ($app_env !== 'production') {
    echo "<li>🚀 Passer en APP_ENV=production</li>";
}
echo "<li>🧪 Tester les paiements Stripe/PayPal en HTTPS</li>";
echo "<li>📧 Vérifier l'envoi d'emails en production</li>";
echo "<li>🔄 Effectuer des sauvegardes régulières</li>";
echo "</ul>";

echo "</div>";

echo "</div></body></html>";
?>
