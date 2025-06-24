<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';
require_once 'includes/currency_manager.php';
require_once 'includes/CartManager.php';
require_once 'includes/PaymentManager.php';

// Initialiser le gestionnaire de panier et de devises
$cartManager = new CartManager($pdo);

// Détecter la devise de l'utilisateur
$userCountry = CurrencyManager::detectCountry();
$userCurrency = CurrencyManager::getCurrencyForCountry($userCountry);

// Gérer le changement de devise
if (isset($_POST['change_currency'])) {
    $newCurrency = $_POST['currency_code'];
    $_SESSION['selected_currency'] = $newCurrency;
    $userCurrency = CurrencyManager::getCurrencyByCode($newCurrency);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Utiliser la devise sélectionnée ou détectée
if (isset($_SESSION['selected_currency'])) {
    $userCurrency = CurrencyManager::getCurrencyByCode($_SESSION['selected_currency']);
}

// Obtenir les méthodes de paiement disponibles pour ce pays
$availablePaymentMethods = PaymentManager::getAvailablePaymentMethods($userCountry);
$recommendedPaymentMethods = PaymentManager::getRecommendedMethods($userCountry);

// Vérifier si le panier n'est pas vide en utilisant le CartManager
$cartSummary = $cartManager->getSummary();
$cart_items = $cartManager->getItems();
$total = $cartSummary['total_amount'];
$cart_count = $cartSummary['total_items'];

// Rediriger vers le panier si vide
if ($cartSummary['is_empty']) {
    $_SESSION['message'] = "Votre panier est vide. Ajoutez des articles avant de passer commande.";
    $_SESSION['message_type'] = "error";
    header("Location: panier.php");
    exit;
}

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passer_commande'])) {
    // Validation des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $mode_livraison = $_POST['mode_livraison'] ?? 'livraison';
    $mode_paiement = $_POST['mode_paiement'] ?? 'especes';
    
    $errors = [];
    
    // Validation des champs obligatoires
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire.";
    if (empty($email)) $errors[] = "L'email est obligatoire.";
    if ($mode_livraison === 'livraison' && empty($adresse)) {
        $errors[] = "L'adresse est obligatoire pour la livraison.";
    }
    
    // Validation du format email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide.";
    }
    
    // Validation du téléphone (format simple)
    if (!empty($telephone) && !preg_match('/^[0-9+\-\s]{8,15}$/', $telephone)) {
        $errors[] = "Format de téléphone invalide.";
    }
    
    if (empty($errors)) {
        try {
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Insérer la commande
            $stmt = $pdo->prepare("
                INSERT INTO Commandes (
                    ClientID, NomClient, PrenomClient, TelephoneClient, EmailClient, 
                    AdresseLivraison, ModePaiement, MontantTotal, Statut, DateCommande
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // Créer l'adresse complète avec instructions et mode de livraison
            $adresse_complete = $adresse;
            if (!empty($instructions)) {
                $adresse_complete .= "\n\nInstructions: " . $instructions;
            }
            $adresse_complete .= "\nMode: " . ($mode_livraison === 'livraison' ? 'Livraison' : 'Retrait sur place');
            
            $user_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;
            $stmt->execute([
                $user_id, $nom, $prenom, $telephone, $email, 
                $adresse_complete, $mode_paiement, $total, 'En attente'
            ]);
            
            $commande_id = $pdo->lastInsertId();
            
            // Insérer les articles de la commande
            $stmt = $pdo->prepare("
                INSERT INTO DetailsCommande (CommandeID, MenuID, NomItem, Prix, Quantite, SousTotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $sous_total = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $commande_id, 
                    $item['menu_id'], 
                    $item['name'],
                    $item['price'], 
                    $item['quantity'], 
                    $sous_total
                ]);
            }
            
            // Vider le panier en utilisant le CartManager
            $cartManager->clear();
            
            // Valider la transaction
            $pdo->commit();
            
            // Log pour debug
            error_log("Commande créée avec succès - ID: " . $commande_id . " - Redirection en cours");
            
            // Rediriger vers la page de confirmation
            $_SESSION['message'] = "Votre commande a été passée avec succès !";
            $_SESSION['message_type'] = "success";
            
            // Nettoyer le buffer de sortie avant la redirection
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            header("Location: confirmation-commande.php?id=" . $commande_id);
            exit;
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
            error_log("Erreur lors de la commande: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Ajouter une session d'erreur pour le debug
            $_SESSION['debug_error'] = $e->getMessage();
            $_SESSION['debug_trace'] = $e->getTraceAsString();
            
            $errors[] = "Erreur lors de la création de la commande: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Finaliser ma commande - La Mangeoire</title>
    <meta name="description" content="Finaliser votre commande - La Mangeoire" />
    
    <!-- Icone de favoris -->
    <link href="assets/img/favcon.jpeg" rel="icon" />
    <link href="assets/img/apple-touch-ico.png" rel="apple-touch-icon" />
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Amatic+SC:wght@400;700&display=swap" rel="stylesheet" />
    
    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/vendor/aos/aos.css" rel="stylesheet" />
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet" />
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet" />
    
    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet" />
    
    <style>
        .checkout-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .checkout-steps {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .step-section {
            background: #f8f9fa;
            border-radius: 15px;
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .step-section:hover {
            box-shadow: 0 5px 20px rgba(0,123,255,0.15);
        }
        
        .step-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .step-number {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .step-header h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .step-content {
            padding: 30px;
            background: white;
        }
        
        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .delivery-option {
            border: 3px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: #fafafa;
        }
        
        .delivery-option:hover {
            border-color: #007bff;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.2);
        }
        
        .delivery-option.selected {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
        }
        
        .delivery-option input[type="radio"] {
            position: absolute;
            top: 20px;
            right: 20px;
            transform: scale(1.3);
        }
        
        .option-content {
            text-align: center;
        }
        
        .option-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .option-info strong {
            display: block;
            font-size: 18px;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .option-info span {
            color: #6c757d;
            font-size: 15px;
        }
        
        .payment-methods {
            display: grid;
            gap: 15px;
        }
        
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: #fafafa;
        }
        
        .payment-option:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0,123,255,0.15);
        }
        
        .payment-option.selected {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
        }
        
        .payment-option input[type="radio"] {
            position: absolute;
            top: 20px;
            right: 20px;
            transform: scale(1.2);
        }
        
        .payment-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .payment-logo {
            font-size: 28px;
            min-width: 50px;
        }
        
        .payment-info {
            flex: 1;
        }
        
        .payment-info strong {
            display: block;
            font-size: 18px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .payment-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .payment-fee {
            font-size: 13px;
            font-weight: 600;
            color: #ffc107;
        }
        
        .payment-fee.free {
            color: #28a745;
        }
        
        .payment-total {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        
        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: sticky;
            top: 120px;
        }
        
        .summary-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f3f4;
        }
        
        .summary-header h4 {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info h6 {
            margin: 0 0 5px 0;
            font-size: 15px;
            color: #2c3e50;
        }
        
        .item-quantity {
            color: #6c757d;
            font-size: 13px;
        }
        
        .item-price {
            font-weight: bold;
            color: #007bff;
            font-size: 16px;
        }
        
        .total-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 15px;
            margin-top: 25px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .total-final {
            font-size: 22px;
            font-weight: bold;
            color: #007bff;
            border-top: 3px solid #007bff;
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 18px 40px;
            font-size: 20px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40,167,69,0.4);
        }
        
        .currency-selector {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .required {
            color: #dc3545;
        }
        
        @media (max-width: 992px) {
            .delivery-options {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
                margin-top: 40px;
            }
            
            .checkout-steps {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container position-relative d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">La Mangeoire</h1>
                <span>.</span>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="index.php#hero">Accueil</a></li>
                    <li><a href="index.php#about">A Propos</a></li>
                    <li><a href="index.php#menu">Menu</a></li>
                    <li><a href="index.php#events">Evenements</a></li>
                    <li><a href="index.php#chefs">Chefs</a></li>
                    <li><a href="index.php#gallery">Galeries</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                    <li><a href="panier.php" class="active"><i class="bi bi-cart"></i> Panier 
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted" href="index.php#book-a-table">Réserver une Table</a>
        </div>
    </header>

    <main class="main">
        <section class="checkout-section">
            <div class="checkout-container">
                <!-- Titre principal -->
                <div class="text-center mb-5" data-aos="fade-up">
                    <h2 style="color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Finaliser ma commande</h2>
                    <p style="color: #6c757d; font-size: 18px;">Plus que quelques étapes pour déguster nos délicieux plats !</p>
                </div>

                <!-- Messages d'erreur -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" data-aos="fade-up">
                    <h5><i class="bi bi-exclamation-circle"></i> Veuillez corriger les erreurs suivantes :</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="row" data-aos="fade-up">
                    <!-- Formulaire de commande -->
                    <div class="col-lg-7">
                        <div class="checkout-steps">
                            <form method="POST" id="checkout-form">
                                <input type="hidden" name="passer_commande" value="1">
                                
                                <!-- Changement de devise (discret) -->
                                <div class="currency-selector">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <small class="text-muted"><strong>Devise :</strong> <?= $userCurrency['name'] ?> (<?= $userCurrency['symbol'] ?>)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="change_currency" value="1">
                                                <select name="currency_code" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="">-- Garder <?= $userCurrency['code'] ?> --</option>
                                                    <?php
                                                    $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'XAF', 'XOF', 'MAD', 'TND', 'DZD'];
                                                    foreach ($currencies as $code) {
                                                        $currency = CurrencyManager::getCurrencyByCode($code);
                                                        if ($currency && $code !== $userCurrency['code']) {
                                                            echo "<option value='$code'>{$currency['name']} ({$currency['symbol']})</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Étape 1: Informations de contact -->
                                <div class="step-section">
                                    <div class="step-header">
                                        <span class="step-number">1</span>
                                        <h4>Vos informations</h4>
                                    </div>
                                    <div class="step-content">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nom" class="form-label">Nom <span class="required">*</span></label>
                                                <input type="text" class="form-control" id="nom" name="nom" 
                                                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="prenom" class="form-label">Prénom <span class="required">*</span></label>
                                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                                       value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="telephone" class="form-label">Téléphone <span class="required">*</span></label>
                                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                                       value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email <span class="required">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Étape 2: Mode de réception -->
                                <div class="step-section">
                                    <div class="step-header">
                                        <span class="step-number">2</span>
                                        <h4>Comment souhaitez-vous recevoir votre commande ?</h4>
                                    </div>
                                    <div class="step-content">
                                        <div class="delivery-options">
                                            <div class="delivery-option <?php echo (!isset($_POST['mode_livraison']) || $_POST['mode_livraison'] === 'livraison') ? 'selected' : ''; ?>" onclick="selectDeliveryMode('livraison', this)">
                                                <input type="radio" name="mode_livraison" value="livraison" id="livraison" 
                                                       <?php echo (!isset($_POST['mode_livraison']) || $_POST['mode_livraison'] === 'livraison') ? 'checked' : ''; ?>>
                                                <div class="option-content">
                                                    <div class="option-icon">🚚</div>
                                                    <div class="option-info">
                                                        <strong>Livraison gratuite</strong>
                                                        <span>30-45 minutes</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="delivery-option <?php echo (isset($_POST['mode_livraison']) && $_POST['mode_livraison'] === 'retrait') ? 'selected' : ''; ?>" onclick="selectDeliveryMode('retrait', this)">
                                                <input type="radio" name="mode_livraison" value="retrait" id="retrait"
                                                       <?php echo (isset($_POST['mode_livraison']) && $_POST['mode_livraison'] === 'retrait') ? 'checked' : ''; ?>>
                                                <div class="option-content">
                                                    <div class="option-icon">🏪</div>
                                                    <div class="option-info">
                                                        <strong>Retrait sur place</strong>
                                                        <span>15-20 minutes</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Adresse de livraison (affiché conditionnellement) -->
                                        <div id="adresse-section" style="display: <?php echo (!isset($_POST['mode_livraison']) || $_POST['mode_livraison'] === 'livraison') ? 'block' : 'none'; ?>;">
                                            <label for="adresse" class="form-label">Adresse de livraison <span class="required">*</span></label>
                                            <textarea class="form-control mb-3" id="adresse" name="adresse" rows="2" 
                                                      placeholder="Numéro, rue, quartier, ville..."><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                                            <input type="text" class="form-control" name="instructions" 
                                                   placeholder="Instructions spéciales (étage, digicode...)" 
                                                   value="<?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Étape 3: Mode de paiement -->
                                <div class="step-section">
                                    <div class="step-header">
                                        <span class="step-number">3</span>
                                        <h4>Choisissez votre mode de paiement</h4>
                                    </div>
                                    <div class="step-content">
                                        <div class="payment-methods">
                                            <?php 
                                            $selectedPayment = $_POST['mode_paiement'] ?? '';
                                            $isFirst = true;
                                            foreach ($recommendedPaymentMethods as $key => $method): 
                                                $fees = PaymentManager::calculateFees($total, $key);
                                                $totalWithFees = PaymentManager::getTotalWithFees($total, $key);
                                                $isSelected = ($selectedPayment === $key) || ($isFirst && empty($selectedPayment));
                                            ?>
                                            <div class="payment-option <?= $isSelected ? 'selected' : '' ?>" onclick="selectPaymentMethod('<?= $key ?>', this)">
                                                <input type="radio" name="mode_paiement" value="<?= $key ?>" id="payment_<?= $key ?>" 
                                                       <?= $isSelected ? 'checked' : '' ?>>
                                                <div class="payment-content">
                                                    <div class="payment-logo">
                                                        <?php
                                                        $icons = [
                                                            'carte_bancaire' => '💳',
                                                            'stripe' => '🔷',
                                                            'paypal' => '🟦',
                                                            'virement_bancaire' => '🏛️',
                                                            'especes' => '💵'
                                                        ];
                                                        echo $icons[$key] ?? '💳';
                                                        ?>
                                                    </div>
                                                    <div class="payment-info">
                                                        <strong><?= $method['name'] ?></strong>
                                                        <span class="payment-description"><?= $method['description'] ?></span>
                                                        <?php if ($fees > 0): ?>
                                                            <span class="payment-fee">Frais: <?= CurrencyManager::formatPrice($fees, $userCurrency['code']) ?></span>
                                                        <?php else: ?>
                                                            <span class="payment-fee free">Sans frais</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="payment-total">
                                                        <?= CurrencyManager::formatPrice($totalWithFees, $userCurrency['code']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php 
                                            $isFirst = false;
                                            endforeach; 
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bouton de validation -->
                                <button type="submit" class="btn btn-confirm">
                                    <i class="bi bi-check-circle"></i> Confirmer ma commande
                                </button>
                                
                                <div class="text-center mt-3">
                                    <a href="panier.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour au panier
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Résumé de commande -->
                    <div class="col-lg-5">
                        <div class="order-summary">
                            <div class="summary-header">
                                <h4><i class="bi bi-cart-check"></i> Votre commande</h4>
                            </div>
                            
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <span class="item-quantity">Quantité: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-price">
                                    <?= CurrencyManager::formatPrice($item['price'] * $item['quantity'], $userCurrency['code']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="total-section">
                                <div class="total-row">
                                    <span>Sous-total:</span>
                                    <span><?= CurrencyManager::formatPrice($total, $userCurrency['code']) ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Livraison:</span>
                                    <span class="text-success">Gratuite</span>
                                </div>
                                <div class="total-row total-final">
                                    <span>Total:</span>
                                    <span><?= CurrencyManager::formatPrice($total, $userCurrency['code']) ?></span>
                                </div>
                            </div>
                            
                            <!-- Garanties -->
                            <div class="mt-4 text-center">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check text-success"></i> Paiement sécurisé<br>
                                    <i class="bi bi-truck text-primary"></i> Livraison rapide<br>
                                    <i class="bi bi-heart text-danger"></i> Satisfaction garantie
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Scripts -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Initialiser AOS
        AOS.init();

        // Gestion des modes de livraison
        function selectDeliveryMode(mode, element) {
            // Retirer la classe selected de tous les éléments
            document.querySelectorAll('.delivery-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Ajouter la classe selected à l'élément cliqué
            element.classList.add('selected');
            
            // Cocher le radio button correspondant
            document.getElementById(mode).checked = true;
            
            // Afficher/masquer la section adresse
            const adresseSection = document.getElementById('adresse-section');
            if (mode === 'livraison') {
                adresseSection.style.display = 'block';
            } else {
                adresseSection.style.display = 'none';
            }
        }

        // Gestion des modes de paiement
        function selectPaymentMethod(method, element) {
            // Retirer la classe selected de tous les éléments
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Ajouter la classe selected à l'élément cliqué
            element.classList.add('selected');
            
            // Cocher le radio button correspondant
            document.getElementById('payment_' + method).checked = true;
        }

        // Validation du formulaire
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();
            const telephone = document.getElementById('telephone').value.trim();
            const email = document.getElementById('email').value.trim();
            const modeLivraison = document.querySelector('input[name="mode_livraison"]:checked').value;
            const adresse = document.getElementById('adresse').value.trim();
            
            if (!nom || !prenom || !telephone || !email) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            if (modeLivraison === 'livraison' && !adresse) {
                e.preventDefault();
                alert('Veuillez saisir votre adresse de livraison.');
                return;
            }
            
            // Confirmation finale
            if (!confirm('Êtes-vous sûr de vouloir confirmer cette commande ?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
