<?php
/**
 * Script d'urgence pour réinitialiser les sessions et tester la connexion à la base de données
 * Utilisez ce script en cas de problème de connexion persistant
 */

// Désactiver la sortie de tampon
ob_start();

// Afficher les erreurs pour le diagnostic
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de session
require_once dirname(__FILE__) . '/includes/session_config.php';

echo "<h1>Outil de réinitialisation d'urgence</h1>";

// 1. Vérifier et détruire toute session existante
if (session_status() === PHP_SESSION_ACTIVE) {
    // Détruire la session existante
    session_destroy();
    echo "<p>Session existante détruite.</p>";
} else {
    echo "<p>Aucune session active n'a été détectée.</p>";
}

// 2. Nettoyer les cookies de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    echo "<p>Cookie de session supprimé.</p>";
} else {
    echo "<p>Aucun cookie de session n'a été détecté.</p>";
}

// 3. Démarrer une nouvelle session pour tester
session_start();
echo "<p>Nouvelle session démarrée avec ID: " . session_id() . "</p>";

// 4. Tester la connexion à la base de données
echo "<h2>Test de connexion à la base de données</h2>";
try {
    require_once 'db_connexion.php';
    echo "<p>Connexion à la base de données établie avec succès.</p>";
    
    // Vérifier les références de connexion
    if (isset($conn)) {
        echo "<p>La variable \$conn est définie.</p>";
        
        // Tester une requête simple
        $stmt = $conn->query("SELECT 1 AS test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['test'] == 1) {
            echo "<p>Test de requête réussi avec \$conn.</p>";
        }
    } else {
        echo "<p>ERREUR: La variable \$conn n'est pas définie.</p>";
    }
} catch (PDOException $e) {
    echo "<p>ERREUR de connexion à la base de données: " . $e->getMessage() . "</p>";
}

// 5. Vérifier les variables globales importantes
echo "<h2>Variables de configuration</h2>";
echo "<p>Nom de session: " . session_name() . "</p>";
echo "<p>Chemin des cookies: " . ini_get('session.cookie_path') . "</p>";
echo "<p>Domaine des cookies: " . ini_get('session.cookie_domain') . "</p>";
echo "<p>Durée de vie des cookies: " . ini_get('session.cookie_lifetime') . "</p>";
echo "<p>HTTP only: " . ini_get('session.cookie_httponly') . "</p>";
echo "<p>Secure: " . ini_get('session.cookie_secure') . "</p>";

// 6. Liens de test
echo "<h2>Liens de test</h2>";
echo "<p><a href='connexion-unifiee.php?admin=1&force_new=1'>Tester la connexion administrateur</a></p>";
echo "<p><a href='admin/admin_diagnostic.php'>Diagnostic administrateur</a></p>";
echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?>
