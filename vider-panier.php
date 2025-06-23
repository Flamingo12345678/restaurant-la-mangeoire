<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';

// Log l'action pour le debugging
error_log("Tentative de vider le panier - User: " . (isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 'Non connecté'));

// Déterminer la page de redirection après nettoyage du panier
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'panier.php';
$redirect = filter_var($redirect, FILTER_SANITIZE_URL);

// Vérifier que la redirection est interne (sécurité)
if (strpos($redirect, 'http') === 0 || strpos($redirect, '//') === 0) {
    $redirect = 'panier.php'; // Redirection par défaut si URL externe
}

// Vider le panier en fonction du type d'utilisateur
if (isset($_SESSION['client_id'])) {
    // Utilisateur connecté - vider dans la base de données
    try {
        $stmt = $pdo->prepare("DELETE FROM Panier WHERE ClientID = ?");
        $stmt->execute([$_SESSION['client_id']]);
        
        $count = $stmt->rowCount();
        error_log("Panier vidé pour l'utilisateur {$_SESSION['client_id']} - {$count} articles supprimés");
        
        $_SESSION['message'] = "Votre panier a été vidé avec succès.";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du panier: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur est survenue lors de la suppression de votre panier.";
        $_SESSION['message_type'] = "error";
    }
} else {
    // Utilisateur non connecté - vider la session
    if (isset($_SESSION['panier'])) {
        $count = count($_SESSION['panier']);
        unset($_SESSION['panier']);
        error_log("Panier session vidé - {$count} articles supprimés");
        
        $_SESSION['message'] = "Votre panier a été vidé avec succès.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Votre panier est déjà vide.";
        $_SESSION['message_type'] = "info";
    }
}

// Rediriger vers la page demandée
header("Location: " . $redirect);
exit;
?>
