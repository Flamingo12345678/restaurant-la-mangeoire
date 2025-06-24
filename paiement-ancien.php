<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/common.php';
require_once 'includes/payment_manager.php';
require_once 'db_connexion.php';

// Initialiser le gestionnaire de paiements
$paymentManager = new PaymentManager();

// Vérifier les paramètres
$commande_id = isset($_GET['commande']) ? intval($_GET['commande']) : 0;
$type_paiement = isset($_GET['type']) ? $_GET['type'] : '';

if ($commande_id <= 0 || empty($type_paiement)) {
    $_SESSION['message'] = "Paramètres de paiement invalides.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit;
}

// Récupérer les détails de la commande avec client
$stmt = $pdo->prepare("SELECT c.*, cl.ClientID, cl.Email, cl.Nom, cl.Prenom 
                      FROM Commandes c 
                      LEFT JOIN Clients cl ON c.ClientID = cl.ClientID 
                      WHERE c.CommandeID = ?");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    $_SESSION['message'] = "Commande non trouvée.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit;
}

// Vérifier si déjà payée
$stmt = $pdo->prepare("SELECT * FROM Paiements WHERE CommandeID = ? AND Statut = 'completed'");
$stmt->execute([$commande_id]);
$paiement_existant = $stmt->fetch(PDO::FETCH_ASSOC);

if ($paiement_existant) {
    $_SESSION['message'] = "Cette commande a déjà été payée.";
    $_SESSION['message_type'] = "info";
    header("Location: confirmation-commande.php?id=" . $commande_id);
    exit;
}

// Traitement du paiement avec notifications automatiques
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_paiement'])) {
    try {
        $type = $_POST['type_paiement'];
        $result = null;
        
        switch ($type) {
            case 'stripe':
                // Traitement Stripe avec emails automatiques
                $result = $paymentManager->processStripePayment(
                    $commande['MontantTotal'], 
                    'eur', 
                    $_POST['payment_method_id'] ?? 'simulated', 
                    $commande_id, 
                    $commande['ClientID']
                );
                break;
                
            case 'paypal':
                // Traitement PayPal avec emails automatiques
                $result = $paymentManager->processPayPalPayment(
                    $_POST['payment_id'] ?? 'simulated', 
                    $_POST['payer_id'] ?? 'simulated', 
                    $commande_id, 
                    $commande['ClientID'], 
                    $commande['MontantTotal']
                );
                break;
                
            case 'virement':
                // Traitement virement avec emails automatiques
                $reference = 'VIR_' . time() . '_' . $commande_id;
                $result = $paymentManager->processWireTransferPayment(
                    $commande_id, 
                    $commande['ClientID'], 
                    $commande['MontantTotal'], 
                    $reference
                );
                break;
                
            default:
                throw new Exception("Type de paiement non valide");
        }
        
        if ($result && $result['success']) {
            // Mettre à jour le statut de la commande
            $stmt = $pdo->prepare("UPDATE Commandes SET Statut = 'payee' WHERE CommandeID = ?");
            $stmt->execute([$commande_id]);
            
            $_SESSION['message'] = "Paiement traité avec succès ! Vous et notre équipe avez reçu une confirmation par email.";
            $_SESSION['message_type'] = "success";
            header("Location: confirmation-paiement.php?type=" . $type . "&commande=" . $commande_id . "&payment_id=" . $result['payment_id']);
            exit;
        } else {
            throw new Exception($result['error'] ?? "Erreur de traitement du paiement");
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Erreur lors du paiement : " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}

// Informations du type de paiement
$payment_info = match($type_paiement) {
    'stripe' => [
        'title' => 'Paiement par Carte Bancaire (Stripe)',
        'icon' => 'bi-credit-card',
        'description' => 'Paiement sécurisé par carte bancaire'
    ],
    'paypal' => [
        'title' => 'Paiement PayPal',
        'icon' => 'bi-paypal',
        'description' => 'Paiement via votre compte PayPal'
    ],
    'virement' => [
        'title' => 'Virement Bancaire',
        'icon' => 'bi-bank',
        'description' => 'Virement bancaire sécurisé'
    ],
    default => [
        'title' => 'Paiement',
        'icon' => 'bi-credit-card',
        'description' => 'Finaliser votre paiement'
    ]
};

// Récupérer les clés publiques pour le frontend
$public_keys = $paymentManager->getPublicKeys();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Paiement - La Mangeoire</title>
    <link href="assets/img/favcon.jpeg" rel="icon" />
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/css/main.css" rel="stylesheet" />
    <style>
        .payment-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .payment-card {
            max-width: 600px;
            margin: 0 auto;
        }
        .payment-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 20px;
        }
        .secure-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container position-relative d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">La Mangeoire</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="menu.php">Notre Menu</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <section class="payment-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card payment-card">
                            <div class="card-header text-center">
                                <div class="payment-icon">
                                    <i class="<?php echo $payment_info['icon']; ?>"></i>
                                </div>
                                <h3><?php echo $payment_info['title']; ?></h3>
                                <p class="text-muted"><?php echo $payment_info['description']; ?></p>
                                <span class="secure-badge"><i class="bi bi-shield-check"></i> Paiement sécurisé</span>
                            </div>
                            
                            <div class="card-body">
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Résumé de commande -->
                                <div class="order-summary mb-4">
                                    <h5>Résumé de la commande</h5>
                                    <div class="d-flex justify-content-between">
                                        <span>Commande #<?php echo $commande['CommandeID']; ?></span>
                                        <span><?php echo date('d/m/Y', strtotime($commande['DateCommande'])); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Client:</span>
                                        <span><?php echo htmlspecialchars($commande['PrenomClient'] . ' ' . $commande['NomClient']); ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total à payer:</span>
                                        <span><?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> €</span>
                                    </div>
                                </div>
                                
                                <!-- Formulaire de paiement -->
                                <form method="POST" id="payment-form">
                                    <input type="hidden" name="type_paiement" value="<?php echo htmlspecialchars($type_paiement); ?>">
                                    
                                    <?php if ($type_paiement === 'stripe'): ?>
                                        <div class="alert alert-info">
                                            <i class="bi bi-shield-check"></i> <strong>Paiement sécurisé par Stripe</strong><br>
                                            Vos données bancaires sont protégées et ne transitent pas par nos serveurs.
                                        </div>
                                        <div class="mb-3">
                                            <label for="card_number" class="form-label">Numéro de carte</label>
                                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                                   placeholder="1234 5678 9012 3456" maxlength="19" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry" class="form-label">Date d'expiration</label>
                                                <input type="text" class="form-control" id="expiry" name="expiry" 
                                                       placeholder="MM/AA" maxlength="5" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cvv" class="form-label">CVV</label>
                                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                                       placeholder="123" maxlength="4" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="card_name" class="form-label">Nom sur la carte</label>
                                            <input type="text" class="form-control" id="card_name" name="card_name" 
                                                   value="<?php echo htmlspecialchars(($commande['Prenom'] ?? '') . ' ' . ($commande['Nom'] ?? '')); ?>" required>
                                        </div>
                                        
                                    <?php elseif ($type_paiement === 'paypal'): ?>
                                        <div class="alert alert-info">
                                            <i class="bi bi-paypal text-primary"></i> <strong>Paiement PayPal</strong><br>
                                            Vous serez redirigé vers PayPal pour finaliser le paiement en toute sécurité.
                                        </div>
                                        <div class="text-center mb-3">
                                            <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-200px.png" 
                                                 alt="PayPal" style="max-height: 50px;">
                                        </div>
                                        
                                    <?php elseif ($type_paiement === 'virement'): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> <strong>Paiement par virement</strong><br>
                                            Votre commande sera confirmée après réception du virement.
                                        </div>
                                        <div class="card bg-light mb-3">
                                            <div class="card-header">
                                                <strong><i class="bi bi-bank"></i> Informations bancaires</strong>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-2"><strong>IBAN:</strong> FR76 1234 5678 9012 3456 7890 123</p>
                                                <p class="mb-2"><strong>BIC:</strong> MANGEOIRE</p>
                                                <p class="mb-2"><strong>Bénéficiaire:</strong> Restaurant La Mangeoire</p>
                                                <p class="mb-0"><strong>Référence obligatoire:</strong> <span class="text-primary">CMD-<?php echo $commande['CommandeID']; ?></span></p>
                                            </div>
                                        </div>
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="bi bi-info-circle"></i> 
                                                <strong>Important :</strong> N'oubliez pas d'indiquer la référence CMD-<?php echo $commande['CommandeID']; ?> 
                                                lors de votre virement. Vous recevrez un email de confirmation dès réception.
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="confirmer_paiement" class="btn btn-success btn-lg">
                                            <i class="bi bi-check-circle"></i> 
                                            <?php if ($type_paiement === 'virement'): ?>
                                                Confirmer la commande (<?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> €)
                                            <?php else: ?>
                                                Payer <?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> €
                                            <?php endif; ?>
                                        </button>
                                        <a href="confirmation-commande.php?id=<?php echo $commande_id; ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Retour à la commande
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formatage automatique du numéro de carte
        document.getElementById('card_number')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
        
        // Formatage de la date d'expiration
        document.getElementById('expiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0,2) + '/' + value.substring(2,4);
            }
            e.target.value = value;
        });
        
        // Validation CVV
        document.getElementById('cvv')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
