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
    }
</style>
