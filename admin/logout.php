<?php
// Déconnexion de l'administrateur
session_start();
// Suppression de toutes les variables de session
$_SESSION = array();
// Destruction de la session
session_destroy();
// Redirection vers la page de connexion
header('Location: login.php');
exit;
