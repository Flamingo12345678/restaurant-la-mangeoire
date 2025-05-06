<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
// Définir le titre de la page
$page_title = "Tableau de bord";
// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);
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
    /* Styles spécifiques au tableau de bord uniquement */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin: 30px 0;
    }

    .stat-card {
      background: #ce1212;
      border-radius: 10px;
      padding: 20px;
      color: #fff;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(206, 18, 18, 0.15);
    }

    .stat-card .stat-title {
      font-size: 1.08rem;
      color: #fff;
      margin-bottom: 8px;
      font-weight: 500;
    }

    .stat-card .stat-value {
      font-size: 2.1rem;
      font-weight: bold;
      color: #fff;
      margin-bottom: 8px;
    }

    .stat-card .stat-chart {
      width: 100%;
      height: 38px;
      margin-top: 8px;
    }

    .dashboard-main,
    .dashboard-card {
      background: #fff;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .dashboard-section {
      margin: 30px 0;
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 25px;
      background: transparent;
      box-shadow: none;
    }

    .activity-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .activity-list li {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      margin-bottom: 15px;
      padding: 10px;
      padding-bottom: 15px;
      border-bottom: 1px solid #f0f0f0;
      background: rgba(0, 0, 0, 0.01);
      border-radius: 8px;
    }

    .activity-list li:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }

    .activity-icon {
      width: 36px;
      height: 36px;
      background-color: rgba(206, 18, 18, 0.15);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ce1212;
      flex-shrink: 0;
    }

    .activity-icon i {
      font-size: 18px;
    }

    .activity-content {
      flex-grow: 1;
    }

    .activity-message {
      font-weight: 500;
      margin-bottom: 4px;
      color: #333;
    }

    .activity-date {
      font-size: 0.85rem;
      color: #888;
    }

    .todo-list {
      padding-left: 5px;
      list-style: none;
    }

    .todo-list li {
      position: relative;
      padding-left: 25px;
      margin-bottom: 12px;
    }

    .todo-list li:before {
      content: "⬜";
      position: absolute;
      left: 0;
      color: #ce1212;
    }

    .section-title {
      font-size: 18px;
      color: #333;
      margin-bottom: 20px;
      font-weight: 600;
    }

    @media (max-width: 900px) {
      .dashboard-section {
        grid-template-columns: 1fr;
      }

      .stats {
        gap: 15px;
      }

      .stat-card {
        min-width: calc(50% - 15px);
        flex-basis: calc(50% - 15px);
        margin-bottom: 0;
      }
    }

    @media (max-width: 768px) {
      .stat-card {
        padding: 15px;
      }

      .stat-card .stat-title {
        font-size: 0.9rem;
      }

      .stat-card .stat-value {
        font-size: 1.7rem;
      }

      .stat-card .stat-chart {
        height: 30px;
      }

      .dashboard-main,
      .dashboard-card {
        padding: 18px;
      }

      .activity-list li {
        margin-bottom: 12px;
        padding-bottom: 12px;
      }

      .section-title {
        font-size: 16px;
        margin-bottom: 15px;
      }

      .activity-icon {
        width: 32px;
        height: 32px;
      }

      .activity-icon i {
        font-size: 15px;
      }
    }

    @media (max-width: 480px) {
      .stats {
        margin: 20px 0;
      }

      .stat-card {
        min-width: 100%;
        flex-basis: 100%;
        margin-bottom: 10px;
      }

      .dashboard-section {
        gap: 15px;
        margin: 20px 0;
      }

      canvas#liveTrafficChart {
        height: 150px !important;
      }

      .dashboard-card {
        margin-bottom: 15px;
      }
    }
  </style>
</head>

<body>
  <?php include 'header_template.php'; ?>

  <!-- Contenu spécifique de la page -->
  <div class="content-wrapper">
    <div style="background: linear-gradient(135deg, #ce1212, #951010); border-radius: 10px; padding: 25px; margin-bottom: 30px; color: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
        <div>
          <h2 style="color: white; font-size: 24px; margin: 0; position: relative; display: flex; align-items: center;">
            <i class="bi bi-speedometer2" style="margin-right: 12px; font-size: 28px;"></i>
            Tableau de bord
          </h2>
          <p style="margin-top: 8px; color: rgba(255,255,255,0.9); margin-bottom: 0; font-size: 15px;">
            Bienvenue sur le tableau de bord d'administration
          </p>
        </div>
        <div style="background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 8px; text-align: center; margin-top: 10px;">
          <i class="bi bi-calendar3" style="margin-right: 5px;"></i>
          <?= date('d/m/Y') ?>
        </div>
      </div>
    </div>

    <div class="stats">
      <div class="stat-card" style="background: linear-gradient(135deg, #2E93fA, #1E6DD8); position: relative; overflow: hidden;">
        <div style="position: absolute; right: 15px; top: 15px; opacity: 0.2; font-size: 3rem;">
          <i class="bi bi-people"></i>
        </div>
        <div class="stat-title">Clients inscrits</div>
        <div class="stat-value"><?php
                                $sql = "SELECT COUNT(*) FROM Clients";
                                $stmt = $conn->query($sql);
                                echo $stmt ? $stmt->fetchColumn() : 0;
                                ?></div>
        <div style="font-size: 0.9rem; margin-top: 5px;">
          <i class="bi bi-arrow-up"></i> Base clients
        </div>
      </div>
      <div class="stat-card" style="background: linear-gradient(135deg, #00C292, #00A574); position: relative; overflow: hidden;">
        <div style="position: absolute; right: 15px; top: 15px; opacity: 0.2; font-size: 3rem;">
          <i class="bi bi-calendar-check"></i>
        </div>
        <div class="stat-title">Réservations à venir</div>
        <div class="stat-value"><?php
                                $now = date('Y-m-d H:i:s');
                                $sql = "SELECT COUNT(*) FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute([$now]);
                                echo $stmt ? $stmt->fetchColumn() : 0;
                                ?></div>
        <div style="font-size: 0.9rem; margin-top: 5px;">
          <i class="bi bi-clock"></i> À venir
        </div>
      </div>
      <div class="stat-card" style="background: linear-gradient(135deg, #F3632B, #E5472C); position: relative; overflow: hidden;">
        <div style="position: absolute; right: 15px; top: 15px; opacity: 0.2; font-size: 3rem;">
          <i class="bi bi-basket"></i>
        </div>
        <div class="stat-title">Commandes totales</div>
        <div class="stat-value"><?php
                                $sql = "SELECT COUNT(*) FROM Commandes";
                                $stmt = $conn->query($sql);
                                echo $stmt ? $stmt->fetchColumn() : 0;
                                ?></div>
        <div style="font-size: 0.9rem; margin-top: 5px;">
          <i class="bi bi-graph-up"></i> Historique
        </div>
      </div>
      <div class="stat-card" style="background: linear-gradient(135deg, #9675CE, #7C5ABD); position: relative; overflow: hidden;">
        <div style="position: absolute; right: 15px; top: 15px; opacity: 0.2; font-size: 3rem;">
          <i class="bi bi-currency-euro"></i>
        </div>
        <div class="stat-title">Revenus totaux</div>
        <div class="stat-value"><?php
                                $sql = "SELECT COALESCE(SUM(Montant),0) FROM Paiements";
                                $stmt = $conn->query($sql);
                                $revenus = $stmt ? $stmt->fetchColumn() : 0;
                                echo number_format($revenus, 2, ',', ' ');
                                ?> €</div>
        <div style="font-size: 0.9rem; margin-top: 5px;">
          <i class="bi bi-cash-stack"></i> Chiffre d'affaires
        </div>
      </div>
    </div>
    <div class="dashboard-section">
      <div class="dashboard-main">
        <h3 class="section-title" style="display: flex; align-items: center; justify-content: space-between;">
          <span>Trafic direct</span>
          <span style="font-size: 0.8rem; background: #f0f0f0; padding: 3px 10px; border-radius: 20px; color: #666;">
            <i class="bi bi-clock"></i> Mise à jour en direct
          </span>
        </h3>
        <div style="width:100%;position:relative;height:200px;background:#f6f8fb;border-radius:10px;padding:10px;box-shadow: inset 0 0 8px rgba(0,0,0,0.05);">
          <canvas id="liveTrafficChart" style="width:100%;height:100%;"></canvas>
        </div>
        <script>
          // Simulation de trafic en direct (exemple, à remplacer par une vraie source si besoin)
          const ctx = document.getElementById('liveTrafficChart').getContext('2d');
          let trafficData = [12, 19, 8, 15, 22, 17, 25];

          function calculateScale() {
            // Adapter aux dimensions de l'écran
            const canvas = ctx.canvas;
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            return {
              xPadding: Math.max(15, canvas.width * 0.05),
              yScale: canvas.height / 30,
              xInterval: (canvas.width - 2 * Math.max(15, canvas.width * 0.05)) / (trafficData.length - 1)
            };
          }

          function drawTraffic(data) {
            const {
              xPadding,
              yScale,
              xInterval
            } = calculateScale();
            const height = ctx.canvas.height;

            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

            // Dessiner le graphique
            ctx.beginPath();
            ctx.moveTo(xPadding, height - data[0] * yScale);
            for (let i = 1; i < data.length; i++) {
              ctx.lineTo(xPadding + i * xInterval, height - data[i] * yScale);
            }
            ctx.strokeStyle = '#2342a4';
            ctx.lineWidth = 3;
            ctx.stroke();

            // Points
            ctx.fillStyle = '#2342a4';
            for (let i = 0; i < data.length; i++) {
              ctx.beginPath();
              ctx.arc(xPadding + i * xInterval, height - data[i] * yScale, 6, 0, 2 * Math.PI);
              ctx.fill();
            }
          }

          // Fonction pour s'adapter aux changements de taille
          function handleResize() {
            drawTraffic(trafficData);
          }

          // Écouter les changements de taille d'écran
          window.addEventListener('resize', handleResize);

          drawTraffic(trafficData);

          // Mise à jour automatique toutes les 3 secondes (simulation)
          setInterval(() => {
            trafficData.push(Math.floor(Math.random() * 20) + 8);
            if (trafficData.length > 7) trafficData.shift();
            drawTraffic(trafficData);
          }, 3000);
        </script>
        <div style="margin-top:24px;" class="stats-summary">
          <h4 style="font-size:0.95rem;color:#666;margin-bottom:15px;border-bottom:1px solid #eee;padding-bottom:8px;">RÉSUMÉ DES PERFORMANCES</h4>
          <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:120px;display:flex;align-items:center;margin-bottom:15px;background:#f9f9f9;padding:15px;border-radius:10px;">
              <span style="display:inline-block;width:45px;height:45px;background:#2E93fA;border-radius:50%;text-align:center;line-height:45px;margin-right:12px;flex-shrink:0;color:#fff;"><i class="bi bi-bag"></i></span>
              <div>
                <b style="font-size:1.2rem;display:block;">9 600</b>
                <span style="color:#666;font-size:0.9rem;">Ventes totales</span>
              </div>
            </div>
            <div style="flex:1;min-width:120px;display:flex;align-items:center;margin-bottom:15px;background:#f9f9f9;padding:15px;border-radius:10px;">
              <span style="display:inline-block;width:45px;height:45px;background:#F3632B;border-radius:50%;text-align:center;line-height:45px;margin-right:12px;flex-shrink:0;color:#fff;"><i class="bi bi-cart"></i></span>
              <div>
                <b style="font-size:1.2rem;display:block;">6 900</b>
                <span style="color:#666;font-size:0.9rem;">Commandes totales</span>
              </div>
            </div>
            <div style="flex:1;min-width:120px;display:flex;align-items:center;margin-bottom:15px;background:#f9f9f9;padding:15px;border-radius:10px;">
              <span style="display:inline-block;width:45px;height:45px;background:#9675CE;border-radius:50%;text-align:center;line-height:45px;margin-right:12px;flex-shrink:0;color:#fff;"><i class="bi bi-currency-euro"></i></span>
              <div>
                <b style="font-size:1.2rem;display:block;">3 800 €</b>
                <span style="color:#666;font-size:0.9rem;">Revenus totaux</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-side">
        <div class="dashboard-card" style="border-left: 4px solid #00C292; border-radius: 10px 10px 10px 10px;">
          <h3 class="section-title" style="display: flex; align-items: center;">
            <i class="bi bi-activity" style="margin-right: 8px; color: #00C292;"></i>
            Activité récente
          </h3>
          <ul class="activity-list">
            <?php
            // Récupérer les dernières activités
            $activities = [];

            // Dernières réservations
            $sql = "SELECT r.ReservationID, r.nom_client, r.DateReservation, 'reservation' AS type 
                    FROM Reservations r 
                    ORDER BY r.ReservationID DESC LIMIT 3";
            $stmt = $conn->query($sql);
            if ($stmt) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activities[] = [
                  'type' => 'reservation',
                  'date' => $row['DateReservation'],
                  'message' => 'Nouvelle réservation par ' . htmlspecialchars($row['nom_client'])
                ];
              }
            }

            // Dernières commandes
            $sql = "SELECT c.CommandeID, r.DateReservation, m.NomItem 
                    FROM Commandes c 
                    JOIN Menus m ON c.MenuID = m.MenuID 
                    JOIN Reservations r ON c.ReservationID = r.ReservationID
                    ORDER BY c.CommandeID DESC LIMIT 3";
            $stmt = $conn->query($sql);
            if ($stmt) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activities[] = [
                  'type' => 'commande',
                  'date' => $row['DateReservation'] ?? date('Y-m-d H:i:s'),
                  'message' => 'Commande de ' . htmlspecialchars($row['NomItem'])
                ];
              }
            }

            // Trier par date
            usort($activities, function ($a, $b) {
              return strtotime($b['date']) - strtotime($a['date']);
            });

            // Afficher les activités
            if (empty($activities)) {
              echo '<li>Aucune activité récente</li>';
            } else {
              foreach (array_slice($activities, 0, 5) as $activity) {
                $icon = $activity['type'] == 'reservation' ? 'calendar-check' : 'basket';
                echo '<li>
                      <span class="activity-icon"><i class="bi bi-' . $icon . '"></i></span>
                      <div class="activity-content">
                        <div class="activity-message">' . $activity['message'] . '</div>
                        <div class="activity-date">' . date('d/m/Y H:i', strtotime($activity['date'])) . '</div>
                      </div>
                    </li>';
              }
            }
            ?>
          </ul>
        </div>
        <div class="dashboard-card" style="border-left: 4px solid #F3632B; border-radius: 10px 10px 10px 10px;">
          <h3 class="section-title" style="display: flex; align-items: center;">
            <i class="bi bi-check2-square" style="margin-right: 8px; color: #F3632B;"></i>
            À faire
          </h3>
          <ul class="todo-list" style="margin-top: 15px;">
            <li style="padding: 10px 10px 10px 30px; background: #f9f9f9; border-radius: 8px; margin-bottom: 10px;">Vérifier les réservations du jour</li>
            <li style="padding: 10px 10px 10px 30px; background: #f9f9f9; border-radius: 8px; margin-bottom: 10px;">Mettre à jour le menu hebdomadaire</li>
            <li style="padding: 10px 10px 10px 30px; background: #f9f9f9; border-radius: 8px; margin-bottom: 10px;">Contacter les fournisseurs</li>
          </ul>
        </div>
      </div>
    </div>
  </div> <!-- Fermeture du content-wrapper -->

  <?php include 'footer_template.php'; ?>
</body>

</html>