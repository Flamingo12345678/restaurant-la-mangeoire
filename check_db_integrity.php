<?php

/**
 * Script de vérification d'intégrité de la base de données
 * Pour le restaurant "La Mangeoire"
 * 
 * Ce script vérifie :
 * - L'existence des tables principales
 * - La structure des tables (colonnes requises)
 * - Les contraintes de clés étrangères
 * - L'intégrité des données
 */

session_start();

// Vérifier si l'utilisateur est connecté en tant qu'administrateur (sauf s'il est en mode développement)
$is_dev_mode = (php_sapi_name() === 'cli' || (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1')));

if (!$is_dev_mode && (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin')) {
  header('Location: admin/login.php');
  exit;
}

// Charger la configuration de la base de données
require_once __DIR__ . '/db_connexion.php';

// Styles CSS pour le rapport
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de l\'intégrité de la base de données - La Mangeoire</title>
    <style>
        body {
            font-family: "Poppins", -apple-system, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #ce1212;
            border-bottom: 2px solid #ce1212;
            padding-bottom: 10px;
        }
        h2 {
            color: #ce1212;
            margin-top: 30px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary {
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Vérification de l\'intégrité de la base de données - La Mangeoire</h1>
    <p>Date de vérification : ' . date('d/m/Y H:i:s') . '</p>
';

// Variables pour les statistiques
$success_count = 0;
$warning_count = 0;
$error_count = 0;

// Fonction pour afficher un message de succès
function showSuccess($message)
{
  global $success_count;
  $success_count++;
  echo "<div class='success'>✅ $message</div>";
}

// Fonction pour afficher un avertissement
function showWarning($message)
{
  global $warning_count;
  $warning_count++;
  echo "<div class='warning'>⚠️ $message</div>";
}

// Fonction pour afficher une erreur
function showError($message)
{
  global $error_count;
  $error_count++;
  echo "<div class='error'>❌ $message</div>";
}

// Fonction qui vérifie l'existence d'une table
function tableExists($conn, $tableName)
{
  try {
    $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $stmt->rowCount() > 0;
  } catch (PDOException $e) {
    return false;
  }
}

// Fonction qui vérifie la structure d'une table
function checkTableStructure($conn, $tableName, $requiredColumns)
{
  if (!tableExists($conn, $tableName)) {
    showError("La table '$tableName' n'existe pas dans la base de données.");
    return false;
  }

  try {
    $stmt = $conn->query("DESCRIBE `$tableName`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $missingColumns = array_diff($requiredColumns, $columns);

    if (count($missingColumns) > 0) {
      showError("La table '$tableName' ne contient pas les colonnes requises : " . implode(', ', $missingColumns));
      return false;
    }

    showSuccess("La structure de la table '$tableName' est correcte.");
    return true;
  } catch (PDOException $e) {
    showError("Erreur lors de la vérification de la structure de la table '$tableName' : " . $e->getMessage());
    return false;
  }
}

// Fonction qui vérifie les contraintes de clé étrangère
function checkForeignKey($conn, $tableName, $columnName, $refTable, $refColumn)
{
  try {
    $stmt = $conn->query("
            SELECT EXISTS (
                SELECT * FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$tableName'
                AND COLUMN_NAME = '$columnName'
                AND REFERENCED_TABLE_NAME = '$refTable'
                AND REFERENCED_COLUMN_NAME = '$refColumn'
            ) AS constraint_exists
        ");

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['constraint_exists'] == 1) {
      showSuccess("La contrainte de clé étrangère '$columnName' dans la table '$tableName' référençant '$refTable($refColumn)' existe.");
      return true;
    } else {
      showWarning("La contrainte de clé étrangère '$columnName' dans la table '$tableName' référençant '$refTable($refColumn)' n'existe pas.");
      return false;
    }
  } catch (PDOException $e) {
    showError("Erreur lors de la vérification de la contrainte de clé étrangère : " . $e->getMessage());
    return false;
  }
}

// Fonction qui vérifie l'intégrité des données (pas de clés orphelines)
function checkDataIntegrity($conn, $tableName, $columnName, $refTable, $refColumn)
{
  try {
    $stmt = $conn->query("
            SELECT COUNT(*) as orphaned_count
            FROM `$tableName` t
            LEFT JOIN `$refTable` r ON t.`$columnName` = r.`$refColumn`
            WHERE t.`$columnName` IS NOT NULL AND r.`$refColumn` IS NULL
        ");

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['orphaned_count'] > 0) {
      showError("Il y a {$result['orphaned_count']} enregistrements orphelins dans la table '$tableName' (colonne '$columnName' référençant '$refTable($refColumn)').");
      return false;
    } else {
      showSuccess("Aucun enregistrement orphelin trouvé dans la table '$tableName' pour la colonne '$columnName'.");
      return true;
    }
  } catch (PDOException $e) {
    showWarning("Erreur lors de la vérification de l'intégrité des données : " . $e->getMessage());
    return false;
  }
}

// Début des vérifications
echo "<h2>Vérification des tables</h2>";

// Liste des tables principales à vérifier
$mainTables = [
  'Utilisateurs',
  'Menus',
  'Panier',
  'CartesBancaires',
  'Commandes',
  'Paiements',
  'ReinitialisationMotDePasse'
];

foreach ($mainTables as $table) {
  if (tableExists($conn, $table)) {
    showSuccess("La table '$table' existe dans la base de données.");
  } else {
    showError("La table '$table' n'existe pas dans la base de données.");
  }
}

// Vérification de la structure des tables
echo "<h2>Vérification de la structure des tables</h2>";

// Structure de la table Utilisateurs
checkTableStructure($conn, 'Utilisateurs', [
  'UtilisateurID',
  'Email',
  'MotDePasse',
  'Nom',
  'Prenom'
]);

// Structure de la table Menus
checkTableStructure($conn, 'Menus', [
  'MenuID',
  'NomItem',
  'Prix'
]);

// Structure de la table Panier
checkTableStructure($conn, 'Panier', [
  'PanierID',
  'UtilisateurID',
  'MenuID',
  'Quantite'
]);

// Structure de la table CartesBancaires
checkTableStructure($conn, 'CartesBancaires', [
  'CarteID',
  'UtilisateurID',
  'NumeroMasque',
  'NomCarte',
  'DateExpiration',
  'TypeCarte'
]);

// Vérification des contraintes de clé étrangère
echo "<h2>Vérification des contraintes de clé étrangère</h2>";

// Contraintes de la table Panier
checkForeignKey($conn, 'Panier', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');
checkForeignKey($conn, 'Panier', 'MenuID', 'Menus', 'MenuID');

// Contraintes de la table CartesBancaires
checkForeignKey($conn, 'CartesBancaires', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');

// Contrainte de la table Commandes (si la colonne a été ajoutée)
if (tableExists($conn, 'Commandes')) {
  try {
    $stmt = $conn->query("SHOW COLUMNS FROM `Commandes` LIKE 'UtilisateurID'");
    if ($stmt->rowCount() > 0) {
      checkForeignKey($conn, 'Commandes', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');
    } else {
      showWarning("La colonne 'UtilisateurID' n'existe pas dans la table 'Commandes'.");
    }
  } catch (PDOException $e) {
    showError("Erreur lors de la vérification de la colonne 'UtilisateurID' dans la table 'Commandes' : " . $e->getMessage());
  }
}

// Contrainte de la table ReinitialisationMotDePasse
checkForeignKey($conn, 'ReinitialisationMotDePasse', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');

// Vérification de l'intégrité des données
echo "<h2>Vérification de l'intégrité des données</h2>";

// Intégrité des données de la table Panier
checkDataIntegrity($conn, 'Panier', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');
checkDataIntegrity($conn, 'Panier', 'MenuID', 'Menus', 'MenuID');

// Intégrité des données de la table CartesBancaires
checkDataIntegrity($conn, 'CartesBancaires', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');

// Intégrité des données de la table Commandes (si la colonne a été ajoutée)
if (tableExists($conn, 'Commandes')) {
  try {
    $stmt = $conn->query("SHOW COLUMNS FROM `Commandes` LIKE 'UtilisateurID'");
    if ($stmt->rowCount() > 0) {
      checkDataIntegrity($conn, 'Commandes', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');
    }
  } catch (PDOException $e) {
    showWarning("Erreur lors de la vérification de l'intégrité des données pour la table 'Commandes' : " . $e->getMessage());
  }
}

// Intégrité des données de la table ReinitialisationMotDePasse
checkDataIntegrity($conn, 'ReinitialisationMotDePasse', 'UtilisateurID', 'Utilisateurs', 'UtilisateurID');

// Informations sur les tables
echo "<h2>Informations sur les tables</h2>";
echo "<table>";
echo "<tr><th>Table</th><th>Nombre d'enregistrements</th></tr>";

foreach ($mainTables as $table) {
  if (tableExists($conn, $table)) {
    try {
      $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      echo "<tr><td>$table</td><td>{$result['count']}</td></tr>";
    } catch (PDOException $e) {
      echo "<tr><td>$table</td><td>Erreur: " . $e->getMessage() . "</td></tr>";
    }
  } else {
    echo "<tr><td>$table</td><td>Table inexistante</td></tr>";
  }
}

echo "</table>";

// Résumé des vérifications
$total_checks = $success_count + $warning_count + $error_count;

echo "<div class='summary' style='background-color: " . ($error_count > 0 ? "#f8d7da" : ($warning_count > 0 ? "#fff3cd" : "#d4edda")) . "'>";
echo "<p>Résumé des vérifications :</p>";
echo "<ul>";
echo "<li>Tests réussis : $success_count</li>";
echo "<li>Avertissements : $warning_count</li>";
echo "<li>Erreurs : $error_count</li>";
echo "<li>Total des vérifications : $total_checks</li>";
echo "</ul>";
echo "</div>";

// Suggestions d'amélioration
if ($error_count > 0 || $warning_count > 0) {
  echo "<h2>Suggestions d'amélioration</h2>";
  echo "<ul>";

  if ($error_count > 0) {
    echo "<li>Corrigez les erreurs identifiées avant de mettre le site en production.</li>";
  }

  if ($warning_count > 0) {
    echo "<li>Examinez les avertissements et déterminez s'ils nécessitent une intervention.</li>";
  }

  echo "<li>Exécutez ce script régulièrement pour surveiller l'intégrité de la base de données.</li>";
  echo "<li>Envisagez de mettre en place des sauvegardes automatiques de la base de données.</li>";
  echo "</ul>";
}

// Liens de retour adaptés selon le contexte (admin ou développement)
if (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
  echo "<p><a href='admin/index.php' style='color: #ce1212;'>Retour au tableau de bord admin</a></p>";
} else {
  echo "<p><a href='index.php' style='color: #ce1212;'>Retour à l'accueil</a></p>";
}

echo "</body></html>";
