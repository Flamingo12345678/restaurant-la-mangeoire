<?php
// Script de diagnostic pour détecter les erreurs du formulaire de contact
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 DIAGNOSTIC FORMULAIRE DE CONTACT\n";
echo "===================================\n\n";

// 1. Test de la connexion à la base de données
echo "1. 🗄️ Test de la connexion à la base de données :\n";
try {
    require_once 'db_connexion.php';
    echo "   ✅ Connexion réussie\n";
    
    // Test de la table Messages
    $stmt = $pdo->query("SELECT COUNT(*) FROM Messages");
    $count = $stmt->fetchColumn();
    echo "   ✅ Table Messages accessible ($count messages)\n";
    
} catch (PDOException $e) {
    echo "   ❌ Erreur de connexion : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "   ❌ Erreur générale : " . $e->getMessage() . "\n";
}

echo "\n2. 🧪 Test de simulation d'envoi de message :\n";
// Simuler un POST
$_POST = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'Test Subject',
    'message' => 'Test message content'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Inclure le traitement du formulaire
ob_start(); // Capturer la sortie
session_start();

$success_message = '';
$error_message = '';

// Traitement du formulaire (copié du contact.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim(strip_tags($_POST['name'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $objet = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));
    
    echo "   📝 Données reçues :\n";
    echo "      - Nom : '$nom'\n";
    echo "      - Email : '$email'\n";
    echo "      - Objet : '$objet'\n";
    echo "      - Message : '$message'\n";
    
    // Validation
    if (empty($nom)) {
        $error_message = "Le nom est requis.";
    } elseif (!$email) {
        $error_message = "Un email valide est requis.";
    } elseif (empty($objet)) {
        $error_message = "L'objet est requis.";
    } elseif (empty($message)) {
        $error_message = "Le message est requis.";
    } else {
        try {
            // Insertion en base de données
            $stmt = $pdo->prepare("
                INSERT INTO Messages (nom, email, objet, message, date_creation)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $nom,
                $email, 
                $objet,
                $message
            ]);
            
            if ($result) {
                $message_id = $pdo->lastInsertId();
                $success_message = "Test réussi ! Message inséré avec ID: $message_id";
                
                // Nettoyer le message de test
                $stmt = $pdo->prepare("DELETE FROM Messages WHERE MessageID = ?");
                $stmt->execute([$message_id]);
                echo "   ✅ Message de test inséré et supprimé (ID: $message_id)\n";
            } else {
                $error_message = "Erreur lors de l'insertion.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur exception : " . $e->getMessage();
        }
    }
}

if ($success_message) {
    echo "   ✅ Succès : $success_message\n";
}

if ($error_message) {
    echo "   ❌ Erreur : $error_message\n";
}

$output = ob_get_clean();

echo "\n3. 🔍 Analyse des erreurs potentielles :\n";

// Vérifier les erreurs communes
$potential_issues = [];

// Vérifier si session_start() peut causer des problèmes
if (headers_sent()) {
    $potential_issues[] = "Headers déjà envoyés - session_start() peut échouer";
}

// Vérifier les permissions d'écriture
if (!is_writable(session_save_path())) {
    $potential_issues[] = "Répertoire de session non accessible en écriture";
}

// Vérifier la configuration PHP
if (!extension_loaded('pdo')) {
    $potential_issues[] = "Extension PDO non chargée";
}

if (!extension_loaded('pdo_mysql')) {
    $potential_issues[] = "Extension PDO MySQL non chargée";
}

if (empty($potential_issues)) {
    echo "   ✅ Aucun problème détecté\n";
} else {
    foreach ($potential_issues as $issue) {
        echo "   ⚠️ $issue\n";
    }
}

echo "\n4. 📊 RÉSUMÉ DU DIAGNOSTIC :\n";
echo "   Base de données : " . (isset($pdo) ? "✅ OK" : "❌ ERREUR") . "\n";
echo "   Table Messages : " . (isset($count) ? "✅ OK" : "❌ ERREUR") . "\n";
echo "   Traitement formulaire : " . ($success_message ? "✅ OK" : "❌ ERREUR") . "\n";
echo "   Erreurs potentielles : " . (empty($potential_issues) ? "✅ AUCUNE" : "⚠️ " . count($potential_issues)) . "\n";

echo "\n";
echo "🎯 CONSEIL : Si vous voyez une erreur spécifique, copiez-la et partagez-la pour un diagnostic précis.\n";
echo "\n";
?>
