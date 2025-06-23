<?php
session_start();
require_once 'db_connexion.php';
require_once 'includes/CartManager.php';

// Migrer le panier de la base vers la session si l'utilisateur était connecté
if (isset($_SESSION['client_id'])) {
    $cartManager = new CartManager($pdo);
    $cartManager->migrateDatabaseToSession($_SESSION['client_id']);
}

// Détruire toutes les variables de session (le panier est déjà migré)
$_SESSION = array();

// Détruire la session et en créer une nouvelle
session_destroy();
session_start();

// Message de confirmation
$_SESSION['cart_message'] = [
    'type' => 'info',
    'text' => 'Vous êtes déconnecté. Votre panier a été conservé.'
];

// Rediriger vers la page d'accueil
header("Location: index.php");
exit;
?>