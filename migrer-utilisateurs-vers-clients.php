<?php
/**
 * Migration des utilisateurs de la table Utilisateurs vers Clients
 * Pour unifier le système d'authentification
 */

require_once 'db_connexion.php';

echo "=== MIGRATION UTILISATEURS → CLIENTS ===\n\n";

try {
    // 1. Vérifier les utilisateurs existants
    $stmt = $pdo->query("SELECT * FROM Utilisateurs");
    $utilisateurs = $stmt->fetchAll();
    
    echo "1. Utilisateurs trouvés dans la table Utilisateurs: " . count($utilisateurs) . "\n";
    
    if (empty($utilisateurs)) {
        echo "   Aucun utilisateur à migrer.\n";
        exit;
    }
    
    // 2. Afficher les utilisateurs
    foreach ($utilisateurs as $user) {
        echo "   - {$user['UtilisateurID']}: {$user['Nom']} {$user['Prenom']} ({$user['Email']})\n";
    }
    
    echo "\n2. Vérification des doublons dans Clients...\n";
    
    // 3. Migrer chaque utilisateur
    $migrated = 0;
    $skipped = 0;
    
    foreach ($utilisateurs as $user) {
        // Vérifier si l'email existe déjà dans Clients
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Clients WHERE Email = ?");
        $stmt->execute([$user['Email']]);
        $exists = $stmt->fetchColumn();
        
        if ($exists > 0) {
            echo "   Utilisateur {$user['Email']} existe déjà dans Clients - ignoré\n";
            $skipped++;
            continue;
        }
        
        // Insérer dans Clients
        $stmt = $pdo->prepare("
            INSERT INTO Clients (Nom, Prenom, Email, Telephone, MotDePasse) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['Nom'],
            $user['Prenom'], 
            $user['Email'],
            $user['Telephone'] ?? null,
            $user['MotDePasse']
        ]);
        
        $new_client_id = $pdo->lastInsertId();
        echo "   ✅ Migré: {$user['Email']} → ClientID: $new_client_id\n";
        $migrated++;
    }
    
    echo "\n3. Résumé de la migration:\n";
    echo "   - Utilisateurs migrés: $migrated\n";
    echo "   - Utilisateurs ignorés (doublons): $skipped\n";
    
    // 4. Optionnel: Supprimer les utilisateurs migrés
    if ($migrated > 0) {
        echo "\n4. Voulez-vous supprimer les utilisateurs migrés de la table Utilisateurs? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($response) === 'y') {
            foreach ($utilisateurs as $user) {
                // Vérifier si l'email existe dans Clients
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Clients WHERE Email = ?");
                $stmt->execute([$user['Email']]);
                $exists = $stmt->fetchColumn();
                
                if ($exists > 0) {
                    $stmt = $pdo->prepare("DELETE FROM Utilisateurs WHERE UtilisateurID = ?");
                    $stmt->execute([$user['UtilisateurID']]);
                    echo "   ✅ Supprimé utilisateur {$user['Email']} de la table Utilisateurs\n";
                }
            }
        }
    }
    
    echo "\n=== MIGRATION TERMINÉE ===\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
}
?>
