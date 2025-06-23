/**
 * CartUI - Interface JavaScript moderne pour le système de panier
 * 
 * Fonctionnalités:
 * - Ajout d'articles via AJAX
 * - Mise à jour temps réel du compteur panier
 * - Notifications visuelles
 * - Gestion des erreurs
 * - Interface responsive
 */

class CartUI {
    constructor() {
        this.apiUrl = 'api/cart.php';
        this.cartCounter = document.querySelector('.cart-counter, .badge-cart');
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateCartDisplay();
        this.loadCartSummary();
    }
    
    bindEvents() {
        // Boutons d'ajout au panier
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-add-to-cart, .add-to-cart')) {
                e.preventDefault();
                this.handleAddToCart(e.target);
            }
            
            if (e.target.matches('.quantity-btn')) {
                e.preventDefault();
                this.handleQuantityChange(e.target);
            }
            
            if (e.target.matches('.btn-remove-item')) {
                e.preventDefault();
                this.handleRemoveItem(e.target);
            }
        });
        
        // Formulaires d'ajout au panier
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.cart-form, .add-to-cart-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });
        
        // Changement de quantité dans les inputs
        document.addEventListener('change', (e) => {
            if (e.target.matches('.quantity-input')) {
                this.handleQuantityInputChange(e.target);
            }
        });
    }
    
    async handleAddToCart(button) {
        try {
            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout...';
            
            // Récupérer les données
            const menuId = button.dataset.menuId || button.getAttribute('data-menu-id');
            const quantity = button.dataset.quantity || 1;
            
            if (!menuId) {
                throw new Error('ID de menu manquant');
            }
            
            // Envoyer la requête
            const response = await this.apiRequest('add', {
                menu_id: menuId,
                quantity: quantity
            });
            
            if (response.success) {
                this.showNotification(response.message, 'success');
                this.updateCartDisplay(response.data.cart_summary);
                
                // Animation du bouton
                button.innerHTML = '<i class="fas fa-check"></i> Ajouté!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            } else {
                throw new Error(response.message);
            }
            
        } catch (error) {
            this.showNotification(error.message, 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    async handleFormSubmit(form) {
        try {
            const formData = new FormData(form);
            const menuId = formData.get('menu_id');
            const quantity = formData.get('quantity') || 1;
            
            if (!menuId) {
                throw new Error('Données de formulaire invalides');
            }
            
            const response = await this.apiRequest('add', {
                menu_id: menuId,
                quantity: quantity
            });
            
            if (response.success) {
                this.showNotification(response.message, 'success');
                this.updateCartDisplay(response.data.cart_summary);
            } else {
                throw new Error(response.message);
            }
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }
    
    async handleQuantityChange(button) {
        try {
            const input = button.parentElement.querySelector('.quantity-input');
            const change = parseInt(button.dataset.change || '0');
            const menuId = button.dataset.menuId || input.dataset.menuId;
            
            if (!input || !menuId) return;
            
            const currentValue = parseInt(input.value) || 0;
            const newValue = Math.max(1, currentValue + change);
            
            input.value = newValue;
            
            await this.updateItemQuantity(menuId, newValue);
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }
    
    async handleQuantityInputChange(input) {
        try {
            const menuId = input.dataset.menuId || input.getAttribute('data-menu-id');
            const quantity = Math.max(1, parseInt(input.value) || 1);
            
            if (!menuId) return;
            
            input.value = quantity; // Corriger la valeur si nécessaire
            
            await this.updateItemQuantity(menuId, quantity);
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }
    
    async handleRemoveItem(button) {
        try {
            const menuId = button.dataset.menuId || button.getAttribute('data-menu-id');
            
            if (!menuId) {
                throw new Error('ID de menu manquant');
            }
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                return;
            }
            
            const response = await this.apiRequest('remove', { menu_id: menuId });
            
            if (response.success) {
                this.showNotification(response.message, 'success');
                this.updateCartDisplay(response.data.cart_summary);
                
                // Supprimer visuellement l'élément
                const cartItem = button.closest('.cart-item');
                if (cartItem) {
                    cartItem.style.opacity = '0.5';
                    setTimeout(() => {
                        cartItem.remove();
                        
                        // Vérifier si le panier est vide
                        if (response.data.cart_summary.is_empty) {
                            location.reload(); // Recharger pour afficher le message "panier vide"
                        }
                    }, 300);
                }
            } else {
                throw new Error(response.message);
            }
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }
    
    async updateItemQuantity(menuId, quantity) {
        try {
            const response = await this.apiRequest('update', {
                menu_id: menuId,
                quantity: quantity
            });
            
            if (response.success) {
                this.updateCartDisplay(response.data.cart_summary);
                
                // Mettre à jour le prix de ligne si présent
                const itemRow = document.querySelector(`[data-menu-id="${menuId}"]`).closest('.cart-item');
                if (itemRow) {
                    const priceElement = itemRow.querySelector('.line-total');
                    if (priceElement) {
                        const unitPrice = parseFloat(itemRow.dataset.unitPrice || '0');
                        const lineTotal = (unitPrice * quantity).toFixed(2);
                        priceElement.textContent = lineTotal + ' €';
                    }
                }
            } else {
                throw new Error(response.message);
            }
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }
    
    async loadCartSummary() {
        try {
            const response = await this.apiRequest('summary');
            
            if (response.success) {
                this.updateCartDisplay(response.data.summary);
            }
            
        } catch (error) {
            console.warn('Impossible de charger le résumé du panier:', error.message);
        }
    }
    
    updateCartDisplay(summary) {
        if (!summary) return;
        
        // Mettre à jour les compteurs
        const counters = document.querySelectorAll('.cart-counter, .badge-cart, .cart-items-count');
        counters.forEach(counter => {
            counter.textContent = summary.total_items || '0';
            
            // Masquer si vide
            if (summary.total_items === 0) {
                counter.style.display = 'none';
            } else {
                counter.style.display = '';
            }
        });
        
        // Mettre à jour les totaux
        const totals = document.querySelectorAll('.cart-total, .total-amount');
        totals.forEach(total => {
            total.textContent = (summary.total_amount || 0).toLocaleString('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            });
        });
        
        // Mettre à jour l'état du bouton panier
        const cartButtons = document.querySelectorAll('.btn-cart, .cart-link');
        cartButtons.forEach(button => {
            if (summary.is_empty) {
                button.classList.add('empty');
            } else {
                button.classList.remove('empty');
            }
        });
    }
    
    async apiRequest(action, data = {}) {
        const url = new URL(this.apiUrl, window.location.origin);
        url.searchParams.set('action', action);
        
        const options = {
            method: data && Object.keys(data).length > 0 ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        
        if (options.method === 'POST') {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Erreur inconnue');
        }
        
        return result;
    }
    
    showNotification(message, type = 'info') {
        // Supprimer les anciennes notifications
        const existingNotifications = document.querySelectorAll('.cart-notification');
        existingNotifications.forEach(notification => notification.remove());
        
        // Créer la nouvelle notification
        const notification = document.createElement('div');
        notification.className = `cart-notification alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        `;
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-masquer après 5 secondes
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
}

// Styles CSS pour les animations
const styles = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .cart-counter {
        transition: all 0.3s ease;
    }
    
    .cart-counter:empty {
        display: none;
    }
    
    .btn-add-to-cart:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .cart-item {
        transition: opacity 0.3s ease;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .quantity-btn {
        background: #007bff;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .quantity-btn:hover {
        background: #0056b3;
    }
    
    .quantity-input {
        width: 60px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px;
    }
`;

// Injecter les styles
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Initialiser l'interface panier au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    window.cartUI = new CartUI();
});

// Exporter pour utilisation globale
window.CartUI = CartUI;
