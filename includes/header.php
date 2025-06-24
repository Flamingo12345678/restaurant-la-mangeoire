<header class="site-header">
    <div class="container">
        <nav class="main-nav">
            <div class="logo">
                <a href="index.php">
                    <h1>La Mangeoire</h1>
                </a>
            </div>
            <div class="nav-menu">
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="index.php#book-a-table">Réservation</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                    <?php if (isset($_SESSION['client_id'])): ?>
                        <li><a href="mon-compte.php">Mon compte</a></li>
                        <li><a href="deconnexion.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="connexion-unifiee.php">Connexion</a></li>
                        <li><a href="inscription.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="cart-icon-container">
                <a href="panier.php" class="cart-icon" id="cartIcon">
                    <i class="bi bi-cart"></i>
                    <span class="cart-counter" id="cartCounter">0</span>
                </a>
            </div>
            <div class="mobile-menu-toggle">
                <i class="bi bi-list"></i>
            </div>
        </nav>
    </div>
</header>

<!-- CSS pour l'en-tête si nécessaire -->
<style>
    .site-header {
        background-color: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .main-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
    }
    
    .cart-icon-container {
        position: relative;
        margin-left: 20px;
    }
    
    .cart-icon {
        display: flex;
        align-items: center;
        color: #333;
        text-decoration: none;
        font-size: 24px;
        transition: color 0.3s ease;
    }
    
    .cart-icon:hover {
        color: #9E2A2B;
    }
    
    .cart-counter {
        background: #9E2A2B;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        position: absolute;
        top: -8px;
        right: -8px;
        min-width: 20px;
        animation: pulse 0.3s ease-in-out;
    }
    
    .cart-counter.hidden {
        display: none;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .logo h1 {
        color: #9E2A2B;
        font-size: 24px;
        margin: 0;
    }
    
    .nav-menu ul {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .nav-menu ul li {
        margin-left: 20px;
    }
    
    .nav-menu ul li a {
        color: #333;
        text-decoration: none;
        font-weight: 500;
        font-size: 16px;
        transition: color 0.3s ease;
    }
    
    .nav-menu ul li a:hover {
        color: #9E2A2B;
    }
    
    .mobile-menu-toggle {
        display: none;
        font-size: 24px;
        cursor: pointer;
    }
    
    @media (max-width: 768px) {
        .nav-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            padding: 20px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 100;
        }
        
        .nav-menu.active {
            display: block;
        }
        
        .nav-menu ul {
            flex-direction: column;
        }
        
        .nav-menu ul li {
            margin: 10px 0;
        }
        
        .mobile-menu-toggle {
            display: block;
        }
        
        .cart-icon-container {
            margin-left: 10px;
        }
    }
</style>

<!-- Script pour le compteur de panier -->
<script>
    // Système de compteur de panier unifié
    window.CartCounter = {
        // Initialiser le compteur
        init: function() {
            this.updateDisplay();
            this.bindEvents();
        },
        
        // Récupérer le nombre d'articles dans le panier
        getCartCount: async function() {
            try {
                // Méthode 1: localStorage (pour compatibilité JavaScript)
                const localCart = JSON.parse(localStorage.getItem('restaurant_cart') || '[]');
                const localCount = localCart.reduce((total, item) => total + (item.quantity || 0), 0);
                
                // Méthode 2: Depuis les données serveur (si disponibles)
                const serverCount = await this.getServerCartCount();
                
                // Retourner le maximum des deux pour éviter les incohérences
                return Math.max(localCount, serverCount);
            } catch (e) {
                console.error('Erreur lecture panier:', e);
                return 0;
            }
        },
        
        // Récupérer le compteur depuis le serveur (si session active)
        getServerCartCount: async function() {
            try {
                const response = await fetch('api/cart-summary.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        return data.data.total_items || 0;
                    }
                }
            } catch (e) {
                console.warn('Impossible de récupérer le panier serveur:', e);
            }
            return 0;
        },
        
        // Mettre à jour l'affichage
        updateDisplay: async function() {
            const counter = document.getElementById('cartCounter');
            if (counter) {
                const count = await this.getCartCount();
                counter.textContent = count;
                
                if (count > 0) {
                    counter.classList.remove('hidden');
                    counter.style.animation = 'pulse 0.3s ease-in-out';
                } else {
                    counter.classList.add('hidden');
                }
                
                // Supprimer l'animation après un délai
                setTimeout(() => {
                    if (counter.style) counter.style.animation = '';
                }, 300);
            }
        },
        
        // Incrémenter le compteur
        increment: function(quantity = 1) {
            this.updateDisplay();
        },
        
        // Lier les événements
        bindEvents: function() {
            // Écouter les mises à jour du panier
            window.addEventListener('cartUpdated', () => {
                this.updateDisplay();
            });
            
            // Écouter les changements de localStorage
            window.addEventListener('storage', (e) => {
                if (e.key === 'restaurant_cart') {
                    this.updateDisplay();
                }
            });
            
            // Mettre à jour périodiquement (toutes les 5 secondes)
            setInterval(() => {
                this.updateDisplay();
            }, 5000);
        }
    };
    
    // Initialiser quand le DOM est prêt
    document.addEventListener('DOMContentLoaded', function() {
        window.CartCounter.init();
    });
    
    // Aussi initialiser immédiatement si le DOM est déjà chargé
    if (document.readyState === 'loading') {
        // Le DOM n'est pas encore prêt
    } else {
        // Le DOM est déjà prêt
        window.CartCounter.init();
    }
</script>
