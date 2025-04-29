<?php
// Traitement du formulaire de réservation de table
require_once '../db_connexion.php';
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
<<<<<<< HEAD
      $sql = "INSERT INTO Reservations (DateReservation, Statut, nom_client, email_client, nb_personnes, telephone) VALUES (?, 'Réservée', ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $result = $stmt->execute([$date . ' ' . $time, $nom, $email, $people, $telephone]);
      if ($result) {
        $message = '<span class="alert alert-success" id="resa-success">Réservation enregistrée avec succès ! Vous allez être redirigé vers l\'accueil.</span>';
        echo '<script>setTimeout(function(){ window.location.href = "/index.html"; }, 3000);</script>';
      } else {
        $errorInfo = $stmt->errorInfo();
        $message = 'Erreur lors de la réservation. Détail SQL : ' . htmlspecialchars($errorInfo[2]);
=======
      // Vérification stricte de la capacité totale à venir (toutes réservations confondues)
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
        // 1. Chercher les tables libres pour la date/heure demandée
        $datetime = $date . ' ' . $time;
        // Récupérer toutes les tables
        $sql = "SELECT TableID, Capacite FROM TablesRestaurant ORDER BY Capacite ASC";
        $tables = $conn->query($sql)->fetchAll();
        // Récupérer les tables déjà réservées à ce créneau
        $sql = "SELECT TableID FROM Reservations WHERE DateReservation = ? AND Statut = 'Réservée' AND TableID IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$datetime]);
        $tables_occupees = array_column($stmt->fetchAll(), 'TableID');
        // Filtrer les tables libres
        $tables_libres = array_filter($tables, function ($t) use ($tables_occupees) {
          return !in_array($t['TableID'], $tables_occupees);
        });
        // Algorithme glouton pour couvrir le nombre de personnes
        $a_affecter = [];
        $places_couvertes = 0;
        foreach ($tables_libres as $t) {
          $a_affecter[] = $t['TableID'];
          $places_couvertes += $t['Capacite'];
          if ($places_couvertes >= $people) break;
        }
        if ($places_couvertes < $people) {
          $message = '<span class="alert alert-error">Impossible de trouver suffisamment de tables libres pour ' . $people . ' personnes à cette date/heure.</span>';
        } else {
          // 2. Insérer la réservation pour chaque table affectée
          foreach ($a_affecter as $table_id) {
            $sql = "INSERT INTO Reservations (DateReservation, Statut, nom_client, email_client, nb_personnes, telephone, TableID) VALUES (?, 'Réservée', ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$datetime, $nom, $email, $people, $telephone, $table_id]);
          }
          $message = '<span class="alert alert-success" id="resa-success">Réservation enregistrée avec succès ! Vous allez être redirigé vers l\'accueil.</span>';
          echo '<script>setTimeout(function(){ window.location.href = "/index.html"; }, 3000);</script>';
        }
>>>>>>> 230e8dc (mise à jour du fichier db_connexion et ajout du fichier .env)
      }
    } catch (PDOException $e) {
      $message = 'Erreur base de données : ' . htmlspecialchars($e->getMessage());
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
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="form-container">
    <h1>Réserver une table</h1>
    <?php if ($message): ?>
      <div class="alert alert-success" id="resa-success" style="display:block; font-size:1.1em; font-weight:bold; margin-bottom:1em;"> <?= $message ?> </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          var form = document.getElementById('resaForm');
          if (form) form.style.display = 'none';
          var msg = document.getElementById('resa-success');
          if (msg) msg.style.display = 'block';
        });
      </script>
    <?php endif; ?>
    <form method="post" autocomplete="off" id="resaForm" novalidate <?php if ($message) echo 'style="display:none;"'; ?>>
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