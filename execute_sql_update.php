<?php
// Script pour exécuter le fichier update_db_structure.sql avec les identifiants de la base de données
require_once __DIR__ . '/db_connexion.php';

// Fonction pour exécuter une requête SQL individuelle et gérer les erreurs
function executerRequete($conn, $requete)
{
  if (trim($requete) === '') {
    return true; // Ignorer les requêtes vides
  }

  try {
    $conn->exec($requete);
    return true;
  } catch (PDOException $e) {
    // Ignorer les avertissements liés aux tables ou colonnes qui existent déjà
    if (
      strpos($e->getMessage(), "already exists") !== false ||
      strpos($e->getMessage(), "Duplicate entry") !== false ||
      strpos($e->getMessage(), "Duplicate column") !== false ||
      strpos($e->getMessage(), "Duplicate key") !== false
    ) {
      echo "Info: " . $e->getMessage() . "\n";
      return true;
    }

    // Afficher l'erreur et la requête qui a échoué
    echo "Erreur lors de l'exécution de la requête: " . $e->getMessage() . "\n";
    echo "Requête problématique: " . $requete . "\n\n";
    return false;
  }
}

try {
  // Lire le contenu du fichier SQL
  $sqlContent = file_get_contents(__DIR__ . '/update_db_structure.sql');

  // Séparer le contenu en requêtes individuelles
  $delimiter = ';';
  $sqlRequetes = explode($delimiter, $sqlContent);

  $success = true;
  $errorsCount = 0;
  $successCount = 0;
  $warningsCount = 0;

  // Exécuter chaque requête individuellement
  foreach ($sqlRequetes as $requete) {
    $requete = trim($requete);
    if (!empty($requete)) {
      $result = executerRequete($conn, $requete . $delimiter);
      if ($result) {
        $successCount++;
      } else {
        $errorsCount++;
        $success = false;
      }
    }
  }

  // Afficher le résultat global
  if ($success) {
    echo "La structure de la base de données a été mise à jour avec succès.\n";
  } else {
    echo "La mise à jour de la structure de la base de données s'est terminée avec des erreurs.\n";
  }

  echo "$successCount requêtes exécutées avec succès, $errorsCount erreurs, $warningsCount avertissements.\n";
} catch (Exception $e) {
  echo "Erreur générale : " . $e->getMessage() . "\n";
}
