<?php
// Script intermédiaire pour accéder à la vérification d'intégrité de la base de données
session_start();

// Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
  header('Location: login.php');
  exit;
}

// Vérifier si l'utilisateur est un superadministrateur
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
  header('Location: index.php?error=forbidden');
  exit;
}

// Rediriger vers le script de vérification d'intégrité
header('Location: ../check_db_integrity.php');
exit;
