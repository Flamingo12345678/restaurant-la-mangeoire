<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Admin - Tableau de bord</title>
</head>

<body>
  <h1>Bienvenue sur l'administration</h1>
  <ul>
    <li><a href="clients.php">Clients</a></li>
    <li><a href="commandes.php">Commandes</a></li>
    <li><a href="employes.php">Employés</a></li>
    <li><a href="menus.php">Menus</a></li>
    <li><a href="paiements.php">Paiements</a></li>
    <li><a href="reservations.php">Réservations</a></li>
    <li><a href="tables.php">Tables</a></li>
    <li><a href="logout.php">Déconnexion</a></li>
  </ul>
</body>

</html>