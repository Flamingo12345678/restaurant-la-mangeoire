<?php

require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = trim($_POST['nom_client'] ?? '');
  $email = $_POST['email_client'] ?? '';
  $date = $_POST['DateReservation'] ?? '';
  $statut = trim($_POST['statut'] ?? 'Réservée');
  $valid = validate_nom($nom) && validate_email($email) && (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date)) && in_array($statut, ['Réservée', 'Annulée']);
  if ($valid) {
    // 1. Vérifier si le client existe déjà
    $sql = "SELECT ClientID FROM Clients WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($client) {
      $client_id = $client['ClientID'];
    } else {
      // 2. Ajouter le client s'il n'existe pas
      $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, '', ?, '')";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$nom, $email]);
      $client_id = $conn->lastInsertId();
    }
    // 3. Insérer la réservation avec ClientID
    $sql = "INSERT INTO Reservations (nom_client, email_client, DateReservation, statut, ClientID) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$nom, $email, $date, $statut, $client_id]);
    if ($result) {
      $reservation_id = $conn->lastInsertId();
      // Association automatique des tables à la réservation et mise à jour du statut
      if (!empty($_POST['table_ids']) && is_array($_POST['table_ids'])) {
        foreach ($_POST['table_ids'] as $table_id) {
          $table_id = intval($table_id);
          $sql = "INSERT INTO ReservationTables (ReservationID, TableID, nb_places) VALUES (?, ?, ?)";
          $stmt_assoc = $conn->prepare($sql);
          $stmt_assoc->execute([$reservation_id, $table_id, 0]); // 0 ou nombre de places attribuées si connu
          $sql = "UPDATE TablesRestaurant SET Statut = 'Réservée' WHERE TableID = ?";
          $stmt_update = $conn->prepare($sql);
          $stmt_update->execute([$table_id]);
        }
      }
      set_message('Réservation ajoutée.');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      set_message('Erreur lors de l\'ajout.', 'error');
    }
  } else {
    set_message('Champs invalides.', 'error');
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Ajouter une réservation</title>
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

    .alert-danger {
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
  <div class="form-container">
    <a href="reservations.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter une réservation</h1>
    <?php display_message(); ?>
    <form method="post" autocomplete="off" id="addReservationForm" novalidate>
      <input type="text" name="nom_client" id="nom_client" placeholder="Nom du client" required>
      <input type="email" name="email_client" id="email_client" placeholder="Email du client" required>
      <input type="datetime-local" name="DateReservation" id="DateReservation" required>
      <input type="text" name="statut" id="statut" placeholder="Statut (Réservée/Annulée)">
      <label for="tables" style="font-weight: 500; margin-top: 1rem;">Choisir une ou plusieurs tables :</label>
      <select name="table_ids[]" id="tables" multiple style="padding: 0.7rem 1rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
        <?php
        // Récupération des tables disponibles
        $sql = "SELECT TableID, NomTable, NumeroTable FROM TablesRestaurant WHERE Statut = 'Libre' ORDER BY TableID";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
          $label = $table['NomTable'] ?? ('Table ' . $table['NumeroTable']);
          echo "<option value='" . $table['TableID'] . "'>" . htmlspecialchars($label) . "</option>";
        }
        ?>
      </select>
      <div id="form-error" class="alert alert-danger" style="display:none;"></div>
      <button type="submit">Ajouter</button>
    </form>
    <script>
      (function() {
        const form = document.getElementById('addReservationForm');
        const nom = document.getElementById('nom_client');
        const email = document.getElementById('email_client');
        const date = document.getElementById('DateReservation');
        const statut = document.getElementById('statut');
        const errorDiv = document.getElementById('form-error');

        function validateEmail(val) {
          return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val);
        }

        function showError(msg) {
          errorDiv.textContent = msg;
          errorDiv.style.display = 'block';
        }

        function clearError() {
          errorDiv.textContent = '';
          errorDiv.style.display = 'none';
        }

        function validateField(field) {
          if (field === nom && nom.value.trim() === '') {
            showError('Veuillez saisir le nom du client.');
            return false;
          }
          if (field === email && (email.value.trim() === '' || !validateEmail(email.value))) {
            showError('Veuillez saisir un email valide.');
            return false;
          }
          if (field === date && date.value === '') {
            showError('Veuillez choisir une date.');
            return false;
          }
          if (field === statut && statut.value && !['Réservée', 'Annulée'].includes(statut.value)) {
            showError('Statut invalide.');
            return false;
          }
          clearError();
          return true;
        }
        [nom, email, date, statut].forEach(input => {
          input.addEventListener('input', function() {
            validateField(this);
          });
          input.addEventListener('blur', function() {
            validateField(this);
          });
        });
        form.addEventListener('submit', function(e) {
          if (!validateField(nom) || !validateField(email) || !validateField(date) || !validateField(statut)) {
            e.preventDefault();
            return false;
          }
          clearError();
        });
      })();
    </script>
  </div>
</body>

</html>