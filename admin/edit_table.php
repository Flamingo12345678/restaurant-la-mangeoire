<?php

/**
 * edit_table.php
 *
 * Permet à un administrateur de modifier les informations d'une table existante.
 * - Protection par session admin obligatoire.
 * - Validation des champs (numéro et nombre de places).
 * - Utilisation de requêtes préparées pour éviter les injections SQL.
 * - Affiche un message de succès ou d'erreur.
 *
 * @auteur Projet La Mangeoire
 * @sécurité Accès restreint aux administrateurs via session.
 */
session_start();
require_once '../db_connexion.php';
$message = '';
function log_admin_action($action, $details = '')
{
  $logfile = __DIR__ . '/../admin/admin_actions.log';
  $date = date('Y-m-d H:i:s');
  $user = $_SESSION['admin'] ?? 'inconnu';
  $entry = "[$date] [$user] $action $details\n";
  file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}
// Contrôle de droits strict : seuls les superadmins peuvent modifier
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
  header('Location: index.php?error=forbidden');
  exit;
}

// Génération du token CSRF si besoin
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = intval($_GET['id'] ?? 0);
$table = null;
if ($id > 0) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      $message = 'Erreur de sécurité (CSRF).';
      log_admin_action('Tentative CSRF modification table');
    } else {
      $numero = intval($_POST['numero'] ?? 0);
      $places = intval($_POST['places'] ?? 0);
      // Validation stricte
      if ($numero > 0 && $places > 0) {
        try {
          $sql = "UPDATE TablesRestaurant SET NumeroTable=?, Capacite=? WHERE TableID=?";
          $stmt = $conn->prepare($sql);
          $result = $stmt->execute([$numero, $places, $id]);
          if ($result) {
            $message = 'Table modifiée.';
            log_admin_action('Modification table', "ID: $id, Numéro: $numero, Places: $places");
          } else {
            $message = 'Erreur lors de la modification.';
            log_admin_action('Erreur modification table', "ID: $id, Numéro: $numero, Places: $places");
          }
        } catch (PDOException $e) {
          $message = 'Erreur base de données.';
          log_admin_action('Erreur PDO modification table', 'PDOException');
        }
      } else {
        $message = 'Champs invalides.';
      }
    }
  }
  // Récupération des infos de la table (MySQL/PDO)
  $sql = "SELECT * FROM TablesRestaurant WHERE TableID=?";
  $stmt = $conn->prepare($sql);
  if ($stmt->execute([$id])) {
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Modifier une table</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .form-container {
      max-width: 400px;
      margin: 40px auto;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
      padding: 2rem 2.5rem 2.5rem 2.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .form-container h1 {
      margin-bottom: 1.5rem;
      color: #b01e28;
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
    }

    .form-container form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .form-container input {
      padding: 0.7rem 1rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: border 0.2s;
    }

    .form-container input:focus {
      border-color: #b01e28;
      outline: none;
    }

    .form-container button {
      background: #b01e28;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0.8rem 0;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .form-container button:hover {
      background: #8c181f;
    }

    .alert {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.8rem 1rem;
      border-radius: 6px;
      font-size: 1rem;
      text-align: center;
    }

    .alert-success {
      background: #e6f9ed;
      color: #217a3c;
      border: 1px solid #b6e2c7;
    }

    .alert-error {
      background: #fdeaea;
      color: #b01e28;
      border: 1px solid #f5c2c7;
    }

    .back-link {
      display: inline-block;
      margin-bottom: 1.5rem;
      color: #b01e28;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: #8c181f;
      text-decoration: underline;
    }

    .admin-nav {
      width: 100vw;
      background: #b01e28;
      color: #fff;
      padding: 0.7rem 0;
      margin-bottom: 2rem;
      text-align: center;
      font-weight: 600;
      letter-spacing: 1px;
    }
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="admin-nav">Administration – Modifier une table</div>
  <div class="form-container">
    <a href="tables.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Modifier une table</h1>
    <?php if ($message): ?>
      <div class="alert <?= strpos($message, 'modifiée') !== false ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <?php if ($table): ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="number" name="numero" value="<?= htmlspecialchars($table['NumeroTable'] ?? '') ?>" placeholder="Numéro de table" required min="1">
        <input type="number" name="places" value="<?= htmlspecialchars($table['Capacite'] ?? '') ?>" placeholder="Nombre de places" required min="1">
        <button type="submit">Enregistrer</button>
      </form>
    <?php else: ?>
      <div class="alert alert-error">Table introuvable.</div>
    <?php endif; ?>
  </div>
</body>

</html>