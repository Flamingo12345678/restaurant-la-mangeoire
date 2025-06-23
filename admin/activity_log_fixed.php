<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();
generate_csrf_token();

// Titre de la page
$page_title = "Logs d'Activité";

// Fonction pour obtenir les logs d'activité
function get_activity_logs() {
  $logs = [];
  $log_file = __DIR__ . '/admin_actions.log';
  
  if (file_exists($log_file) && is_readable($log_file)) {
    $f = fopen($log_file, 'r');
    while (($line = fgets($f)) !== false) {
      $line = trim($line);
      if ($line) {
        // Parser la ligne de log (format: [DATE] USER: ACTION - DETAILS)
        if (preg_match('/^\[([^\]]+)\]\s+([^:]+):\s+([^-]+)(?:\s*-\s*(.*))?$/', $line, $matches)) {
          $logs[] = [
            'date' => $matches[1],
            'user' => trim($matches[2]),
            'action' => trim($matches[3]),
            'details' => isset($matches[4]) ? trim($matches[4]) : ''
          ];
        } else {
          // Format simple si le parsing échoue
          $logs[] = [
            'date' => date('Y-m-d H:i:s'),
            'user' => 'Système',
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
  
  return $logs;
}

// Obtenir les logs
$logs = get_activity_logs();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;
$total_logs = count($logs);
$total_pages = ceil($total_logs / $per_page);
$logs_page = array_slice($logs, $offset, $per_page);

// Filtrage par utilisateur
$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
if ($filter_user) {
  $logs_page = array_filter($logs_page, function($log) use ($filter_user) {
    return stripos($log['user'], $filter_user) !== false;
  });
}

// Obtenir la liste des utilisateurs uniques pour le filtre
$users = array_unique(array_column($logs, 'user'));
sort($users);

// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);
require_once 'header_template.php';
?>

<section class="admin-section">
  <div class="admin-container">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2><i class="bi bi-journals"></i> Journal d'activité administrateur</h2>
        <p>Consultez l'historique des actions réalisées par les administrateurs</p>
      </div>

      <div class="admin-card-body">
        <!-- Section de filtrage -->
        <div class="filter-section">
          <form method="get" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <label for="user">Filtrer par utilisateur :</label>
            <select name="user" id="user" class="filter-select">
              <option value="">Tous les utilisateurs</option>
              <?php foreach ($users as $user): ?>
                <option value="<?php echo htmlspecialchars($user); ?>" <?php echo ($filter_user == $user) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($user); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="filter-button">Filtrer</button>
            <a href="activity_log.php" class="filter-button reset">Réinitialiser</a>
          </form>
        </div>

        <!-- Affichage des logs -->
        <?php if (empty($logs_page)): ?>
          <div class="log-empty">
            <i class="bi bi-journal-x" style="font-size: 48px; color: #ccc;"></i>
            <h4>Aucun log d'activité</h4>
            <p>Il n'y a pas encore d'activité enregistrée<?php echo $filter_user ? " pour l'utilisateur \"$filter_user\"" : ''; ?>.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive-wrapper">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Date/Heure</th>
                  <th>Utilisateur</th>
                  <th>Action</th>
                  <th>Détails</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logs_page as $log): ?>
                  <tr class="log-entry">
                    <td class="log-date"><?php echo htmlspecialchars($log['date']); ?></td>
                    <td class="log-user"><?php echo htmlspecialchars($log['user']); ?></td>
                    <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                    <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="pagination">
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                  <span><?php echo $i; ?></span>
                <?php else: ?>
                  <a href="?page=<?php echo $i; ?><?php echo $filter_user ? '&user=' . urlencode($filter_user) : ''; ?>">
                    <?php echo $i; ?>
                  </a>
                <?php endif; ?>
              <?php endfor; ?>
            </div>
          <?php endif; ?>

          <div class="mt-3 text-muted">
            <small>
              <?php echo count($logs_page); ?> entrée(s) affichée(s) sur <?php echo $total_logs; ?> au total
              <?php echo $filter_user ? " (filtrées pour \"$filter_user\")" : ''; ?>
            </small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<style>
  .admin-section {
    padding: 20px;
    background-color: #f8f9fa;
    min-height: 100vh;
  }

  .admin-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .admin-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .admin-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
  }

  .admin-card-header h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
  }

  .admin-card-header p {
    margin: 5px 0 0;
    opacity: 0.9;
  }

  .admin-card-body {
    padding: 20px;
  }

  .filter-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
  }

  .filter-section label {
    font-weight: 500;
    color: #333;
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
    text-decoration: none;
  }

  .filter-button:hover {
    background-color: #0069d9;
    color: white;
  }

  .filter-button.reset {
    background-color: #6c757d;
  }

  .filter-button.reset:hover {
    background-color: #5a6268;
  }

  .log-entry {
    transition: background-color 0.2s;
  }

  .log-entry:hover {
    background-color: #f9f9f9;
  }

  .log-date {
    font-size: 0.85rem;
    color: #666;
    white-space: nowrap;
  }

  .log-user {
    font-weight: 500;
    color: #2c3e50;
  }

  .log-action {
    color: #27ae60;
    font-weight: 500;
  }

  .log-details {
    color: #7f8c8d;
    font-size: 0.9rem;
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

  @media (max-width: 768px) {
    .filter-section {
      flex-direction: column;
      align-items: stretch;
    }
    
    .filter-section form {
      flex-direction: column !important;
      gap: 10px !important;
    }
    
    .admin-table th:first-child,
    .admin-table td:first-child {
      display: none;
    }
    
    .admin-table th,
    .admin-table td {
      padding: 8px 6px;
      font-size: 0.85rem;
    }
  }

  @media (max-width: 480px) {
    .admin-table th:nth-child(4),
    .admin-table td:nth-child(4) {
      display: none;
    }
  }
</style>

<?php
require_once 'footer_template.php';
?>
