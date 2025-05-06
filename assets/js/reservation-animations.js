/**
 * Animations avancées pour la page de réservations
 * Restaurant La Mangeoire
 */

document.addEventListener('DOMContentLoaded', function() {
    // Afficher les informations détaillées au survol des tables
    const tableCards = document.querySelectorAll('.table-card');
    tableCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // Éviter d'ajouter plusieurs tooltips
            if (this.querySelector('.table-tooltip')) return;
            
            // Récupérer les informations de la table
            const tableNum = this.querySelector('.table-title').textContent;
            const capacity = this.dataset.capacite || '0';
            const isOccupied = this.classList.contains('occupied');
            
            // Créer le tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'table-tooltip';
            tooltip.style.position = 'absolute';
            tooltip.style.bottom = '-70px';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translateX(-50%)';
            tooltip.style.backgroundColor = 'rgba(33, 33, 33, 0.9)';
            tooltip.style.color = '#fff';
            tooltip.style.padding = '8px 12px';
            tooltip.style.borderRadius = '6px';
            tooltip.style.fontSize = '0.8rem';
            tooltip.style.whiteSpace = 'nowrap';
            tooltip.style.zIndex = '100';
            tooltip.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.2)';
            tooltip.style.opacity = '0';
            tooltip.style.transition = 'opacity 0.3s ease';
            
            // Contenu du tooltip
            if (isOccupied) {
                tooltip.innerHTML = `<div><strong>${tableNum}</strong> - ${capacity} places</div><div>Table actuellement occupée</div>`;
                tooltip.style.backgroundColor = 'rgba(220, 38, 38, 0.9)';
            } else {
                tooltip.innerHTML = `<div><strong>${tableNum}</strong> - ${capacity} places</div><div>Table disponible</div>`;
            }
            
            // Ajouter le tooltip
            this.appendChild(tooltip);
            this.style.overflow = 'visible';
            
            // Afficher avec animation
            setTimeout(() => {
                tooltip.style.opacity = '1';
            }, 50);
        });
        
        card.addEventListener('mouseleave', function() {
            // Supprimer le tooltip
            const tooltip = this.querySelector('.table-tooltip');
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(() => {
                    tooltip.remove();
                    this.style.overflow = 'hidden';
                }, 300);
            }
        });
    });
    
    // Animation pour le tableau de réservations
    const reservationTable = document.querySelector('.admin-table');
    if (reservationTable) {
        const rows = reservationTable.querySelectorAll('tbody tr');
        
        rows.forEach((row, index) => {
            // Animation de défilement au survol
            row.style.transition = 'all 0.3s ease';
            
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(249, 250, 251, 0.9)';
                this.style.transform = 'translateX(5px) scale(1.01)';
                this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.05)';
                this.style.zIndex = '10';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = '';
                this.style.boxShadow = '';
                this.style.zIndex = '';
            });
        });
    }
    
    // Effet d'onde pour le clic des boutons
    const allButtons = document.querySelectorAll('button, .btn-retour-public');
    allButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            ripple.style.position = 'absolute';
            ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
            ripple.style.borderRadius = '50%';
            ripple.style.width = '0';
            ripple.style.height = '0';
            ripple.style.top = y + 'px';
            ripple.style.left = x + 'px';
            ripple.style.transform = 'translate(-50%, -50%)';
            ripple.style.animation = 'ripple 0.6s linear';
            
            // S'assurer que le bouton est positionné correctement
            if (getComputedStyle(button).position === 'static') {
                button.style.position = 'relative';
            }
            button.style.overflow = 'hidden';
            
            button.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 700);
        });
    });
    
    // Animation CSS pour l'effet d'onde
    if (!document.querySelector('style#ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = `
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
            
            .table-card.selected {
                position: relative;
                overflow: hidden;
            }
            
            .table-card.selected::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(
                    45deg,
                    rgba(59, 183, 126, 0) 0%,
                    rgba(59, 183, 126, 0.1) 50%,
                    rgba(59, 183, 126, 0) 100%
                );
                animation: shine 2s infinite;
                pointer-events: none;
            }
            
            @keyframes shine {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
        `;
        document.head.appendChild(style);
    }
});
