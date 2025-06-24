<?php
/**
 * Test rapide du système de logging
 */

require_once __DIR__ . '/includes/email_notifications.php';

echo "Test du système de logging...\n";

try {
    $notifier = new EmailNotifications();
    
    // Test de création de log
    $test_data = [
        'nom' => 'Test Log System',
        'email' => 'test@example.com',
        'objet' => 'Test de logging',
        'message' => 'Message de test pour vérifier le système de logging'
    ];
    
    echo "Création d'un log de test...\n";
    $notifier->logNewMessage($test_data);
    
    echo "✅ Log créé avec succès !\n";
    
    // Vérifier que le fichier de log existe
    $log_file = __DIR__ . '/logs/contact_messages.log';
    if (file_exists($log_file)) {
        echo "✅ Fichier de log créé : $log_file\n";
        echo "Contenu du dernier log :\n";
        $logs = file($log_file);
        echo end($logs);
    } else {
        echo "❌ Fichier de log non trouvé\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

echo "\nTest terminé.\n";
?>
