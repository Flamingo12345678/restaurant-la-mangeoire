<?php
// 🔧 DIAGNOSTIC ENVIRONNEMENT - Restaurant La Mangeoire
// Fichier pour déboguer les problèmes de variables d'environnement

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Environnement - La Mangeoire</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #27ae60; background: #d5f4e6; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .error { color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .warning { color: #f39c12; background: #fef9e7; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .info { color: #3498db; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .var-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .var-table th, .var-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .var-table th { background: #f2f2f2; }
        .masked { background: #ffffcc; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnostic Environnement - Restaurant La Mangeoire</h1>
        
        <div class="section">
            <h2>📋 Informations Générales</h2>
            <div class="info">
                <strong>Date/Heure:</strong> <?= date('Y-m-d H:i:s') ?><br>
                <strong>PHP Version:</strong> <?= PHP_VERSION ?><br>
                <strong>Serveur:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' ?><br>
                <strong>OS:</strong> <?= PHP_OS ?><br>
                <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu' ?><br>
                <strong>Script Path:</strong> <?= __DIR__ ?>
            </div>
        </div>

        <div class="section">
            <h2>📁 Fichiers d'Environnement</h2>
            <?php
            $envFile = __DIR__ . '/.env';
            $envProdFile = __DIR__ . '/.env.production';
            
            if (file_exists($envFile)) {
                echo "<div class='success'>✅ Fichier .env trouvé</div>";
                echo "<div class='info'>Taille: " . filesize($envFile) . " octets</div>";
            } else {
                echo "<div class='error'>❌ Fichier .env introuvable</div>";
            }
            
            if (file_exists($envProdFile)) {
                echo "<div class='success'>✅ Fichier .env.production trouvé</div>";
                echo "<div class='info'>Taille: " . filesize($envProdFile) . " octets</div>";
            } else {
                echo "<div class='warning'>⚠️ Fichier .env.production introuvable</div>";
            }
            ?>
        </div>

        <div class="section">
            <h2>🔍 Détection d'Environnement</h2>
            <?php
            $isRailway = getenv('RAILWAY_ENVIRONMENT') || isset($_ENV['RAILWAY_ENVIRONMENT']);
            $isProduction = getenv('APP_ENV') === 'production' || isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production';
            
            if ($isRailway) {
                echo "<div class='success'>✅ Environnement Railway détecté</div>";
            } else {
                echo "<div class='info'>ℹ️ Environnement Railway non détecté</div>";
            }
            
            if ($isProduction) {
                echo "<div class='success'>✅ Mode production détecté</div>";
            } else {
                echo "<div class='warning'>⚠️ Mode production non détecté</div>";
            }
            ?>
        </div>

        <div class="section">
            <h2>🗃️ Variables d'Environnement Essentielles</h2>
            <table class="var-table">
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Valeur</th>
                        <th>Source</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $essentialVars = [
                        'MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT',
                        'STRIPE_PUBLISHABLE_KEY', 'STRIPE_SECRET_KEY',
                        'PAYPAL_CLIENT_ID', 'PAYPAL_SECRET_KEY',
                        'FORCE_HTTPS', 'APP_ENV', 'SITE_URL',
                        'SMTP_HOST', 'SMTP_USER', 'SMTP_PASS'
                    ];
                    
                    foreach ($essentialVars as $var) {
                        $value = $_ENV[$var] ?? getenv($var) ?? '';
                        $source = '';
                        $status = '';
                        
                        if (isset($_ENV[$var])) {
                            $source = '$_ENV';
                        } elseif (getenv($var)) {
                            $source = 'getenv()';
                        } else {
                            $source = 'Aucune';
                        }
                        
                        if (empty($value)) {
                            $status = "<span style='color: #e74c3c;'>❌ Manquante</span>";
                            $displayValue = '<em>Non définie</em>';
                        } else {
                            $status = "<span style='color: #27ae60;'>✅ OK</span>";
                            // Masquer les valeurs sensibles
                            if (strpos($var, 'PASSWORD') !== false || strpos($var, 'SECRET') !== false || strpos($var, 'KEY') !== false) {
                                $displayValue = "<span class='masked'>" . substr($value, 0, 10) . "..." . substr($value, -4) . "</span>";
                            } else {
                                $displayValue = htmlspecialchars($value);
                            }
                        }
                        
                        echo "<tr>";
                        echo "<td><strong>$var</strong></td>";
                        echo "<td>$displayValue</td>";
                        echo "<td>$source</td>";
                        echo "<td>$status</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>🔗 Test de Connexion Base de Données</h2>
            <?php
            try {
                // Tester la connexion
                $host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? '';
                $database = $_ENV['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?? '';
                $username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? '';
                $password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';
                $port = $_ENV['MYSQLPORT'] ?? getenv('MYSQLPORT') ?? 3306;
                
                if (empty($host) || empty($database) || empty($username)) {
                    throw new Exception("Variables de base de données manquantes");
                }
                
                $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                // Test simple
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                
                if ($result['test'] == 1) {
                    echo "<div class='success'>✅ Connexion base de données réussie</div>";
                    echo "<div class='info'>Serveur: $host:$port | Base: $database</div>";
                } else {
                    echo "<div class='error'>❌ Test de connexion échoué</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erreur de connexion: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
        </div>

        <div class="section">
            <h2>📞 Recommandations</h2>
            <?php
            $recommendations = [];
            
            if (empty($_ENV['FORCE_HTTPS'] ?? getenv('FORCE_HTTPS'))) {
                $recommendations[] = "Définir FORCE_HTTPS=true pour la sécurité";
            }
            
            if (empty($_ENV['APP_ENV'] ?? getenv('APP_ENV'))) {
                $recommendations[] = "Définir APP_ENV=production";
            }
            
            if (empty($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'))) {
                $recommendations[] = "Configurer les clés Stripe pour les paiements";
            }
            
            if (empty($_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST'))) {
                $recommendations[] = "Configurer SMTP pour l'envoi d'emails";
            }
            
            if (empty($recommendations)) {
                echo "<div class='success'>✅ Configuration semble correcte</div>";
            } else {
                echo "<div class='warning'>⚠️ Améliorations suggérées:</div>";
                echo "<ul>";
                foreach ($recommendations as $rec) {
                    echo "<li>$rec</li>";
                }
                echo "</ul>";
            }
            ?>
        </div>

        <div class="section">
            <h2>⚙️ Actions Suggérées</h2>
            <div class="info">
                <strong>Si vous êtes sur Railway:</strong><br>
                1. Utilisez la commande: <code>railway variables set VARIABLE_NAME=value</code><br>
                2. Ou configurez via l'interface web Railway<br>
                3. Redéployez après modification: <code>railway up</code>
            </div>
            <div class="info">
                <strong>Si vous êtes en local:</strong><br>
                1. Vérifiez que le fichier .env existe<br>
                2. Vérifiez les permissions du fichier<br>
                3. Rechargez votre serveur web
            </div>
        </div>
    </div>
</body>
</html>
