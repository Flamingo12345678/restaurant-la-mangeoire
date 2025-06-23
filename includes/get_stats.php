<?php
// Récupération des statistiques depuis la base de données
require_once __DIR__ . '/../db_connexion.php';

function getStatistiques() {
    global $pdo;
    
    try {
        // Nombre de clients
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Clients");
        $nbClients = $stmt->fetch()['total'];
        
        // Nombre de menus/plats
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Menus");
        $nbMenus = $stmt->fetch()['total'];
        
        // Nombre d'heures d'ouverture par semaine (à ajuster selon vos horaires réels)
        // Par exemple, si votre restaurant est ouvert 7 jours sur 7, de 10h à 23h
        $heuresParJour = 13; // 23h - 10h
        $joursParSemaine = 7;
        $heuresOuverture = $heuresParJour * $joursParSemaine;
        
        // Nombre d'employés
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Employes");
        $nbEmployes = $stmt->fetch()['total'];
        
        return [
            'clients' => $nbClients,
            'menus' => $nbMenus,
            'heures_ouverture' => $heuresOuverture,
            'employes' => $nbEmployes
        ];
    } catch (PDOException $e) {
        // En cas d'erreur, on retourne des valeurs par défaut
        error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
        return [
            'clients' => 0,
            'menus' => 0,
            'heures_ouverture' => 0,
            'employes' => 0
        ];
    }
}
