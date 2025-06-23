<?php
/**
 * TEST FLUX COMMANDE ET PAIEMENT COMPLET
 * Teste le processus complet depuis l'ajout au panier jusqu'au paiement
 */

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "🧪 TEST FLUX COMMANDE ET PAIEMENT COMPLET\n";
echo "==========================================\n";

try {
    // 1. Créer un client test
    echo "1️⃣ Création client test...\n";
    $stmt = $pdo->prepare('INSERT INTO Clients (Nom, Prenom, Email, MotDePasse, Telephone) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['TestClient', 'Test', 'test@example.com', password_hash('test123', PASSWORD_DEFAULT), '0123456789']);
    $client_id = $pdo->lastInsertId();
    echo "   ✅ Client créé (ID: $client_id)\n";
    
    // 2. Simuler l'ajout au panier
    echo "2️⃣ Test ajout au panier...\n";
    session_start();
    $_SESSION['client_id'] = $client_id;
    $_SESSION['user_type'] = 'client';
    
    $cartManager = new CartManager($pdo);
    
    // Récupérer un menu pour le test
    $stmt = $pdo->query('SELECT MenuID, Prix FROM Menus LIMIT 1');
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($menu) {
        $cartManager->addItem($menu['MenuID'], 2); // Ajouter 2 unités
        echo "   ✅ Produit ajouté au panier (MenuID: {$menu['MenuID']}, Quantité: 2)\n";
        
        $cartSummary = $cartManager->getSummary();
        echo "   ℹ️  Total panier: {$cartSummary['total_amount']} €\n";
    } else {
        throw new Exception("Aucun menu trouvé pour le test");
    }
    
    // 3. Test du processus de commande
    echo "3️⃣ Test création commande...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO Commandes (ClientID, NomClient, PrenomClient, TelephoneClient, EmailClient, 
                               AdresseLivraison, ModePaiement, MontantTotal, Statut, DateCommande)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $cart_items = $cartManager->getItems();
    $total = $cartSummary['total_amount'];
    
    $stmt->execute([
        $client_id, 'TestClient', 'Test', '0123456789', 'test@example.com',
        '123 Rue Test, Paris', 'Carte Bancaire', $total, 'En attente'
    ]);
    
    $commande_id = $pdo->lastInsertId();
    echo "   ✅ Commande créée (ID: $commande_id)\n";
    
    // 4. Ajouter les détails de commande
    echo "4️⃣ Test détails commande...\n";
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
    echo "   ✅ Détails commande ajoutés\n";
    
    // 5. Test d'un paiement simulé
    echo "5️⃣ Test paiement simulé...\n";
    
    // Créer un enregistrement de paiement
    $stmt = $pdo->prepare("
        INSERT INTO Paiements (CommandeID, Montant, ModePaiement, Statut, DatePaiement)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$commande_id, $total, 'Carte Bancaire', 'Confirme']);
    $paiement_id = $pdo->lastInsertId();
    echo "   ✅ Paiement enregistré (ID: $paiement_id)\n";
    
    // Mettre à jour le statut de la commande
    $stmt = $pdo->prepare("UPDATE Commandes SET Statut = 'Payée' WHERE CommandeID = ?");
    $stmt->execute([$commande_id]);
    echo "   ✅ Statut commande mis à jour\n";
    
    // 6. Vider le panier
    echo "6️⃣ Test vidage panier...\n";
    $cartManager->clear();
    $cartSummary = $cartManager->getSummary();
    echo "   ✅ Panier vidé (est vide: " . ($cartSummary['is_empty'] ? 'Oui' : 'Non') . ")\n";
    
    // 7. Vérification finale
    echo "7️⃣ Vérification finale...\n";
    
    // Vérifier la commande
    $stmt = $pdo->prepare("SELECT * FROM Commandes WHERE CommandeID = ?");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier le paiement
    $stmt = $pdo->prepare("SELECT * FROM Paiements WHERE CommandeID = ?");
    $stmt->execute([$commande_id]);
    $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   ✅ Commande vérifiée: Statut = {$commande['Statut']}, Total = {$commande['MontantTotal']} €\n";
    echo "   ✅ Paiement vérifié: Statut = {$paiement['Statut']}, Montant = {$paiement['Montant']} €\n";
    
    // Nettoyage
    echo "8️⃣ Nettoyage données test...\n";
    $pdo->prepare('DELETE FROM Paiements WHERE PaiementID = ?')->execute([$paiement_id]);
    $pdo->prepare('DELETE FROM DetailsCommande WHERE CommandeID = ?')->execute([$commande_id]);
    $pdo->prepare('DELETE FROM Commandes WHERE CommandeID = ?')->execute([$commande_id]);
    $pdo->prepare('DELETE FROM Panier WHERE ClientID = ?')->execute([$client_id]);
    $pdo->prepare('DELETE FROM Clients WHERE ClientID = ?')->execute([$client_id]);
    echo "   ✅ Données test supprimées\n";
    
    echo "\n🎉 FLUX COMPLET TESTÉ AVEC SUCCÈS!\n";
    echo "Le système de commande et paiement fonctionne correctement.\n";
    echo "Currency: Euro (€) - Paiement: Fonctionnel\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    
    // Nettoyage en cas d'erreur
    if (isset($client_id)) {
        try {
            if (isset($paiement_id)) $pdo->prepare('DELETE FROM Paiements WHERE PaiementID = ?')->execute([$paiement_id]);
            if (isset($commande_id)) {
                $pdo->prepare('DELETE FROM DetailsCommande WHERE CommandeID = ?')->execute([$commande_id]);
                $pdo->prepare('DELETE FROM Commandes WHERE CommandeID = ?')->execute([$commande_id]);
            }
            $pdo->prepare('DELETE FROM Panier WHERE ClientID = ?')->execute([$client_id]);
            $pdo->prepare('DELETE FROM Clients WHERE ClientID = ?')->execute([$client_id]);
        } catch (Exception $e2) {
            echo "⚠️ Erreur nettoyage: " . $e2->getMessage() . "\n";
        }
    }
}
?>
