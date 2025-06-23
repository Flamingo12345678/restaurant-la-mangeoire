<?php
session_start();
require_once 'db_connexion.php';

/**
 * Script de vérification et réparation du panier
 */

echo "<!DOCTYPE html>";
echo "<html lang='fr'><head><meta charset='UTF-8'><title>Réparation Panier</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
    .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
    .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 3px; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: black; }
</style></head><body>";

echo "<h1>🔧 Réparation et Maintenance du Panier</h1>";

$action = $_GET['action'] ?? '';

if ($action == 'clean_orphans') {
    // Nettoyer les articles orphelins
    echo "<div class='alert alert-warning'>";
    echo "<h3>🧹 Nettoyage des articles orphelins</h3>";
    
    try {
        // Supprimer les articles du panier dont le menu n'existe plus
        $stmt = $pdo->prepare("
            DELETE p FROM Panier p 
            LEFT JOIN Menus m ON p.MenuID = m.MenuID 
            WHERE m.MenuID IS NULL
        ");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        
        echo "<p>✅ $deleted articles orphelins supprimés du panier.</p>";
        
        // Supprimer les articles avec quantité <= 0
        $stmt = $pdo->prepare("DELETE FROM Panier WHERE Quantite <= 0");
        $stmt->execute();
        $deleted_qty = $stmt->rowCount();
        
        echo "<p>✅ $deleted_qty articles avec quantité invalide supprimés.</p>";
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

if ($action == 'reset_session_cart') {
    // Réinitialiser le panier de session
    unset($_SESSION['panier']);
    echo "<div class='alert alert-success'>";
    echo "<h3>🔄 Panier de session réinitialisé</h3>";
    echo "<p>Le panier en session a été vidé.</p>";
    echo "</div>";
}

if ($action == 'fix_quantities') {
    // Corriger les quantités négatives ou nulles
    echo "<div class='alert alert-warning'>";
    echo "<h3>🔢 Correction des quantités</h3>";
    
    try {
        $stmt = $pdo->prepare("UPDATE Panier SET Quantite = 1 WHERE Quantite <= 0");
        $stmt->execute();
        $fixed = $stmt->rowCount();
        
        echo "<p>✅ $fixed quantités corrigées (remises à 1).</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

// Affichage des statistiques
echo "<div class='alert alert-success'>";
echo "<h3>📊 Statistiques du panier</h3>";

try {
    // Nombre total d'articles dans tous les paniers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Panier");
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre d'utilisateurs avec un panier
    $stmt = $pdo->query("SELECT COUNT(DISTINCT UtilisateurID) as users FROM Panier");
    $users_with_cart = $stmt->fetch(PDO::FETCH_ASSOC)['users'];
    
    // Articles orphelins
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphans 
        FROM Panier p 
        LEFT JOIN Menus m ON p.MenuID = m.MenuID 
        WHERE m.MenuID IS NULL
    ");
    $orphan_items = $stmt->fetch(PDO::FETCH_ASSOC)['orphans'];
    
    // Articles avec quantité problématique
    $stmt = $pdo->query("SELECT COUNT(*) as bad_qty FROM Panier WHERE Quantite <= 0");
    $bad_qty = $stmt->fetch(PDO::FETCH_ASSOC)['bad_qty'];
    
    echo "<ul>";
    echo "<li>🛒 <strong>$total_items</strong> articles total dans tous les paniers</li>";
    echo "<li>👥 <strong>$users_with_cart</strong> utilisateurs ont un panier</li>";
    echo "<li>⚠️ <strong>$orphan_items</strong> articles orphelins (menu supprimé)</li>";
    echo "<li>🔢 <strong>$bad_qty</strong> articles avec quantité problématique</li>";
    echo "</ul>";
    
    // Afficher les paniers récents
    echo "<h4>Paniers récents:</h4>";
    $stmt = $pdo->query("
        SELECT p.UtilisateurID, p.MenuID, p.Quantite, p.DateAjout, m.NomItem 
        FROM Panier p 
        LEFT JOIN Menus m ON p.MenuID = m.MenuID 
        ORDER BY p.DateAjout DESC 
        LIMIT 10
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Utilisateur</th><th>Article</th><th>Quantité</th><th>Date</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['NomItem'] ? '' : ' style="background:#ffebee"';
        echo "<tr$status>";
        echo "<td>" . $row['UtilisateurID'] . "</td>";
        echo "<td>" . ($row['NomItem'] ?: 'ARTICLE SUPPRIMÉ') . "</td>";
        echo "<td>" . $row['Quantite'] . "</td>";
        echo "<td>" . $row['DateAjout'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur lors de la récupération des statistiques: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Actions disponibles
echo "<div class='alert alert-warning'>";
echo "<h3>🛠️ Actions de maintenance</h3>";
echo "<p>Cliquez sur une action pour l'exécuter:</p>";
echo "<a href='?action=clean_orphans' class='btn btn-warning'>🧹 Nettoyer les articles orphelins</a>";
echo "<a href='?action=fix_quantities' class='btn btn-warning'>🔢 Corriger les quantités</a>";
echo "<a href='?action=reset_session_cart' class='btn btn-warning'>🔄 Réinitialiser panier session</a>";
echo "<br><br>";
echo "<a href='panier.php' class='btn btn-primary'>📋 Voir le panier</a>";
echo "<a href='diagnostic-panier-complet.php' class='btn btn-success'>🔍 Diagnostic complet</a>";
echo "</div>";

// Informations sur la session courante
if (isset($_SESSION['client_id'])) {
    echo "<div class='alert alert-success'>";
    echo "<h3>👤 Votre session</h3>";
    echo "<p>Connecté en tant qu'utilisateur ID: " . $_SESSION['client_id'] . "</p>";
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, m.NomItem, m.Prix 
            FROM Panier p 
            JOIN Menus m ON p.MenuID = m.MenuID 
            WHERE p.UtilisateurID = ?
        ");
        $stmt->execute([$_SESSION['client_id']]);
        $my_cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($my_cart)) {
            echo "<p>Votre panier contient " . count($my_cart) . " articles:</p>";
            echo "<ul>";
            foreach ($my_cart as $item) {
                echo "<li>" . $item['NomItem'] . " x" . $item['Quantite'] . " = " . number_format($item['Prix'] * $item['Quantite'], 0, ',', ' ') . " XAF</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Votre panier est vide.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Erreur lors de la récupération de votre panier: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h3>👤 Session non connectée</h3>";
    echo "<p>Vous n'êtes pas connecté. Utilisation du panier en session.</p>";
    if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
        echo "<p>Panier session: " . count($_SESSION['panier']) . " articles</p>";
    } else {
        echo "<p>Panier session vide.</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Maintenance effectuée le " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>
