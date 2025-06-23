<?php
session_start();
require_once 'includes/payment_manager.php';
require_once 'db_connexion.php';

// Vérifier les paramètres
$type_paiement = $_GET['type'] ?? '';
$commande_id = $_GET['commande'] ?? '';

if (!$type_paiement || !$commande_id) {
    $_SESSION['message'] = "Paramètres de paiement manquants.";
    $_SESSION['message_type'] = "error";
    header('Location: menu.php');
    exit;
}

// Initialiser le gestionnaire de paiements
$paymentManager = new PaymentManager();

// Récupérer les informations de la commande
try {
    $stmt = $pdo->prepare("SELECT * FROM Commandes WHERE CommandeID = ?");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commande) {
        throw new Exception("Commande introuvable");
    }
} catch (Exception $e) {
    $_SESSION['message'] = "Erreur lors de la récupération de la commande.";
    $_SESSION['message_type'] = "error";
    header('Location: menu.php');
    exit;
}

// Vérifier si déjà payée
$stmt = $pdo->prepare("SELECT * FROM Paiements WHERE CommandeID = ? AND Statut = 'Confirme'");
$stmt->execute([$commande_id]);
$paiement_existant = $stmt->fetch(PDO::FETCH_ASSOC);

if ($paiement_existant) {
    $_SESSION['message'] = "Cette commande a déjà été payée.";
    $_SESSION['message_type'] = "info";
    header('Location: confirmation-paiement.php?commande=' . $commande_id);
    exit;
}

// Récupérer les clés publiques
$public_keys = $paymentManager->getPublicKeys();

// Informations du type de paiement
$payment_info = match($type_paiement) {
    'stripe' => [
        'title' => 'Paiement par Carte Bancaire (Stripe)',
        'icon' => 'bi-credit-card',
        'description' => 'Paiement sécurisé par carte bancaire',
        'color' => '#007bff'
    ],
    'paypal' => [
        'title' => 'Paiement PayPal',
        'icon' => 'bi-paypal',
        'description' => 'Paiement via votre compte PayPal',
        'color' => '#ffc107'
    ],
    'virement' => [
        'title' => 'Virement Bancaire',
        'icon' => 'bi-bank',
        'description' => 'Virement bancaire sécurisé',
        'color' => '#28a745'
    ],
    default => [
        'title' => 'Paiement',
        'icon' => 'bi-credit-card',
        'description' => 'Finaliser votre paiement',
        'color' => '#007bff'
    ]
};
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Restaurant La Mangeoire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <?php if ($type_paiement === 'stripe' && $public_keys['stripe_publishable_key']): ?>
        <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?php echo $payment_info['color']; ?>;
            --primary-hover: #0056b3;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .payment-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-payment {
            background: var(--primary-color);
            border: none;
            border-radius: 25px;
            padding: 15px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-payment:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            color: white;
        }
        
        .secure-indicators {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .secure-badge {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .bank-info {
            background: #e8f4f8;
            border-left: 4px solid #17a2b8;
            padding: 20px;
            border-radius: 10px;
        }
        
        .payment-form {
            padding: 40px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        #stripe-card-element {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: white;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Traitement en cours...</span>
            </div>
            <h4>Traitement du paiement...</h4>
            <p class="text-muted">Veuillez patienter, ne fermez pas cette page.</p>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <div class="payment-icon">
                    <i class="<?php echo $payment_info['icon']; ?>"></i>
                </div>
                <h2><?php echo $payment_info['title']; ?></h2>
                <p class="mb-0"><?php echo $payment_info['description']; ?></p>
            </div>
            
            <div class="payment-form">
                <!-- Résumé de la commande -->
                <div class="order-summary">
                    <h5><i class="bi bi-receipt"></i> Résumé de votre commande</h5>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Commande #<?php echo $commande['CommandeID']; ?></span>
                        <span><?php echo date('d/m/Y H:i', strtotime($commande['DateCommande'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total à payer</strong>
                        <strong class="text-primary"><?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> €</strong>
                    </div>
                </div>

                <?php if ($type_paiement === 'stripe'): ?>
                    <!-- Formulaire Stripe -->
                    <form id="stripe-payment-form">
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-credit-card"></i> Informations de la carte</label>
                            <div id="stripe-card-element">
                                <!-- Stripe Elements sera injecté ici -->
                            </div>
                            <div id="stripe-card-errors" class="error-message"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-payment w-100" id="stripe-submit">
                            <span id="stripe-button-text">
                                <i class="bi bi-lock"></i> Payer <?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> € avec Stripe
                            </span>
                            <div id="stripe-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                <span class="visually-hidden">Traitement...</span>
                            </div>
                        </button>
                    </form>

                <?php elseif ($type_paiement === 'paypal'): ?>
                    <!-- Formulaire PayPal -->
                    <form id="paypal-payment-form">
                        <div class="text-center mb-4">
                            <p class="lead">Vous allez être redirigé vers PayPal pour finaliser votre paiement en toute sécurité.</p>
                        </div>
                        
                        <button type="submit" class="btn btn-payment w-100" id="paypal-submit">
                            <span id="paypal-button-text">
                                <i class="bi bi-paypal"></i> Payer <?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> € avec PayPal
                            </span>
                            <div id="paypal-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                <span class="visually-hidden">Redirection...</span>
                            </div>
                        </button>
                    </form>

                <?php elseif ($type_paiement === 'virement'): ?>
                    <!-- Informations virement -->
                    <div class="bank-info">
                        <h5><i class="bi bi-bank"></i> Informations bancaires</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>IBAN :</strong><br>
                                <code>FR76 1234 5678 9012 3456 7890 123</code>
                            </div>
                            <div class="col-md-6">
                                <strong>BIC :</strong><br>
                                <code>BNPAFRPPXXX</code>
                            </div>
                        </div>
                        <div class="mt-3">
                            <strong>Référence à indiquer :</strong><br>
                            <code>MANGEOIRE-<?php echo $commande['CommandeID']; ?>-<?php echo date('Ymd'); ?></code>
                        </div>
                        <div class="mt-3">
                            <strong>Bénéficiaire :</strong> Restaurant La Mangeoire<br>
                            <strong>Montant exact :</strong> <?php echo number_format($commande['MontantTotal'], 2, ',', ' '); ?> €
                        </div>
                    </div>
                    
                    <form id="virement-form">
                        <button type="submit" class="btn btn-payment w-100 mt-4">
                            <i class="bi bi-check-circle"></i> Confirmer la réception des informations
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Indicateurs de sécurité -->
                <div class="secure-indicators">
                    <div class="secure-badge">
                        <i class="bi bi-shield-check"></i> Paiement Sécurisé
                    </div>
                    <div class="secure-badge">
                        <i class="bi bi-lock"></i> SSL 256-bit
                    </div>
                    <div class="secure-badge">
                        <i class="bi bi-envelope-check"></i> Confirmation Email
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        const loadingOverlay = document.getElementById('loading-overlay');
        const commandeId = <?php echo json_encode($commande_id); ?>;
        const clientId = <?php echo json_encode($commande['ClientID']); ?>;
        const montant = <?php echo json_encode($commande['MontantTotal']); ?>;
        
        function showLoading() {
            loadingOverlay.style.display = 'flex';
        }
        
        function hideLoading() {
            loadingOverlay.style.display = 'none';
        }

        <?php if ($type_paiement === 'stripe' && $public_keys['stripe_publishable_key']): ?>
        // Configuration Stripe
        const stripe = Stripe('<?php echo $public_keys['stripe_publishable_key']; ?>');
        const elements = stripe.elements();
        
        // Créer l'élément carte
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
            },
        });
        
        cardElement.mount('#stripe-card-element');
        
        // Gérer les erreurs en temps réel
        cardElement.addEventListener('change', function(event) {
            const displayError = document.getElementById('stripe-card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Traitement du formulaire Stripe
        document.getElementById('stripe-payment-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const submitButton = document.getElementById('stripe-submit');
            const spinner = document.getElementById('stripe-spinner');
            const buttonText = document.getElementById('stripe-button-text');
            
            submitButton.disabled = true;
            spinner.classList.remove('d-none');
            buttonText.innerHTML = '<i class="bi bi-clock"></i> Traitement en cours...';
            showLoading();
            
            try {
                // Créer le payment method
                const {error, paymentMethod} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                });
                
                if (error) {
                    throw new Error(error.message);
                }
                
                // Envoyer au serveur
                const response = await fetch('api/payments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'stripe_payment',
                        payment_method_id: paymentMethod.id,
                        montant: montant,
                        commande_id: commandeId,
                        client_id: clientId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Succès - rediriger vers confirmation
                    window.location.href = `confirmation-paiement.php?type=stripe&commande=${commandeId}&payment_id=${result.payment_id}`;
                } else if (result.requires_action) {
                    // Authentification 3D Secure nécessaire
                    const {error: confirmError} = await stripe.confirmCardPayment(result.client_secret);
                    if (confirmError) {
                        throw new Error(confirmError.message);
                    } else {
                        window.location.href = `confirmation-paiement.php?type=stripe&commande=${commandeId}`;
                    }
                } else {
                    throw new Error(result.error || 'Erreur de paiement');
                }
                
            } catch (error) {
                console.error('Erreur Stripe:', error);
                alert('Erreur de paiement: ' + error.message);
                
                // Restaurer le bouton
                submitButton.disabled = false;
                spinner.classList.add('d-none');
                buttonText.innerHTML = '<i class="bi bi-lock"></i> Payer ' + montant.toFixed(2).replace('.', ',') + ' € avec Stripe';
                hideLoading();
            }
        });
        <?php endif; ?>

        <?php if ($type_paiement === 'paypal'): ?>
        // Traitement PayPal
        document.getElementById('paypal-payment-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const submitButton = document.getElementById('paypal-submit');
            const spinner = document.getElementById('paypal-spinner');
            const buttonText = document.getElementById('paypal-button-text');
            
            submitButton.disabled = true;
            spinner.classList.remove('d-none');
            buttonText.innerHTML = '<i class="bi bi-arrow-right"></i> Redirection vers PayPal...';
            showLoading();
            
            try {
                const response = await fetch('api/payments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_paypal_payment',
                        montant: montant,
                        commande_id: commandeId,
                        client_id: clientId,
                        return_url: window.location.origin + '/api/paypal_return.php',
                        cancel_url: window.location.href
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.approval_url) {
                    // Rediriger vers PayPal
                    window.location.href = result.approval_url;
                } else {
                    throw new Error(result.error || 'Erreur de création du paiement PayPal');
                }
                
            } catch (error) {
                console.error('Erreur PayPal:', error);
                alert('Erreur PayPal: ' + error.message);
                
                // Restaurer le bouton
                submitButton.disabled = false;
                spinner.classList.add('d-none');
                buttonText.innerHTML = '<i class="bi bi-paypal"></i> Payer ' + montant.toFixed(2).replace('.', ',') + ' € avec PayPal';
                hideLoading();
            }
        });
        <?php endif; ?>

        <?php if ($type_paiement === 'virement'): ?>
        // Traitement virement
        document.getElementById('virement-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            showLoading();
            
            try {
                const response = await fetch('api/payments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'process_wire_transfer',
                        montant: montant,
                        commande_id: commandeId,
                        client_id: clientId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = `confirmation-paiement.php?type=virement&commande=${commandeId}&payment_id=${result.payment_id}`;
                } else {
                    throw new Error(result.error || 'Erreur lors de la confirmation du virement');
                }
                
            } catch (error) {
                console.error('Erreur virement:', error);
                alert('Erreur: ' + error.message);
                hideLoading();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
