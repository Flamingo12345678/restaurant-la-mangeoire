<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: login.php');
  exit;
}
require_once '../db_connexion.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Admin - Tableau de bord</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Segoe UI', Arial, sans-serif;
    }

    .sidebar {
      background: #1a237e;
      color: #fff;
      width: 240px;
      min-height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      z-index: 10;
      transition: transform 0.3s ease;
      transform: translateX(0);
    }

    .sidebar.open {
      transform: translateX(0);
    }

    .sidebar .logo {
      font-size: 2rem;
      font-weight: bold;
      padding: 32px 0 24px 0;
      text-align: center;
      letter-spacing: 2px;
      color: #fff;
    }

    .sidebar nav ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar nav ul li {
      margin: 0;
    }

    .sidebar nav ul li a {
      display: flex;
      align-items: center;
      color: #fff;
      text-decoration: none;
      padding: 16px 32px;
      font-size: 1.1rem;
      transition: background 0.2s;
      border-left: 4px solid transparent;
    }

    .sidebar nav ul li a.active,
    .sidebar nav ul li a:hover {
      background: #283593;
      border-left: 4px solid #42a5f5;
      color: #42a5f5;
    }

    .sidebar nav ul li a i {
      margin-right: 12px;
      font-size: 1.3rem;
    }

    .main-content {
      margin-left: 240px;
      padding: 0;
      min-height: 100vh;
      background: #f6f8fb;
    }

    /* Correction du débordement du fond blanc derrière les cards stats */
    .stats {
      display: flex;
      gap: 32px;
      margin: 32px 48px 0 48px;
      background: transparent !important;
      box-shadow: none !important;
      padding: 0 !important;
    }

    .stat-card {
      flex: 1;
      background: #5a4fcf;
      border-radius: 18px;
      box-shadow: 0 4px 24px 0 #2d217a1a;
      padding: 28px 24px 18px 24px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      min-width: 180px;
      color: #fff;
      position: relative;
      overflow: hidden;
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
      background: none;
      border-radius: 0 0 12px 12px;
      border-bottom: none;
    }

    .dashboard-section {
      margin: 32px 48px;
      display: flex;
      gap: 32px;
    }

    .dashboard-main {
      flex: 2;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px 0 #2342a41a;
      padding: 32px 28px 24px 28px;
      min-width: 0;
    }

    .dashboard-side {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 24px;
      min-width: 260px;
    }

    .dashboard-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px 0 #2342a41a;
      padding: 24px 20px;
    }

    .activity-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .activity-list li {
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .activity-list .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #eee;
      object-fit: cover;
      border: 2px solid #fff;
      box-shadow: 0 2px 8px #0001;
    }

    .pie-chart {
      width: 60px;
      height: 60px;
      display: inline-block;
      margin-right: 12px;
    }

    @media (max-width: 900px) {

      .stats,
      .dashboard-section {
        flex-direction: column;
      }

      .main-content {
        margin-left: 0;
      }

      .sidebar {
        position: relative;
        width: 100%;
        flex-direction: row;
        height: auto;
      }
    }

    .btn-retour-public {
      display: inline-flex;
      align-items: center;
      gap: 0.5em;
      background: linear-gradient(90deg, #7f53ac 0%, #647dee 100%);
      color: #fff !important;
      border: none;
      border-radius: 2em;
      padding: 0.6em 1.6em;
      font-size: 1.1rem;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(100, 125, 222, 0.15);
      transition: background 0.3s, box-shadow 0.3s, transform 0.2s;
      text-decoration: none;
    }

    .btn-retour-public:hover {
      background: linear-gradient(90deg, #647dee 0%, #7f53ac 100%);
      box-shadow: 0 4px 16px rgba(100, 125, 222, 0.25);
      color: #fff !important;
      transform: translateY(-2px) scale(1.04);
      text-decoration: none;
    }

    .btn-retour-public i {
      font-size: 1.2em;
      margin-right: 0.3em;
    }

    .menu-toggle {
      display: none;
      position: absolute;
      top: 20px;
      left: 20px;
      background: #1a237e;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 20;
      font-size: 1.5rem;
    }

    @media (max-width: 768px) {
      .menu-toggle {
        display: flex;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
        padding: 32px;
      }
    }
  </style>
</head>

<body>
  <!-- Bouton menu-toggle pour mobile (affiché via CSS @media) -->
  <button class="menu-toggle" aria-label="Ouvrir le menu" onclick="document.querySelector('.sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="sidebar">
    <div class="logo">La Mangeoire</div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="bi bi-house"></i> Tableau de bord</a></li>
        <li><a href="clients.php"><i class="bi bi-people"></i> Clients</a></li>
        <li><a href="commandes.php"><i class="bi bi-basket"></i> Commandes</a></li>
        <li><a href="menus.php"><i class="bi bi-book"></i> Menus</a></li>
        <li><a href="reservations.php"><i class="bi bi-calendar-check"></i> Réservations</a></li>
        <li><a href="tables.php"><i class="bi bi-table"></i> Tables</a></li>
        <li><a href="employes.php"><i class="bi bi-person-badge"></i> Employés</a></li>
        <li><a href="paiements.php"><i class="bi bi-credit-card"></i> Paiements</a></li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <div class="main-content" style="margin-left:240px; padding:32px;">
    <header class="header d-flex align-items-center sticky-top" style="background: #fff; border-bottom: 1px solid #eee;">
      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
          <h1 class="sitename">Admin</h1>
          <span>.</span>
        </a>
        <a href="../index.html" class="btn-retour-public">
          <i class="bi bi-arrow-left-circle"></i> Retour au site public
        </a>
      </div>
    </header>
    <div class="topbar">
      <div class="icons">
        <img src="../assets/img/favcon.jpeg" alt="Profil" style="width:50px;height:50px;border-radius:50%;background:#eee;">
      </div>
    </div>
    <div class="stats">
      <div class="stat-card">
        <div class="stat-title">Clients inscrits</div>
        <div class="stat-value"><?php
                                $sql = "SELECT COUNT(*) FROM Clients";
                                $stmt = $conn->query($sql);
                                echo $stmt ? $stmt->fetchColumn() : 0;
                                ?></div>
        <div class="stat-chart"></div>
      </div>
      <div class="stat-card">
        <div class="stat-title">Réservations à venir</div>
        <div class="stat-value"><?php
                                $now = date('Y-m-d H:i:s');
                                $sql = "SELECT COUNT(*) FROM Reservations WHERE Statut = 'Réservée' AND DateReservation >= ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute([$now]);
                                echo $stmt ? $stmt->fetchColumn() : 0;
                                ?></div>
        <div class="stat-chart"></div>
      </div>
      <div class="stat-card">
        <div class="stat-title">Commandes totales</div>
        <div class="stat-value"><?php
                                $sql = "SELECT COUNT(*) FROM Commandes";
                                $stmt = $conn->query($sql);
                                echo $stmt ? $stmt->fetchColumn() : 0;
                                ?></div>
        <div class="stat-chart"></div>
      </div>
      <div class="stat-card">
        <div class="stat-title">Revenus totaux (€)</div>
        <div class="stat-value"><?php
                                $sql = "SELECT COALESCE(SUM(Montant),0) FROM Paiements";
                                $stmt = $conn->query($sql);
                                $revenus = $stmt ? $stmt->fetchColumn() : 0;
                                echo number_format($revenus, 2, ',', ' ');
                                ?></div>
        <div class="stat-chart"></div>
      </div>
    </div>
    <div class="dashboard-section">
      <div class="dashboard-main">
        <h2>Trafic direct</h2>
        <canvas id="liveTrafficChart" width="100%" height="180" style="background:#f6f8fb;"></canvas>
        <script>
          // Simulation de trafic en direct (exemple, à remplacer par une vraie source si besoin)
          const ctx = document.getElementById('liveTrafficChart').getContext('2d');
          let trafficData = [12, 19, 8, 15, 22, 17, 25];

          function drawTraffic(data) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            ctx.beginPath();
            ctx.moveTo(30, 150 - data[0] * 5);
            for (let i = 1; i < data.length; i++) {
              ctx.lineTo(30 + i * 60, 150 - data[i] * 5);
            }
            ctx.strokeStyle = '#2342a4';
            ctx.lineWidth = 3;
            ctx.stroke();
            // Points
            ctx.fillStyle = '#2342a4';
            for (let i = 0; i < data.length; i++) {
              ctx.beginPath();
              ctx.arc(30 + i * 60, 150 - data[i] * 5, 6, 0, 2 * Math.PI);
              ctx.fill();
            }
          }
          drawTraffic(trafficData);
          // Mise à jour automatique toutes les 3 secondes (simulation)
          setInterval(() => {
            trafficData.push(Math.floor(Math.random() * 20) + 8);
            if (trafficData.length > 7) trafficData.shift();
            drawTraffic(trafficData);
          }, 3000);
        </script>
        <div style="display:flex;gap:32px;margin-top:24px;">
          <div><span class="pie-chart"><svg viewBox="0 0 32 32">
                <circle r="16" cx="16" cy="16" fill="#eee" />
                <path d="M16 16 L16 0 A16 16 0 1 1 2.8 25.6 Z" fill="#2342a4" />
              </svg></span> <b>9600</b><br><span style="color:#888">Ventes totales</span></div>
          <div><span class="pie-chart"><svg viewBox="0 0 32 32">
                <circle r="16" cx="16" cy="16" fill="#eee" />
                <path d="M16 16 L16 0 A16 16 0 1 1 16 32 Z" fill="#ff4d4f" />
              </svg></span> <b>6900</b><br><span style="color:#888">Commandes totales</span></div>
          <div><span class="pie-chart"><svg viewBox="0 0 32 32">
                <circle r="16" cx="16" cy="16" fill="#eee" />
                <path d="M16 16 L16 0 A16 16 0 1 1 8 30 Z" fill="#ffb300" />
              </svg></span> <b>3800</b><br><span style="color:#888">Revenus totaux</span></div>
        </div>
      </div>
      <div class="dashboard-side">
        <div class="dashboard-card">
          <h3>Activité</h3>
          <ul class="activity-list">
            <li><img src="/assets/img/chefs/chefs-1.jpg" class="avatar" alt=""> Sophie Michiels <span style="color:#888;font-size:0.9em;">il y a 3 jours</span></li>
            <li><img src="/assets/img/chefs/chefs-2.jpg" class="avatar" alt=""> Jean Dupont <span style="color:#888;font-size:0.9em;">il y a 5 jours</span></li>
            <li><img src="/assets/img/chefs/chefs-3.jpg" class="avatar" alt=""> Alice Martin <span style="color:#888;font-size:0.9em;">il y a 1 semaine</span></li>
          </ul>
        </div>
        <div class="dashboard-card">
          <h3>Meilleures ventes</h3>
          <ul style="padding-left:18px;">
            <li>Storite Portable Bag</li>
            <li>Menu Dégustation</li>
            <li>Formule Midi</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</body>
<script>
  // Fermer la sidebar quand on clique en dehors sur mobile
  window.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.menu-toggle');
    if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    }
  });
</script>