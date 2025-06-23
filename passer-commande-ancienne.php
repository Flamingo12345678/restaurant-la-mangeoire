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
    <title>Passer Commande - La Mangeoire</title>
    <meta name="description" content="Finaliser votre commande - La Mangeoire" />
    <meta name="keywords" content="restaurant, commande, livraison, paiement" />
    
    <!-- Icone de favoris -->
    <link href="assets/img/favcon.jpeg" rel="icon" />
    <link href="assets/img/apple-touch-ico.png" rel="apple-touch-icon" />
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Amatic+SC:wght@400;700&display=swap"
      rel="stylesheet"
    />
    
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
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        /* Design par étapes */
        .checkout-steps {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .step-section {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .step-section:hover {
            border-color: #007bff;
            box-shadow: 0 2px 10px rgba(0,123,255,0.1);
        }
        
        .step-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .step-number {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .step-header h4 {
            margin: 0;
            font-size: 18px;
        }
        
        .step-content {
            padding: 25px;
        }
        
        /* Options de livraison */
        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .delivery-option {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .delivery-option:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.2);
        }
        
        .delivery-option.selected {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd, #f8f9ff);
        }
        
        .delivery-option input[type="radio"] {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .option-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .option-icon {
            font-size: 24px;
            min-width: 40px;
        }
        
        .option-info strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .option-info span {
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Options de paiement */
        .payment-methods {
            display: grid;
            gap: 12px;
        }
        
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .payment-option:hover {
            border-color: #007bff;
            box-shadow: 0 2px 10px rgba(0,123,255,0.1);
        }
        
        .payment-option.selected {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd, #f8f9ff);
        }
        
        .payment-option input[type="radio"] {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .payment-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .payment-logo {
            font-size: 24px;
            min-width: 40px;
        }
        
        .payment-info {
            flex: 1;
        }
        
        .payment-info strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .payment-description {
            color: #6c757d;
            font-size: 14px;
            display: block;
            margin-bottom: 3px;
        }
        
        .payment-fee {
            font-size: 12px;
            color: #ffc107;
            font-weight: 500;
        }
        
        .payment-fee.free {
            color: #28a745;
        }
        
        .payment-total {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        
        /* Résumé de commande */
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 100px;
        }
        
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info h6 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .item-quantity {
            color: #6c757d;
            font-size: 12px;
        }
        
        .item-price {
            font-weight: bold;
            color: #007bff;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-final {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            border-top: 2px solid #007bff;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        /* Bouton de confirmation */
        .btn-confirm {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40,167,69,0.3);
        }
        
        .required {
            color: #dc3545;
        }
        
        /* Responsivité */
        @media (max-width: 992px) {
            .delivery-options {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
                margin-top: 30px;
            }
        }
        
        /* Changement de devise discret */
        .currency-selector {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .currency-selector select {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 14px;
        }
    </style>
</head>

<body>
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
                    <li><a href="panier.php"><i class="bi bi-cart"></i> Panier 
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li>
                        <?php if (isset($_SESSION['client_id'])): ?>
                            <a href="mon-compte.php"><i class="bi bi-person"></i> Mon Compte</a>
                        <?php else: ?>
                            <a href="admin/login.php"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
                        <?php endif; ?>
                    </li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted" href="index.php#book-a-table">Réserver une Table</a>
        </div>
    </header>

    <main class="main">
        <section class="checkout-section">
            <div class="container">
                <div class="section-title" data-aos="fade-up">
                    <h2>Finaliser votre commande</h2>
                    <p>
                        <span>Récapitulatif</span>
                        <span class="description-title">Et informations de livraison</span>
                    </p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" data-aos="fade-up">
                    <h5><i class="bi bi-exclamation-circle"></i> Erreurs détectées :</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="row" data-aos="fade-up">
                    <div class="col-lg-8">
                        <div class="form-section">
                            <form method="POST" id="checkout-form">
                                <input type="hidden" name="passer_commande" value="1">
                                
                                <!-- Informations personnelles -->
                                <div class="mb-4">
                                    <h4><i class="bi bi-person"></i> Informations personnelles</h4>
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

                                <!-- Mode de livraison -->
                                <div class="mb-4">
                                    <h4><i class="bi bi-truck"></i> Mode de livraison</h4>
                                    <div class="delivery-option" data-mode="livraison">
                                        <input type="radio" name="mode_livraison" value="livraison" id="livraison" 
                                               <?php echo (!isset($_POST['mode_livraison']) || $_POST['mode_livraison'] === 'livraison') ? 'checked' : ''; ?>>
                                        <label for="livraison" class="ms-2">
                                            <strong>Livraison à domicile</strong> (Gratuite)
                                            <br><small class="text-muted">Délai : 30-45 minutes</small>
                                        </label>
                                    </div>
                                    <div class="delivery-option" data-mode="retrait">
                                        <input type="radio" name="mode_livraison" value="retrait" id="retrait"
                                               <?php echo (isset($_POST['mode_livraison']) && $_POST['mode_livraison'] === 'retrait') ? 'checked' : ''; ?>>
                                        <label for="retrait" class="ms-2">
                                            <strong>Retrait sur place</strong>
                                            <br><small class="text-muted">Prêt en 15-20 minutes</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Adresse de livraison -->
                                <div class="mb-4" id="adresse-section">
                                    <h4><i class="bi bi-geo-alt"></i> Adresse de livraison</h4>
                                    <div class="mb-3">
                                        <label for="adresse" class="form-label">Adresse complète <span class="required">*</span></label>
                                        <textarea class="form-control" id="adresse" name="adresse" rows="3" 
                                                  placeholder="Numéro, rue, quartier, ville..."><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- Instructions spéciales -->
                                <div class="mb-4">
                                    <h4><i class="bi bi-chat-text"></i> Instructions spéciales</h4>
                                    <div class="mb-3">
                                        <label for="instructions" class="form-label">Instructions pour la livraison (optionnel)</label>
                                        <textarea class="form-control" id="instructions" name="instructions" rows="2" 
                                                  placeholder="Étage, digicode, remarques particulières..."><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- Sélection de devise -->
                                <div class="mb-4">
                                    <h4><i class="bi bi-globe"></i> Devise et Prix</h4>
                                    <div class="currency-info alert alert-info">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <strong>Pays détecté :</strong> <?= $userCountry ?><br>
                                                <strong>Devise actuelle :</strong> <?= $userCurrency['name'] ?> (<?= $userCurrency['symbol'] ?>)
                                            </div>
                                            <div class="col-md-6">
                                                <!-- Formulaire séparé pour le changement de devise -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="change_currency" value="1">
                                                    <label for="currency_select" class="form-label">Changer de devise :</label>
                                                    <select name="currency_code" id="currency_select" class="form-select" onchange="this.form.submit()">
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
                                </div>

                                <!-- Mode de paiement -->
                                <div class="mb-4">
                                    <h4><i class="bi bi-credit-card"></i> Modes de paiement disponibles</h4>
                                    <p class="text-muted mb-3">Méthodes de paiement recommandées pour votre région :</p>
                                    
                                    <?php 
                                    $selectedPayment = $_POST['mode_paiement'] ?? '';
                                    $isFirst = true;
                                    foreach ($recommendedPaymentMethods as $key => $method): 
                                        $fees = PaymentManager::calculateFees($total, $key);
                                        $totalWithFees = PaymentManager::getTotalWithFees($total, $key);
                                    ?>
                                    <div class="payment-option card mb-3" data-payment="<?= $key ?>">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <input type="radio" name="mode_paiement" value="<?= $key ?>" id="payment_<?= $key ?>" 
                                                       <?= ($selectedPayment === $key || ($isFirst && empty($selectedPayment))) ? 'checked' : '' ?>>
                                                <label for="payment_<?= $key ?>" class="ms-3 flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><?= $method['icon'] ?> <?= $method['name'] ?></strong>
                                                            <br><small class="text-muted"><?= $method['description'] ?></small>
                                                            <br><small class="text-info">⏱️ <?= $method['processing_time'] ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <?php if ($fees > 0): ?>
                                                                <small class="text-warning">Frais: <?= CurrencyManager::formatPrice($fees, $userCurrency['code']) ?></small><br>
                                                                <strong><?= CurrencyManager::formatPrice($totalWithFees, $userCurrency['code']) ?></strong>
                                                            <?php else: ?>
                                                                <small class="text-success">Sans frais</small><br>
                                                                <strong><?= CurrencyManager::formatPrice($total, $userCurrency['code']) ?></strong>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php 
                                        $isFirst = false;
                                    endforeach; 
                                    ?>
                                    
                                    <!-- Autres méthodes disponibles -->
                                    <?php 
                                    $otherMethods = array_diff_key($availablePaymentMethods, $recommendedPaymentMethods);
                                    if (!empty($otherMethods)): 
                                    ?>
                                    <div class="mt-3">
                                        <h6>Autres méthodes disponibles :</h6>
                                        <?php foreach ($otherMethods as $key => $method): 
                                            $fees = PaymentManager::calculateFees($total, $key);
                                            $totalWithFees = PaymentManager::getTotalWithFees($total, $key);
                                        ?>
                                        <div class="payment-option card mb-2" data-payment="<?= $key ?>">
                                            <div class="card-body py-2">
                                                <div class="d-flex align-items-center">
                                                    <input type="radio" name="mode_paiement" value="<?= $key ?>" id="payment_<?= $key ?>" 
                                                           <?= ($selectedPayment === $key) ? 'checked' : '' ?>>
                                                    <label for="payment_<?= $key ?>" class="ms-3 flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong><?= $method['icon'] ?> <?= $method['name'] ?></strong>
                                                                <small class="text-muted ms-2"><?= $method['description'] ?></small>
                                                            </div>
                                                            <div class="text-end">
                                                                <?php if ($fees > 0): ?>
                                                                    <small class="text-warning">+<?= CurrencyManager::formatPrice($fees, $userCurrency['code']) ?></small>
                                                                <?php endif; ?>
                                                                <strong><?= CurrencyManager::formatPrice($totalWithFees, $userCurrency['code']) ?></strong>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-circle"></i> Confirmer la commande
                                    </button>
                                    <a href="panier.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour au panier
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="order-summary">
                            <h4><i class="bi bi-cart-check"></i> Récapitulatif de commande</h4>
                            
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Quantité: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong><?= CurrencyManager::formatPrice($item['price'] * $item['quantity'], $userCurrency['code']) ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="total-section">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Sous-total:</span>
                                    <span><?= CurrencyManager::formatPrice($total, $userCurrency['code']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Frais de livraison:</span>
                                    <span class="text-success">Gratuit</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2" id="payment-fees-display" style="display: none;">
                                    <span>Frais de paiement:</span>
                                    <span class="text-warning" id="fees-amount">-</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold h5">
                                    <span>Total:</span>
                                    <span id="total-amount"><?= CurrencyManager::formatPrice($total, $userCurrency['code']) ?></span>
                                </div>
                                
                                <!-- Info devise -->
                                <div class="alert alert-light mt-3">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        Prix affiché en <strong><?= $userCurrency['name'] ?></strong>
                                        <?php if ($userCurrency['code'] !== 'EUR'): ?>
                                        <br>Prix de base: <?= CurrencyManager::formatPrice($total, 'EUR') ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> 
                                    Vous recevrez un SMS de confirmation avec le numéro de suivi de votre commande.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="container">
            <div class="row gy-3">
                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-geo-alt icon"></i>
                    <div class="address">
                        <h4>Adresse</h4>
                        <p>Hotel du plateau</p>
                        <p>ESSOS</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-telephone icon"></i>
                    <div>
                        <h4>Contact</h4>
                        <p>
                            <strong>telephone:</strong> <span>+237 6 96 56 85 20</span><br />
                            <strong>Email:</strong> <span>la-mangeoire@gmail.com</span><br />
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 d-flex">
                    <i class="bi bi-clock icon"></i>
                    <div>
                        <h4>Heures d'ouverture</h4>
                        <p>
                            <strong>Lun-Sam:</strong> <span>11H - 23H</span><br />
                            <strong>Dimanche</strong>: <span>Fermé</span>
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h4>Suivez Nous</h4>
                    <div class="social-links d-flex">
                        <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container copyright text-center mt-4">
            <p>
                © <span>Copyright</span>
                <strong class="px-1 sitename">La Mangeoire</strong>
                <span>All Rights Reserved</span>
            </p>
            <div class="credits">
                Designed by <a href="https://bootstrapmade.com/">FLAMINGO</a> Distributed by <a href="https://themewagon.com">JOSEPH</a>
            </div>
        </div>
    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Données de paiement et frais
            const paymentFees = <?php 
                $paymentFeesData = [];
                foreach ($availablePaymentMethods as $key => $method) {
                    $fees = PaymentManager::calculateFees($total, $key);
                    $totalWithFees = PaymentManager::getTotalWithFees($total, $key);
                    $paymentFeesData[$key] = [
                        'fees' => $method['fees'],
                        'name' => $method['name'],
                        'feesAmount' => $fees,
                        'totalWithFees' => $totalWithFees,
                        'formattedFees' => CurrencyManager::formatPrice($fees, $userCurrency['code']),
                        'formattedTotal' => CurrencyManager::formatPrice($totalWithFees, $userCurrency['code'])
                    ];
                }
                echo json_encode($paymentFeesData);
            ?>;
            
            const baseTotal = <?= $total ?>;
            const baseTotalFormatted = '<?= CurrencyManager::formatPrice($total, $userCurrency['code']) ?>';
            
            // Gestion des options de livraison
            const deliveryOptions = document.querySelectorAll('.delivery-option');
            const adresseSection = document.getElementById('adresse-section');
            const adresseInput = document.getElementById('adresse');
            
            function updateDeliverySelection() {
                const selectedMode = document.querySelector('input[name="mode_livraison"]:checked').value;
                
                deliveryOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.dataset.mode === selectedMode) {
                        option.classList.add('selected');
                    }
                });
                
                // Afficher/masquer l'adresse selon le mode
                if (selectedMode === 'livraison') {
                    adresseSection.style.display = 'block';
                    adresseInput.required = true;
                } else {
                    adresseSection.style.display = 'none';
                    adresseInput.required = false;
                }
            }
            
            deliveryOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    updateDeliverySelection();
                });
            });
            
            // Gestion des options de paiement
            const paymentOptions = document.querySelectorAll('.payment-option');
            const feesDisplay = document.getElementById('payment-fees-display');
            const feesAmount = document.getElementById('fees-amount');
            const totalAmount = document.getElementById('total-amount');
            
            function updatePaymentSelection() {
                const selectedPayment = document.querySelector('input[name="mode_paiement"]:checked').value;
                
                paymentOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.dataset.payment === selectedPayment) {
                        option.classList.add('selected');
                    }
                });
                
                // Mettre à jour les frais et le total
                if (paymentFees[selectedPayment]) {
                    const payment = paymentFees[selectedPayment];
                    
                    if (payment.feesAmount > 0) {
                        feesDisplay.style.display = 'flex';
                        feesAmount.textContent = payment.formattedFees;
                    } else {
                        feesDisplay.style.display = 'none';
                    }
                    
                    totalAmount.textContent = payment.formattedTotal;
                } else {
                    feesDisplay.style.display = 'none';
                    totalAmount.textContent = baseTotalFormatted;
                }
            }
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    updatePaymentSelection();
                });
            });
            
            // Initialiser les sélections
            updateDeliverySelection();
            updatePaymentSelection();
            
            // Validation du formulaire
            const form = document.getElementById('checkout-form');
            form.addEventListener('submit', function(e) {
                const nom = document.getElementById('nom').value.trim();
                const prenom = document.getElementById('prenom').value.trim();
                const telephone = document.getElementById('telephone').value.trim();
                const email = document.getElementById('email').value.trim();
                const modeLivraison = document.querySelector('input[name="mode_livraison"]:checked').value;
                const adresse = document.getElementById('adresse').value.trim();
                
                let errors = [];
                
                if (!nom) errors.push('Le nom est requis');
                if (!prenom) errors.push('Le prénom est requis');
                if (!telephone) errors.push('Le téléphone est requis');
                if (!email) errors.push('L\'email est requis');
                if (modeLivraison === 'livraison' && !adresse) {
                    errors.push('L\'adresse est requise pour la livraison');
                }
                
                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Erreurs détectées:\n' + errors.join('\n'));
                    return false;
                }
                
                return confirm('Êtes-vous sûr de vouloir passer cette commande ?');
            });
        });
    </script>
</body>
</html>
