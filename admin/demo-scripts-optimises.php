<?php
require_once 'check_admin_access.php';
define('INCLUDED_IN_PAGE', true);
$page_title = "Exemple - Scripts Optimisés";

// CSS spécifiques à cette page de démonstration
$additional_css = array(
    'assets/css/demo-styles.css'  // CSS personnalisé pour cette page
);

// Scripts à charger dans le head (avant le body)
$head_js = array(
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js'  // Chart.js pour graphiques
);

// Scripts à charger en fin de page
$additional_js = array(
    'assets/js/demo-charts.js',     // Script personnalisé pour les graphiques
    'assets/js/demo-interactions.js' // Script pour interactions spécifiques
);

require_once 'header_template.php';
?>

<!-- Contenu de la page de démonstration -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3">📊 Démonstration - Système de Scripts Optimisé</h1>
            <p class="text-muted">Cette page démontre l'utilisation du système de scripts JavaScript optimisé.</p>
        </div>
    </div>

    <!-- Cartes de statistiques (utilisant les scripts communs) -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="display-4">6</h3>
                    <p class="mb-0">Scripts Communs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="display-4">2</h3>
                    <p class="mb-0">Scripts Spécifiques</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="display-4">1</h3>
                    <p class="mb-0">CSS Personnalisé</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="display-4">100%</h3>
                    <p class="mb-0">Optimisé</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Exemple de graphique (utilisant Chart.js chargé dans le head) -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📈 Graphique de Démonstration</h5>
                </div>
                <div class="card-body">
                    <canvas id="demoChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">⚙️ Fonctionnalités Testées</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Scripts communs automatiques
                            <span class="badge bg-success">✓</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Chart.js dans le head
                            <span class="badge bg-success">✓</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Scripts additionnels
                            <span class="badge bg-success">✓</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            CSS personnalisé
                            <span class="badge bg-success">✓</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Responsivité mobile
                            <span class="badge bg-success">✓</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons de test pour les interactions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">🎮 Tests d'Interaction</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary" onclick="testCommonScript()">
                            Tester Script Commun
                        </button>
                        <button class="btn btn-success" onclick="testSpecificScript()">
                            Tester Script Spécifique
                        </button>
                        <button class="btn btn-info" onclick="updateChart()">
                            Mettre à jour Graphique
                        </button>
                        <button class="btn btn-warning" onclick="testMobileOptimization()">
                            Test Mobile
                        </button>
                    </div>
                    <div id="test-results" class="mt-3 alert" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS inline pour la démonstration -->
<style>
    .stats-card {
        transition: transform 0.2s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    #test-results {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    #test-results.show {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .d-flex.gap-2 {
            flex-direction: column;
        }
        
        .d-flex.gap-2 .btn {
            margin-bottom: 0.5rem;
        }
    }
</style>

<!-- Script inline pour démonstration -->
<script>
// Ce script démontre l'interaction avec les scripts chargés
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Page de démonstration chargée');
    console.log('📊 Chart.js disponible:', typeof Chart !== 'undefined');
    console.log('🎨 Bootstrap disponible:', typeof bootstrap !== 'undefined');
    
    // Fonction pour afficher les résultats de test
    window.showTestResult = function(message, type = 'success') {
        const resultsDiv = document.getElementById('test-results');
        resultsDiv.className = `mt-3 alert alert-${type} show`;
        resultsDiv.style.display = 'block';
        resultsDiv.textContent = message;
        
        setTimeout(() => {
            resultsDiv.classList.remove('show');
            setTimeout(() => {
                resultsDiv.style.display = 'none';
            }, 300);
        }, 3000);
    };
    
    // Fonction de test pour script commun
    window.testCommonScript = function() {
        if (typeof bootstrap !== 'undefined') {
            showTestResult('✅ Scripts communs chargés avec succès! Bootstrap disponible.', 'success');
        } else {
            showTestResult('❌ Erreur: Scripts communs non chargés.', 'danger');
        }
    };
    
    // Fonction de test pour script spécifique
    window.testSpecificScript = function() {
        if (typeof Chart !== 'undefined') {
            showTestResult('✅ Script spécifique (Chart.js) chargé avec succès!', 'success');
        } else {
            showTestResult('❌ Erreur: Chart.js non disponible.', 'danger');
        }
    };
    
    // Test d'optimisation mobile
    window.testMobileOptimization = function() {
        const isMobile = window.innerWidth <= 768;
        const message = isMobile ? 
            '📱 Mode mobile détecté - Optimisations actives!' : 
            '🖥️ Mode desktop - Interface complète disponible!';
        showTestResult(message, 'info');
    };
    
    // Initialiser le graphique si Chart.js est disponible
    if (typeof Chart !== 'undefined') {
        const ctx = document.getElementById('demoChart');
        if (ctx) {
            window.demoChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Scripts Communs', 'Scripts Spécifiques', 'CSS Personnalisé'],
                    datasets: [{
                        data: [6, 2, 1],
                        backgroundColor: [
                            '#0d6efd',
                            '#198754', 
                            '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    // Fonction pour mettre à jour le graphique
    window.updateChart = function() {
        if (window.demoChart) {
            const newData = [
                Math.floor(Math.random() * 10) + 1,
                Math.floor(Math.random() * 5) + 1,
                Math.floor(Math.random() * 3) + 1
            ];
            window.demoChart.data.datasets[0].data = newData;
            window.demoChart.update();
            showTestResult('📊 Graphique mis à jour avec de nouvelles données!', 'info');
        } else {
            showTestResult('❌ Graphique non disponible.', 'warning');
        }
    };
});
</script>

<?php require_once 'footer_template.php'; ?>
