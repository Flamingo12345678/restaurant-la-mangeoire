<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-info">
                <h3>Restaurant La Mangeoire</h3>
                <p>123 Rue de la Gastronomie<br>75000 Paris, France</p>
                <p><i class="bi bi-telephone"></i> +33 1 23 45 67 89</p>
                <p><i class="bi bi-envelope"></i> contact@la-mangeoire.fr</p>
            </div>
            <div class="footer-links">
                <h4>Liens rapides</h4>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="index.php#book-a-table">Réservation</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-social">
                <h4>Suivez-nous</h4>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Restaurant La Mangeoire. Tous droits réservés.</p>
            <p class="privacy-links">
                <a href="mentions-legales.php">Mentions légales</a> | 
                <a href="politique-confidentialite.php">Politique de confidentialité</a> | 
                <?php if (function_exists('cookie_preferences_link')) echo cookie_preferences_link(); ?>
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
.site-footer {
    background-color: #141414;
    color: #fff;
    padding: 40px 0 20px;
    margin-top: 60px;
}

.footer-content {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin-bottom: 30px;
}

.footer-info, .footer-links, .footer-social {
    flex: 1;
    min-width: 200px;
    margin-bottom: 20px;
    padding: 0 15px;
}

.footer-info h3 {
    color: #9E2A2B;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.footer-info p {
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.footer-links h4, .footer-social h4 {
    color: #9E2A2B;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.footer-links ul {
    list-style: none;
    padding: 0;
}

.footer-links ul li {
    margin-bottom: 10px;
}

.footer-links ul li a {
    color: #fff;
    text-decoration: none;
    transition: color 0.3s;
    font-size: 0.9rem;
}

.footer-links ul li a:hover {
    color: #9E2A2B;
}

.social-icons {
    display: flex;
    gap: 15px;
}

.social-icon {
    color: #fff;
    font-size: 1.5rem;
    transition: color 0.3s;
}

.social-icon:hover {
    color: #9E2A2B;
}

.copyright {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #333;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
    }
    
    .footer-info, .footer-links, .footer-social {
        width: 100%;
        padding: 0;
    }
}
</style>
