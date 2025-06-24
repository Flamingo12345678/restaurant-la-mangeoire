# 👨‍💻 Guide Développeur - La Mangeoire

## 🎯 Vue d'Ensemble

Ce guide fournit toutes les informations nécessaires pour maintenir et faire évoluer le système du restaurant La Mangeoire.

## 📁 Architecture du Projet

```
restaurant-la-mangeoire/
├── 🏠 Pages Principales
│   ├── index.php              # Page d'accueil
│   ├── menu.php               # Menu dynamique avec panier
│   ├── panier.php             # Gestion panier (client-side)
│   ├── commande-moderne.php   # Processus de commande
│   └── confirmation-*.php     # Pages de confirmation
│
├── 🔧 Configuration
│   ├── .env                   # Variables d'environnement
│   ├── db_connexion.php       # Connexion base de données
│   └── config/
│       └── email_config.php   # Configuration SMTP
│
├── 📚 Modules Système
│   ├── includes/
│   │   ├── audit-logger.php   # Système d'audit
│   │   ├── order-manager.php  # Gestion commandes
│   │   ├── payment-manager.php # Gestion paiements
│   │   └── currency_manager.php # Gestion devises
│   │
│   ├── api/                   # APIs REST
│   │   ├── orders/            # Endpoints commandes
│   │   ├── payments/          # Endpoints paiements
│   │   └── cart/              # Synchronisation panier
│   │
├── 🎨 Assets
│   ├── assets/css/main.css    # Styles principaux
│   ├── images/                # Images du site
│   └── js/                    # Scripts JavaScript
│
├── 🗄️ Base de Données
│   ├── sql/                   # Schémas et migrations
│   └── *.php                  # Scripts de création/mise à jour
│
├── 🔍 Administration
│   ├── dashboard-admin.php    # Tableau de bord admin
│   ├── api-dashboard.php      # API dashboard
│   └── admin-*.php            # Pages d'administration
│
└── 📊 Monitoring
    ├── logs/                  # Journaux système
    ├── optimiser-base-donnees.php
    └── check-production-setup.php
```

## 🗄️ Base de Données

### Tables Principales

#### 1. **menus**
```sql
CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    actif BOOLEAN DEFAULT 1,
    categorie VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_menus_actif (actif),
    INDEX idx_menus_prix (prix),
    INDEX idx_menus_categorie (categorie)
);
```

#### 2. **commandes**
```sql
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    items JSON NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'preparee', 'livree', 'annulee') DEFAULT 'en_attente',
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    INDEX idx_commandes_date (date_commande),
    INDEX idx_commandes_status (statut),
    INDEX idx_commandes_client (client_id)
);
```

#### 3. **paiements**
```sql
CREATE TABLE paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    methode ENUM('stripe', 'paypal', 'especes') NOT NULL,
    statut ENUM('en_attente', 'confirme', 'echoue', 'rembourse') DEFAULT 'en_attente',
    transaction_id VARCHAR(255),
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_paiements_date (date_paiement),
    INDEX idx_paiements_status (statut),
    INDEX idx_paiements_commande (commande_id),
    FOREIGN KEY (commande_id) REFERENCES commandes(id)
);
```

#### 4. **audit_logs**
```sql
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    user_type ENUM('client', 'employe', 'admin', 'system') DEFAULT 'system',
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    INDEX idx_timestamp (timestamp),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_severity (severity)
);
```

## 🛒 Système de Panier

### Architecture Client-Side

Le panier fonctionne entièrement côté client avec localStorage :

```javascript
// CartManager - Gestionnaire global du panier
class CartManager {
    constructor() {
        this.storageKey = 'restaurant_cart';
        this.listeners = [];
    }
    
    // Ajouter un article
    addItem(item) {
        const cart = this.getCart();
        const existingIndex = cart.findIndex(cartItem => cartItem.id === item.id);
        
        if (existingIndex !== -1) {
            cart[existingIndex].quantite += item.quantite || 1;
        } else {
            cart.push({
                id: item.id,
                nom: item.nom,
                prix: parseFloat(item.prix),
                quantite: item.quantite || 1,
                image: item.image || ''
            });
        }
        
        this.saveCart(cart);
        this.notifyListeners('add', item);
    }
    
    // Récupérer le panier
    getCart() {
        try {
            return JSON.parse(localStorage.getItem(this.storageKey)) || [];
        } catch (error) {
            console.error('Erreur lecture panier:', error);
            return [];
        }
    }
    
    // Sauvegarder le panier
    saveCart(cart) {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(cart));
        } catch (error) {
            console.error('Erreur sauvegarde panier:', error);
        }
    }
}
```

### Synchronisation Cross-Page

Toutes les pages utilisent le même `CartManager` :

```html
<!-- À inclure dans chaque page -->
<script src="assets/js/cart-manager.js"></script>
<script>
    const cartManager = new CartManager();
</script>
```

## 💳 Système de Paiement

### Configuration

Variables d'environnement requises dans `.env` :

```env
# PayPal
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox  # ou live

# Stripe
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

# Général
CURRENCY=EUR
```

### PaymentManager

```php
class PaymentManager {
    public function processPayment($orderId, $amount, $method, $details = []) {
        switch ($method) {
            case 'paypal':
                return $this->processPayPalPayment($orderId, $amount, $details);
            case 'stripe':
                return $this->processStripePayment($orderId, $amount, $details);
            default:
                throw new Exception('Méthode de paiement non supportée');
        }
    }
    
    private function processPayPalPayment($orderId, $amount, $details) {
        // Implémentation PayPal
        // Voir includes/payment-manager.php
    }
    
    private function processStripePayment($orderId, $amount, $details) {
        // Implémentation Stripe
        // Voir includes/payment-manager.php
    }
}
```

## 📊 Système d'Audit

### Utilisation

```php
// Inclure le système d'audit
require_once 'includes/audit-logger.php';

// Logger une action
audit_log('user_login', ['user_id' => 123], 'info');

// Logger une commande
audit_order(456, AuditActions::ORDER_CREATE, ['total' => 29.99]);

// Logger un paiement
audit_payment(789, AuditActions::PAYMENT_SUCCESS, ['method' => 'stripe']);

// Logger une erreur
audit_error('Erreur connexion BD', ['error_code' => 1045]);

// Logger une alerte sécurité
audit_security('Tentative accès non autorisé', ['ip' => '192.168.1.1']);
```

### Consultation des Logs

Via le dashboard admin : `dashboard-admin.php`

Ou directement :

```php
$auditLogger = new AuditLogger();

// Logs récents
$logs = $auditLogger->getRecentLogs(50);

// Logs d'un utilisateur
$userLogs = $auditLogger->getLogsByUser(123, 'client');

// Logs par action
$orderLogs = $auditLogger->getLogsByAction('order_create');

// Statistiques
$stats = $auditLogger->getStatistics();
```

## 🔧 APIs REST

### Structure

```
api/
├── orders/
│   └── index.php       # GET, POST, PUT, DELETE /api/orders
├── payments/
│   └── index.php       # GET, POST /api/payments
└── cart/
    └── index.php       # POST /api/cart/sync
```

### Endpoints Commandes

```php
// GET /api/orders - Liste des commandes
// GET /api/orders/{id} - Détail d'une commande
// POST /api/orders - Créer une commande
{
    "items": [
        {"id": 1, "quantite": 2, "prix": 12.50},
        {"id": 3, "quantite": 1, "prix": 8.00}
    ],
    "total": 33.00,
    "client_info": {
        "nom": "Dupont",
        "email": "dupont@email.com",
        "telephone": "0123456789"
    }
}

// PUT /api/orders/{id} - Modifier une commande
// DELETE /api/orders/{id} - Annuler une commande
```

### Endpoints Paiements

```php
// POST /api/payments - Traiter un paiement
{
    "commande_id": 123,
    "montant": 33.00,
    "methode": "stripe",
    "token": "stripe_token_here"
}
```

## 🎨 Styles et UI

### Structure CSS

```css
/* assets/css/main.css */

/* Variables globales */
:root {
    --primary-color: #d4a574;
    --secondary-color: #8B4513;
    --accent-color: #ff6b35;
    --text-color: #333;
    --background-color: #f8f9fa;
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Composants réutilisables */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s ease;
}

.card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 20px;
}

/* Layout responsive */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

@media (max-width: 768px) {
    .container { padding: 0 15px; }
}
```

### Animations

```css
/* Transitions fluides */
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Loading spinner */
.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid var(--primary-color);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
```

## 🔒 Sécurité

### Bonnes Pratiques Implémentées

1. **Protection CSRF**
```php
// Génération token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérification token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

2. **Validation des Données**
```php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
```

3. **Préparation des Requêtes**
```php
// ✅ Correct
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ Incorrect
$query = "SELECT * FROM users WHERE email = '$email'";
```

## 📈 Monitoring et Performance

### Optimisation Base de Données

Exécuter régulièrement :

```bash
php optimiser-base-donnees.php
```

### Surveillance

1. **Dashboard Admin** : `dashboard-admin.php`
2. **Logs d'Audit** : `logs/audit.log`
3. **Métriques** : API `/api-dashboard.php?action=stats`

### Sauvegarde

```bash
# Sauvegarde complète
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarde structure seulement
mysqldump -u username -p --no-data database_name > structure.sql
```

## 🚀 Déploiement

### Checklist Pré-Déploiement

```bash
# 1. Vérifier la configuration
php check-production-setup.php

# 2. Optimiser la base de données
php optimiser-base-donnees.php

# 3. Tester le workflow complet
php test-workflow-complet.php

# 4. Nettoyer les fichiers de debug
./cleanup-legacy.sh
```

### Variables d'environnement Production

```env
# Base de données
DB_HOST=production_host
DB_USER=production_user
DB_PASS=secure_password
DB_NAME=production_db

# Email
SMTP_HOST=smtp.production.com
SMTP_PORT=587
SMTP_USER=noreply@restaurant.com
SMTP_PASS=smtp_password

# Paiements
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=live_paypal_id
STRIPE_SECRET_KEY=sk_live_...

# Sécurité
APP_ENV=production
DEBUG_MODE=false
```

## 🐛 Debugging

### Logs de Debug

```php
// Activer le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Logger des informations
error_log("Debug info: " . print_r($data, true));
```

### Outils de Debug

1. **Test Panier** : `debug-panier.php`
2. **Test Workflow** : `test-workflow-complet.php`
3. **Test Responsive** : `test-responsivite.php`

### Console Browser

```javascript
// Debug panier
console.log('Panier actuel:', cartManager.getCart());
console.log('Total:', cartManager.getTotal());

// Test localStorage
console.log('LocalStorage cart:', localStorage.getItem('restaurant_cart'));
```

## 📚 Extensions Futures

### Suggestions d'Améliorations

1. **Notifications Push** : Service Worker + Web Push API
2. **Géolocalisation** : API Geolocation pour livraisons
3. **Chat en Direct** : WebSocket pour support client
4. **Analytics** : Google Analytics / Matomo
5. **A/B Testing** : Split testing pour optimisation
6. **CDN** : CloudFlare pour performances
7. **Cache Redis** : Mise en cache avancée

### Architecture Microservices

```
Services Futurs:
├── auth-service/          # Authentification
├── order-service/         # Gestion commandes
├── payment-service/       # Traitement paiements
├── notification-service/  # Notifications
└── analytics-service/     # Analytiques
```

## 📞 Support et Maintenance

### Contacts

- **Développeur Principal** : À définir
- **Administrateur Système** : À définir
- **Support Technique** : support@restaurant.com

### Planning de Maintenance

- **Hebdomadaire** : Nettoyage des logs
- **Mensuel** : Optimisation BD, sauvegardes
- **Trimestriel** : Audit sécurité, mise à jour dépendances
- **Annuel** : Review architecture, plan d'évolution

---

*Documentation générée le 21 juin 2025*  
*Version : 2.0*  
*Statut : Production Ready*
