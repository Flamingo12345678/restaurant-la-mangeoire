<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - La Mangeoire</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .contact-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .contact-header {
            background: linear-gradient(135deg, #ce1212 0%, #e74c3c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .contact-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .contact-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .contact-form {
            padding: 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating label {
            color: #666;
        }
        
        .form-control:focus {
            border-color: #ce1212;
            box-shadow: 0 0 0 0.2rem rgba(206, 18, 18, 0.25);
        }
        
        .btn-contact {
            background: linear-gradient(135deg, #ce1212 0%, #e74c3c 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(206, 18, 18, 0.3);
            color: white;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="contact-container">
            <div class="contact-header">
                <h1><i class="bi bi-envelope"></i> Contactez-nous</h1>
                <p>Nous sommes là pour vous aider</p>
            </div>
            
            <div class="contact-form">
                <?php
                // Démarrer la session seulement si elle n'est pas déjà active
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                require_once 'db_connexion.php';
                require_once 'includes/email_notifications.php';
                
                $success_message = '';
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
                            // Insertion en base de données (table Messages ou similaire)
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
                                $message_id = $conn->lastInsertId();
                                $success_message = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.";
                                
                                // Envoi de notification email à l'admin
                                try {
                                    $emailNotification = new EmailNotification();
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
                                
                                // Réinitialiser les champs
                                $_POST = [];
                            } else {
                                $error_message = "Erreur lors de l'envoi du message. Veuillez réessayer.";
                            }
                        } catch (Exception $e) {
                            // Log de l'erreur pour diagnostic
                            error_log("Erreur formulaire contact: " . $e->getMessage());
                            $error_message = "Une erreur s'est produite lors de l'envoi de votre message. Veuillez réessayer.";
                        }
                    }
                }
                ?>
                
                <?php if ($success_message): ?>
                    <div class="success-message">
                        <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contactForm">
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control" 
                               id="name" 
                               name="name" 
                               placeholder="Votre Nom"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               required>
                        <label for="name">Votre Nom</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Votre Email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                        <label for="email">Votre Email</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control" 
                               id="subject" 
                               name="subject" 
                               placeholder="Objet"
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                               required>
                        <label for="subject">Objet</label>
                    </div>
                    
                    <div class="form-floating">
                        <textarea class="form-control" 
                                  id="message" 
                                  name="message" 
                                  placeholder="Message"
                                  style="height: 150px"
                                  required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <label for="message">Message</label>
                    </div>
                    
                    <button type="submit" class="btn btn-contact">
                        <i class="bi bi-send"></i> Envoyer le Message
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à l'accueil
                    </a>
                </div>
                
                <!-- Informations de contact -->
                <div class="row mt-4 pt-4 border-top">
                    <div class="col-md-6 text-center mb-3">
                        <h6><i class="bi bi-telephone"></i> Téléphone</h6>
                        <p class="text-muted mb-0">+237 6 96 56 85 20</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <h6><i class="bi bi-envelope"></i> Email</h6>
                        <p class="text-muted mb-0">la-mangeoire@gmail.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const nameInput = document.getElementById('name');
            
            // Auto-focus sur le premier champ
            nameInput.focus();
            
            // Animation de soumission
            form.addEventListener('submit', function(e) {
                // Validation finale
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }
            });
        });
    </script>
</body>
</html>
