<?php
// Démarrer la session avant tout output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connexion.php';
require_once 'includes/common.php';
require_once 'includes/email_notifications.php';

$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim(strip_tags($_POST['nom'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $telephone = trim(strip_tags($_POST['telephone'] ?? ''));
    $nombre_personnes = (int)($_POST['nombre_personnes'] ?? 0);
    $date_reservation = $_POST['date_reservation'] ?? '';
    $heure_reservation = $_POST['heure_reservation'] ?? '';
    $message = trim(strip_tags($_POST['message'] ?? ''));
    
    // Validation
    if (empty($nom)) {
        $error_message = "Le nom est requis.";
    } elseif (!$email) {
        $error_message = "Un email valide est requis.";
    } elseif (empty($telephone)) {
        $error_message = "Le téléphone est requis.";
    } elseif ($nombre_personnes < 1 || $nombre_personnes > 20) {
        $error_message = "Le nombre de personnes doit être entre 1 et 20.";
    } elseif (empty($date_reservation)) {
        $error_message = "La date de réservation est requise.";
    } elseif (empty($heure_reservation)) {
        $error_message = "L'heure de réservation est requise.";
    } else {
        try {
            // Insertion en base de données
            $stmt = $conn->prepare("
                INSERT INTO reservations (nom, email, telephone, nombre_personnes, date_reservation, heure_reservation, message, date_creation, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'En attente')
            ");
            
            $result = $stmt->execute([
                $nom,
                $email,
                $telephone,
                $nombre_personnes,
                $date_reservation,
                $heure_reservation,
                $message
            ]);
            
            if ($result) {
                $success_message = "Votre réservation a été enregistrée avec succès ! Nous vous confirmerons par email dans les plus brefs délais.";
                
                // Envoi de notification email à l'admin (si disponible)
                try {
                    if (class_exists('EmailNotifications')) {
                        $emailNotification = new EmailNotifications();
                        $reservation_data = [
                            'nom' => $nom,
                            'email' => $email,
                            'telephone' => $telephone,
                            'nombre_personnes' => $nombre_personnes,
                            'date_reservation' => $date_reservation,
                            'heure_reservation' => $heure_reservation,
                            'message' => $message
                        ];
                        
                        // On pourrait ajouter une méthode sendNewReservationNotification()
                        // Pour l'instant, on utilise la notification de contact
                        $emailNotification->sendNewMessageNotification([
                            'nom' => $nom,
                            'email' => $email,
                            'objet' => 'Nouvelle réservation',
                            'message' => "Nouvelle réservation de $nom pour $nombre_personnes personne(s) le $date_reservation à $heure_reservation. Téléphone: $telephone. Message: $message"
                        ]);
                    }
                } catch (Exception $e) {
                    // Log l'erreur mais continue (la réservation est déjà enregistrée)
                    error_log("Erreur envoi email réservation: " . $e->getMessage());
                }
                
                // Réinitialiser les champs
                $nom = $email = $telephone = $date_reservation = $heure_reservation = $message = '';
                $nombre_personnes = 2;
            } else {
                $error_message = "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver une Table - La Mangeoire</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .reservation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .reservation-header {
            background: linear-gradient(135deg, #ce1212 0%, #e74c3c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .reservation-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 600;
        }
        
        .reservation-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .reservation-form {
            padding: 40px 30px;
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
        
        .btn-reservation {
            background: linear-gradient(135deg, #ce1212 0%, #e74c3c 100%);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-reservation:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(206, 18, 18, 0.3);
            color: white;
        }
        
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #ce1212;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reservation-container">
            <div class="reservation-header">
                <h1><i class="bi bi-calendar-check"></i> Réservation de Table</h1>
                <p>Réservez votre table en quelques clics</p>
            </div>
            
            <div class="reservation-form">
                
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
                
                <form method="POST" action="" id="reservationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="nom" 
                                       name="nom" 
                                       placeholder="Votre nom complet"
                                       value="<?php echo htmlspecialchars($nom ?? ''); ?>"
                                       required>
                                <label for="nom"><i class="bi bi-person"></i> Nom complet</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="votre@email.com"
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       required>
                                <label for="email"><i class="bi bi-envelope"></i> Email</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="tel" 
                                       class="form-control" 
                                       id="telephone" 
                                       name="telephone" 
                                       placeholder="Votre numéro de téléphone"
                                       value="<?php echo htmlspecialchars($telephone ?? ''); ?>"
                                       required>
                                <label for="telephone"><i class="bi bi-telephone"></i> Téléphone</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" 
                                       class="form-control" 
                                       id="nombre_personnes" 
                                       name="nombre_personnes" 
                                       min="1" 
                                       max="20"
                                       placeholder="Nombre de personnes"
                                       value="<?php echo htmlspecialchars($nombre_personnes ?? 2); ?>"
                                       required>
                                <label for="nombre_personnes"><i class="bi bi-people"></i> Nombre de personnes</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" 
                                       class="form-control" 
                                       id="date_reservation" 
                                       name="date_reservation" 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($date_reservation ?? ''); ?>"
                                       required>
                                <label for="date_reservation"><i class="bi bi-calendar"></i> Date</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="time" 
                                       class="form-control" 
                                       id="heure_reservation" 
                                       name="heure_reservation" 
                                       min="11:00" 
                                       max="23:00"
                                       value="<?php echo htmlspecialchars($heure_reservation ?? ''); ?>"
                                       required>
                                <label for="heure_reservation"><i class="bi bi-clock"></i> Heure</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating">
                        <textarea class="form-control" 
                                  id="message" 
                                  name="message" 
                                  placeholder="Message optionnel"
                                  style="height: 120px"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        <label for="message"><i class="bi bi-chat-text"></i> Message (optionnel)</label>
                    </div>
                    
                    <div class="info-card">
                        <h6><i class="bi bi-info-circle"></i> Informations importantes</h6>
                        <ul class="mb-0">
                            <li>Horaires d'ouverture : 11h00 - 23h00 (fermé le dimanche)</li>
                            <li>Les réservations sont confirmées par téléphone</li>
                            <li>En cas d'empêchement, merci de nous prévenir</li>
                        </ul>
                    </div>
                    
                    <div class="loading" id="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Traitement de votre réservation...</p>
                    </div>
                    
                    <button type="submit" class="btn btn-reservation" id="submitBtn">
                        <i class="bi bi-calendar-check"></i> Réserver ma table
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reservationForm');
            const loading = document.getElementById('loading');
            const submitBtn = document.getElementById('submitBtn');
            
            // Validation en temps réel
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const dateInput = document.getElementById('date');
            const timeInput = document.getElementById('time');
            const peopleInput = document.getElementById('people');
            
            // Formatage du téléphone
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = value.replace(/(\d{3})(\d{2})(\d{2})(\d{2})(\d{2})/, '+237 $1 $2 $3 $4 $5');
                }
                e.target.value = value;
            });
            
            // Validation de la date
            dateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    this.setCustomValidity('La date doit être dans le futur');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Animation de soumission
            form.addEventListener('submit', function(e) {
                // Validation finale
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }
                
                // Animation de chargement
                loading.classList.add('show');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement...';
            });
            
            // Auto-focus sur le premier champ
            nameInput.focus();
        });
    </script>
</body>
</html>
