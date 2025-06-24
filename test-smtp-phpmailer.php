<?php
// Test SMTP avec PHPMailer - Diagnostic complet
echo "=== TEST SMTP COMPLET AVEC PHPMAILER ===\n\n";

// Charger les dépendances
require_once 'vendor/autoload.php';
require_once 'db_connexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    
    echo "✅ PHPMailer chargé avec succès\n";
    
    // Configuration depuis .env
    $smtp_host = getenv('SMTP_HOST');
    $smtp_username = getenv('SMTP_USERNAME');
    $smtp_password = getenv('SMTP_PASSWORD');
    $smtp_port = getenv('SMTP_PORT') ?: 587;
    $from_email = getenv('FROM_EMAIL');
    $admin_email = getenv('ADMIN_EMAIL');
    
    echo "\n=== CONFIGURATION ===\n";
    echo "SMTP Host: $smtp_host\n";
    echo "SMTP Username: $smtp_username\n";
    echo "SMTP Password: " . (empty($smtp_password) ? "❌ VIDE" : "✅ Défini") . "\n";
    echo "SMTP Port: $smtp_port\n";
    echo "From Email: $from_email\n";
    echo "Admin Email: $admin_email\n";
    
    if (empty($smtp_password)) {
        echo "\n❌ ERREUR CRITIQUE: Mot de passe SMTP vide!\n";
        exit(1);
    }
    
    // Créer l'instance PHPMailer
    $mail = new PHPMailer(true);
    
    echo "\n=== CONFIGURATION PHPMAILER ===\n";
    
    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->Port = $smtp_port;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    
    // Activer le debug SMTP
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "SMTP DEBUG: $str\n";
    };
    
    echo "✅ Configuration SMTP appliquée\n";
    
    // Destinataires
    $mail->setFrom($from_email, 'Restaurant La Mangeoire');
    $mail->addAddress($admin_email, 'Admin Restaurant');
    
    // Contenu
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Test SMTP - Restaurant La Mangeoire';
    $mail->Body = '
    <h2>🧪 Test SMTP</h2>
    <p>Ceci est un email de test pour vérifier la configuration SMTP.</p>
    <p><strong>Date:</strong> ' . date('d/m/Y H:i:s') . '</p>
    <p><strong>Serveur:</strong> ' . $smtp_host . '</p>
    <p><strong>Port:</strong> ' . $smtp_port . '</p>
    <p>Si vous recevez cet email, la configuration SMTP fonctionne correctement!</p>
    ';
    
    echo "\n=== ENVOI EMAIL ===\n";
    echo "Tentative d'envoi vers: $admin_email\n";
    echo "Depuis: $from_email\n\n";
    
    // Envoi
    $mail->send();
    
    echo "\n✅ EMAIL ENVOYÉ AVEC SUCCÈS!\n";
    echo "📧 Vérifiez votre boîte mail: $admin_email\n";
    echo "📧 Vérifiez aussi le dossier SPAM/INDÉSIRABLES\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR LORS DE L'ENVOI:\n";
    echo "Message d'erreur: " . $e->getMessage() . "\n";
    echo "Code d'erreur: " . $e->getCode() . "\n";
    
    // Suggestions de correction
    echo "\n=== SUGGESTIONS DE CORRECTION ===\n";
    
    if (strpos($e->getMessage(), 'Authentication failed') !== false) {
        echo "❌ Problème d'authentification Gmail\n";
        echo "Solutions possibles:\n";
        echo "1. Vérifier que le mot de passe d'application Gmail est correct\n";
        echo "2. Activer l'authentification à 2 facteurs sur Gmail\n";
        echo "3. Générer un nouveau mot de passe d'application\n";
    }
    
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "❌ Problème de connexion réseau\n";
        echo "Solutions possibles:\n";
        echo "1. Vérifier la connexion internet\n";
        echo "2. Vérifier les paramètres de pare-feu\n";
        echo "3. Tester avec un autre port (465 pour SSL)\n";
    }
    
    if (strpos($e->getMessage(), 'stream_socket_enable_crypto') !== false) {
        echo "❌ Problème de chiffrement SSL/TLS\n";
        echo "Solutions possibles:\n";
        echo "1. Vérifier les certificats SSL\n";
        echo "2. Essayer avec SMTPSecure = 'ssl' et port 465\n";
    }
}

echo "\n=== GUIDE CONFIGURATION GMAIL ===\n";
echo "Pour configurer Gmail avec PHPMailer:\n";
echo "1. Activer l'authentification à 2 facteurs\n";
echo "2. Aller dans: Compte Google > Sécurité > Mots de passe d'application\n";
echo "3. Générer un mot de passe d'application pour 'Mail'\n";
echo "4. Utiliser ce mot de passe dans SMTP_PASSWORD\n";
echo "5. Utiliser les paramètres:\n";
echo "   - Host: smtp.gmail.com\n";
echo "   - Port: 587\n";
echo "   - Encryption: STARTTLS\n";

?>
