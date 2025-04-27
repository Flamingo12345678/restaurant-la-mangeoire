<?php

/**
 * add_client.php
 *
 * Permet à un administrateur d'ajouter un nouveau client dans la base de données.
 * - Protection par session admin obligatoire.
 * - Validation des champs (nom, prénom, email).
 * - Utilisation de requêtes préparées pour éviter les injections SQL.
 * - Affiche un message de succès ou d'erreur.
 *
 * @auteur Projet La Mangeoire
 * @sécurité Accès restreint aux administrateurs via session.
 */

session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
$message = '';

// Journalisation de l'action admin (ajout de client)
function log_admin_action($action, $details = '')
{
  $logfile = __DIR__ . '/../admin/admin_actions.log';
  $date = date('Y-m-d H:i:s');
  $user = $_SESSION['admin'] ?? 'inconnu';
  $entry = "[$date] [$user] $action $details\n";
  file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}

// Contrôle de droits (préparation multi-niveaux)
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
  // Ici, on peut restreindre certaines actions selon le rôle
  // Par exemple, seuls les superadmins peuvent ajouter des clients
  // Pour l'instant, on laisse passer tout admin
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  $tel = trim($_POST['telephone'] ?? '');
  if ($nom && $prenom && $email) {
    try {
      $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $result = $stmt->execute([$nom, $prenom, $email, $tel]);
      if ($result) {
        $message = 'Client ajouté avec succès.';
        log_admin_action('Ajout client', "Nom: $nom, Prénom: $prenom, Email: $email");
      } else {
        $message = 'Erreur lors de l\'ajout.';
        log_admin_action('Erreur ajout client', "Nom: $nom, Prénom: $prenom, Email: $email");
      }
    } catch (PDOException $e) {
      $message = 'Erreur base de données.';
      log_admin_action('Erreur PDO ajout client', $e->getMessage());
    }
  } else {
    $message = 'Champs invalides.';
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Ajouter un client</title>
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
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="admin-nav">Administration – Ajouter un client</div>
  <div class="form-container">
    <a href="clients.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter un client</h1>
    <?php if ($message): ?>
      <div class="alert <?= strpos($message, 'succès') !== false ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="text" name="nom" placeholder="Nom" required>
      <input type="text" name="prenom" placeholder="Prénom" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="telephone" placeholder="Téléphone">
      <button type="submit">Ajouter</button>
    </form>
  </div>
</body>

</html>