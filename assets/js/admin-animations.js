/**
 * Animations pour l'interface d'administration
 * Restaurant La Mangeoire
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestion du menu burger et de la sidebar
    const burgerBtn = document.getElementById('admin-burger-btn');
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');
    
    if (burgerBtn && sidebar) {
        // Fonction pour ouvrir/fermer la sidebar
        function toggleSidebar() {
            const wasOpen = sidebar.classList.contains('open');
            sidebar.classList.toggle('open');
            
            if (!wasOpen) {
                // La sidebar est maintenant ouverte
                
                // Faire disparaître le bouton burger avec une transition fluide
                burgerBtn.classList.add('active');
                burgerBtn.style.opacity = '0';
                burgerBtn.style.visibility = 'hidden';
                burgerBtn.style.transform = 'translateX(-20px)';
                
                if (overlay) {
                    overlay.style.display = 'block';
                    setTimeout(() => {
                        overlay.style.opacity = '1';
                    }, 10);
                }
                
                // Bloquer le défilement du corps de la page
                document.body.style.overflow = 'hidden';
            } else {
                // La sidebar est maintenant fermée
                
                // Faire réapparaître le bouton burger
                setTimeout(() => {
                    burgerBtn.classList.remove('active');
                    burgerBtn.style.opacity = '1';
                    burgerBtn.style.visibility = 'visible';
                    burgerBtn.style.transform = 'translateX(0)';
                }, 150); // Petit délai pour que la transition soit plus naturelle
                
                if (overlay) {
                    overlay.style.opacity = '0';
                    setTimeout(() => {
                        overlay.style.display = 'none';
                    }, 300);
                }
                
                // Réactiver le défilement
                document.body.style.overflow = '';
            }
        }
        
        // Gestionnaire d'événement pour le bouton burger
        burgerBtn.addEventListener('click', toggleSidebar);
        
        // Clic sur l'overlay ferme la sidebar
        if (overlay) {
            overlay.addEventListener('click', function() {
                toggleSidebar();
            });
        }
        
        // Fermeture avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                toggleSidebar();
            }
        });
        
        // Gestion des événements tactiles pour fermer la sidebar
        let touchStartX = 0;
        let touchEndX = 0;
        
        sidebar.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        sidebar.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            
            // Si l'utilisateur glisse de gauche à droite, fermer la sidebar
            if (touchStartX < touchEndX && (touchEndX - touchStartX) > 70) {
                if (sidebar.classList.contains('open')) {
                    toggleSidebar();
                }
            }
        }, { passive: true });
    }
    
    // Animation d'apparition progressive pour les cards du tableau de bord
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 100));
    });

    // Animation d'apparition des tableaux
    const tables = document.querySelectorAll('.admin-table');
    tables.forEach(table => {
        table.style.opacity = '0';
        table.style.transform = 'translateY(20px)';
        table.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            table.style.opacity = '1';
            table.style.transform = 'translateY(0)';
        }, 300);
    });

    // Animation d'apparition des formulaires
    const forms = document.querySelectorAll('.form-section');
    forms.forEach(form => {
        form.style.opacity = '0';
        form.style.transform = 'translateY(20px)';
        form.style.transition = 'opacity 0.7s ease, transform 0.7s ease';
        
        setTimeout(() => {
            form.style.opacity = '1';
            form.style.transform = 'translateY(0)';
        }, 200);
    });

    // Effet de survol pour les boutons
    const buttons = document.querySelectorAll('button[type="submit"], .main-button');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 8px 20px rgba(206, 18, 18, 0.3)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Animation des lignes de tableau au survol
    const tableRows = document.querySelectorAll('.admin-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transition = 'background-color 0.3s ease';
            this.style.backgroundColor = 'rgba(206, 18, 18, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Effet d'onde pour les clics (effet ripple)
    document.querySelectorAll('button, .table-card:not(.occupied)').forEach(element => {
        element.addEventListener('click', function(e) {
            const rect = element.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const circle = document.createElement('span');
            circle.style.position = 'absolute';
            circle.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
            circle.style.borderRadius = '50%';
            circle.style.width = '0';
            circle.style.height = '0';
            circle.style.top = y + 'px';
            circle.style.left = x + 'px';
            circle.style.transform = 'translate(-50%, -50%)';
            circle.style.animation = 'ripple 0.6s linear';
            
            if (!element.style.position || element.style.position === 'static') {
                element.style.position = 'relative';
            }
            element.style.overflow = 'hidden';
            
            element.appendChild(circle);
            
            setTimeout(() => {
                circle.remove();
            }, 600);
        });
    });

    // Ajout de l'animation CSS pour l'effet ripple
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes ripple {
            0% {
                width: 0;
                height: 0;
                opacity: 0.5;
            }
            100% {
                width: 500px;
                height: 500px;
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
});
