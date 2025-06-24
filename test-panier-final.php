<?php
/**
 * Test rapide du système de panier - sans conflits de session
 */

require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

echo "=== TEST SYSTÈME PANIER MODERNE ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Initialiser le gestionnaire de panier
    $cartManager = new CartManager($pdo);
    
    echo "✅ CartManager initialisé avec succès\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Oui' : 'Non') . "\n";
    
    // Test ajout d'un article
    $result = $cartManager->addItem(1, 2);
    if ($result['success']) {
        echo "✅ Ajout article réussi: " . $result['message'] . "\n";
    } else {
        echo "❌ Erreur ajout: " . $result['message'] . "\n";
    }
    
    // Résumé du panier
    $summary = $cartManager->getSummary();
    echo "\n📊 Résumé du panier:\n";
    echo "- Articles: " . $summary['items_count'] . "\n";
    echo "- Quantité: " . $summary['total_items'] . "\n";
    echo "- Total: " . number_format($summary['total_amount'], 2) . " €\n";
    echo "- Vide: " . ($summary['is_empty'] ? 'Oui' : 'Non') . "\n";
    
    // Nettoyer
    $cartManager->clear();
    echo "\n🧹 Panier nettoyé\n";
    
    echo "\n🎉 Tous les tests sont passés - Le système fonctionne correctement!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
