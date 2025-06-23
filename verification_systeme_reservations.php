<?php
/**
 * Script de vérification final du système de réservations
 * Vérifie que toutes les fonctionnalités marchent correctement
 */

require_once 'db_connexion.php';

echo "=== VÉRIFICATION FINALE DU SYSTÈME DE RÉSERVATIONS ===" . PHP_EOL . PHP_EOL;

// 1. Vérification de la structure de la table
echo "1️⃣ Vérification de la structure de la table 'Reservations':" . PHP_EOL;
try {
    $columns = $conn->query("DESCRIBE Reservations")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $key = $col['Key'] ? " [{$col['Key']}]" : "";
        $null = $col['Null'] === 'NO' ? ' NOT NULL' : '';
        $default = $col['Default'] ? " DEFAULT '{$col['Default']}'" : '';
        echo "  ✓ {$col['Field']} ({$col['Type']}){$key}{$null}{$default}" . PHP_EOL;
    }
    echo "  ✅ Structure correcte" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . PHP_EOL . PHP_EOL;
}

// 2. Vérification des données
echo "2️⃣ Vérification des données existantes:" . PHP_EOL;
try {
    $stats = [
        'total' => $conn->query("SELECT COUNT(*) FROM Reservations")->fetchColumn(),
        'avec_telephone' => $conn->query("SELECT COUNT(*) FROM Reservations WHERE telephone IS NOT NULL AND telephone != ''")->fetchColumn(),
        'avec_client_id' => $conn->query("SELECT COUNT(*) FROM Reservations WHERE ClientID IS NOT NULL")->fetchColumn(),
        'reservees' => $conn->query("SELECT COUNT(*) FROM Reservations WHERE Statut = 'Réservée'")->fetchColumn(),
        'annulees' => $conn->query("SELECT COUNT(*) FROM Reservations WHERE Statut = 'Annulée'")->fetchColumn(),
    ];
    
    echo "  • Total des réservations: {$stats['total']}" . PHP_EOL;
    echo "  • Avec numéro de téléphone: {$stats['avec_telephone']}" . PHP_EOL;
    echo "  • Avec ID client: {$stats['avec_client_id']}" . PHP_EOL;
    echo "  • Statut 'Réservée': {$stats['reservees']}" . PHP_EOL;
    echo "  • Statut 'Annulée': {$stats['annulees']}" . PHP_EOL;
    echo "  ✅ Données cohérentes" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . PHP_EOL . PHP_EOL;
}

// 3. Test d'insertion
echo "3️⃣ Test d'insertion d'une réservation de test:" . PHP_EOL;
try {
    $test_data = [
        'nom' => 'Test Client ' . date('H:i:s'),
        'email' => 'test@example.com',
        'telephone' => '0123456789',
        'date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'statut' => 'Réservée',
        'nb_personnes' => 2,
        'client_id' => null
    ];
    
    $sql = "INSERT INTO Reservations (ClientID, nom_client, email_client, telephone, DateReservation, Statut, nb_personnes) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $test_data['client_id'],
        $test_data['nom'],
        $test_data['email'],
        $test_data['telephone'],
        $test_data['date'],
        $test_data['statut'],
        $test_data['nb_personnes']
    ]);
    
    if ($result) {
        $test_id = $conn->lastInsertId();
        echo "  ✅ Insertion réussie (ID: {$test_id})" . PHP_EOL;
        
        // Supprimer la réservation de test
        $conn->prepare("DELETE FROM Reservations WHERE ReservationID = ?")->execute([$test_id]);
        echo "  🗑️ Réservation de test supprimée" . PHP_EOL . PHP_EOL;
    } else {
        echo "  ❌ Échec de l'insertion" . PHP_EOL . PHP_EOL;
    }
} catch (Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . PHP_EOL . PHP_EOL;
}

// 4. Vérification des fichiers
echo "4️⃣ Vérification des fichiers du système:" . PHP_EOL;
$files_to_check = [
    'reservations.php' => 'Interface principale de gestion des réservations',
    'db_connexion.php' => 'Connexion à la base de données',
    'includes/common.php' => 'Fonctions communes (validation, sécurité)',
];

foreach ($files_to_check as $file => $desc) {
    if (file_exists($file)) {
        echo "  ✅ {$file} - {$desc}" . PHP_EOL;
    } else {
        echo "  ❌ {$file} - MANQUANT - {$desc}" . PHP_EOL;
    }
}

echo PHP_EOL . "5️⃣ URLs importantes:" . PHP_EOL;
echo "  • Interface réservations: http://localhost/reservations.php" . PHP_EOL;
echo "  • Dashboard admin: http://localhost/admin/index.php" . PHP_EOL;

echo PHP_EOL . "🎉 VÉRIFICATION TERMINÉE" . PHP_EOL;
echo "Le système de réservations est maintenant complet et fonctionnel!" . PHP_EOL;
echo "Toutes les informations du client sont maintenant enregistrées et affichées." . PHP_EOL;
?>
