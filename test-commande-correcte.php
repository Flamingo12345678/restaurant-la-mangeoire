<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'><head><meta charset='UTF-8'><title>Test Commande Corrigée</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";
echo "</head><body>";

echo "<h1>🧪 Test de la Commande Corrigée</h1>";

// Ajouter un article au panier pour test si nécessaire
if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
    // Récupérer un menu pour le test
    $stmt = $pdo->query("SELECT MenuID, NomItem, Prix FROM Menus LIMIT 1");
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($menu) {
        $_SESSION['panier'] = [[
            'MenuID' => $menu['MenuID'],
            'NomItem' => $menu['NomItem'],
            'Prix' => $menu['Prix'],
            'Quantite' => 1
        ]];
        echo "<p class='success'>✓ Article ajouté au panier pour test: " . $menu['NomItem'] . "</p>";
    }
}

// Données de test
$nom = 'YOMBI';
$prenom = 'Ernest Evrard';
$telephone = '0644060866';
$email = 'ernestyombi20@gmail.com';
$adresse = '123 Rue Test, Yaoundé';
$instructions = 'Test de commande';
$mode_livraison = 'livraison';
$mode_paiement = 'especes';

// Calculer le total
$cart_items = $_SESSION['panier'] ?? [];
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['Prix'] * $item['Quantite'];
}

echo "<h3>Données de la commande de test:</h3>";
echo "<ul>";
echo "<li>Nom: $nom</li>";
echo "<li>Prénom: $prenom</li>";
echo "<li>Téléphone: $telephone</li>";
echo "<li>Email: $email</li>";
echo "<li>Total: $total €</li>";
echo "<li>Articles: " . count($cart_items) . "</li>";
echo "</ul>";

if (!empty($cart_items)) {
    try {
        // Commencer une transaction
        $pdo->beginTransaction();
        
        // Insérer la commande avec la structure correcte
        $stmt = $pdo->prepare("
            INSERT INTO Commandes (
                ClientID, NomClient, PrenomClient, TelephoneClient, EmailClient, 
                AdresseLivraison, ModePaiement, MontantTotal, Statut, DateCommande
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'En attente', NOW())
        ");
        
        // Créer l'adresse complète
        $adresse_complete = $adresse;
        if (!empty($instructions)) {
            $adresse_complete .= "\n\nInstructions: " . $instructions;
        }
        $adresse_complete .= "\nMode: " . ($mode_livraison === 'livraison' ? 'Livraison' : 'Retrait sur place');
        
        $user_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;
        $result = $stmt->execute([
            $user_id, $nom, $prenom, $telephone, $email, 
            $adresse_complete, $mode_paiement, $total
        ]);
        
        if ($result) {
            $commande_id = $pdo->lastInsertId();
            echo "<p class='success'>✓ Commande créée avec ID: $commande_id</p>";
            
            // Insérer les détails
            $stmt = $pdo->prepare("
                INSERT INTO DetailsCommande (CommandeID, MenuID, NomItem, Prix, Quantite, SousTotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $sous_total = $item['Prix'] * $item['Quantite'];
                $detail_result = $stmt->execute([
                    $commande_id, 
                    $item['MenuID'], 
                    $item['NomItem'],
                    $item['Prix'], 
                    $item['Quantite'], 
                    $sous_total
                ]);
                
                if ($detail_result) {
                    echo "<p class='success'>✓ Détail ajouté: " . $item['NomItem'] . "</p>";
                } else {
                    echo "<p class='error'>✗ Erreur détail: " . $item['NomItem'] . "</p>";
                }
            }
            
            // Valider la transaction
            $pdo->commit();
            echo "<p class='success'><strong>✅ Commande de test créée avec succès !</strong></p>";
            echo "<p>ID de commande: $commande_id</p>";
            
            // Afficher la commande créée
            $stmt = $pdo->prepare("SELECT * FROM Commandes WHERE CommandeID = ?");
            $stmt->execute([$commande_id]);
            $commande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Détails de la commande créée:</h3>";
            echo "<pre>" . print_r($commande, true) . "</pre>";
            
        } else {
            echo "<p class='error'>✗ Échec de l'insertion de commande</p>";
            echo "<pre>Error Info: " . print_r($stmt->errorInfo(), true) . "</pre>";
        }
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        echo "<p class='error'>✗ Exception: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p class='error'>✗ Panier vide, impossible de tester</p>";
}

echo "<hr>";
echo "<p><a href='passer-commande.php'>Tester la vraie page de commande</a></p>";
echo "</body></html>";
?>
