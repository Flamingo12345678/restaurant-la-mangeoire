<?php
// Démarrer la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connexion.php';

$success_message = '';
$error_message = '';

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error_message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Adresse email invalide.";
    } else {
        try {
            // Insérer le message dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO Messages (Nom, Email, Sujet, Message, DateEnvoi) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nom, $email, $sujet, $message]);
            
            $success_message = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.";
            
        } catch (PDOException $e) {
            error_log("Erreur insertion message: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors de l'envoi de votre message. Veuillez réessayer.";
        }
    }
}
?>

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
        
        .container {
            max-width: 800px;
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .contact-header h1 {
            color: #ce1212;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .contact-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .contact-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .form-control {
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #ce1212;
            box-shadow: 0 0 0 0.2rem rgba(206, 18, 18, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ce1212 0%, #e74c3c 100%);
            border: none;
            padding: 15px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(206, 18, 18, 0.3);
        }
        
        .contact-info {
            background: #ce1212;
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .contact-info h3 {
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .contact-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .contact-info-item i {
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Bouton retour -->
        <a href="index.php" class="btn-back">
            <i class="bi bi-arrow-left me-2"></i>
            Retour à l'accueil
        </a>
        
        <!-- En-tête -->
        <div class="contact-header">
            <h1><i class="bi bi-envelope-heart me-2"></i>Contactez-nous</h1>
            <p>Nous sommes là pour vous écouter ! N'hésitez pas à nous faire part de vos questions, suggestions ou commentaires.</p>
        </div>
        
        <!-- Messages de feedback -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire de contact -->
        <div class="contact-card">
            <h3 class="mb-4"><i class="bi bi-chat-dots me-2"></i>Envoyez-nous un message</h3>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom" class="form-label">Nom complet *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required 
                               value="<?= isset($nom) ? htmlspecialchars($nom) : '' ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="sujet" class="form-label">Sujet *</label>
                    <input type="text" class="form-control" id="sujet" name="sujet" required
                           value="<?= isset($sujet) ? htmlspecialchars($sujet) : '' ?>">
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message *</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required><?= isset($message) ? htmlspecialchars($message) : '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>Envoyer le message
                </button>
            </form>
        </div>
        
        <!-- Informations de contact -->
        <div class="contact-info">
            <h3><i class="bi bi-info-circle me-2"></i>Nos coordonnées</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-info-item">
                        <i class="bi bi-geo-alt-fill"></i>
                        <div>
                            <strong>Adresse</strong><br>
                            123 Rue de la Gastronomie<br>
                            75001 Paris, France
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="bi bi-telephone-fill"></i>
                        <div>
                            <strong>Téléphone</strong><br>
                            +33 1 23 45 67 89
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="contact-info-item">
                        <i class="bi bi-envelope-fill"></i>
                        <div>
                            <strong>Email</strong><br>
                            contact@lamangeoire.fr
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="bi bi-clock-fill"></i>
                        <div>
                            <strong>Horaires</strong><br>
                            Lun-Dim : 11h30 - 23h00
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
