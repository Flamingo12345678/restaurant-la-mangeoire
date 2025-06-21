<?php
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/security_utils.php';
require_superadmin();
require_once '../db_connexion.php';

// Définir le titre de la page
$page_title = "Journal d'activité administrateur";
// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);

// Fichier de log
$logfile = __DIR__ . '/admin_actions.log';
$logs = [];

// Nombre de lignes à afficher par page
$lines_per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Lecture du fichier de log
if (file_exists($logfile)) {
  // Compter le nombre total de lignes
  $total_lines = 0;
  $f = fopen($logfile, 'r');
  while (!feof($f)) {
    $line = fgets($f);
    if (trim($line) !== '') {
      $total_lines++;
    }
  }
  fclose($f);

  // Calculer le nombre total de pages
  $total_pages = ceil($total_lines / $lines_per_page);

  // Lire les lignes pour la page actuelle
  $f = fopen($logfile, 'r');
  $start_line = max(0, $total_lines - ($page * $lines_per_page));
  $end_line = max(0, $total_lines - (($page - 1) * $lines_per_page));

  $current_line = 0;
  while (!feof($f)) {
    $line = fgets($f);
    if (trim($line) === '') continue;

    $current_line++;
    if ($current_line > $start_line && $current_line <= $end_line) {
      // Parse la ligne de log
      if (preg_match('/\[(.*?)\] \[(.*?)\] (.*?)( (.*))?$/', $line, $matches)) {
        $logs[] = [
          'date' => $matches[1],
          'user' => $matches[2],
          'action' => $matches[3],
          'details' => $matches[5] ?? ''
        ];
      } else {
        $logs[] = [
          'date' => 'N/A',
          'user' => 'N/A',
          'action' => $line,
          'details' => ''
        ];
      }
    }
  }
  fclose($f);

  // Inverser l'ordre pour avoir les plus récents en premier
  $logs = array_reverse($logs);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($page_title); ?> - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/admin-animations.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .log-entry {
      padding: 10px;
      border-bottom: 1px solid #eee;
      transition: background-color 0.2s;
    }

    .log-entry:hover {
      background-color: #f9f9f9;
    }

    .log-date {
      font-size: 0.85rem;
      color: #666;
    }

    .log-user {
      font-weight: 600;
      margin-left: 10px;
    }

    .log-action {
      display: block;
      margin-top: 5px;
      font-weight: 500;
    }

    .log-details {
      display: block;
      margin-top: 3px;
      font-size: 0.9rem;
      color: #555;
    }

    .filter-controls {
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
      padding: 15px;
      background-color: #f8f9fa;
      border-radius: 8px;
    }

    .filter-control {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-label {
      font-weight: 500;
      white-space: nowrap;
    }

    .filter-input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.9rem;
    }

    .filter-select {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.9rem;
      min-width: 150px;
    }

    .filter-button {
      padding: 8px 15px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.2s;
    }

    .filter-button:hover {
      background-color: #0069d9;
    }

    .filter-button.reset {
      background-color: #6c757d;
    }

    .filter-button.reset:hover {
      background-color: #5a6268;
    }

    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 5px;
    }

    .pagination a,
    .pagination span {
      padding: 8px 15px;
      border: 1px solid #ddd;
      border-radius: 4px;
      text-decoration: none;
      color: #007bff;
    }

    .pagination span {
      background-color: #007bff;
      color: white;
    }

    .pagination a:hover {
      background-color: #f8f9fa;
    }

    .log-empty {
      text-align: center;
      padding: 40px 0;
      color: #666;
    }
  </style>
</head>

<body>
  <?php include 'header_template.php'; ?>

  <section class="admin-section">
    <div class="admin-container">
      <div class="admin-card">
        <div class="admin-card-header">
          <h2><i class="bi bi-journals"></i> Journal d'activité administrateur</h2>
          <p>Consultez l'historique des actions réalisées par les administrateurs</p>
        </div>

        <div class="admin-card-body">
          <div class="filter-controls">
            <div class="filter-control">
              <span class="filter-label">Filtrer par :</span>
              <input type="text" id="filterText" class="filter-input" placeholder="Recherche..." onkeyup="filterLogs()">
            </div>
            <div class="filter-control">
              <span class="filter-label">Administrateur :</span>
              <select id="filterUser" class="filter-select" onchange="filterLogs()">
                <option value="">Tous</option>
                <?php
                $unique_users = [];
                foreach ($logs as $log) {
                  if (!in_array($log['user'], $unique_users) && $log['user'] !== 'N/A') {
                    $unique_users[] = $log['user'];
                    echo '<option value="' . htmlspecialchars($log['user']) . '">' . htmlspecialchars($log['user']) . '</option>';
                  }
                }
                ?>
              </select>
            </div>
            <div class="filter-control">
              <span class="filter-label">Action :</span>
              <select id="filterAction" class="filter-select" onchange="filterLogs()">
                <option value="">Toutes</option>
                <?php
                $unique_actions = [];
                foreach ($logs as $log) {
                  $action = preg_replace('/\s+.*$/', '', $log['action']);
                  if (!in_array($action, $unique_actions) && $action !== 'N/A') {
                    $unique_actions[] = $action;
                    echo '<option value="' . htmlspecialchars($action) . '">' . htmlspecialchars($action) . '</option>';
                  }
                }
                ?>
              </select>
            </div>
            <div class="filter-control">
              <button type="button" class="filter-button reset" onclick="resetFilters()">Réinitialiser</button>
            </div>
          </div>

          <div class="log-container">
            <?php if (empty($logs)): ?>
              <div class="log-empty">
                <i class="bi bi-exclamation-circle"></i>
                <p>Aucune entrée de journal trouvée.</p>
              </div>
            <?php else: ?>
              <div class="log-entries">
                <?php foreach ($logs as $log): ?>
                  <div class="log-entry">
                    <span class="log-date"><?php echo htmlspecialchars($log['date']); ?></span>
                    <span class="log-user"><?php echo htmlspecialchars($log['user']); ?></span>
                    <span class="log-action"><?php echo htmlspecialchars($log['action']); ?></span>
                    <?php if (!empty($log['details'])): ?>
                      <span class="log-details"><?php echo htmlspecialchars($log['details']); ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <?php if ($total_pages > 1): ?>
                <div class="pagination">
                  <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                  <?php endif; ?>

                  <?php
                  $start_page = max(1, min($page - 2, $total_pages - 4));
                  $end_page = min($total_pages, max(5, $page + 2));

                  if ($start_page > 1) {
                    echo '<a href="?page=1">1</a>';
                    if ($start_page > 2) {
                      echo '<span>...</span>';
                    }
                  }

                  for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                      echo '<span>' . $i . '</span>';
                    } else {
                      echo '<a href="?page=' . $i . '">' . $i . '</a>';
                    }
                  }

                  if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                      echo '<span>...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                  }
                  ?>

                  <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer_template.php'; ?>

  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>
  <script>
    function filterLogs() {
      const filterText = document.getElementById('filterText').value.toLowerCase();
      const filterUser = document.getElementById('filterUser').value;
      const filterAction = document.getElementById('filterAction').value;

      const logEntries = document.querySelectorAll('.log-entry');
      let visibleCount = 0;

      logEntries.forEach(entry => {
        const date = entry.querySelector('.log-date').textContent.toLowerCase();
        const user = entry.querySelector('.log-user').textContent.toLowerCase();
        const action = entry.querySelector('.log-action').textContent.toLowerCase();
        const details = entry.querySelector('.log-details') ?
          entry.querySelector('.log-details').textContent.toLowerCase() :
          '';

        // Vérifier les filtres
        const matchesText = filterText === '' ||
          date.includes(filterText) ||
          user.includes(filterText) ||
          action.includes(filterText) ||
          details.includes(filterText);

        const matchesUser = filterUser === '' || user.trim() === filterUser.toLowerCase();

        const matchesAction = filterAction === '' || action.startsWith(filterAction.toLowerCase());

        // Afficher ou masquer l'entrée
        if (matchesText && matchesUser && matchesAction) {
          entry.style.display = '';
          visibleCount++;
        } else {
          entry.style.display = 'none';
        }
      });

      // Afficher un message si aucune entrée ne correspond
      const emptyMessage = document.querySelector('.log-empty');
      if (emptyMessage) {
        if (visibleCount === 0) {
          emptyMessage.style.display = 'block';
          emptyMessage.querySelector('p').textContent = 'Aucune entrée ne correspond aux filtres.';
        } else {
          emptyMessage.style.display = 'none';
        }
      } else if (visibleCount === 0) {
        const container = document.querySelector('.log-entries');
        const newEmptyMessage = document.createElement('div');
        newEmptyMessage.className = 'log-empty';
        newEmptyMessage.innerHTML = '<i class="bi bi-exclamation-circle"></i><p>Aucune entrée ne correspond aux filtres.</p>';
        container.appendChild(newEmptyMessage);
      }
    }

    function resetFilters() {
      document.getElementById('filterText').value = '';
      document.getElementById('filterUser').value = '';
      document.getElementById('filterAction').value = '';
      filterLogs();
    }
  </script>
</body>

</html>