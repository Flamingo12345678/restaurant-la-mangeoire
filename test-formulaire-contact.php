<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Formulaire Contact - La Mangeoire</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center mb-0">🧪 Test du Formulaire de Contact</h2>
                        <p class="text-center text-muted mb-0">Vérification du système d'email</p>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }

                        // Afficher les messages de session
                        if (isset($_SESSION['contact_success'])) {
                            echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . $_SESSION['contact_success'] . '</div>';
                            unset($_SESSION['contact_success']);
                        }
                        if (isset($_SESSION['contact_error'])) {
                            echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . $_SESSION['contact_error'] . '</div>';
                            unset($_SESSION['contact_error']);
                        }
                        ?>
                        
                        <form action="forms/contact.php" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom complet</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="Test Utilisateur" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="test@example.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Objet</label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="Test du système de notification email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required>Ceci est un message de test pour vérifier que :

1. Le formulaire de contact fonctionne correctement
2. Les données sont bien enregistrées en base de données  
3. L'email de notification est envoyé à l'administrateur
4. Le système d'email SMTP avec Gmail est opérationnel

Si vous recevez cet email, cela signifie que tout fonctionne parfaitement ! ✅

Test effectué le <?php echo date('d/m/Y à H:i'); ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    📧 Envoyer le Message de Test
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>📋 Instructions :</h5>
                                <ol>
                                    <li>Cliquez sur "Envoyer le Message de Test"</li>
                                    <li>Vérifiez votre boîte mail : <strong>ernestyombi20@gmail.com</strong></li>
                                    <li>Regardez aussi dans le dossier "Spam/Indésirables"</li>
                                    <li>Consultez le panel admin : <a href="admin-messages.php" target="_blank">Messages</a></li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h5>🔧 Diagnostic :</h5>
                                <ul>
                                    <li>✅ SMTP configuré (Gmail)</li>
                                    <li>✅ Mode production activé</li>
                                    <li>✅ PHPMailer installé</li>
                                    <li>✅ Erreurs de syntaxe corrigées</li>
                                </ul>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-secondary">← Retour à l'accueil</a>
                    <a href="admin-messages.php" class="btn btn-info">Voir les messages admin</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
