<?php
/**
 * Nouvelle page de commande unifiée
 * Restaurant La Mangeoire - 21 juin 2025
 * 
 * Interface moderne pour finaliser une commande depuis le panier localStorage
 */

session_start();
require_once 'includes/common.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser votre commande - Restaurant La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
        
        .checkout-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            font-size: 1.2rem;
        }
        
        .form-section h3::before {
            content: '';
            width: 24px;
            height: 24px;
            margin-right: 0.5rem;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .form-section:nth-child(1) h3::before { content: '1'; }
        .form-section:nth-child(2) h3::before { content: '2'; }
        .form-section:nth-child(3) h3::before { content: '3'; }
        .form-section:nth-child(4) h3::before { content: '4'; }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .delivery-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .delivery-option {
            position: relative;
        }
        
        .delivery-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .delivery-option label {
            display: block;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .delivery-option input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .delivery-option .icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .payment-method {
            position: relative;
        }
        
        .payment-method input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .payment-method label {
            display: block;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .payment-method input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(52, 152, 219, 0.1);
        }
        
        .payment-method .method-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .payment-method .method-description {
            font-size: 0.9rem;
            color: #666;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .cart-item-price {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .cart-item-quantity {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-total {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .total-line.grand-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            padding-top: 0.5rem;
            border-top: 2px solid var(--primary-color);
            margin-top: 1rem;
        }
        
        .btn-place-order {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }
        
        .btn-place-order:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .btn-place-order:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message,
        .success-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .guest-login-toggle {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .guest-login-toggle button {
            background: none;
            border: none;
            color: var(--primary-color);
            text-decoration: underline;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .login-form {
            display: none;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Formulaire de commande -->
        <div class="checkout-form">
            <h1>Finaliser votre commande</h1>
            
            <div class="error-message" id="error-message"></div>
            <div class="success-message" id="success-message"></div>
            
            <form id="checkout-form">
                <!-- Section 1: Informations client -->
                <div class="form-section">
                    <h3>Vos informations</h3>
                    
                    <?php if (!isset($_SESSION['client_id'])): ?>
                    <div class="guest-login-toggle">
                        <p>Déjà client ? <button type="button" onclick="toggleLogin()">Se connecter</button></p>
                    </div>
                    
                    <div class="login-form" id="login-form">
                        <div class="form-group">
                            <label for="login-email">Email</label>
                            <input type="email" id="login-email" name="login_email">
                        </div>
                        <div class="form-group">
                            <label for="login-password">Mot de passe</label>
                            <input type="password" id="login-password" name="login_password">
                        </div>
                        <button type="button" onclick="handleLogin()" class="btn btn-secondary">Se connecter</button>
                        <button type="button" onclick="toggleLogin()" class="btn btn-link">Continuer en invité</button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer-name">Nom complet *</label>
                            <input type="text" id="customer-name" name="customer_name" required
                                   value="<?= isset($_SESSION['client_name']) ? htmlspecialchars($_SESSION['client_name']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="customer-phone">Téléphone</label>
                            <input type="tel" id="customer-phone" name="customer_phone"
                                   value="<?= isset($_SESSION['client_phone']) ? htmlspecialchars($_SESSION['client_phone']) : '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer-email">Email *</label>
                        <input type="email" id="customer-email" name="customer_email" required
                               value="<?= isset($_SESSION['client_email']) ? htmlspecialchars($_SESSION['client_email']) : '' ?>">
                    </div>
                </div>
                
                <!-- Section 2: Type de commande -->
                <div class="form-section">
                    <h3>Type de commande</h3>
                    
                    <div class="delivery-options">
                        <div class="delivery-option">
                            <input type="radio" id="takeaway" name="delivery_type" value="emporter" checked>
                            <label for="takeaway">
                                <span class="icon">🏪</span>
                                <strong>À emporter</strong><br>
                                <small>Gratuit</small>
                            </label>
                        </div>
                        <div class="delivery-option">
                            <input type="radio" id="delivery" name="delivery_type" value="livraison">
                            <label for="delivery">
                                <span class="icon">🚗</span>
                                <strong>Livraison</strong><br>
                                <small>5,00 €</small>
                            </label>
                        </div>
                        <div class="delivery-option">
                            <input type="radio" id="dine-in" name="delivery_type" value="sur_place">
                            <label for="dine-in">
                                <span class="icon">🍽️</span>
                                <strong>Sur place</strong><br>
                                <small>Gratuit</small>
                            </label>
                        </div>
                    </div>
                    
                    <div id="delivery-address" style="display: none; margin-top: 1rem;">
                        <div class="form-group">
                            <label for="address">Adresse de livraison *</label>
                            <input type="text" id="address" name="delivery_address" placeholder="Rue, numéro">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Ville *</label>
                                <input type="text" id="city" name="delivery_city" placeholder="Ville">
                            </div>
                            <div class="form-group">
                                <label for="postal">Code postal *</label>
                                <input type="text" id="postal" name="delivery_postal" placeholder="00000">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section 3: Notes spéciales -->
                <div class="form-section">
                    <h3>Notes spéciales</h3>
                    
                    <div class="form-group">
                        <label for="special-notes">Instructions particulières (optionnel)</label>
                        <textarea id="special-notes" name="special_notes" rows="3" 
                                  placeholder="Allergies, préférences de cuisson, instructions de livraison..."></textarea>
                    </div>
                </div>
                
                <!-- Section 4: Paiement -->
                <div class="form-section">
                    <h3>Mode de paiement</h3>
                    
                    <div class="payment-methods" id="payment-methods">
                        <!-- Les méthodes de paiement seront chargées dynamiquement -->
                    </div>
                </div>
                
                <button type="submit" class="btn-place-order" id="place-order-btn">
                    Confirmer la commande
                </button>
            </form>
        </div>
        
        <!-- Résumé de commande -->
        <div class="order-summary">
            <h3>Résumé de votre commande</h3>
            
            <div id="cart-items">
                <!-- Les articles seront chargés dynamiquement -->
            </div>
            
            <div class="order-total">
                <div class="total-line">
                    <span>Sous-total:</span>
                    <span id="subtotal">0,00 €</span>
                </div>
                <div class="total-line">
                    <span>Taxes (10%):</span>
                    <span id="taxes">0,00 €</span>
                </div>
                <div class="total-line" id="delivery-fee-line" style="display: none;">
                    <span>Frais de livraison:</span>
                    <span id="delivery-fee">5,00 €</span>
                </div>
                <div class="total-line grand-total">
                    <span>Total:</span>
                    <span id="total">0,00 €</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Overlay de chargement -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
        <p style="color: white; margin-top: 1rem;">Traitement en cours...</p>
    </div>

    <script>
        // Variables globales
        let cart = [];
        let paymentMethods = [];
        let totals = {};
        
        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadCart();
            loadPaymentMethods();
            setupEventListeners();
        });
        
        // Système de panier unifié
        window.CartManager = window.CartManager || {
            getCart: function() {
                try {
                    return JSON.parse(localStorage.getItem('restaurant_cart')) || [];
                } catch (e) {
                    console.error('Erreur lecture panier:', e);
                    return [];
                }
            },
            saveCart: function(cart) {
                try {
                    localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                    return true;
                } catch (e) {
                    console.error('Erreur sauvegarde panier:', e);
                    return false;
                }
            }
        };
        
        // Charger le panier depuis localStorage
        function loadCart() {
            const cartData = localStorage.getItem('restaurant_cart');
            if (cartData) {
                try {
                    cart = JSON.parse(cartData);
                    displayCartItems();
                    calculateTotals();
                } catch (e) {
                    console.error('Erreur parsing panier:', e);
                    cart = [];
                }
            }
            
            // Rediriger si panier vide
            if (cart.length === 0) {
                window.location.href = 'menu.php';
                return;
            }
        }
        
        // Afficher les articles du panier
        function displayCartItems() {
            const container = document.getElementById('cart-items');
            if (!container) return;
            
            let html = '';
            
            cart.forEach(item => {
                html += `
                    <div class="cart-item">
                        <img src="${item.image || 'assets/images/placeholder.jpg'}" alt="${item.name}" onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-quantity">Quantité: ${item.quantity}</div>
                            <div class="cart-item-price">${(item.price * item.quantity).toFixed(2)} €</div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Calculer les totaux
        function calculateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const taxes = subtotal * 0.10;
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'emporter';
            const deliveryFee = deliveryType === 'livraison' ? 5.00 : 0.00;
            const total = subtotal + taxes + deliveryFee;
            
            totals = { subtotal, taxes, deliveryFee, total };
            
            // Mettre à jour l'affichage
            document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' €';
            document.getElementById('taxes').textContent = taxes.toFixed(2) + ' €';
            document.getElementById('delivery-fee').textContent = deliveryFee.toFixed(2) + ' €';
            document.getElementById('total').textContent = total.toFixed(2) + ' €';
            
            // Afficher/masquer la ligne frais de livraison
            const deliveryFeeLine = document.getElementById('delivery-fee-line');
            if (deliveryFee > 0) {
                deliveryFeeLine.style.display = 'flex';
            } else {
                deliveryFeeLine.style.display = 'none';
            }
        }
        
        // Charger les méthodes de paiement
        async function loadPaymentMethods() {
            try {
                const response = await fetch('/api/payments/methods');
                const data = await response.json();
                
                if (data.success) {
                    paymentMethods = data.methods.filter(method => method.enabled);
                    displayPaymentMethods();
                }
            } catch (error) {
                console.error('Erreur chargement méthodes paiement:', error);
                // Méthodes par défaut en cas d'erreur
                paymentMethods = [
                    { id: 'stripe_card', name: 'Carte bancaire', description: 'Paiement sécurisé par carte', enabled: true },
                    { id: 'especes', name: 'Espèces', description: 'Paiement en espèces au restaurant', enabled: true }
                ];
                displayPaymentMethods();
            }
        }
        
        // Afficher les méthodes de paiement
        function displayPaymentMethods() {
            const container = document.getElementById('payment-methods');
            if (!container) return;
            
            let html = '';
            
            paymentMethods.forEach((method, index) => {
                html += `
                    <div class="payment-method">
                        <input type="radio" id="payment-${method.id}" name="payment_method" value="${method.id}" ${index === 0 ? 'checked' : ''}>
                        <label for="payment-${method.id}">
                            <div class="method-name">${method.name}</div>
                            <div class="method-description">${method.description}</div>
                        </label>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Configuration des événements
        function setupEventListeners() {
            // Type de livraison
            document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const deliveryAddress = document.getElementById('delivery-address');
                    if (this.value === 'livraison') {
                        deliveryAddress.style.display = 'block';
                        deliveryAddress.querySelectorAll('input').forEach(input => {
                            input.required = true;
                        });
                    } else {
                        deliveryAddress.style.display = 'none';
                        deliveryAddress.querySelectorAll('input').forEach(input => {
                            input.required = false;
                        });
                    }
                    calculateTotals();
                });
            });
            
            // Soumission du formulaire
            document.getElementById('checkout-form').addEventListener('submit', handleCheckout);
        }
        
        // Gérer la soumission de commande
        async function handleCheckout(event) {
            event.preventDefault();
            
            if (cart.length === 0) {
                showError('Votre panier est vide');
                return;
            }
            
            const formData = new FormData(event.target);
            const orderData = {
                cart: cart,
                customer: {
                    name: formData.get('customer_name'),
                    email: formData.get('customer_email'),
                    phone: formData.get('customer_phone')
                },
                options: {
                    type: formData.get('delivery_type'),
                    delivery_address: formData.get('delivery_address'),
                    delivery_city: formData.get('delivery_city'),
                    delivery_postal: formData.get('delivery_postal'),
                    special_notes: formData.get('special_notes')
                }
            };
            
            const paymentMethod = formData.get('payment_method');
            
            try {
                showLoading(true);
                
                // 1. Créer la commande
                const orderResponse = await fetch('/api/orders/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                
                const orderResult = await orderResponse.json();
                
                if (!orderResult.success) {
                    throw new Error(orderResult.error || 'Erreur création commande');
                }
                
                // 2. Créer l'intention de paiement
                const paymentResponse = await fetch('/api/payments/create-intent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderResult.order_id,
                        payment_method: paymentMethod,
                        metadata: {
                            customer_email: orderData.customer.email,
                            customer_name: orderData.customer.name
                        }
                    })
                });
                
                const paymentResult = await paymentResponse.json();
                
                if (!paymentResult.success) {
                    throw new Error(paymentResult.error || 'Erreur création paiement');
                }
                
                // 3. Traiter selon la méthode de paiement
                await handlePaymentMethod(paymentMethod, paymentResult, orderResult);
                
            } catch (error) {
                console.error('Erreur commande:', error);
                showError(error.message || 'Une erreur est survenue');
            } finally {
                showLoading(false);
            }
        }
        
        // Gérer la méthode de paiement spécifique
        async function handlePaymentMethod(method, paymentResult, orderResult) {
            switch (method) {
                case 'stripe_card':
                    await handleStripePayment(paymentResult);
                    break;
                    
                case 'paypal':
                    handlePayPalPayment(paymentResult);
                    break;
                    
                case 'especes':
                case 'virement':
                    handleOfflinePayment(method, orderResult, paymentResult);
                    break;
                    
                default:
                    throw new Error('Méthode de paiement non supportée');
            }
        }
        
        // Traiter paiement Stripe
        async function handleStripePayment(paymentResult) {
            if (typeof Stripe === 'undefined') {
                // Charger Stripe.js si pas encore chargé
                await loadStripeJS();
            }
            
            const stripe = Stripe('pk_test_your_publishable_key'); // À configurer
            
            const { error } = await stripe.confirmPayment({
                clientSecret: paymentResult.client_secret,
                confirmParams: {
                    return_url: window.location.origin + '/confirmation-stripe.php'
                }
            });
            
            if (error) {
                throw error;
            }
        }
        
        // Traiter paiement PayPal
        function handlePayPalPayment(paymentResult) {
            // Rediriger vers PayPal
            window.location.href = paymentResult.approval_url;
        }
        
        // Traiter paiement hors ligne
        function handleOfflinePayment(method, orderResult, paymentResult) {
            // Vider le panier avec le système unifié
            window.CartManager.saveCart([]);
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
            
            // Rediriger vers confirmation
            const params = new URLSearchParams({
                order_id: orderResult.order_id,
                payment_id: paymentResult.payment_id,
                method: method
            });
            
            window.location.href = 'confirmation-commande.php?' + params.toString();
        }
        
        // Charger Stripe.js dynamiquement
        function loadStripeJS() {
            return new Promise((resolve, reject) => {
                if (typeof Stripe !== 'undefined') {
                    resolve();
                    return;
                }
                
                const script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        
        // Fonctions utilitaires
        function toggleLogin() {
            const loginForm = document.getElementById('login-form');
            loginForm.style.display = loginForm.style.display === 'none' ? 'block' : 'none';
        }
        
        async function handleLogin() {
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            if (!email || !password) {
                showError('Email et mot de passe requis');
                return;
            }
            
            try {
                showLoading(true);
                
                const response = await fetch('auth_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `email=${encodeURIComponent(email)}&mot_de_passe=${encodeURIComponent(password)}`
                });
                
                const result = await response.text();
                
                if (result.includes('Connexion réussie')) {
                    // Recharger la page pour mettre à jour les informations client
                    window.location.reload();
                } else {
                    showError('Email ou mot de passe incorrect');
                }
                
            } catch (error) {
                showError('Erreur de connexion');
            } finally {
                showLoading(false);
            }
        }
        
        function showLoading(show) {
            const overlay = document.getElementById('loading-overlay');
            const button = document.getElementById('place-order-btn');
            
            if (show) {
                overlay.style.display = 'flex';
                button.disabled = true;
            } else {
                overlay.style.display = 'none';
                button.disabled = false;
            }
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            
            // Faire défiler vers le haut
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Masquer après 5 secondes
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('success-message');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            
            // Faire défiler vers le haut
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
