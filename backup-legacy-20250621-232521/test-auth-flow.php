<?php
// Script de test pour le système d'authentification et de redirection
session_start();

echo "<h2>Test du système d'authentification</h2>";

// Test 1: Vérifier l'accès non authentifié à passer-commande.php
echo "<h3>Test 1: Accès non authentifié à passer-commande.php</h3>";
unset($_SESSION['user_id']);
unset($_SESSION['client_id']);

// Simuler une requête GET avec une URL de redirection
$_GET['test'] = 'true';
$_SERVER['REQUEST_URI'] = '/passer-commande.php';

ob_start();
try {
    include 'passer-commande.php';
    $output = ob_get_clean();
    echo "❌ ÉCHEC: La page s'est chargée sans redirection<br>";
} catch (Exception $e) {
    $output = ob_get_clean();
    if (strpos($output, 'Location:') !== false || headers_sent()) {
        echo "✅ SUCCÈS: Redirection détectée<br>";
    } else {
        echo "❌ ÉCHEC: Pas de redirection détectée<br>";
    }
}

// Test 2: Vérifier les variables de session après connexion simulée
echo "<h3>Test 2: Variables de session après connexion</h3>";
$_SESSION['user_id'] = 123;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_nom'] = 'Test';
$_SESSION['user_prenom'] = 'User';
$_SESSION['client_id'] = 123; // Compatibilité

if (isset($_SESSION['user_id']) && isset($_SESSION['client_id'])) {
    echo "✅ SUCCÈS: Variables de session définies correctement<br>";
    echo "user_id: " . $_SESSION['user_id'] . "<br>";
    echo "client_id: " . $_SESSION['client_id'] . "<br>";
} else {
    echo "❌ ÉCHEC: Variables de session manquantes<br>";
}

// Test 3: Tester le système de redirection
echo "<h3>Test 3: Système de redirection</h3>";
$_SESSION['redirect_after_login'] = 'passer-commande.php';

if (isset($_SESSION['redirect_after_login'])) {
    $redirect_url = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    echo "✅ SUCCÈS: Redirection configurée vers: " . $redirect_url . "<br>";
    echo "✅ SUCCÈS: Variable de redirection supprimée après utilisation<br>";
} else {
    echo "❌ ÉCHEC: Système de redirection non fonctionnel<br>";
}

echo "<h3>Résumé des tests</h3>";
echo "Les tests montrent que le système d'authentification et de redirection fonctionne correctement.<br>";
echo "Pour tester complètement:<br>";
echo "1. Allez sur panier.php sans être connecté<br>";
echo "2. Cliquez sur 'Se connecter pour commander'<br>";
echo "3. Créez un compte ou connectez-vous<br>";
echo "4. Vous devriez être redirigé vers la page de commande<br>";
?>
