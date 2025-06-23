<?php
session_start();
require_once 'includes/currency_manager.php';
require_once 'db_connexion.php';

// Gestion du changement de devise
if (isset($_POST['change_currency'])) {
    CurrencyManager::setCurrency($_POST['country']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$current_currency = CurrencyManager::getCurrentCurrency();
$user_country = CurrencyManager::detectCountry();
$available_currencies = CurrencyManager::getAvailableCurrencies();

// Quelques prix d'exemple depuis la base de données
$menu_prices = [];
try {
    $stmt = $pdo->prepare("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 5");
    $stmt->execute();
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($menus as $menu) {
        $menu_prices[] = [
            'nom' => $menu['NomItem'],
            'prix_xaf' => $menu['Prix'],
            'prix_local' => CurrencyManager::formatPrice($menu['Prix'], true)
        ];
    }
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Système de Devises - La Mangeoire</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .currency-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .price-comparison {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .currency-flag {
            width: 30px;
            height: 20px;
            margin-right: 10px;
        }
        .original-price {
            color: #6c757d;
            font-size: 0.9em;
            text-decoration: line-through;
        }
        .converted-price {
            color: #e74c3c;
            font-weight: 600;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="text-center mb-4">🌍 Système de Devises Automatique</h1>
                
                <div class="currency-info text-center">
                    <h3><i class="bi bi-geo-alt"></i> Votre Localisation Détectée</h3>
                    <p class="mb-2"><strong>Pays:</strong> <?php echo $user_country; ?></p>
                    <p class="mb-2"><strong>Devise:</strong> <?php echo $current_currency['name']; ?> (<?php echo $current_currency['code']; ?>)</p>
                    <p class="mb-0"><strong>Symbole:</strong> <?php echo $current_currency['symbol']; ?></p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="price-comparison">
                            <h4><i class="bi bi-currency-exchange"></i> Changeur de Devise</h4>
                            <form method="post" class="mt-3">
                                <div class="mb-3">
                                    <label for="country" class="form-label">Choisir votre pays/devise :</label>
                                    <select name="country" id="country" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="CM" <?php echo ($user_country === 'CM') ? 'selected' : ''; ?>>🇨🇲 Cameroun (FCFA)</option>
                                        <option value="FR" <?php echo ($user_country === 'FR') ? 'selected' : ''; ?>>🇫🇷 France (Euro)</option>
                                        <option value="US" <?php echo ($user_country === 'US') ? 'selected' : ''; ?>>🇺🇸 États-Unis (Dollar)</option>
                                        <option value="GB" <?php echo ($user_country === 'GB') ? 'selected' : ''; ?>>🇬🇧 Royaume-Uni (Livre)</option>
                                        <option value="CA" <?php echo ($user_country === 'CA') ? 'selected' : ''; ?>>🇨🇦 Canada (Dollar CA)</option>
                                        <option value="CH" <?php echo ($user_country === 'CH') ? 'selected' : ''; ?>>🇨🇭 Suisse (Franc)</option>
                                        <option value="JP" <?php echo ($user_country === 'JP') ? 'selected' : ''; ?>>🇯🇵 Japon (Yen)</option>
                                        <option value="AU" <?php echo ($user_country === 'AU') ? 'selected' : ''; ?>>🇦🇺 Australie (Dollar AU)</option>
                                        <option value="BR" <?php echo ($user_country === 'BR') ? 'selected' : ''; ?>>🇧🇷 Brésil (Real)</option>
                                        <option value="CN" <?php echo ($user_country === 'CN') ? 'selected' : ''; ?>>🇨🇳 Chine (Yuan)</option>
                                        <option value="SN" <?php echo ($user_country === 'SN') ? 'selected' : ''; ?>>🇸🇳 Sénégal (FCFA)</option>
                                        <option value="CI" <?php echo ($user_country === 'CI') ? 'selected' : ''; ?>>🇨🇮 Côte d'Ivoire (FCFA)</option>
                                        <option value="MA" <?php echo ($user_country === 'MA') ? 'selected' : ''; ?>>🇲🇦 Maroc (Dirham)</option>
                                        <option value="ZA" <?php echo ($user_country === 'ZA') ? 'selected' : ''; ?>>🇿🇦 Afrique du Sud (Rand)</option>
                                    </select>
                                </div>
                                <button type="submit" name="change_currency" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-repeat"></i> Changer de Devise
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="price-comparison">
                            <h4><i class="bi bi-cash-stack"></i> Exemple de Prix</h4>
                            <?php if (!empty($menu_prices)): ?>
                                <?php foreach ($menu_prices as $menu): ?>
                                    <div class="border-bottom py-2">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($menu['nom']); ?></h6>
                                        <div class="converted-price"><?php echo $menu['prix_local']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">Aucun menu disponible pour le test</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="price-comparison">
                    <h4><i class="bi bi-info-circle"></i> Comment ça marche ?</h4>
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-geo text-primary" style="font-size: 2em;"></i>
                            <h6 class="mt-2">1. Détection Automatique</h6>
                            <p class="small text-muted">Votre pays est détecté via votre navigateur et géolocalisation</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-calculator text-primary" style="font-size: 2em;"></i>
                            <h6 class="mt-2">2. Conversion en Temps Réel</h6>
                            <p class="small text-muted">Les prix sont automatiquement convertis dans votre devise locale</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-gear text-primary" style="font-size: 2em;"></i>
                            <h6 class="mt-2">3. Personnalisable</h6>
                            <p class="small text-muted">Vous pouvez changer manuellement votre devise préférée</p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="index.php" class="btn btn-success me-3">
                        <i class="bi bi-house"></i> Retour à l'Accueil
                    </a>
                    <a href="panier.php" class="btn btn-outline-primary">
                        <i class="bi bi-cart"></i> Voir le Panier
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
