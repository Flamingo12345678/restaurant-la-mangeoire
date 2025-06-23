<?php
/**
 * Gestionnaire d'emails avec support SMTP
 * Utilise les configurations du fichier .env
 */

// Charger l'autoloader Composer pour PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailManager {
    private $smtp_host;
    private $smtp_username; 
    private $smtp_password;
    private $smtp_port;
    private $smtp_encryption;
    private $from_email;
    private $from_name;
    private $admin_email;
    private $admin_name;
    private $test_mode;
    private $debug;

    public function __construct() {
        // S'assurer que le fichier .env est chargé
        $this->loadEnvFile();
        
        // Charger les variables d'environnement
        $this->smtp_host = getEnvVar('SMTP_HOST');
        $this->smtp_username = getEnvVar('SMTP_USERNAME');
        $this->smtp_password = getEnvVar('SMTP_PASSWORD');
        $this->smtp_port = getEnvVar('SMTP_PORT', 587);
        $this->smtp_encryption = getEnvVar('SMTP_ENCRYPTION', 'tls');
        
        $this->from_email = getEnvVar('FROM_EMAIL');
        $this->from_name = getEnvVar('FROM_NAME', 'Restaurant La Mangeoire');
        $this->admin_email = getEnvVar('ADMIN_EMAIL');
        $this->admin_name = getEnvVar('ADMIN_NAME', 'Restaurant La Mangeoire');
        
        $this->test_mode = getEnvVar('EMAIL_TEST_MODE', 'false') === 'true';
        $this->debug = getEnvVar('EMAIL_DEBUG', 'false') === 'true';
    }

    /**
     * Charge le fichier .env si ce n'est pas déjà fait
     */
    private function loadEnvFile() {
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            return false;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Diviser la ligne en clé=valeur
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, "\" \t\n\r\0\x0B");
                
                // Définir la variable d'environnement seulement si pas déjà définie
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        return true;
    }

    /**
     * Envoie un email de notification pour un nouveau message de contact
     */
    public function sendContactNotification($nom, $email, $sujet, $message) {
        $subject = "Nouveau message de contact - $sujet";
        
        $html_body = $this->buildContactEmailTemplate($nom, $email, $sujet, $message);
        
        return $this->sendEmail(
            $this->admin_email,
            $this->admin_name,
            $subject,
            $html_body
        );
    }

    /**
     * Envoie un email de confirmation au client
     */
    public function sendContactConfirmation($nom, $email, $sujet) {
        $subject = "Confirmation de réception - $sujet";
        
        $html_body = $this->buildConfirmationEmailTemplate($nom, $sujet);
        
        return $this->sendEmail(
            $email,
            $nom,
            $subject,
            $html_body
        );
    }

    /**
     * Envoie un email de notification de paiement à l'admin
     */
    public function sendPaymentNotificationToAdmin($payment_info, $client_info) {
        $subject = "Nouveau paiement reçu - " . number_format($payment_info['Montant'], 2) . "€";
        
        $html_body = $this->buildPaymentNotificationTemplate($payment_info, $client_info);
        
        return $this->sendEmail(
            $this->admin_email,
            $this->admin_name,
            $subject,
            $html_body
        );
    }

    /**
     * Envoie un email de confirmation de paiement au client
     */
    public function sendPaymentConfirmationToClient($payment_info, $client_info) {
        $subject = "Confirmation de paiement - Restaurant La Mangeoire";
        
        $html_body = $this->buildPaymentConfirmationTemplate($payment_info, $client_info);
        
        return $this->sendEmail(
            $client_info['Email'],
            $client_info['Prenom'] . ' ' . $client_info['Nom'],
            $subject,
            $html_body
        );
    }

    /**
     * Envoi d'email d'alerte système
     */
    public function sendAlert($subject, $message, $level = 'WARNING') {
        // Créer le corps HTML de l'alerte
        $html_body = $this->buildAlertBody($subject, $message, $level);
        
        // Envoyer à l'administrateur
        return $this->sendEmail(
            $this->admin_email,
            $this->admin_name,
            "[ALERTE SYSTÈME] $subject",
            $html_body
        );
    }

    /**
     * Construction du corps HTML pour les alertes
     */
    private function buildAlertBody($subject, $message, $level) {
        $color = match($level) {
            'CRITICAL' => '#dc3545',
            'ERROR' => '#fd7e14',
            'WARNING' => '#ffc107',
            'INFO' => '#0dcaf0',
            default => '#6c757d'
        };
        
        $icon = match($level) {
            'CRITICAL' => '🚨',
            'ERROR' => '❌',
            'WARNING' => '⚠️',
            'INFO' => 'ℹ️',
            default => '📢'
        };
        
        $timestamp = date('Y-m-d H:i:s');
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='background: $color; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 24px;'>$icon Alerte Système</h1>
                    <p style='margin: 5px 0 0 0; opacity: 0.9;'>Niveau: $level</p>
                </div>
                
                <div style='padding: 30px;'>
                    <h2 style='color: #333; margin-top: 0;'>$subject</h2>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid $color;'>
                        <p style='margin: 0; color: #666; white-space: pre-line;'>$message</p>
                    </div>
                    
                    <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;'>
                        <p style='margin: 0; color: #999; font-size: 14px;'>
                            <strong>Horodatage:</strong> $timestamp<br>
                            <strong>Serveur:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "<br>
                            <strong>Système:</strong> Restaurant La Mangeoire
                        </p>
                    </div>
                </div>
            </div>
        </div>";
    }

    /**
     * Méthode principale d'envoi d'email
     */
    private function sendEmail($to_email, $to_name, $subject, $html_body) {
        if ($this->debug) {
            error_log("EmailManager: Tentative d'envoi email vers $to_email");
        }

        // En mode test, simuler l'envoi
        if ($this->test_mode) {
            error_log("EmailManager [TEST MODE]: Email simulé vers $to_email - $subject");
            return true;
        }

        // Vérifier les configurations SMTP
        if (empty($this->smtp_host) || empty($this->smtp_username) || empty($this->smtp_password)) {
            error_log("EmailManager: Configuration SMTP incomplète");
            return false;
        }

        // Construire les headers
        $headers = $this->buildHeaders();
        
        // Corps de l'email
        $body = $this->buildEmailBody($html_body);

        // Utiliser PHPMailer pour un vrai support SMTP
        return $this->sendWithPHPMailer($to_email, $to_name, $subject, $html_body);
    }

    /**
     * Envoi avec PHPMailer (recommandé)
     */
    private function sendWithPHPMailer($to_email, $to_name, $subject, $html_body) {
        try {
            $mail = new PHPMailer(true);

            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->Port = $this->smtp_port;
            
            if ($this->debug) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            if ($this->smtp_encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Destinataires
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($this->from_email, $this->from_name);

            // Contenu
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $html_body;

            $mail->send();
            
            if ($this->debug) {
                error_log("EmailManager: Email envoyé avec succès via PHPMailer vers $to_email");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("EmailManager: Erreur PHPMailer - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoi avec mail() natif PHP (fallback)
     */
    private function sendWithNativeMail($to_email, $subject, $body, $headers) {
        try {
            $result = mail($to_email, $subject, $body, $headers);
            
            if ($result) {
                if ($this->debug) {
                    error_log("EmailManager: Email envoyé avec succès via mail() vers $to_email");
                }
                return true;
            } else {
                error_log("EmailManager: Échec envoi via mail()");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EmailManager: Erreur mail() - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construit les headers pour mail()
     */
    private function buildHeaders() {
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        return implode("\r\n", $headers);
    }

    /**
     * Corps de l'email pour mail()
     */
    private function buildEmailBody($html_body) {
        return $html_body;
    }

    /**
     * Template HTML pour notification de contact
     */
    private function buildContactEmailTemplate($nom, $email, $sujet, $message) {
        $date = date('d/m/Y à H:i');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouveau message de contact</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #ce1212; text-align: center;'>🍽️ Nouveau message de contact</h2>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #333;'>Détails du message</h3>
                    <p><strong>Nom :</strong> $nom</p>
                    <p><strong>Email :</strong> $email</p>
                    <p><strong>Sujet :</strong> $sujet</p>
                    <p><strong>Date :</strong> $date</p>
                </div>
                
                <div style='background: #fff; padding: 15px; border: 1px solid #eee; border-radius: 5px;'>
                    <h4 style='color: #333; margin-top: 0;'>Message :</h4>
                    <p style='white-space: pre-wrap;'>$message</p>
                </div>
                
                <div style='text-align: center; margin-top: 20px; padding: 15px; background: #f1f1f1; border-radius: 5px;'>
                    <p style='margin: 0; font-size: 12px; color: #666;'>
                        Restaurant La Mangeoire - Système de gestion des messages<br>
                        Répondez directement à l'adresse : $email
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Template HTML pour confirmation client
     */
    private function buildConfirmationEmailTemplate($nom, $sujet) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmation de réception</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #ce1212; text-align: center;'>🍽️ Restaurant La Mangeoire</h2>
                
                <p>Bonjour $nom,</p>
                
                <p>Nous avons bien reçu votre message concernant : <strong>$sujet</strong></p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0;'>
                        <strong>✅ Votre message a été enregistré</strong><br>
                        Notre équipe vous répondra dans les plus brefs délais.
                    </p>
                </div>
                
                <p>Merci de votre confiance !</p>
                
                <div style='text-align: center; margin-top: 30px; padding: 15px; background: #ce1212; color: white; border-radius: 5px;'>
                    <h3 style='margin: 0 0 10px 0;'>Restaurant La Mangeoire</h3>
                    <p style='margin: 0; font-size: 14px;'>
                        123 Rue de la Gastronomie, 75001 Paris<br>
                        Tél : +33 1 23 45 67 89 | Email : contact@lamangeoire.fr
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Template HTML pour notification de paiement admin
     */
    private function buildPaymentNotificationTemplate($payment_info, $client_info) {
        $date = date('d/m/Y à H:i');
        $montant = number_format($payment_info['Montant'], 2);
        $mode_paiement = ucfirst($payment_info['ModePaiement']);
        $nom_client = $client_info['Prenom'] . ' ' . $client_info['Nom'];
        $statut = ucfirst($payment_info['Statut']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouveau paiement reçu</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #ce1212; text-align: center;'>💳 Nouveau paiement reçu</h2>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>
                    <h3 style='margin-top: 0; color: #333;'>Détails du paiement</h3>
                    <p><strong>Montant :</strong> {$montant}€</p>
                    <p><strong>Mode de paiement :</strong> {$mode_paiement}</p>
                    <p><strong>Statut :</strong> {$statut}</p>
                    <p><strong>Transaction ID :</strong> " . ($payment_info['TransactionID'] ?? 'N/A') . "</p>
                    <p><strong>Date :</strong> $date</p>
                </div>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #333;'>Informations client</h3>
                    <p><strong>Nom :</strong> {$nom_client}</p>
                    <p><strong>Email :</strong> " . $client_info['Email'] . "</p>
                    <p><strong>Téléphone :</strong> " . ($client_info['Telephone'] ?? 'N/A') . "</p>
                </div>
                
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #333;'>Actions à effectuer :</h4>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li>Vérifier les détails de la commande</li>
                        <li>Préparer la commande si statut 'completed'</li>
                        <li>Contacter le client si nécessaire</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin-top: 20px; padding: 15px; background: #f1f1f1; border-radius: 5px;'>
                    <p style='margin: 0; font-size: 12px; color: #666;'>
                        Restaurant La Mangeoire - Notification automatique de paiement<br>
                        Pour gérer les commandes : <a href='http://localhost:8000/admin-reservations.php'>Interface Admin</a>
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Template HTML pour confirmation de paiement client
     */
    private function buildPaymentConfirmationTemplate($payment_info, $client_info) {
        $montant = number_format($payment_info['Montant'], 2);
        $mode_paiement = ucfirst($payment_info['ModePaiement']);
        $nom_client = $client_info['Prenom'];
        $statut = ucfirst($payment_info['Statut']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmation de paiement</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #ce1212; text-align: center;'>🍽️ Restaurant La Mangeoire</h2>
                
                <p>Bonjour {$nom_client},</p>
                
                <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; border-left: 4px solid #28a745;'>
                    <h3 style='margin-top: 0; color: #28a745;'>✅ Paiement confirmé !</h3>
                    <p style='font-size: 18px; margin: 10px 0;'><strong>{$montant}€</strong></p>
                    <p style='margin: 0;'>Payé par {$mode_paiement}</p>
                </div>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #333;'>Détails de votre paiement</h4>
                    <p><strong>Statut :</strong> {$statut}</p>
                    <p><strong>Référence :</strong> " . ($payment_info['TransactionID'] ?? 'N/A') . "</p>
                    <p><strong>Date :</strong> " . date('d/m/Y à H:i') . "</p>
                </div>
                
                " . ($statut === 'Completed' ? "
                <div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #bee5eb;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>📋 Prochaines étapes</h4>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li>Votre commande est en cours de préparation</li>
                        <li>Vous recevrez une notification lors de la livraison/du retrait</li>
                        <li>Conservez cet email comme preuve de paiement</li>
                    </ul>
                </div>
                " : "
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>⏳ Paiement en attente</h4>
                    <p style='margin: 0;'>Nous avons bien reçu votre demande de paiement. Nous vous confirmerons dès réception du virement.</p>
                </div>
                ") . "
                
                <p>Merci pour votre confiance !</p>
                
                <div style='text-align: center; margin-top: 30px; padding: 15px; background: #ce1212; color: white; border-radius: 5px;'>
                    <h3 style='margin: 0 0 10px 0;'>Restaurant La Mangeoire</h3>
                    <p style='margin: 0; font-size: 14px;'>
                        123 Rue de la Gastronomie, 75001 Paris<br>
                        Tél : +33 1 23 45 67 89 | Email : contact@lamangeoire.fr<br>
                        <a href='http://localhost:8000/mon-compte.php' style='color: white;'>Voir mes commandes</a>
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Test de la configuration email
     */
    public function testConfiguration() {
        $config = [
            'smtp_host' => $this->smtp_host,
            'smtp_username' => $this->smtp_username,
            'smtp_password' => !empty($this->smtp_password) ? '***configuré***' : 'manquant',
            'smtp_port' => $this->smtp_port,
            'smtp_encryption' => $this->smtp_encryption,
            'from_email' => $this->from_email,
            'admin_email' => $this->admin_email,
            'test_mode' => $this->test_mode ? 'Activé' : 'Désactivé',
            'debug' => $this->debug ? 'Activé' : 'Désactivé'
        ];
        
        return $config;
    }
}

// Fonction helper pour les variables d'environnement
if (!function_exists('getEnvVar')) {
    function getEnvVar($key, $default = '') {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

?>
