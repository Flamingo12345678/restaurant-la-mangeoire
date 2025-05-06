/**
 * Script pour la gestion interactive des réservations
 * Restaurant La Mangeoire
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la sélection des tables
    const tableCards = document.querySelectorAll('.table-card:not(.occupied)');
    const tableCheckboxes = document.querySelectorAll('.table-checkbox');
    
    tableCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur la checkbox directement
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                updateTableCardStatus(this, checkbox.checked);
            }
        });
    });
    
    tableCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.table-card');
            updateTableCardStatus(card, this.checked);
        });
    });
    
    function updateTableCardStatus(card, isSelected) {
        if (isSelected) {
            card.classList.add('selected');
            
            // Animation de sélection
            const checkmark = document.createElement('div');
            checkmark.className = 'table-selected-mark';
            checkmark.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            
            // Supprimer les anciens checkmarks s'ils existent
            const existingMark = card.querySelector('.table-selected-mark');
            if (existingMark) {
                existingMark.remove();
            }
            
            card.appendChild(checkmark);
            
            // Effet de pulsation
            card.style.transform = 'scale(1.03)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, 200);
        } else {
            card.classList.remove('selected');
            
            // Supprimer le checkmark
            const checkmark = card.querySelector('.table-selected-mark');
            if (checkmark) {
                checkmark.style.opacity = '0';
                setTimeout(() => {
                    checkmark.remove();
                }, 300);
            }
        }
    }
    
    // Initialisation des états des cartes de table
    tableCheckboxes.forEach(checkbox => {
        const card = checkbox.closest('.table-card');
        updateTableCardStatus(card, checkbox.checked);
    });
    
    // Fonction pour afficher un message d'erreur ou de succès
    function showNotification(message, type = 'error') {
        // Supprimer les notifications existantes
        const existingNotifs = document.querySelectorAll('.form-notification');
        existingNotifs.forEach(notif => notif.remove());
        
        // Créer une notification
        const notification = document.createElement('div');
        notification.className = `form-notification ${type === 'error' ? 'error' : 'success'}`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.padding = '15px 20px';
        notification.style.borderRadius = '10px';
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '350px';
        notification.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
        notification.style.transform = 'translateX(400px)';
        notification.style.transition = 'transform 0.3s ease';
        
        if (type === 'error') {
            notification.style.background = 'rgba(239, 68, 68, 0.95)';
            notification.style.color = 'white';
            notification.innerHTML = `<i class="bi bi-exclamation-circle" style="margin-right: 8px;"></i> ${message}`;
        } else {
            notification.style.background = 'rgba(59, 183, 126, 0.95)';
            notification.style.color = 'white';
            notification.innerHTML = `<i class="bi bi-check-circle" style="margin-right: 8px;"></i> ${message}`;
        }
        
        document.body.appendChild(notification);
        
        // Afficher avec animation
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Cacher après 5 secondes
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
    
    // Validation du formulaire
    const reservationForm = document.querySelector('.reservation-form');
    if (reservationForm) {
        // Valider les inputs en temps réel
        const inputs = reservationForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('input-error');
                const errorMsg = this.parentNode.querySelector('.input-error-msg');
                if (errorMsg) errorMsg.remove();
            });
        });
        
        reservationForm.addEventListener('submit', function(e) {
            let formValid = true;
            
            // Valider les champs requis
            const requiredInputs = reservationForm.querySelectorAll('input[required], select[required]');
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    e.preventDefault();
                    formValid = false;
                    
                    // Ajouter un effet d'erreur
                    input.classList.add('input-error');
                    
                    // Ajouter un message d'erreur sous le champ
                    const existingError = input.parentNode.querySelector('.input-error-msg');
                    if (!existingError) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'input-error-msg';
                        errorMsg.style.color = '#dc2626';
                        errorMsg.style.fontSize = '0.8rem';
                        errorMsg.style.marginTop = '5px';
                        errorMsg.innerHTML = `<i class="bi bi-exclamation-circle"></i> Ce champ est obligatoire`;
                        input.parentNode.appendChild(errorMsg);
                    }
                }
            });
            
            // Valider les tables sélectionnées
            const nbPersonnes = parseInt(document.querySelector('#nb_personnes').value) || 0;
            const tableCheckboxes = document.querySelectorAll('.table-checkbox:checked');
            let capaciteSelectionnee = 0;
            let tablesSelectionnees = [];
            
            tableCheckboxes.forEach(checkbox => {
                const capacite = parseInt(checkbox.dataset.capacite) || 0;
                capaciteSelectionnee += capacite;
                tablesSelectionnees.push(checkbox.value);
            });
            
            if (!formValid) {
                showNotification('Veuillez remplir tous les champs obligatoires.', 'error');
                return false;
            }
            
            if (tableCheckboxes.length === 0) {
                e.preventDefault();
                showNotification('Veuillez sélectionner au moins une table pour la réservation.', 'error');
                return false;
            }
            
            if (capaciteSelectionnee < nbPersonnes) {
                e.preventDefault();
                showNotification(`La capacité des tables sélectionnées (${capaciteSelectionnee} places) est insuffisante pour ${nbPersonnes} personnes.`, 'error');
                return false;
            }
            
            // Tout est valide
            return true;
        });
    }
    
    // Mise à jour dynamique des tables disponibles en fonction du nombre de personnes
    const nbPersonnesInput = document.querySelector('#nb_personnes');
    if (nbPersonnesInput) {
        // Message d'aide dynamique
        const helpMessage = document.createElement('div');
        helpMessage.className = 'table-help-message';
        helpMessage.style.marginTop = '10px';
        helpMessage.style.fontSize = '0.9rem';
        helpMessage.style.color = '#555';
        helpMessage.style.display = 'none';
        nbPersonnesInput.parentNode.appendChild(helpMessage);
        
        nbPersonnesInput.addEventListener('input', function() {
            const nbPersonnes = parseInt(this.value) || 0;
            const tableCards = document.querySelectorAll('.table-card:not(.occupied)');
            let tablesRecommendees = 0;
            let capaciteTotale = 0;
            
            // Enlever les recommandations précédentes
            document.querySelectorAll('.table-card').forEach(c => {
                c.classList.remove('recommended');
                c.classList.remove('too-small');
                
                // Supprimer les badges de recommandation
                const badges = c.querySelectorAll('.recommendation-badge');
                badges.forEach(badge => badge.remove());
            });
            
            // Recalculer les recommandations
            tableCards.forEach(card => {
                const capacite = parseInt(card.dataset.capacite) || 0;
                capaciteTotale += capacite;
                
                // Mettre en évidence les tables qui peuvent accueillir le nombre de personnes
                if (capacite >= nbPersonnes) {
                    card.classList.add('recommended');
                    
                    // Ajouter un badge de recommandation
                    const badge = document.createElement('div');
                    badge.className = 'recommendation-badge';
                    badge.innerHTML = '<i class="bi bi-stars"></i> Idéale';
                    card.appendChild(badge);
                    
                    tablesRecommendees++;
                } else if (nbPersonnes > capacite) {
                    card.classList.add('too-small');
                }
            });
            
            // Mettre à jour le message d'aide
            if (nbPersonnes > 0) {
                helpMessage.style.display = 'block';
                
                if (tablesRecommendees === 0) {
                    if (capaciteTotale >= nbPersonnes) {
                        helpMessage.innerHTML = `<i class="bi bi-info-circle"></i> Vous devrez sélectionner plusieurs tables pour accueillir ${nbPersonnes} personnes.`;
                        helpMessage.style.color = '#0070da';
                    } else {
                        helpMessage.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Il n'y a pas assez de places disponibles pour ${nbPersonnes} personnes.`;
                        helpMessage.style.color = '#b92e2e';
                    }
                } else {
                    helpMessage.innerHTML = `<i class="bi bi-check-circle"></i> ${tablesRecommendees} table(s) peuvent accueillir ${nbPersonnes} personnes.`;
                    helpMessage.style.color = '#2ca26e';
                }
            } else {
                helpMessage.style.display = 'none';
            }
        });
        
        // Déclencher l'événement une première fois pour initialiser
        const event = new Event('input');
        nbPersonnesInput.dispatchEvent(event);
    }
    
    // Animation pour les messages
    const messages = document.querySelectorAll('.success-message, .error-message');
    messages.forEach(msg => {
        // Ajouter un effet de fondu en entrée
        msg.style.opacity = '0';
        msg.style.transition = 'opacity 0.5s ease';
        
        setTimeout(() => {
            msg.style.opacity = '1';
        }, 100);
        
        // Ajouter un bouton de fermeture
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.marginLeft = 'auto';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontSize = '1.2rem';
        closeBtn.style.fontWeight = 'bold';
        closeBtn.addEventListener('click', () => {
            msg.style.opacity = '0';
            setTimeout(() => {
                msg.remove();
            }, 500);
        });
        
        msg.appendChild(closeBtn);
        
        // Auto-fermeture après 8 secondes
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => {
                if (msg.parentNode) {
                    msg.remove();
                }
            }, 500);
        }, 8000);
    });
    
    // Format statut avec des indicateurs visuels
    const statusCells = document.querySelectorAll('td:nth-child(6)');
    statusCells.forEach(cell => {
        const status = cell.textContent.trim();
        
        if (status === 'Réservée') {
            cell.innerHTML = `<span class="status-indicator status-reserved">Réservée</span>`;
        } else if (status === 'Confirmée') {
            cell.innerHTML = `<span class="status-indicator status-confirmed">Confirmée</span>`;
        } else if (status === 'Annulée') {
            cell.innerHTML = `<span class="status-indicator status-cancelled">Annulée</span>`;
        } else if (status === 'Terminée') {
            cell.innerHTML = `<span class="status-indicator status-completed">Terminée</span>`;
        }
    });
});
