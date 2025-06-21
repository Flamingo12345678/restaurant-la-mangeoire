<?php
// Handler pour le formulaire de contact de l'index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db_connexion.php';
require_once '../includes/email_notifications.php';

$success = false;
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim(strip_tags($_POST['name'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $objet = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));
    
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
            $stmt = $conn->prepare("
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
                $success = true;
                $_SESSION['contact_success'] = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.";
                
                // Envoi de notification email à l'admin
                try {
                    $emailNotification = new EmailNotifications();
                    $message_data = [
                        'nom' => $nom,
                        'email' => $email,
                        'objet' => $objet,
                        'message' => $message
                    ];
                    $emailNotification->sendNewMessageNotification($message_data);
                } catch (Exception $e) {
                    // Log l'erreur mais ne bloque pas le processus
                    error_log("Erreur notification email: " . $e->getMessage());
                }
            } else {
                $error_message = "Erreur lors de l'envoi du message. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            error_log("Erreur formulaire contact index: " . $e->getMessage());
            $error_message = "Une erreur s'est produite lors de l'envoi de votre message. Veuillez réessayer.";
        }
    }
}

// Redirection vers l'index avec message
if ($success) {
    header('Location: ../index.php#contact');
    exit();
} else {
    $_SESSION['contact_error'] = $error_message;
    header('Location: ../index.php#contact');
    exit();
}
?>
