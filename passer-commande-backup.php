<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/common.php';
require_once 'db_connexion.php';
require_once 'includes/currency_manager.php';
require_once 'includes/CartManager.php';

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
    error_log("DEBUG: Traitement de la commande commencé"); // Debug
    error_log("DEBUG: POST data: " . print_r($_POST, true)); // Debug
    
    // Validation des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $mode_livraison = $_POST['mode_livraison'] ?? 'livraison';
    
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
                    AdresseLivraison, MontantTotal, Statut, DateCommande
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
                $adresse_complete, $total, 'En attente'
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
        
        /* Styles pour les onglets de paiement */
        .payment-tabs {
            margin-bottom: 0;
        }
        
        .payment-tabs .nav-tabs {
            border-bottom: 3px solid #e9ecef;
            margin-bottom: 25px;
        }
        
        .payment-tabs .nav-link {
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 15px 25px;
            margin-right: 5px;
            background: #f8f9fa;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .payment-tabs .nav-link:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-2px);
        }
        
        .payment-tabs .nav-link.active {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-bottom: 3px solid #007bff;
        }
        
        .payment-tab-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .payment-tab-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 2px solid #f1f3f4;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
            animation: fadeInUp 0.4s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .payment-method-details {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .payment-method-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .payment-method-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .payment-method-description {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .payment-fee-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .payment-fee-info .fee-label {
            font-weight: 600;
            color: #495057;
        }
        
        .payment-fee-info .fee-amount {
            font-size: 18px;
            font-weight: bold;
        }
        
        .payment-fee-info .fee-amount.free {
            color: #28a745;
        }
        
        .payment-fee-info .fee-amount.paid {
            color: #ffc107;
        }
        
        .payment-total-display {
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 2px solid #007bff;
        }
        
        .payment-total-label {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .payment-total-amount {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
        }
        
        .payment-radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
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
                                            <!-- Formulaire de devise séparé -->
                                            <select id="currency-selector" class="form-select form-select-sm" onchange="changeCurrency(this.value)">
                                                <option value="">-- Garder <?= $userCurrency['code'] ?> --</option>
                                                <?php
                                                $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'MAD', 'TND', 'DZD'];
                                                foreach ($currencies as $code) {
                                                    $currency = CurrencyManager::getCurrencyByCode($code);
                                                    if ($currency && $code !== $userCurrency['code']) {
                                                        echo "<option value='$code'>{$currency['name']} ({$currency['symbol']})</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
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

                                <!-- Bouton de validation -->


                                            <!-- Onglets de navigation -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <?php 
                                                $selectedPayment = $_POST['mode_paiement'] ?? '';
                                                $isFirst = true;
                                                foreach ($recommendedPaymentMethods as $key => $method): 
                                                    $isSelected = ($selectedPayment === $key) || ($isFirst && empty($selectedPayment));
                                                    $icons = [
                                                        'carte_bancaire' => '💳',
                                                        'stripe' => '🔷',
                                                        'paypal' => '🟦',
                                                        'virement_bancaire' => '🏛️',
                                                        'especes' => '💵'
                                                    ];
                                                ?>
                                                <li class="nav-item" role="presentation">
                                                    <a class="nav-link <?= $isSelected ? 'active' : '' ?>" 
                                                       data-bs-toggle="tab" 
                                                       href="#payment-tab-<?= $key ?>" 
                                                       role="tab" 
                                                       onclick="selectPaymentTab('<?= $key ?>')">
                                                        <span class="payment-tab-icon"><?= $icons[$key] ?? '💳' ?></span>
                                                        <?= $method['name'] ?>
                                                    </a>
                                                </li>
                                                <?php 
                                                $isFirst = false;
                                                endforeach; 
                                                ?>
                                            </ul>
                                            
                                            <!-- Contenu des onglets -->
                                            <div class="tab-content payment-tab-content">
                                                <?php 
                                                $selectedPayment = $_POST['mode_paiement'] ?? '';
                                                $isFirst = true;
                                                foreach ($recommendedPaymentMethods as $key => $method): 
                                                    $fees = PaymentManager::calculateFees($total, $key);
                                                    $totalWithFees = PaymentManager::getTotalWithFees($total, $key);
                                                    $isSelected = ($selectedPayment === $key) || ($isFirst && empty($selectedPayment));
                                                    $icons = [
                                                        'carte_bancaire' => '💳',
                                                        'stripe' => '🔷',
                                                        'paypal' => '🟦',
                                                        'virement_bancaire' => '🏛️',
                                                        'especes' => '💵'
                                                    ];
                                                ?>
                                                <div class="tab-pane <?= $isSelected ? 'active' : '' ?>" 
                                                     id="payment-tab-<?= $key ?>" 
                                                     role="tabpanel">
                                                     
                                                    <!-- Radio button caché -->
                                                    <input type="radio" 
                                                           class="payment-radio" 
                                                           name="mode_paiement" 
                                                           value="<?= $key ?>" 
                                                           id="payment_<?= $key ?>" 
                                                           <?= $isSelected ? 'checked' : '' ?>>
                                                    
                                                    <!-- Détails de la méthode de paiement -->
                                                    <div class="payment-method-details">
                                                        <span class="payment-method-icon"><?= $icons[$key] ?? '💳' ?></span>
                                                        <h5 class="payment-method-title"><?= $method['name'] ?></h5>
                                                        <p class="payment-method-description"><?= $method['description'] ?></p>
                                                    </div>
                                                    
                                                    <!-- Informations sur les frais -->
                                                    <div class="payment-fee-info">
                                                        <div class="row align-items-center">
                                                            <div class="col-6">
                                                                <span class="fee-label">Frais de transaction :</span>
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <?php if ($fees > 0): ?>
                                                                    <span class="fee-amount paid">
                                                                        <?= CurrencyManager::formatPrice($fees, $userCurrency['code']) ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="fee-amount free">Gratuit</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Total avec frais -->
                                                    <div class="payment-total-display">
                                                        <div class="payment-total-label">Total à payer :</div>
                                                        <div class="payment-total-amount">
                                                            <?= CurrencyManager::formatPrice($totalWithFees, $userCurrency['code']) ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Instructions spécifiques selon le mode de paiement -->
                                                    <?php if ($key === 'carte_bancaire'): ?>
                                                        <div class="mt-3 text-center">
                                                            <small class="text-muted">
                                                                <i class="bi bi-shield-check text-success"></i> 
                                                                Paiement sécurisé par SSL<br>
                                                                Cartes acceptées : Visa, Mastercard, American Express
                                                            </small>
                                                        </div>
                                                    <?php elseif ($key === 'paypal'): ?>
                                                        <div class="mt-3 text-center">
                                                            <small class="text-muted">
                                                                <i class="bi bi-paypal text-primary"></i> 
                                                                Vous serez redirigé vers PayPal<br>
                                                                Protection des achats incluse
                                                            </small>
                                                        </div>
                                                    <?php elseif ($key === 'especes'): ?>
                                                        <div class="mt-3 text-center">
                                                            <small class="text-muted">
                                                                <i class="bi bi-cash text-success"></i> 
                                                                Paiement à la livraison ou au retrait<br>
                                                                Préparez la monnaie exacte si possible
                                                            </small>
                                                        </div>
                                                    <?php elseif ($key === 'virement_bancaire'): ?>
                                                        <div class="mt-3 text-center">
                                                            <small class="text-muted">
                                                                <i class="bi bi-bank text-info"></i> 
                                                                Virement SEPA pour l'Europe<br>
                                                                Délai de traitement : 1-2 jours ouvrés
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php 
                                                $isFirst = false;
                                                endforeach; 
                                                ?>
                                            </div>
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

        // Fonction pour changer la devise
        function changeCurrency(currencyCode) {
            if (currencyCode) {
                // Créer un formulaire temporaire pour soumettre le changement
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const input1 = document.createElement('input');
                input1.type = 'hidden';
                input1.name = 'change_currency';
                input1.value = '1';
                
                const input2 = document.createElement('input');
                input2.type = 'hidden';
                input2.name = 'currency_code';
                input2.value = currencyCode;
                
                form.appendChild(input1);
                form.appendChild(input2);
                document.body.appendChild(form);
                form.submit();
            }
        }

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

        // Gestion des onglets de paiement
        function selectPaymentTab(method) {
            console.log('Sélection du mode de paiement:', method); // Debug
            
            // Décocher tous les radio buttons de paiement
            document.querySelectorAll('input[name="mode_paiement"]').forEach(radio => {
                radio.checked = false;
            });
            
            // Cocher le radio button correspondant
            const radioEl = document.getElementById('payment_' + method);
            if (radioEl) {
                radioEl.checked = true;
                console.log('Radio button coché:', radioEl.id); // Debug
            } else {
                console.error('Radio button non trouvé:', 'payment_' + method); // Debug
            }
            
            // Mettre à jour le résumé si nécessaire
            updatePaymentSummary(method);
        }
        
        // Fonction pour mettre à jour le résumé de paiement (optionnel)
        function updatePaymentSummary(method) {
            // Cette fonction peut être utilisée pour mettre à jour le résumé en temps réel
            // Par exemple, mettre à jour les frais dans la sidebar
            console.log('Mode de paiement sélectionné:', method);
        }
        
        // Initialiser les onglets Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé - Initialisation des onglets'); // Debug
            
            // Activer les onglets Bootstrap
            var triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'));
            triggerTabList.forEach(function (triggerEl) {
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    var tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                    
                    // Extraire le mode de paiement de l'href
                    var href = triggerEl.getAttribute('href');
                    var method = href.replace('#payment-tab-', '');
                    selectPaymentTab(method);
                });
            });
            
            // Sélectionner le premier onglet par défaut
            const firstTab = document.querySelector('[data-bs-toggle="tab"]');
            if (firstTab) {
                const tab = new bootstrap.Tab(firstTab);
                tab.show();
                
                // Activer le premier mode de paiement
                const href = firstTab.getAttribute('href');
                const method = href.replace('#payment-tab-', '');
                selectPaymentTab(method);
            }
        });

        // Validation du formulaire
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            console.log('🚀 Tentative de soumission du formulaire'); // Debug
            
            const nom = document.getElementById('nom') ? document.getElementById('nom').value.trim() : '';
            const prenom = document.getElementById('prenom') ? document.getElementById('prenom').value.trim() : '';
            const telephone = document.getElementById('telephone') ? document.getElementById('telephone').value.trim() : '';
            const email = document.getElementById('email') ? document.getElementById('email').value.trim() : '';
            const modeLivraisonEl = document.querySelector('input[name="mode_livraison"]:checked');
            const modeLivraison = modeLivraisonEl ? modeLivraisonEl.value : 'livraison';
            const adresse = document.getElementById('adresse') ? document.getElementById('adresse').value.trim() : '';
            
            console.log('📋 Données du formulaire:', {nom, prenom, telephone, email, modeLivraison, adresse}); // Debug
            
            let errors = [];
            
            // Validation des champs obligatoires
            if (!nom) errors.push('Le nom est obligatoire');
            if (!prenom) errors.push('Le prénom est obligatoire');
            if (!telephone) errors.push('Le téléphone est obligatoire');
            if (!email) errors.push('L\'email est obligatoire');
            
            // Validation de l'adresse pour la livraison
            if (modeLivraison === 'livraison' && !adresse) {
                errors.push('L\'adresse est obligatoire pour la livraison');
            }
            
            // Vérifier qu'un mode de paiement est sélectionné
            const modePaiementEl = document.querySelector('input[name="mode_paiement"]:checked');
            if (!modePaiementEl) {
                errors.push('Veuillez sélectionner un mode de paiement');
            }
            
            console.log('❌ Erreurs trouvées:', errors); // Debug
            
            // Si des erreurs, les afficher et empêcher la soumission
            if (errors.length > 0) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs suivantes:\n• ' + errors.join('\n• '));
                return false;
            }
            
            console.log('✅ Validation réussie, demande de confirmation'); // Debug
            
            // Confirmation finale
            if (!confirm('Êtes-vous sûr de vouloir confirmer cette commande ?')) {
                console.log('❌ Commande annulée par l\'utilisateur'); // Debug
                e.preventDefault();
                return false;
            }
            
            console.log('🎉 Soumission autorisée !'); // Debug
            return true;
        });
    </script>
</body>
</html>
