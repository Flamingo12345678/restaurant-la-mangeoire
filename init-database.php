<?php
/**
 * Script d'initialisation de la base de données
 * Crée toutes les tables nécessaires pour le restaurant
 */

echo "=== INITIALISATION BASE DE DONNÉES ===\n\n";

// Configuration locale
$host = 'localhost';
$dbname = 'restaurant_db';
$username = 'root';
$password = '';
$port = 3306;
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // 1. Table Messages
    echo "Création de la table Messages...\n";
    $sql_messages = "
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
    
    $pdo->exec($sql_messages);
    echo "✅ Table Messages créée\n";
    
    // 2. Table Commandes
    echo "Création de la table Commandes...\n";
    $sql_commandes = "
    CREATE TABLE IF NOT EXISTS Commandes (
        CommandeID INT AUTO_INCREMENT PRIMARY KEY,
        NomClient VARCHAR(100) NOT NULL,
        EmailClient VARCHAR(150) NOT NULL,
        TelephoneClient VARCHAR(20),
        AdresseLivraison TEXT,
        MontantTotal DECIMAL(10,2) NOT NULL,
        Statut ENUM('En_attente', 'Confirmee', 'En_preparation', 'Prete', 'Livree', 'Payée') DEFAULT 'En_attente',
        ModePaiement VARCHAR(50),
        DateCommande DATETIME DEFAULT CURRENT_TIMESTAMP,
        Notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_commandes);
    echo "✅ Table Commandes créée\n";
    
    // 3. Table Paiements
    echo "Création de la table Paiements...\n";
    $sql_paiements = "
    CREATE TABLE IF NOT EXISTS Paiements (
        PaiementID INT AUTO_INCREMENT PRIMARY KEY,
        CommandeID INT NOT NULL,
        Montant DECIMAL(10,2) NOT NULL,
        ModePaiement VARCHAR(50) NOT NULL,
        Statut ENUM('En_attente', 'Confirme', 'Echoue', 'Rembourse') DEFAULT 'En_attente',
        DatePaiement DATETIME DEFAULT CURRENT_TIMESTAMP,
        TransactionID VARCHAR(100),
        DetailsTransaction TEXT,
        FOREIGN KEY (CommandeID) REFERENCES Commandes(CommandeID) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_paiements);
    echo "✅ Table Paiements créée\n";
    
    // 4. Table Menu (optionnelle pour les articles)
    echo "Création de la table MenuItems...\n";
    $sql_menu = "
    CREATE TABLE IF NOT EXISTS MenuItems (
        ItemID INT AUTO_INCREMENT PRIMARY KEY,
        Nom VARCHAR(100) NOT NULL,
        Description TEXT,
        Prix DECIMAL(8,2) NOT NULL,
        Categorie VARCHAR(50),
        ImageURL VARCHAR(255),
        Disponible BOOLEAN DEFAULT TRUE,
        DateCreation DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_menu);
    echo "✅ Table MenuItems créée\n";
    
    // 5. Insérer quelques données de test
    echo "\nInsertion de données de test...\n";
    
    // Quelques articles de menu
    $items = [
        ['Burger Classique', 'Burger avec steak, salade, tomate, oignon', 12.50, 'Burgers'],
        ['Pizza Margherita', 'Tomate, mozzarella, basilic', 14.00, 'Pizzas'],
        ['Salade César', 'Salade romaine, croûtons, parmesan, sauce césar', 10.50, 'Salades'],
        ['Pâtes Carbonara', 'Pâtes aux œufs, lardons, crème, parmesan', 13.00, 'Pâtes']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO MenuItems (Nom, Description, Prix, Categorie) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->execute($item);
    }
    echo "✅ " . count($items) . " articles ajoutés au menu\n";
    
    // Test d'insertion d'un message
    $stmt = $pdo->prepare("INSERT INTO Messages (Nom, Email, Sujet, Message) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin Test', 'admin@lamangeoire.fr', 'Test système', 'Message de test pour vérifier le fonctionnement']);
    echo "✅ Message de test inséré (ID: " . $pdo->lastInsertId() . ")\n";
    
    echo "\n=== INITIALISATION TERMINÉE AVEC SUCCÈS ===\n";
    echo "Base de données prête à l'emploi !\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

?>
