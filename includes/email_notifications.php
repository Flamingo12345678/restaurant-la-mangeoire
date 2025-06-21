<?php
/**
 * Système de notifications email moderne avec PHPMailer
 * Support SMTP + fallback fonction mail() PHP
 */

// Charger l'autoloader de Composer pour PHPMailer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotifications {
    private $config;
    private $mailer;
    
    public function __construct() {
        // Charger la configuration
        $config_file = __DIR__ . '/../config/email_config.php';
        if (file_exists($config_file)) {
            $this->config = include $config_file;
        } else {
            // Configuration par défaut si pas de fichier config
            $this->config = $this->getDefaultConfig();
        }
        
        $this->setupMailer();
    }
    
    private function getDefaultConfig() {
        return [
            'admin' => [
                'email' => 'la-mangeoire@gmail.com',
                'name' => 'Restaurant La Mangeoire'
            ],
            'from' => [
                'email' => 'noreply@la-mangeoire.com',
                'name' => 'La Mangeoire - Contact'
            ],
            'fallback_php_mail' => true,
            'test_mode' => false
        ];
    }
    
    private function setupMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Configuration SMTP si disponible
            if (isset($this->config['smtp']) || isset($this->config['mailtrap'])) {
                $smtp_config = $this->config['test_mode'] ? 
                    $this->config['mailtrap'] : $this->config['smtp'];
                
                $this->mailer->isSMTP();
                $this->mailer->Host = $smtp_config['host'];
                $this->mailer->SMTPAuth = $smtp_config['auth'];
                $this->mailer->Username = $smtp_config['username'];
                $this->mailer->Password = $smtp_config['password'];
                $this->mailer->SMTPSecure = $smtp_config['encryption'];
                $this->mailer->Port = $smtp_config['port'];
                
                // Debug SMTP (décommenter pour diagnostiquer)
                // $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            // Configuration générale
            $this->mailer->setFrom(
                $this->config['from_email'], 
                $this->config['from_name']
            );
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            // Log l'erreur mais continue
            error_log("Erreur configuration PHPMailer: " . $e->getMessage());
        }
    }
    
    public function sendNewMessageNotification($message_data) {
        try {
            // Créer une nouvelle instance pour ce message
            $mail = clone $this->mailer;
            
            // Destinataire
            $mail->addAddress(
                $this->config['admin_email'], 
                $this->config['admin_name']
            );
            
            // Reply-To vers l'expéditeur du message
            $mail->addReplyTo($message_data['email'], $message_data['nom']);
            
            // Sujet
            $mail->Subject = "🍽️ Nouveau message de contact - La Mangeoire";
            
            // Corps du message en HTML et texte
            $mail->isHTML(true);
            $mail->Body = $this->buildHtmlBody($message_data);
            $mail->AltBody = $this->buildTextBody($message_data);
            
            // Tentative d'envoi avec PHPMailer
            $sent = $mail->send();
            
            if ($sent) {
                $this->logEmailSuccess($message_data);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Erreur PHPMailer: " . $e->getMessage());
            
            // Fallback vers fonction mail() PHP si configuré
            if ($this->config['fallback_to_mail']) {
                return $this->sendWithPhpMail($message_data);
            }
        }
        
        return false;
    }
    
    private function buildHtmlBody($data) {
        $admin_url = $this->getAdminUrl();
        
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #ce1212; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .message-box { background: white; padding: 15px; border-left: 4px solid #ce1212; margin: 10px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #ce1212; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🍽️ Nouveau Message de Contact</h1>
                <p>Restaurant La Mangeoire</p>
            </div>
            
            <div class='content'>
                <p>Un nouveau message a été reçu sur votre site web :</p>
                
                <div class='message-box'>
                    <p><strong>👤 Nom :</strong> " . htmlspecialchars($data['nom']) . "</p>
                    <p><strong>📧 Email :</strong> " . htmlspecialchars($data['email']) . "</p>
                    <p><strong>📝 Objet :</strong> " . htmlspecialchars($data['objet']) . "</p>
                    <p><strong>💬 Message :</strong></p>
                    <div style='background: #fff; padding: 10px; border: 1px solid #ddd;'>
                        " . nl2br(htmlspecialchars($data['message'])) . "
                    </div>
                    <p><strong>📅 Reçu le :</strong> " . date('d/m/Y à H:i') . "</p>
                </div>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$admin_url' class='btn'>🔗 Gérer ce message</a>
                </p>
                
                <p><strong>Actions possibles :</strong></p>
                <ul>
                    <li>Répondre directement en répondant à cet email</li>
                    <li>Accéder à l'interface admin pour marquer comme traité</li>
                    <li>Archiver le message si nécessaire</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p>Notification automatique du système de contact - La Mangeoire</p>
                <p>Ne pas répondre à ce email, utilisez directement l'email du client.</p>
            </div>
        </body>
        </html>";
    }
    
    private function buildTextBody($data) {
        $admin_url = $this->getAdminUrl();
        
        return "
🍽️ NOUVEAU MESSAGE DE CONTACT - LA MANGEOIRE
==============================================

Un nouveau message a été reçu sur votre site web.

📧 DÉTAILS DU MESSAGE :
👤 Nom : {$data['nom']}
📧 Email : {$data['email']}
📝 Objet : {$data['objet']}

💬 Message :
{$data['message']}

📅 Reçu le : " . date('d/m/Y à H:i') . "

🔗 Gérer ce message : $admin_url

ACTIONS POSSIBLES :
- Répondre directement en répondant à cet email
- Accéder à l'interface admin pour marquer comme traité
- Archiver le message si nécessaire

---
Notification automatique du système de contact - La Mangeoire
        ";
    }
    
    private function sendWithPhpMail($message_data) {
        $to = $this->config['admin_email'];
        $subject = "🍽️ Nouveau message de contact - La Mangeoire";
        $message = $this->buildTextBody($message_data);
        
        $headers = [
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $message_data['email'],
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        $sent = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($sent) {
            $this->logEmailSuccess($message_data, 'php_mail');
        } else {
            error_log("Échec envoi email avec fonction mail() PHP");
        }
        
        return $sent;
    }
    
    private function getAdminUrl() {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return "$protocol://$host/admin-messages.php";
    }
    
    private function logEmailSuccess($message_data, $method = 'phpmailer') {
        $log_entry = date('Y-m-d H:i:s') . " - Email envoyé via $method pour message de " . 
                     $message_data['nom'] . " (" . $message_data['email'] . ") - " . 
                     $message_data['objet'] . "\n";
        
        file_put_contents(__DIR__ . '/../logs/email_notifications.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public function logNewMessage($message_data) {
        // Log dans un fichier pour historique
        $log_entry = date('Y-m-d H:i:s') . " - Nouveau message de " . 
                     $message_data['nom'] . " (" . $message_data['email'] . ") - " . 
                     $message_data['objet'] . "\n";
        
        // Créer le dossier logs s'il n'existe pas
        $logs_dir = __DIR__ . '/../logs';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
        
        file_put_contents($logs_dir . '/contact_messages.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Test de la configuration email
     */
    public function testEmailConfiguration() {
        try {
            $test_data = [
                'nom' => 'Test System',
                'email' => 'test@example.com',
                'objet' => 'Test de configuration email',
                'message' => 'Ceci est un test automatique du système de notification email.'
            ];
            
            return $this->sendNewMessageNotification($test_data);
            
        } catch (Exception $e) {
            error_log("Erreur test email: " . $e->getMessage());
            return false;
        }
    }
}

// Utilisation dans vos formulaires de contact
function notifyNewContactMessage($message_data) {
    $notifier = new EmailNotification();
    
    // Envoyer notification email (si configuré)
    $email_sent = $notifier->sendNewMessageNotification($message_data);
    
    // Logger le message
    $notifier->logNewMessage($message_data);
    
    return $email_sent;
}

// Exemple d'intégration dans contact.php :
/*
if ($result) {
    $message_id = $conn->lastInsertId();
    
    // Données du message pour notification
    $message_data = [
        'nom' => $nom,
        'email' => $email,
        'objet' => $objet,
        'message' => $message
    ];
    
    // Envoyer notification
    notifyNewContactMessage($message_data);
    
    $success_message = "Votre message a été envoyé avec succès !";
}
*/
?>
