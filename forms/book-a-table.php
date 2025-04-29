<?php
require_once '../db_connexion.php';
// ...existing code...
// Traitement du formulaire de réservation de table
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = trim(strip_tags($_POST['name'] ?? ''));
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  $date = $_POST['date'] ?? '';
  $time = $_POST['time'] ?? '';
  $people = filter_var($_POST['people'] ?? '', FILTER_VALIDATE_INT);
  $telephone = trim(strip_tags($_POST['phone'] ?? ''));
  if ($nom && $email && $date && $time && $people > 0 && $telephone) {
    try {
      // Vérification stricte de la capacité totale à venir (toutes réservations confondues)
      $datetime = $date . ' ' . $time;
      $now = date('Y-m-d H:i:s');
      $sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$now]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $total_reserves = intval($row['total_reserves']);
      $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
      $stmt = $conn->query($sql);
      $total_places = 0;
      if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_places = intval($row['total_places']);
      }
      if ($total_reserves + $people > $total_places) {
        $message = '<span class="alert alert-error">Impossible d\'enregistrer la réservation : la capacité maximale de la salle serait dépassée.</span>';
      } else {
        // Affectation automatique d'une table disponible
        $sql = "SELECT TableID FROM TablesRestaurant WHERE TableID NOT IN (SELECT TableID FROM Reservations WHERE DateReservation = ? AND Statut = 'Réservée') LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$datetime]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
          $table_id = $row['TableID'];
          $sql = "INSERT INTO Reservations (DateReservation, Statut, nom_client, email_client, nb_personnes, telephone, TableID) VALUES (?, 'Réservée', ?, ?, ?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $result = $stmt->execute([$datetime, $nom, $email, $people, $telephone, $table_id]);
          if ($result) {
            $message = '<span class="alert alert-success" id="resa-success">Réservation enregistrée avec succès ! Vous allez être redirigé vers l\'accueil.</span>';
            echo '<script>setTimeout(function(){ window.location.href = "../index.html"; }, 3000);</script>';
          } else {
            $errorInfo = $stmt->errorInfo();
            $message = 'Erreur lors de la réservation. Détail SQL : ' . htmlspecialchars($errorInfo[2]);
          }
        } else {
          $message = '<span class="alert alert-error">Aucune table disponible à cette date/heure.</span>';
        }
      }
    } catch (PDOException $e) {
      $message = 'Erreur lors de la réservation : ' . $e->getMessage();
    }
  } else {
    $message = '<span class="alert alert-error">Champs invalides ou manquants.</span>';
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Réserver une table</title>
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

    .form-container input,
    .form-container select {
      padding: 0.7rem 1rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: border 0.2s;
    }

    .form-container input:focus,
    .form-container select:focus {
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

    /* Style pour le tableau des réservations */
    .styled-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
      margin: 32px auto 0 auto;
      overflow: hidden;
      font-size: 1rem;
      min-width: 700px;
      max-width: 1000px;
    }

    .styled-table th,
    .styled-table td {
      padding: 14px 18px;
      text-align: left;
    }

    .styled-table th {
      background: #f7f7f7;
      color: #283593;
      font-weight: 700;
      border-bottom: 2px solid #e0e0e0;
    }

    .styled-table tr:not(:last-child) {
      border-bottom: 1px solid #e0e0e0;
    }

    .styled-table tr:hover {
      background: #f2f6ff;
    }

    .styled-table td {
      color: #222;
    }

    .styled-table .actions {
      text-align: center;
    }

    @media (max-width: 900px) {

      .styled-table,
      .styled-table thead,
      .styled-table tbody,
      .styled-table th,
      .styled-table td,
      .styled-table tr {
        display: block;
      }

      .styled-table th {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }

      .styled-table td {
        position: relative;
        padding-left: 50%;
        min-height: 40px;
        border-bottom: 1px solid #eee;
      }

      .styled-table td:before {
        position: absolute;
        top: 12px;
        left: 18px;
        width: 45%;
        white-space: nowrap;
        color: #888;
        font-weight: 600;
        content: attr(data-label);
      }

      .styled-table {
        min-width: 0;
      }
    }
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="form-container">
    <h1>Réserver une table</h1>
    <?php if ($message): ?>
      <?= $message ?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          var form = document.getElementById('resaForm');
          if (form) form.style.display = 'none';
          var msg = document.getElementById('resa-message');
          if (msg) msg.style.display = 'block';
          // Redirection automatique après 3 secondes si succès
          if (msg.textContent.includes('succès')) {
            setTimeout(function() {
              window.location.href = '../index.html';
            }, 3000);
          }
        });
      </script>
    <?php endif; ?>
    <form method="post" autocomplete="off" id="resaForm" novalidate<?php if ($message) echo ' style="display:none;"'; ?>>
      <input type="text" name="name" id="name" placeholder="Nom" required>
      <input type="email" name="email" id="email" placeholder="Email" required>
      <input type="date" name="date" id="date" required>
      <input type="time" name="time" id="time" required>
      <input type="number" name="people" id="people" placeholder="Nombre de personnes" min="1" required>
      <input type="text" name="phone" id="phone" placeholder="Téléphone" required pattern="[0-9 +().-]{6,20}">
      <div id="form-error" class="alert alert-error" style="display:none;"></div>
      <button type="submit">Réserver</button>
    </form>
  </div>
  <script>
    var resaForm = document.getElementById('resaForm');
    if (resaForm) resaForm.addEventListener('submit', function(e) {
      var nom = document.getElementById('name').value.trim();
      var email = document.getElementById('email').value.trim();
      var date = document.getElementById('date').value;
      var time = document.getElementById('time').value;
      var people = document.getElementById('people').value;
      var phone = document.getElementById('phone').value.trim();
      var error = '';
      var emailRegex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      var phoneRegex = /^[0-9 +().-]{6,20}$/;
      if (!nom) {
        error = 'Veuillez saisir votre nom.';
        document.getElementById('name').focus();
      } else if (!email || !emailRegex.test(email)) {
        error = 'Veuillez saisir un email valide.';
        document.getElementById('email').focus();
      } else if (!date) {
        error = 'Veuillez choisir une date.';
        document.getElementById('date').focus();
      } else if (!time) {
        error = 'Veuillez choisir une heure.';
        document.getElementById('time').focus();
      } else if (!people || isNaN(people) || parseInt(people) < 1) {
        error = 'Veuillez indiquer le nombre de personnes.';
        document.getElementById('people').focus();
      } else if (!phone || !phoneRegex.test(phone)) {
        error = 'Veuillez saisir un numéro de téléphone valide.';
        document.getElementById('phone').focus();
      }
      if (error) {
        e.preventDefault();
        var errDiv = document.getElementById('form-error');
        errDiv.textContent = error;
        errDiv.style.display = 'block';
        return false;
      } else {
        document.getElementById('form-error').style.display = 'none';
      }
    });
  </script>
</body>

</html>