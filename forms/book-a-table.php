<?php
session_start();
// Traitement du formulaire de réservation de table
require_once '../db_connexion.php';
require_once '../includes/common.php';

// Variable pour suivre si une réservation a été effectuée avec succès
$reservation_success = false;
$success_details = [];

// Traiter le formulaire uniquement s'il a été soumis
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
        $error_message = 'Impossible d\'enregistrer la réservation : la capacité maximale de la salle serait dépassée.';
      } else {
        // Recherche de toutes les tables et calcul des places déjà réservées sur le créneau
        // Définir le créneau de réservation (début et fin)
        $start = $datetime;
        $end = date('Y-m-d H:i:s', strtotime($datetime) + 2 * 3600);
        $sql = "SELECT t.TableID, t.Capacite
        FROM TablesRestaurant t
        WHERE t.TableID NOT IN (
          SELECT TableID FROM Reservations
          WHERE Statut = 'Réservée'
            AND ((DateReservation < ? AND DATE_ADD(DateReservation, INTERVAL 2 HOUR) > ?)
                 OR (DateReservation >= ? AND DateReservation < ?))
        )
        ORDER BY t.Capacite DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$end, $start, $start, $end]);
        $tables_libres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Trier les tables libres par capacité croissante pour privilégier la plus petite table adaptée
        usort($tables_libres, function ($a, $b) {
          return $a['Capacite'] <=> $b['Capacite'];
        });
        // --- Optimisation : trouver la meilleure combinaison de tables (surplus minimal) ---
        function combinations($arr, $k)
        {
          $n = count($arr);
          if ($k == 0) return [[]];
          if ($k > $n) return [];
          $res = [];
          for ($i = 0; $i <= $n - $k; $i++) {
            $head = [$arr[$i]];
            foreach (combinations(array_slice($arr, $i + 1), $k - 1) as $tail) {
              $res[] = array_merge($head, $tail);
            }
          }
          return $res;
        }
        function meilleure_combinaison($tables, $personnes)
        {
          $n = count($tables);
          $indices = range(0, $n - 1);
          $min_surplus = null;
          $meilleur = null;
          $max_tables = min($n, 7); // Limite pour la performance
          for ($k = 1; $k <= $max_tables; $k++) {
            foreach (combinations($indices, $k) as $comb) {
              $somme = 0;
              foreach ($comb as $i) $somme += $tables[$i]['Capacite'];
              if ($somme >= $personnes) {
                $surplus = $somme - $personnes;
                if ($min_surplus === null || $surplus < $min_surplus) {
                  $min_surplus = $surplus;
                  $meilleur = $comb;
                  if ($surplus === 0) return $comb;
                }
              }
            }
          }
          return $meilleur;
        }
        $tables_attribuees = [];
        $personnes_restantes = $people;
        $meilleure = meilleure_combinaison($tables_libres, $people);
        if ($meilleure) {
          foreach ($meilleure as $i) {
            $places = min($tables_libres[$i]['Capacite'], $personnes_restantes);
            $tables_attribuees[] = [
              'TableID' => $tables_libres[$i]['TableID'],
              'places' => $places,
              'Capacite' => $tables_libres[$i]['Capacite']
            ];
            $personnes_restantes -= $places;
          }
        } else {
          // fallback : ancienne logique (plus grandes tables d'abord)
          usort($tables_libres, function ($a, $b) {
            return $b['Capacite'] <=> $a['Capacite'];
          });
          foreach ($tables_libres as $table) {
            if ($personnes_restantes <= 0) break;
            $places = min($table['Capacite'], $personnes_restantes);
            $tables_attribuees[] = [
              'TableID' => $table['TableID'],
              'places' => $places,
              'Capacite' => $table['Capacite']
            ];
            $personnes_restantes -= $places;
          }
        }
        // --- Fin optimisation ---
        if ($personnes_restantes <= 0) {
          // Recherche ou création du client
          $sql = "SELECT ClientID FROM Clients WHERE Email = ?";
          $stmt_client = $conn->prepare($sql);
          $stmt_client->execute([$email]);
          $client = $stmt_client->fetch(PDO::FETCH_ASSOC);
          if ($client) {
            $client_id = $client['ClientID'];
          } else {
            $sql = "INSERT INTO Clients (Nom, Prenom, Email, Telephone) VALUES (?, '', ?, ?)";
            $stmt_insert = $conn->prepare($sql);
            $stmt_insert->execute([$nom, $email, $telephone]);
            $client_id = $conn->lastInsertId();
          }
          // 1 seule réservation pour tout le groupe
          $sql = "INSERT INTO Reservations (DateReservation, Statut, nom_client, email_client, nb_personnes, telephone, ClientID) VALUES (?, 'Réservée', ?, ?, ?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $stmt->execute([$datetime, $nom, $email, $people, $telephone, $client_id]);
          $reservation_id = $conn->lastInsertId();
          // Associer toutes les tables à la réservation dans ReservationTables (avec le nombre de places attribuées)
          if (count($tables_attribuees) > 0) {
            foreach ($tables_attribuees as $table) {
              $sql = "INSERT INTO ReservationTables (ReservationID, TableID, nb_places) VALUES (?, ?, ?)";
              $stmt = $conn->prepare($sql);
              $stmt->execute([$reservation_id, $table['TableID'], $table['places']]);
              
              // Mettre à jour le statut de la table
              $sql = "UPDATE TablesRestaurant SET Statut = 'Réservée' WHERE TableID = ?";
              $stmt_update = $conn->prepare($sql);
              $stmt_update->execute([$table['TableID']]);
            }
            
            // Marquer la réservation comme réussie directement (sans utiliser de session)
            $reservation_success = true;
            
            // Préparer les détails des tables pour l'affichage
            $success_details = array_map(function ($t) {
              return 'Table ' . $t['TableID'] . ' (' . $t['places'] . ' pers.)';
            }, $tables_attribuees);
          } else {
            $error_message = 'Aucune table disponible n\'a pu être assignée à cette réservation.';
          }
        } else {
          $error_message = 'Pas assez de places disponibles pour accueillir ' . $people . ' personnes à cette date/heure.';
        }
      }
    } catch (PDOException $e) {
      $error_message = 'Erreur lors de la réservation : ' . $e->getMessage();
    }
  } else {
    $error_message = 'Veuillez remplir tous les champs obligatoires.';
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

    /* Style pour le message de confirmation et le compteur */
    .reservation-confirmation {
      text-align: center;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      background-color: #e6f9ed;
      border-radius: 8px;
      border: 1px solid #b6e2c7;
      color: #217a3c;
      animation: fadeIn 0.5s ease;
      width: 100%;
      box-sizing: border-box;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .success-heading {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: #217a3c;
    }
    
    .success-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    
    /* Styles pour le compteur */
    .countdown-container {
      margin-top: 1.5rem;
      font-weight: bold;
    }
    
    #countdown {
      display: inline-block;
      background-color: #217a3c;
      color: white;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      line-height: 36px;
      text-align: center;
      animation: pulse 1s infinite;
      font-weight: bold;
    }
    
    /* Amélioration de l'effet pulsant du countdown */
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
  </style>
</head>

<body style="background:#f7f7f7; min-height:100vh;">
  <div class="form-container">
    <h1>Réserver une table</h1>
    
    <?php if ($reservation_success): ?>
      <!-- Affichage direct du message de réservation réussie -->
      <div class="reservation-confirmation">
        <div class="success-icon">✓</div>
        <div class="success-heading">Réservation confirmée</div>
        <p>Réservation enregistrée avec succès !</p>
        <p>Tables attribuées : <?php echo implode(', ', $success_details); ?></p>
        <div class="countdown-container">
          Redirection vers l'accueil dans <span id="countdown">5</span> secondes...
        </div>
      </div>
      
      <script>
        // Script de redirection avec compteur
        let seconds = 5;
        const countdownDisplay = document.getElementById('countdown');
        const countdown = setInterval(() => {
          seconds--;
          countdownDisplay.textContent = seconds;
          if (seconds <= 0) {
            clearInterval(countdown);
            window.location.href = '../index.html';
          }
        }, 1000);
      </script>
    <?php else: ?>
      <!-- Affichage d'erreur s'il y en a une -->
      <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <!-- Formulaire de réservation -->
      <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="resaForm" novalidate>
        <input type="text" name="name" id="name" placeholder="Nom" required 
               value="<?php echo htmlspecialchars($nom ?? ''); ?>">
               
        <input type="email" name="email" id="email" placeholder="Email" required
               value="<?php echo htmlspecialchars($email ?? ''); ?>">
               
        <input type="date" name="date" id="date" required
               value="<?php echo htmlspecialchars($date ?? ''); ?>">
               
        <input type="time" name="time" id="time" required
               value="<?php echo htmlspecialchars($time ?? ''); ?>">
               
        <input type="number" name="people" id="people" placeholder="Nombre de personnes" min="1" required
               value="<?php echo htmlspecialchars($people ?? ''); ?>">
               
        <input type="text" name="phone" id="phone" placeholder="Téléphone" required
               value="<?php echo htmlspecialchars($telephone ?? ''); ?>">
               
        <div id="form-error" class="alert alert-error" style="display:none;"></div>
        <button type="submit">Réserver</button>
      </form>
    <?php endif; ?>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Code JavaScript de validation existant...
      
      // Définir la date minimale (aujourd'hui)
      var dateInput = document.getElementById('date');
      if (dateInput) {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        today = yyyy + '-' + mm + '-' + dd;
        dateInput.setAttribute('min', today);
      }
    });
  </script>
</body>
</html>