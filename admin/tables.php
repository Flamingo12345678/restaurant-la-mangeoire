<?php
define('CAPACITE_SALLE', 100);
require_once __DIR__ . '/../includes/common.php';
require_admin();
require_once '../db_connexion.php';
$message = '';

// Initialisation par défaut pour éviter les warnings
$alerte = '';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$total_places = 0;
$places_restantes = 0;
$total_reserves = 0;

// Fonction pour gérer l'ajout automatique de tables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajout_tables_types'])) {
  // Vérifier la capacité restante avant ajout
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $pdo->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  $places_restantes = CAPACITE_SALLE - $total_places;

  // Tables standard à créer (2, 4, 6, 8 places)
  $tables_a_creer = [
    ['capacite' => 2, 'nombre' => 5],
    ['capacite' => 4, 'nombre' => 8],
    ['capacite' => 6, 'nombre' => 5],
    ['capacite' => 8, 'nombre' => 2]
  ];

  $total_ajoutees = 0;
  $total_places_ajoutees = 0;

  // Trouver le dernier numéro de table
  $sql = "SELECT MAX(NumeroTable) as max_num FROM TablesRestaurant";
  $stmt = $pdo->query($sql);
  $dernier_numero = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dernier_numero = intval($row['max_num']);
  }

  // Préparer la requête d'insertion
  $sql = "INSERT INTO TablesRestaurant (NumeroTable, NomTable, Capacite) VALUES (?, ?, ?)";
  $stmt = $pdo->prepare($sql);

  // Ajouter les tables
  foreach ($tables_a_creer as $table_type) {
    $capacite = $table_type['capacite'];
    $nombre = $table_type['nombre'];

    for ($i = 0; $i < $nombre; $i++) {
      // Vérifier si on dépasse la capacité de la salle
      if ($total_places_ajoutees + $capacite > $places_restantes) {
        break;
      }

      $dernier_numero++;
      $nom_table = "Table " . $dernier_numero;
      $result = $stmt->execute([$dernier_numero, $nom_table, $capacite]);

      if ($result) {
        $total_ajoutees++;
        $total_places_ajoutees += $capacite;
      }
    }
  }

  if ($total_ajoutees > 0) {
    $message = "$total_ajoutees tables ajoutées automatiquement ($total_places_ajoutees places au total).";
  } else {
    $message = "Aucune table n'a pu être ajoutée. Capacité maximale de la salle atteinte.";
  }

  // Rediriger pour éviter la soumission du formulaire à nouveau lors du rafraîchissement
  header('Location: tables.php?message=' . urlencode($message));
  exit;
}
// Ajout manuel d'une table
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero'], $_POST['capacite'])) {
  $numero = intval($_POST['numero']);
  $capacite = intval($_POST['capacite']);
  // Vérifier la capacité restante avant ajout
  $sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
  $stmt = $pdo->query($sql);
  $total_places = 0;
  if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_places = intval($row['total_places']);
  }
  $places_restantes = CAPACITE_SALLE - $total_places;
  if ($numero > 0 && $capacite > 0) {
    if ($capacite > $places_restantes) {
      $message = 'Impossible d\'ajouter cette table : capacité maximale de la salle atteinte.';
    } else {
      $nom_table = $_POST['nom_table'] ?? '';
      if (empty($nom_table)) {
        $nom_table = 'Table ' . $numero;
      }
      $sql = "INSERT INTO TablesRestaurant (NumeroTable, NomTable, Capacite) VALUES (?, ?, ?)";
      $stmt = $pdo->prepare($sql);
      $result = $stmt->execute([$numero, $nom_table, $capacite]);
      if ($result) {
        $message = 'Table ajoutée avec succès.';
      } else {
        $message = 'Erreur lors de l\'ajout de la table.';
      }
    }
  } else {
    $message = 'Champs invalides. Le numéro et la capacité doivent être des nombres positifs.';
  }
}
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM TablesRestaurant WHERE TableID = ?";
  $stmt = $pdo->prepare($sql);
  $result = $stmt->execute([$id]);
  if ($result) {
    $message = 'Table supprimée.';
  } else {
    $message = 'Erreur lors de la suppression.';
  }
}
$tables = [];
$sql = "SELECT * FROM TablesRestaurant ORDER BY TableID DESC";
$stmt = $pdo->query($sql);
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[] = $row;
  }
}

// Calcul dynamique des statistiques pour les cards
$sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
$stmt = $pdo->query($sql);
$total_places = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_places = intval($row['total_places']);
}
// Correction : places restantes = capacité totale de la salle - personnes réservées à venir
$now = date('Y-m-d H:i:s');
$sql = "SELECT SUM(Capacite) AS total_places FROM TablesRestaurant";
$stmt = $pdo->query($sql);
$total_places = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $total_places = intval($row['total_places']);
}
$sql = "SELECT COALESCE(SUM(nb_personnes),0) AS total_reserves FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$now]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_reserves = intval($row['total_reserves']);
$places_restantes = $total_places - $total_reserves;
// Card : nombre de tables disponibles (statut 'Libre')
$sql = "SELECT COUNT(*) AS nb_libres FROM TablesRestaurant WHERE Statut = 'Libre'";
$stmt = $pdo->query($sql);
$nb_tables_libres = 0;
if ($stmt) {
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $nb_tables_libres = intval($row['nb_libres']);
}
// Card : nombre de tables disponibles (statut 'Libre') et détail par type
$sql = "SELECT Capacite, COUNT(*) AS nb FROM TablesRestaurant WHERE Statut = 'Libre' GROUP BY Capacite ORDER BY Capacite ASC";
$stmt = $pdo->query($sql);
$types_tables_libres = [];
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $types_tables_libres[] = $row;
  }
}

// Définir le titre de la page
$page_title = "Gestion des Tables";

// CSS supplémentaires spécifiques à cette page
$additional_css = [
    'css/admin-messages.css'
];

// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);
require_once 'header_template.php';
?>
<!-- Contenu spécifique de la page -->
<div class="content-wrapper">
<div style="background-color: #f9f9f9; border-radius: 5px;">
<h2 style="color: #222; font-size: 23px; margin-bottom: 30px; position: relative;">Gestion des tables</h2>
</div>
<?php if ($alerte) echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' . $alerte . '</div>'; ?>
<?php if ($message) echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $message . '</div>'; ?>
<div class="dashboard-cards">
<div class="dashboard-card">
<div class="card-title">Places totales</div>
<div class="card-value"><?php echo $total_places; ?></div>
</div>
<div class="dashboard-card">
<div class="card-title">Places restantes</div>
<div class="card-value"><?php echo $places_restantes; ?></div>
</div>
<div class="dashboard-card">
<div class="card-title">Personnes réservées à venir</div>
<div class="card-value"><?php echo $total_reserves; ?></div>
</div>
<div class="dashboard-card">
<div class="card-title">Tables disponibles</div>
<div class="card-value"><?php echo $nb_tables_libres; ?></div>
<?php if (!empty($types_tables_libres)): ?>
<div style="font-size:1em;color:#444;margin-top:8px;">
<?php foreach ($types_tables_libres as $t): ?>
<div><?= $t['nb'] ?> table<?= $t['nb'] > 1 ? 's' : '' ?> de <?= $t['Capacite'] ?> place<?= $t['Capacite'] > 1 ? 's' : '' ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
<div class="form-section">
<h3 class="section-title">Ajouter une table</h3>
<form method="post" class="form-grid">
<div class="form-group">
<input type="number" name="numero" placeholder="Numéro de table" required>
</div>
<div class="form-group">
<input type="number" name="capacite" placeholder="Capacité" required>
</div>
<div class="form-group">
<input type="text" name="nom_table" placeholder="Nom de la table" required>
</div>
<div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 15px;">
<button type="submit" class="submit-btn">Ajouter</button>
<button type="submit" name="ajout_tables_types" class="submit-btn" style="background-color: var(--primary-dark);">Ajout auto (2,4,6,8 places)</button>
</div>
</form>
</div>
<h3 class="section-title" style="margin-top: 30px;">Liste des tables</h3>
<div class="table-responsive-wrapper">
<table class="admin-table">
<thead>
<tr>
<th>ID</th>
<th>Numéro</th>
<th>Capacité</th>
<th>Statut</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if (isset($tables)) foreach ($tables as $t): ?>
<tr>
<td><?= htmlspecialchars($t['TableID']) ?></td>
<td><?= htmlspecialchars($t['NumeroTable']) ?></td>
<td><?= htmlspecialchars($t['Capacite']) ?></td>
<td>
<?php if (isset($t['Statut'])): ?>
<span style="font-weight:bold;color:<?= $t['Statut'] === 'Réservée' ? '#b01e28' : '#217a3c' ?>;">
<?= htmlspecialchars($t['Statut']) ?>
</span>
<?php else: ?>
<span style="color:#757575;">-</span>
<?php endif; ?>
</td>
<td><a href="?delete=<?= $t['TableID'] ?>" onclick="return confirm('Supprimer cette table ?')"><i class="bi bi-trash"></i></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div> <!-- Fermeture du content-wrapper -->
<?php
  require_once 'footer_template.php';
  ?>