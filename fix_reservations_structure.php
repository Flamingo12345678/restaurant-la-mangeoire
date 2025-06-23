<?php
/**
 * Script de correction et harmonisation de la table des réservations
 * Ce script corrige les incohérences entre le code et la structure de la base de données
 */

require_once 'db_connexion.php';

try {
    echo "🔧 Correction de la structure des réservations...\n\n";

    // 1. Vérifier si l'ancienne table Reservations existe
    $check_old = $conn->query("SHOW TABLES LIKE 'Reservations'");
    $old_exists = $check_old->rowCount() > 0;
    
    // 2. Vérifier si la nouvelle table reservations existe
    $check_new = $conn->query("SHOW TABLES LIKE 'reservations'");
    $new_exists = $check_new->rowCount() > 0;
    
    echo "📊 État actuel :\n";
    echo "- Table 'Reservations' (ancien format) : " . ($old_exists ? "✅ Existe" : "❌ N'existe pas") . "\n";
    echo "- Table 'reservations' (nouveau format) : " . ($new_exists ? "✅ Existe" : "❌ N'existe pas") . "\n\n";
    
    // 3. Créer ou corriger la table avec la bonne structure
    $sql_create = "CREATE TABLE IF NOT EXISTS reservations (
        ReservationID INT AUTO_INCREMENT PRIMARY KEY,
        nom_client VARCHAR(100) NOT NULL,
        email_client VARCHAR(255) NOT NULL,
        telephone VARCHAR(20),
        nb_personnes INT NOT NULL DEFAULT 1,
        DateReservation DATETIME NOT NULL,
        message TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut ENUM('Réservée', 'Annulée', 'Confirmée', 'Terminée') DEFAULT 'Réservée',
        
        INDEX idx_date_reservation (DateReservation),
        INDEX idx_statut (statut),
        INDEX idx_email (email_client)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql_create);
    echo "✅ Table 'reservations' créée/mise à jour avec la bonne structure\n";
    
    // 4. Si l'ancienne table existe, migrer les données
    if ($old_exists) {
        echo "📦 Migration des données de l'ancienne table...\n";
        
        // Récupérer les données de l'ancienne table
        $old_data = $conn->query("SELECT * FROM Reservations")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($old_data)) {
            $insert_sql = "INSERT INTO reservations (ReservationID, nom_client, email_client, nb_personnes, DateReservation, statut) 
                          VALUES (?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          nom_client = VALUES(nom_client),
                          email_client = VALUES(email_client),
                          nb_personnes = VALUES(nb_personnes),
                          DateReservation = VALUES(DateReservation),
                          statut = VALUES(statut)";
            
            $stmt = $conn->prepare($insert_sql);
            
            foreach ($old_data as $row) {
                $stmt->execute([
                    $row['ReservationID'] ?? null,
                    $row['nom_client'] ?? '',
                    $row['email_client'] ?? '',
                    $row['nb_personnes'] ?? 1,
                    $row['DateReservation'] ?? date('Y-m-d H:i:s'),
                    $row['statut'] ?? 'Réservée'
                ]);
            }
            
            echo "✅ " . count($old_data) . " réservations migrées\n";
        }
        
        // Renommer l'ancienne table en sauvegarde
        $conn->exec("RENAME TABLE Reservations TO Reservations_backup_" . date('Y_m_d_H_i_s'));
        echo "📋 Ancienne table sauvegardée\n";
    }
    
    // 5. Ajouter quelques réservations de test si la table est vide
    $count = $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    
    if ($count == 0) {
        echo "📝 Ajout de données de test...\n";
        
        $test_data = [
            ['Ernest Evrard YOMBI', 'ernestyombi20@gmail.com', 4, '2025-06-25 19:00:00', 'Réservée'],
            ['Marie Dupont', 'marie.dupont@email.com', 2, '2025-06-26 20:00:00', 'Réservée'],
            ['Jean Martin', 'jean.martin@email.com', 6, '2025-06-27 19:30:00', 'Confirmée'],
            ['Sophie Durand', 'sophie.durand@email.com', 3, '2025-06-28 20:30:00', 'Réservée']
        ];
        
        $stmt = $conn->prepare("INSERT INTO reservations (nom_client, email_client, nb_personnes, DateReservation, statut) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($test_data as $data) {
            $stmt->execute($data);
        }
        
        echo "✅ " . count($test_data) . " réservations de test ajoutées\n";
    }
    
    // 6. Afficher la structure finale
    echo "\n📋 Structure finale de la table 'reservations' :\n";
    echo str_repeat("=", 80) . "\n";
    
    $describe = $conn->query("DESCRIBE reservations");
    $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
    
    printf("%-20s %-25s %-10s %-10s %-15s\n", "Colonne", "Type", "Null", "Clé", "Défaut");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-20s %-25s %-10s %-10s %-15s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'],
            $column['Default'] ?? 'NULL'
        );
    }
    
    // 7. Afficher le contenu actuel
    $current_count = $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    echo "\n📊 Contenu actuel : $current_count réservations\n";
    
    if ($current_count > 0) {
        $sample = $conn->query("SELECT ReservationID, nom_client, email_client, DateReservation, statut FROM reservations ORDER BY ReservationID DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Aperçu des dernières réservations :\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-5s %-25s %-30s %-20s %-15s\n", "ID", "Nom", "Email", "Date", "Statut");
        echo str_repeat("-", 100) . "\n";
        
        foreach ($sample as $res) {
            printf("%-5s %-25s %-30s %-20s %-15s\n", 
                $res['ReservationID'],
                substr($res['nom_client'], 0, 24),
                substr($res['email_client'], 0, 29),
                substr($res['DateReservation'], 0, 19),
                $res['statut']
            );
        }
    }
    
    echo "\n✨ Correction terminée avec succès !\n";
    echo "🔗 Vous pouvez maintenant accéder à : http://localhost:8000/reservations.php\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la correction : " . $e->getMessage() . "\n";
    echo "📝 Détails : " . $e->getTraceAsString() . "\n";
}
?>
