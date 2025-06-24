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

// Test des conversions avec des prix d'exemple
$test_prices = [
    ['nom' => 'Salade César', 'prix_eur' => 12.90],
    ['nom' => 'Foie Gras Maison', 'prix_eur' => 16.50],
    ['nom' => 'Entrecôte Grillée', 'prix_eur' => 24.90],
    ['nom' => 'Pavé de Saumon', 'prix_eur' => 22.50],
    ['nom' => 'Tiramisu Classique', 'prix_eur' => 8.90]
];

// Quelques prix d'exemple depuis la base de données
$menu_prices = [];
try {
    $stmt = $pdo->prepare("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 10");
    $stmt->execute();
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($menus as $menu) {
        $menu_prices[] = [
            'id' => $menu['MenuID'],
            'nom' => $menu['NomItem'],
            'prix_eur' => $menu['Prix'],
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
    <title>Démonstration Système de Devises - La Mangeoire</title>
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
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .currency-selector {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .price-demo {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .price-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .price-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .price-original {
            color: #6c757d;
            font-size: 0.9em;
        }
        .price-converted {
            font-size: 1.2em;
            font-weight: 600;
            color: #ce1212;
        }
        .status-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .test-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .conversion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .conversion-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .conversion-card .currency {
            font-weight: 600;
            color: #495057;
        }
        .conversion-card .amount {
            font-size: 1.1em;
            font-weight: 600;
            color: #ce1212;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-5">
                    <i class="bi bi-currency-exchange"></i>
                    Démonstration du Système de Devises
                </h1>
            </div>
        </div>

        <!-- Informations système actuel -->
        <div class="row">
            <div class="col-12">
                <div class="currency-info">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-geo-alt"></i> Détection Automatique</h3>
                            <p class="mb-2"><strong>Pays détecté:</strong> <?php echo $user_country; ?></p>
                            <p class="mb-2"><strong>Devise actuelle:</strong> <?php echo $current_currency['name']; ?> (<?php echo $current_currency['code']; ?>)</p>
                            <p class="mb-0"><strong>Symbole:</strong> <?php echo $current_currency['symbol']; ?></p>
                            <span class="status-badge">✓ Système EUR comme base</span>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="display-1"><?php echo $current_currency['symbol']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sélecteur de devise -->
        <div class="row">
            <div class="col-12">
                <div class="currency-selector">
                    <h4><i class="bi bi-toggles"></i> Changer de Devise</h4>
                    <p class="text-muted mb-3">Testez le système en changeant la devise d'affichage</p>
                    
                    <form method="post" class="row g-3">
                        <div class="col-md-8">
                            <select name="country" class="form-select">
                                <optgroup label="Europe">
                                    <option value="FR" <?php echo ($user_country === 'FR') ? 'selected' : ''; ?>>🇫🇷 France - Euro (€)</option>
                                    <option value="DE" <?php echo ($user_country === 'DE') ? 'selected' : ''; ?>>🇩🇪 Allemagne - Euro (€)</option>
                                    <option value="GB" <?php echo ($user_country === 'GB') ? 'selected' : ''; ?>>🇬🇧 Royaume-Uni - Livre Sterling (£)</option>
                                    <option value="CH" <?php echo ($user_country === 'CH') ? 'selected' : ''; ?>>🇨🇭 Suisse - Franc Suisse (CHF)</option>
                                </optgroup>
                                <optgroup label="Amériques">
                                    <option value="US" <?php echo ($user_country === 'US') ? 'selected' : ''; ?>>🇺🇸 États-Unis - Dollar US ($)</option>
                                    <option value="CA" <?php echo ($user_country === 'CA') ? 'selected' : ''; ?>>🇨🇦 Canada - Dollar Canadien (C$)</option>
                                </optgroup>
                                <optgroup label="Afrique">
                                    <option value="CM" <?php echo ($user_country === 'CM') ? 'selected' : ''; ?>>🇨🇲 Cameroun - Franc CFA (FCFA)</option>
                                    <option value="SN" <?php echo ($user_country === 'SN') ? 'selected' : ''; ?>>🇸🇳 Sénégal - Franc CFA (FCFA)</option>
                                    <option value="ZA" <?php echo ($user_country === 'ZA') ? 'selected' : ''; ?>>🇿🇦 Afrique du Sud - Rand (R)</option>
                                </optgroup>
                                <optgroup label="Asie-Océanie">
                                    <option value="JP" <?php echo ($user_country === 'JP') ? 'selected' : ''; ?>>🇯🇵 Japon - Yen (¥)</option>
                                    <option value="AU" <?php echo ($user_country === 'AU') ? 'selected' : ''; ?>>🇦🇺 Australie - Dollar Australien (A$)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="change_currency" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Changer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Test de conversion avec prix exemple -->
        <div class="row">
            <div class="col-md-6">
                <div class="test-section">
                    <h4><i class="bi bi-calculator"></i> Test de Conversion</h4>
                    <p class="text-muted">Exemple avec un prix de 25,00 € (prix de base)</p>
                    
                    <div class="conversion-grid">
                        <?php
                        $test_price = 25.00;
                        $key_currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'XAF', 'JPY', 'AUD'];
                        
                        foreach ($key_currencies as $currency_code) {
                            $converted = CurrencyManager::convertPrice($test_price, $currency_code);
                            $currency_info = null;
                            
                            // Trouver les infos de la devise
                            foreach (CurrencyManager::getAvailableCurrencies() as $curr) {
                                if ($curr['code'] === $currency_code) {
                                    $currency_info = $curr;
                                    break;
                                }
                            }
                            
                            if ($currency_info) {
                        ?>
                        <div class="conversion-card <?php echo ($currency_code === $current_currency['code']) ? 'border-primary bg-light' : ''; ?>">
                            <div class="currency"><?php echo $currency_info['code']; ?></div>
                            <div class="amount">
                                <?php 
                                if (in_array($currency_code, ['JPY', 'KRW', 'VND', 'IDR', 'XAF', 'XOF'])) {
                                    echo number_format($converted, 0, ',', ' ');
                                } else {
                                    echo number_format($converted, 2, ',', ' ');
                                }
                                echo ' ' . $currency_info['symbol']; 
                                ?>
                            </div>
                            <?php if ($currency_code === $current_currency['code']): ?>
                            <small class="text-primary">← Actuel</small>
                            <?php endif; ?>
                        </div>
                        <?php 
                            }
                        } 
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="test-section">
                    <h4><i class="bi bi-info-circle"></i> Fonctionnalités</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Base EUR:</strong> Tous les prix sont stockés en Euro dans la base de données
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Détection automatique:</strong> Pays et devise détectés selon la localisation
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Conversion en temps réel:</strong> Tous les prix sont convertis à l'affichage
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Formatage intelligent:</strong> Respect des conventions locales (décimales, séparateurs)
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Sessions persistantes:</strong> Choix de devise sauvegardé en session
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Multi-pages:</strong> Système intégré sur index.php, menu.php, panier.php
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Prix réels de la base de données -->
        <?php if (!empty($menu_prices)): ?>
        <div class="row">
            <div class="col-12">
                <div class="price-demo">
                    <h4><i class="bi bi-database"></i> Prix Réels de la Base de Données</h4>
                    <p class="text-muted mb-4">Exemples de prix récupérés depuis la base de données et convertis automatiquement</p>
                    
                    <div class="row">
                        <?php foreach ($menu_prices as $menu): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="price-item">
                                <h6 class="mb-2"><?php echo htmlspecialchars($menu['nom']); ?></h6>
                                <div class="price-converted"><?php echo $menu['prix_local']; ?></div>
                                <div class="price-original">Prix de base: <?php echo number_format($menu['prix_eur'], 2); ?> €</div>
                                <small class="text-muted">ID: <?php echo $menu['id']; ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>            
        </div>
        <?php endif; ?>

        <!-- Navigation vers les autres pages -->
        <div class="row">
            <div class="col-12">
                <div class="test-section">
                    <h4><i class="bi bi-link-45deg"></i> Tester sur les Autres Pages</h4>
                    <p class="text-muted mb-3">Le système de devises est maintenant intégré sur les pages principales :</p>
                    
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="index.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-house"></i> Accueil
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="menu.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-book"></i> Menu
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="panier.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-cart"></i> Panier
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="test-currency.php" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Actualiser Test
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
