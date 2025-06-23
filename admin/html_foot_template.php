<?php
// Protection contre l'inclusion directe
if (!defined('INCLUDED_IN_PAGE')) {
  die('Ce fichier ne peut pas être appelé directement.');
}
?>
        </div>
    </main>
    
    <!-- Scripts JavaScript -->
    <?php 
    // Scripts JavaScript communs
    $js_files = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        $asset_path . 'js/main.js',
        $asset_path . 'js/admin-sidebar.js',
        $asset_path . 'js/admin-modals.js',
        $asset_path . 'js/harmonize-admin-styles.js'
    ];
    
    // Ajouter JS spécifiques selon la page
    if (isset($additional_js) && is_array($additional_js)) {
        $js_files = array_merge($js_files, $additional_js);
    }
    
    // Inclure tous les JS
    foreach ($js_files as $js_file) {
        echo '<script src="' . $js_file . '"></script>' . "\n    ";
    }
    ?>
    
    <!-- Script pour optimisation mobile -->
    <script>
        // Prévenir le zoom sur iOS quand on touche un input
        document.addEventListener('touchstart', function() {
            var meta = document.querySelector('meta[name="viewport"]');
            if (meta) {
                meta.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            }
        });
        
        // Optimisations responsive
        function optimizeForMobile() {
            // Ajuster la hauteur des éléments selon la taille d'écran
            if (window.innerWidth <= 768) {
                document.body.classList.add('mobile-view');
                
                // Réduire les marges et paddings sur mobile
                const cards = document.querySelectorAll('.card, .admin-card');
                cards.forEach(card => {
                    card.style.margin = '0.5rem 0';
                    card.style.padding = '0.5rem';
                });
                
                // Optimiser les tableaux
                const tables = document.querySelectorAll('.table');
                tables.forEach(table => {
                    table.style.fontSize = '0.8rem';
                });
                
                // Stats cards en ligne horizontale forcée
                const statsRows = document.querySelectorAll('.row.g-4, .stats-row');
                statsRows.forEach(row => {
                    row.style.display = 'flex';
                    row.style.flexWrap = 'nowrap';
                    row.style.gap = '0.25rem';
                    
                    const cols = row.querySelectorAll('.col-md-3, .stats-card');
                    cols.forEach(col => {
                        col.style.flex = '1 1 25%';
                        col.style.minWidth = '0';
                        col.style.maxWidth = '25%';
                    });
                });
            } else {
                document.body.classList.remove('mobile-view');
            }
        }
        
        // Exécuter au chargement et au redimensionnement
        window.addEventListener('load', optimizeForMobile);
        window.addEventListener('resize', optimizeForMobile);
        
        // Touch scrolling optimization
        if ('ontouchstart' in window) {
            document.body.style.webkitOverflowScrolling = 'touch';
        }
    </script>
</body>
</html>
