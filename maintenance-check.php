<?php
/**
 * 🔧 SCRIPT DE MAINTENANCE - Restaurant La Mangeoire
 * 
 * Ce script vérifie la santé de votre système de panier
 * et vous alerte si quelque chose nécessite une attention.
 * 
 * UTILISATION :
 * - Via web : http://votre-site.com/maintenance-check.php
 * - Via CLI : php maintenance-check.php
 * 
 * @version 1.0
 * @date 2024
 */

// Configuration
$MAINTENANCE_MODE = false; // Basculer sur true pour fermer temporairement le site

// Démarrage des vérifications
echo "🔧 CONTRÔLE DE MAINTENANCE - " . date('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$issues = [];
$warnings = [];
$success_count = 0;
$total_checks = 0;

/**
 * Fonction utilitaire pour afficher les résultats
 */
function check_result($name, $success, $message = '', &$issues = [], &$warnings = [], &$success_count = 0, &$total_checks = 0) {
    $total_checks++;
    
    if ($success) {
        echo "✅ $name : OK\n";
        if ($message) echo "   → $message\n";
        $success_count++;
    } else {
        echo "❌ $name : PROBLÈME\n";
        if ($message) echo "   → $message\n";
        $issues[] = "$name : $message";
    }
    echo "\n";
}

/**
 * Fonction pour avertissement non-critique
 */
function warning_result($name, $message, &$warnings = []) {
    echo "⚠️ $name : ATTENTION\n";
    echo "   → $message\n\n";
    $warnings[] = "$name : $message";
}

// ===============================
// 1. VÉRIFICATIONS DE BASE
// ===============================

echo "📋 VÉRIFICATIONS DE BASE\n";
echo "-" . str_repeat("-", 30) . "\n";

// Mode maintenance
if ($MAINTENANCE_MODE) {
    warning_result("Mode maintenance", "Le site est en mode maintenance", $warnings);
}

// Connexion base de données
try {
    // Test de connexion simple sans dépendance externe
    $pdo = new PDO("mysql:host=localhost;dbname=restaurant_db;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->query("SELECT 1")->fetch();
    check_result("Base de données", true, "Connexion établie", $issues, $warnings, $success_count, $total_checks);
} catch (Exception $e) {
    // En cas d'échec, continuer les autres tests
    check_result("Base de données", false, "Erreur de connexion : " . $e->getMessage(), $issues, $warnings, $success_count, $total_checks);
    $pdo = null; // S'assurer que $pdo est null pour les tests suivants
}

// Fichiers critiques
$critical_files = [
    'ajouter-au-panier.php' => 'Script d\'ajout au panier',
    'includes/CartManager.php' => 'Gestionnaire de panier',
    'includes/https-security.php' => 'Sécurité HTTPS',
    'api/cart-summary.php' => 'API résumé panier',
    '.htaccess' => 'Configuration Apache'
];

foreach ($critical_files as $file => $description) {
    $exists = file_exists($file);
    $readable = $exists && is_readable($file);
    
    if ($exists && $readable) {
        check_result($description, true, "Fichier accessible", $issues, $warnings, $success_count, $total_checks);
    } else {
        $msg = $exists ? "Fichier non lisible" : "Fichier manquant";
        check_result($description, false, "$msg : $file", $issues, $warnings, $success_count, $total_checks);
    }
}

// ===============================
// 2. VÉRIFICATIONS FONCTIONNELLES
// ===============================

echo "⚙️ VÉRIFICATIONS FONCTIONNELLES\n";
echo "-" . str_repeat("-", 35) . "\n";

// Test de session
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    check_result("Sessions PHP", true, "Sessions fonctionnelles", $issues, $warnings, $success_count, $total_checks);
} else {
    check_result("Sessions PHP", false, "Impossible de démarrer une session", $issues, $warnings, $success_count, $total_checks);
}

// Test CartManager
try {
    if (file_exists('includes/CartManager.php')) {
        require_once 'includes/CartManager.php';
        $cart = new CartManager($pdo); // $pdo peut être null, CartManager doit le gérer
        check_result("CartManager", true, "Classe instanciée correctement", $issues, $warnings, $success_count, $total_checks);
        
        // Test ajout simple seulement si on a une connexion DB
        if ($pdo !== null) {
            $test_result = $cart->addItem(1, 1);
            if (is_array($test_result) && isset($test_result['success']) && $test_result['success']) {
                check_result("Ajout au panier", true, "Test d'ajout réussi", $issues, $warnings, $success_count, $total_checks);
            } else {
                $error_msg = is_array($test_result) && isset($test_result['message']) ? $test_result['message'] : 'Erreur inconnue';
                check_result("Ajout au panier", false, "Échec du test d'ajout : $error_msg", $issues, $warnings, $success_count, $total_checks);
            }
        } else {
            warning_result("Test ajout panier", "Base de données non disponible - test ignoré", $warnings);
        }
    } else {
        check_result("CartManager", false, "Fichier CartManager.php introuvable", $issues, $warnings, $success_count, $total_checks);
    }
} catch (Exception $e) {
    check_result("CartManager", false, "Erreur : " . $e->getMessage(), $issues, $warnings, $success_count, $total_checks);
}

// ===============================
// 3. VÉRIFICATIONS SÉCURITÉ
// ===============================

echo "🔒 VÉRIFICATIONS SÉCURITÉ\n";
echo "-" . str_repeat("-", 30) . "\n";

// HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    check_result("HTTPS actif", true, "Connexion sécurisée", $issues, $warnings, $success_count, $total_checks);
} else {
    if (php_sapi_name() === 'cli') {
        warning_result("HTTPS", "Test CLI - impossible de vérifier HTTPS", $warnings);
    } else {
        check_result("HTTPS actif", false, "Site non sécurisé - vérifiez la configuration", $issues, $warnings, $success_count, $total_checks);
    }
}

// Configuration session sécurisée
$session_config = [
    'cookie_secure' => ini_get('session.cookie_secure'),
    'cookie_httponly' => ini_get('session.cookie_httponly'),
    'cookie_samesite' => ini_get('session.cookie_samesite')
];

$secure_config = true;
$config_issues = [];

if (!$session_config['cookie_httponly']) {
    $secure_config = false;
    $config_issues[] = 'httponly désactivé';
}

if ($session_config['cookie_samesite'] !== 'Strict' && $session_config['cookie_samesite'] !== 'Lax') {
    $config_issues[] = 'samesite non configuré';
}

if (php_sapi_name() !== 'cli' && !$session_config['cookie_secure']) {
    $secure_config = false;
    $config_issues[] = 'secure désactivé';
}

if ($secure_config) {
    check_result("Config session", true, "Sessions sécurisées", $issues, $warnings, $success_count, $total_checks);
} else {
    check_result("Config session", false, "Problèmes : " . implode(', ', $config_issues), $issues, $warnings, $success_count, $total_checks);
}

// ===============================
// 4. VÉRIFICATIONS PERFORMANCE
// ===============================

echo "📊 VÉRIFICATIONS PERFORMANCE\n";
echo "-" . str_repeat("-", 35) . "\n";

// Taille des fichiers de log (si ils existent)
$log_files = ['error.log', 'access.log', 'debug.log'];
foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        $size = filesize($log_file);
        if ($size > 50 * 1024 * 1024) { // 50MB
            warning_result("Fichier log volumineux", "$log_file fait " . round($size/1024/1024, 1) . "MB - nettoyage recommandé", $warnings);
        }
    }
}

// Vérification de l'espace disque
$free_space = disk_free_space('./');
$total_space = disk_total_space('./');
$used_percent = (1 - ($free_space / $total_space)) * 100;

if ($used_percent > 90) {
    warning_result("Espace disque", "Espace utilisé à " . round($used_percent, 1) . "% - nettoyage recommandé", $warnings);
} else {
    echo "✅ Espace disque : " . round(100 - $used_percent, 1) . "% disponible\n\n";
}

// ===============================
// 5. RÉSUMÉ FINAL
// ===============================

echo "=" . str_repeat("=", 60) . "\n";
echo "📋 RÉSUMÉ DE MAINTENANCE\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$success_rate = $total_checks > 0 ? round(($success_count / $total_checks) * 100, 1) : 0;

echo "🎯 Score de santé : $success_count/$total_checks ($success_rate%)\n\n";

if (empty($issues) && empty($warnings)) {
    echo "🎉 EXCELLENT ! Votre système fonctionne parfaitement !\n";
    echo "✨ Aucun problème détecté.\n\n";
} else {
    if (!empty($issues)) {
        echo "🚨 PROBLÈMES CRITIQUES À RÉSOUDRE :\n";
        foreach ($issues as $issue) {
            echo "   • $issue\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠️ AVERTISSEMENTS (non-critiques) :\n";
        foreach ($warnings as $warning) {
            echo "   • $warning\n";
        }
        echo "\n";
    }
}

// Recommandations
echo "💡 RECOMMANDATIONS :\n";
echo "   • Exécutez ce script régulièrement (hebdomadaire)\n";
echo "   • Surveillez les logs d'erreur\n";
echo "   • Faites des sauvegardes régulières de la base de données\n";
echo "   • Testez régulièrement l'ajout au panier depuis différents appareils\n\n";

echo "🔧 Maintenance terminée - " . date('Y-m-d H:i:s') . "\n";

// Mode CLI : code de sortie
if (php_sapi_name() === 'cli') {
    exit(empty($issues) ? 0 : 1);
}
?>
