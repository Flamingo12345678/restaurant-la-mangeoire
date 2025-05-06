<?php

/**
 * add_table.php
 *
 * Permet à un administrateur d'ajouter une nouvelle table dans la base de données.
 * - Protection par session admin obligatoire.
 * - Validation des champs (numéro et nombre de places).
 * - Utilisation de requêtes préparées pour éviter les injections SQL.
 * - Affiche un message de succès ou d'erreur.
 *
 * @auteur Projet La Mangeoire
 * @sécurité Accès restreint aux administrateurs via session.
 */

require_once __DIR__ . '/../includes/common.php';
require_superadmin();
generate_csrf_token();
require_once '../db_connexion.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Vérification du token CSRF
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    set_message('Erreur de sécurité (CSRF).', 'error');
  } else {
    $numero = $_POST['numero'] ?? '';
    $places = $_POST['places'] ?? '';
    $valid = validate_numero_table($numero) && validate_places($places);
    if ($valid) {
      $numero = intval($numero);
      $places = intval($places);
      // Vérification de la capacité maximale de la salle
      define('CAPACITE_SALLE', 100);
      $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
      $stmt = $conn->query($sql);
      $total_places = 0;
      if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_places = intval($row['total_places']);
      }
      $places_restantes = CAPACITE_SALLE - $total_places;
      if ($places > $places_restantes) {
        set_message("Impossible d'ajouter cette table : capacité maximale de la salle atteinte.", 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      }
      $statut = $_POST['statut'] ?? 'Libre';
      if (!in_array($statut, ['Libre', 'Réservée', 'Maintenance'])) {
        $statut = 'Libre';
      }
      // Contrôle d'unicité du numéro de table
      $sql = "SELECT COUNT(*) FROM TablesRestaurant WHERE NumeroTable = ?";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$numero]);
      $count = $stmt->fetchColumn();
      if ($count > 0) {
        set_message("Ce numéro de table existe déjà.", 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      }
      $nom_table = $_POST['nom_table'] ?? '';
      if (empty($nom_table)) {
        $nom_table = 'Table ' . $numero;
      }
      $sql = "INSERT INTO TablesRestaurant (NumeroTable, NomTable, Capacite, Statut) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      if ($stmt->execute([$numero, $nom_table, $places, $statut])) {
        set_message('Table ajoutée.');
        // log_admin_action('Ajout table', "Numéro: $numero, Places: $places, Statut: $statut");
        header('Location: tables.php');
        exit;
      } else {
        set_message('Erreur lors de l\'ajout.', 'error');
        // log_admin_action('Erreur ajout table', "Numéro: $numero, Places: $places, Statut: $statut");
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      }
    } else {
      set_message('Champs invalides.', 'error');
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Ajouter une table</title>
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
  <div class="form-container">
    <a href="tables.php" class="back-link">&larr; Retour à la liste</a>
    <h1>Ajouter une table</h1>
    <?php display_message(); ?>
    <form method="post" autocomplete="off" id="addTableForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="number" name="numero" id="numero" placeholder="Numéro de table" required min="1">
      <input type="number" name="places" id="places" placeholder="Nombre de places" required min="1">
      <input type="text" name="nom_table" id="nom_table" placeholder="Nom de la table" required>
      <select name="statut" id="statut" required>
        <option value="Libre" selected>Libre</option>
        <option value="Réservée">Réservée</option>
        <option value="Maintenance">Maintenance</option>
      </select>
      <div id="form-error" class="alert alert-error" style="display:none;"></div>
      <button type="submit">Ajouter</button>
    </form>
    <script>
      (function() {
        const form = document.getElementById('addTableForm');
        const numero = document.getElementById('numero');
        const places = document.getElementById('places');
        const nom_table = document.getElementById('nom_table');
        const statut = document.getElementById('statut');
        const errorDiv = document.getElementById('form-error');

        function showError(msg) {
          errorDiv.textContent = msg;
          errorDiv.style.display = 'block';
        }

        function clearError() {
          errorDiv.textContent = '';
          errorDiv.style.display = 'none';
        }

        function validateField(field) {
          if (field === numero && (numero.value === '' || isNaN(numero.value) || parseInt(numero.value) < 1)) {
            showError('Numéro de table invalide.');
            return false;
          }
          if (field === places && (places.value === '' || isNaN(places.value) || parseInt(places.value) < 1)) {
            showError('Nombre de places invalide.');
            return false;
          }
          if (field === nom_table && nom_table.value.trim() === '') {
            showError('Veuillez saisir le nom de la table.');
            return false;
          }
          if (field === statut && !['Libre', 'Réservée', 'Maintenance'].includes(statut.value)) {
            showError('Statut invalide.');
            return false;
          }
          clearError();
          return true;
        }
        [numero, places, nom_table, statut].forEach(input => {
          input.addEventListener('input', function() {
            validateField(this);
          });
          input.addEventListener('blur', function() {
            validateField(this);
          });
        });
        form.addEventListener('submit', function(e) {
          if (!validateField(numero) || !validateField(places) || !validateField(nom_table) || !validateField(statut)) {
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