<?php

require_once 'check_admin_access.php';
// Ce fichier contient le footer commun et COMPLET pour toutes les pages admin
// Il gère automatiquement la fermeture de la structure HTML

// Protection contre l'inclusion directe
if (!defined('INCLUDED_IN_PAGE')) {
  die('Ce fichier ne peut pas être appelé directement.');
}

// Détecter le niveau de répertoire pour les chemins relatifs
$is_in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
$asset_path = $is_in_admin_folder ? '../assets/' : 'assets/';

// Vérifier si nous devons fermer la structure HTML complète
$need_html_structure = !headers_sent();
?>

        </div><!-- Fermeture container-fluid -->
    </main><!-- Fermeture admin-main-content -->

    <!-- Scripts JavaScript -->
    <?php
    // Définir les scripts JavaScript communs à toutes les pages admin
    $common_scripts = array(
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        $asset_path . 'js/main.js',
        $asset_path . 'js/admin-sidebar.js', 
        $asset_path . 'js/admin-animations.js',
        $asset_path . 'js/admin-modals.js',
        $asset_path . 'js/harmonize-admin-styles.js'
    );
    
    // Charger les scripts communs
    foreach ($common_scripts as $script_src) {
        echo '<script src="' . $script_src . '"></script>' . "\n    ";
    }
    
    // Inclure scripts JavaScript spécifiques à la page si définis
    if (isset($additional_js) && is_array($additional_js)) {
        foreach ($additional_js as $js_file) {
            echo '<script src="' . $js_file . '"></script>' . "\n    ";
        }
    }
    ?>
    
    <!-- Script d'optimisation mobile intégré -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Optimisations pour mobile
            function optimizeForMobile() {
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    document.body.classList.add('mobile-view');
                    
                    // Force les stats cards en ligne horizontale
                    const statsRows = document.querySelectorAll('.row.g-4, .stats-row');
                    statsRows.forEach(row => {
                        row.style.display = 'flex';
                        row.style.flexWrap = 'nowrap';
                        row.style.gap = window.innerWidth <= 480 ? '0.125rem' : '0.25rem';
                        
                        const cols = row.querySelectorAll('.col-md-3, .stats-card');
                        cols.forEach(col => {
                            col.style.flex = '1 1 25%';
                            col.style.minWidth = '0';
                            col.style.maxWidth = '25%';
                        });
                    });
                    
                    // Optimiser les formulaires pour éviter le zoom iOS
                    const inputs = document.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (input.style.fontSize !== '16px') {
                            input.style.fontSize = '16px';
                        }
                    });
                    
                } else {
                    document.body.classList.remove('mobile-view');
                }
            }
            
            // Gestion du burger menu améliorée
            const burgerBtn = document.getElementById('admin-burger-btn');
            const sidebar = document.getElementById('admin-sidebar');
            
            if (burgerBtn && sidebar) {
                burgerBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
                
                // Fermer la sidebar en cliquant à l'extérieur sur mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 991 && 
                        !sidebar.contains(e.target) && 
                        !burgerBtn.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                });
            }
            
            // Prévenir le zoom sur iOS lors du focus des inputs
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                        const viewport = document.querySelector('meta[name=viewport]');
                        if (viewport) {
                            viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                        }
                    }
                });
            });
            
            // Exécuter optimisations
            optimizeForMobile();
            window.addEventListener('resize', optimizeForMobile);
            
            // Optimisation du scrolling tactile
            if ('ontouchstart' in window) {
                document.body.style.webkitOverflowScrolling = 'touch';
            }
        });
    </script>

<?php if ($need_html_structure): ?>
</body>
</html>
<?php endif; ?>