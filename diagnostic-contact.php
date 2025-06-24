<?php
/**
 * Test de diagnostic - Contact et Base de données
 */

echo "=== DIAGNOSTIC CONTACT/DATABASE ===\n\n";

// 1. Test des variables d'environnement
echo "1. Variables d'environnement:\n";
$env_vars = ['MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT'];
foreach ($env_vars as $var) {
    $value = getenv($var) ?: ($_ENV[$var] ?? '');
    if (empty($value)) {
        echo "   ❌ $var: manquante\n";
    } else {
        echo "   ✅ $var: présente\n";
    }
}

echo "\n2. Test de connexion avec configuration locale:\n";

// Configuration locale pour développement
$local_config = [
    'host' => 'localhost',
    'dbname' => 'restaurant_db',
    'username' => 'root',
    'password' => '',
    'port' => 3306
];

try {
    $dsn = "mysql:host={$local_config['host']};port={$local_config['port']};dbname={$local_config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $local_config['username'], $local_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "   ✅ Connexion locale réussie\n";
    
    // Test de la table Messages
    try {
        $stmt = $pdo->query("DESCRIBE Messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✅ Table Messages existe:\n";
        foreach ($columns as $col) {
            echo "      - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Table Messages n'existe pas, création...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS Messages (
            MessageID INT AUTO_INCREMENT PRIMARY KEY,
            Nom VARCHAR(100) NOT NULL,
            Email VARCHAR(150) NOT NULL,
            Sujet VARCHAR(200) NOT NULL,
            Message TEXT NOT NULL,
            DateEnvoi DATETIME DEFAULT CURRENT_TIMESTAMP,
            Statut ENUM('Nouveau', 'Lu', 'Traite') DEFAULT 'Nouveau'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        echo "   ✅ Table Messages créée avec succès\n";
    }
    
    // Test d'insertion
    echo "\n3. Test d'insertion dans Messages:\n";
    $stmt = $pdo->prepare("INSERT INTO Messages (Nom, Email, Sujet, Message) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Test', 'test@example.com', 'Test Sujet', 'Message de test']);
    echo "   ✅ Insertion test réussie (ID: " . $pdo->lastInsertId() . ")\n";
    
    // Compter les messages
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Messages");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ℹ️  Total messages en base: {$count['count']}\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur connexion locale: " . $e->getMessage() . "\n";
    
    echo "\n4. Création d'un fichier de connexion local:\n";
    
    $local_db_content = '<?php
// Configuration locale pour développement
$host = "localhost";
$dbname = "restaurant_db";
$username = "root";
$password = "";
$port = 3306;
$charset = "utf8mb4";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    // Créer la table Messages si elle n\'existe pas
    $sql = "
    CREATE TABLE IF NOT EXISTS Messages (
        MessageID INT AUTO_INCREMENT PRIMARY KEY,
        Nom VARCHAR(100) NOT NULL,
        Email VARCHAR(150) NOT NULL,
        Sujet VARCHAR(200) NOT NULL,
        Message TEXT NOT NULL,
        DateEnvoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        Statut ENUM(\'Nouveau\', \'Lu\', \'Traite\') DEFAULT \'Nouveau\'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    
} catch (PDOException $e) {
    error_log("Erreur connexion BD: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Vérifiez votre configuration.");
}
?>';
    
    file_put_contents('db_connexion_local.php', $local_db_content);
    echo "   ✅ Fichier db_connexion_local.php créé\n";
}

echo "\n=== SOLUTIONS RECOMMANDÉES ===\n";
echo "1. Si vous développez en local, utilisez db_connexion_local.php\n";
echo "2. Assurez-vous que MySQL/XAMPP/MAMP est démarré\n";
echo "3. Créez la base de données 'restaurant_db' si nécessaire\n";
echo "4. Modifiez contact.php pour utiliser la configuration locale\n";

?>
