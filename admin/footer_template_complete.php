        </div>
    </main>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // Inclure les scripts JavaScript communs
    if (isset($common_scripts) && is_array($common_scripts)) {
        foreach ($common_scripts as $script_file) {
            if (file_exists($script_file)) {
                echo '<script src="' . $script_file . '"></script>' . "\n    ";
            }
        }
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
                    
                    // Optimiser les formulaires
                    const inputs = document.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.style.fontSize = '16px';
                    });
                    
                } else {
                    document.body.classList.remove('mobile-view');
                }
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
            
            // Exécuter au chargement et redimensionnement
            optimizeForMobile();
            window.addEventListener('resize', optimizeForMobile);
            
            // Optimisation du scrolling tactile
            if ('ontouchstart' in window) {
                document.body.style.webkitOverflowScrolling = 'touch';
            }
        });
        
        // Gestion du burger menu améliorée
        document.addEventListener('DOMContentLoaded', function() {
            const burgerBtn = document.getElementById('admin-burger-btn');
            const sidebar = document.getElementById('admin-sidebar');
            
            if (burgerBtn && sidebar) {
                burgerBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
                
                // Fermer la sidebar en cliquant à l'extérieur sur mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 991 && !sidebar.contains(e.target) && !burgerBtn.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                });
            }
        });
    </script>
</body>
</html>
