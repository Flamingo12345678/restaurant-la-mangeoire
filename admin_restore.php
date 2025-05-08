<?php
// Script pour restaurer toutes les tables d'administration
require_once __DIR__ . '/db_connexion.php';

// Fonction pour afficher les messages
function display_message($title, $message, $type = 'success')
{
  $background = $type === 'success' ? '#f8f9fa' : '#fef7f7';
  $border = $type === 'success' ? 'none' : '1px solid #f5c6cb';
  $titleColor = $type === 'success' ? '#ce1212' : '#721c24';

  echo "<div style='font-family:Arial,sans-serif; padding:20px; max-width:800px; margin:20px auto; background:{$background}; border:{$border}; border-radius:5px;'>";
  echo "<h2 style='color:{$titleColor};'>{$title}</h2>";
  echo $message;
  echo "</div>";
}

// Vérification de sécurité
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$confirmCode = isset($_GET['confirm']) ? $_GET['confirm'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Page d'accueil avec options
if (empty($action)) {
  echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Restauration du système d'administration - La Mangeoire</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                color: #333;
            }
            h1 {
                color: #ce1212;
                border-bottom: 2px solid #ce1212;
                padding-bottom: 10px;
            }
            .card {
                background: #f8f9fa;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
            }
            .danger {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            .button {
                display: inline-block;
                background: #ce1212;
                color: #fff;
                padding: 10px 15px;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 10px;
            }
            .button.secondary {
                background: #6c757d;
            }
        </style>
    </head>
    <body>
        <h1>Restauration du système d'administration</h1>
        <p>Cet outil vous permet de restaurer le système d'administration du restaurant La Mangeoire en cas de problème.</p>
        
        <div class='card warning'>
            <h3>⚠️ Attention</h3>
            <p>Ces opérations peuvent affecter l'intégrité de votre base de données. Assurez-vous de faire une sauvegarde avant de continuer.</p>
        </div>
        
        <div class='card'>
            <h3>Créer la table des administrateurs</h3>
            <p>Cette opération créera la table Administrateurs si elle n'existe pas déjà et ajoutera un administrateur par défaut.</p>
            <p><a href='?action=create_admin_table' class='button'>Créer la table des administrateurs</a></p>
        </div>
        
        <div class='card'>
            <h3>Vérification de l'intégrité du système d'administration</h3>
            <p>Cette opération vérifiera l'intégrité des tables d'administration et corrigera les problèmes éventuels.</p>
            <p><a href='?action=check_integrity' class='button'>Vérifier l'intégrité</a></p>
        </div>
        
        <div class='card danger'>
            <h3>🔥 Opérations avancées</h3>
            <p>Ces opérations sont potentiellement destructrices. À n'utiliser qu'en dernier recours.</p>
            <p><a href='?action=reset_admin' class='button secondary'>Réinitialiser le compte administrateur</a></p>
            <p><a href='?action=recreate_all_tables' class='button secondary'>Recréer toutes les tables (nécessite une confirmation)</a></p>
        </div>
        
        <p><a href='index.html' style='color:#ce1212; text-decoration:none;'>Retour à l'accueil</a></p>
    </body>
    </html>";
  exit;
}

// Traitement des actions
try {
  $result = "";

  switch ($action) {
    case 'create_admin_table':
      // Créer la table des administrateurs
      $sql = file_get_contents(__DIR__ . '/init_admin.sql');
      $conn->exec($sql);
      $result = "<p>La table 'Administrateurs' a été créée/mise à jour avec succès.</p>
                      <p>Un compte administrateur par défaut a été créé :</p>
                      <ul>
                        <li><strong>Email :</strong> admin@lamangeoire.fr</li>
                        <li><strong>Mot de passe :</strong> admin</li>
                      </ul>
                      <p style='color:red;'><strong>ATTENTION :</strong> Veuillez changer ce mot de passe après votre première connexion pour des raisons de sécurité.</p>
                      <p><a href='admin/login.php' style='background:#ce1212; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:10px;'>Se connecter à l'administration</a></p>
                      <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";
      display_message("Création de la table des administrateurs", $result);
      break;

    case 'check_integrity':
      // Vérifier l'existence de la table
      $stmt = $conn->query("SHOW TABLES LIKE 'Administrateurs'");
      $tableExists = $stmt->rowCount() > 0;

      $result = "<ul>";
      if ($tableExists) {
        $result .= "<li>✅ La table 'Administrateurs' existe.</li>";

        // Vérifier les colonnes
        $stmt = $conn->query("DESCRIBE Administrateurs");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['AdminID', 'Email', 'MotDePasse', 'Nom', 'Prenom', 'Role', 'DateCreation'];

        $missingColumns = array_diff($requiredColumns, $columns);
        if (empty($missingColumns)) {
          $result .= "<li>✅ La structure de la table 'Administrateurs' est correcte.</li>";
        } else {
          $result .= "<li>❌ La table 'Administrateurs' ne contient pas toutes les colonnes requises. Manquantes : " . implode(', ', $missingColumns) . "</li>";
        }

        // Vérifier la présence d'au moins un administrateur
        $stmt = $conn->query("SELECT COUNT(*) FROM Administrateurs");
        $count = $stmt->fetchColumn();

        if ($count > 0) {
          $result .= "<li>✅ Au moins un administrateur est défini dans la base de données.</li>";
        } else {
          $result .= "<li>❌ Aucun administrateur n'est défini. Considérez l'option 'Réinitialiser le compte administrateur'.</li>";
        }
      } else {
        $result .= "<li>❌ La table 'Administrateurs' n'existe pas. Vous devez la créer.</li>";
      }
      $result .= "</ul>";

      if (!$tableExists || !empty($missingColumns) || $count == 0) {
        $result .= "<p>Des problèmes ont été détectés. Utilisez les options ci-dessous pour les résoudre :</p>
                           <p><a href='?action=create_admin_table' style='background:#ce1212; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block; margin-right:10px;'>Créer/Réparer la table des administrateurs</a>
                           <a href='?action=reset_admin' style='background:#6c757d; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block;'>Réinitialiser le compte administrateur</a></p>";
      } else {
        $result .= "<p>Tout semble être en ordre ! ✨</p>";
      }

      $result .= "<p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";

      display_message("Vérification de l'intégrité du système d'administration", $result);
      break;

    case 'reset_admin':
      // Réinitialiser le compte administrateur
      $sql = "INSERT INTO `Administrateurs` (`Email`, `MotDePasse`, `Nom`, `Prenom`, `Role`)
                   VALUES ('admin@lamangeoire.fr', '\$2y\$10\$VdZBjMmO8qJp1d6aXQJ7i.vXuP3w48SvkOkw6Gs5i04ZdfrQrNjnC', 'Admin', 'Super', 'superadmin')
                   ON DUPLICATE KEY UPDATE `MotDePasse` = '\$2y\$10\$VdZBjMmO8qJp1d6aXQJ7i.vXuP3w48SvkOkw6Gs5i04ZdfrQrNjnC', `Role` = 'superadmin'";
      $conn->exec($sql);

      $result = "<p>Le compte administrateur par défaut a été réinitialisé avec succès :</p>
                      <ul>
                        <li><strong>Email :</strong> admin@lamangeoire.fr</li>
                        <li><strong>Mot de passe :</strong> admin</li>
                      </ul>
                      <p style='color:red;'><strong>ATTENTION :</strong> Veuillez changer ce mot de passe après votre première connexion pour des raisons de sécurité.</p>
                      <p><a href='admin/login.php' style='background:#ce1212; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:10px;'>Se connecter à l'administration</a></p>
                      <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";

      display_message("Réinitialisation du compte administrateur", $result);
      break;

    case 'recreate_all_tables':
      // Cette opération est potentiellement dangereuse, on demande une confirmation
      if ($confirmCode !== 'confirm_recreate_tables' && !$isLocalhost) {
        $result = "<p>Cette opération est potentiellement destructrice et nécessite une confirmation.</p>
                          <p>Si vous êtes sûr de vouloir continuer, cliquez sur le bouton ci-dessous :</p>
                          <p><a href='?action=recreate_all_tables&confirm=confirm_recreate_tables' style='background:#dc3545; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:10px;'>Je comprends le risque, recréer toutes les tables</a></p>
                          <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Annuler et retourner aux options</a></p>";

        display_message("Confirmation requise", $result, 'error');
        break;
      }

      // Recréer toutes les tables d'administration
      try {
        // Supprimer la table si elle existe
        $conn->exec("DROP TABLE IF EXISTS `Administrateurs`");

        // Recréer la table
        $sql = file_get_contents(__DIR__ . '/init_admin.sql');
        $conn->exec($sql);

        $result = "<p>Toutes les tables d'administration ont été recréées avec succès.</p>
                          <p>Un compte administrateur par défaut a été créé :</p>
                          <ul>
                            <li><strong>Email :</strong> admin@lamangeoire.fr</li>
                            <li><strong>Mot de passe :</strong> admin</li>
                          </ul>
                          <p style='color:red;'><strong>ATTENTION :</strong> Veuillez changer ce mot de passe après votre première connexion pour des raisons de sécurité.</p>
                          <p><a href='admin/login.php' style='background:#ce1212; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:10px;'>Se connecter à l'administration</a></p>
                          <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";

        display_message("Recréation des tables d'administration", $result);
      } catch (Exception $e) {
        $result = "<p>Une erreur est survenue lors de la recréation des tables :</p>
                          <p>" . htmlspecialchars($e->getMessage()) . "</p>
                          <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";

        display_message("Erreur", $result, 'error');
      }
      break;

    default:
      $result = "<p>Action non reconnue.</p>
                      <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";
      display_message("Erreur", $result, 'error');
      break;
  }
} catch (PDOException $e) {
  $result = "<p>Une erreur est survenue :</p>
              <p>" . htmlspecialchars($e->getMessage()) . "</p>
              <p>Vérifiez les messages d'erreur ci-dessus et consultez l'administrateur système si nécessaire.</p>
              <p><a href='admin_restore.php' style='color:#ce1212; text-decoration:none;'>Retour aux options de restauration</a></p>";

  display_message("Erreur", $result, 'error');
}
