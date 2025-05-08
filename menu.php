<?php
require_once 'includes/common.php';
$page_title = "Menu - Restaurant La Mangeoire";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .menu-section {
            margin-bottom: 40px;
        }
        
        .menu-title {
            color: #ce1212;
            text-align: center;
            padding-bottom: 30px;
            font-size: 2.5rem;
        }
        
        .menu-category {
            color: #ce1212;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ce1212;
            font-size: 1.8rem;
        }
        
        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }
        
        .menu-item-name {
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .menu-item-description {
            color: #555;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .menu-item-price {
            font-weight: 700;
            color: #ce1212;
            font-size: 1.1rem;
        }
        
        .menu-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 50px 20px;
        }
        
        .menu-img {
            max-width: 80px;
            height: auto;
            border-radius: 8px;
            margin-right: 15px;
        }
        
        .menu-item-details {
            display: flex;
            flex: 1;
        }
        
        .menu-item-info {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .menu-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .menu-item-price {
                margin-top: 10px;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="menu-container">
        <h1 class="menu-title">Notre Menu</h1>
        
        <div class="menu-section">
            <h2 class="menu-category">Entrées</h2>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/salade.jpg" alt="Salade" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Salade César</div>
                        <div class="menu-item-description">Laitue romaine, croûtons, parmesan, sauce César maison</div>
                    </div>
                </div>
                <div class="menu-item-price">12.90 €</div>
            </div>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/foie-gras.jpg" alt="Foie Gras" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Foie Gras Maison</div>
                        <div class="menu-item-description">Foie gras de canard mi-cuit, chutney de figues, toast briochés</div>
                    </div>
                </div>
                <div class="menu-item-price">16.50 €</div>
            </div>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/soupe.jpg" alt="Soupe" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Velouté de Butternut</div>
                        <div class="menu-item-description">Velouté de courge butternut, crème fraîche, graines torréfiées</div>
                    </div>
                </div>
                <div class="menu-item-price">9.90 €</div>
            </div>
        </div>
        
        <div class="menu-section">
            <h2 class="menu-category">Plats</h2>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/steak.jpg" alt="Steak" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Entrecôte Grillée</div>
                        <div class="menu-item-description">Entrecôte de bœuf 300g, frites maison, sauce au poivre</div>
                    </div>
                </div>
                <div class="menu-item-price">24.90 €</div>
            </div>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/saumon.jpg" alt="Saumon" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Pavé de Saumon</div>
                        <div class="menu-item-description">Pavé de saumon, risotto crémeux aux asperges, sauce citronnée</div>
                    </div>
                </div>
                <div class="menu-item-price">22.50 €</div>
            </div>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/risotto.jpg" alt="Risotto" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Risotto aux Champignons</div>
                        <div class="menu-item-description">Risotto crémeux, champignons des bois, truffe, parmesan</div>
                    </div>
                </div>
                <div class="menu-item-price">18.90 €</div>
            </div>
        </div>
        
        <div class="menu-section">
            <h2 class="menu-category">Desserts</h2>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/tiramisu.jpg" alt="Tiramisu" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Tiramisu Classique</div>
                        <div class="menu-item-description">Mascarpone, café, biscuits, amaretto</div>
                    </div>
                </div>
                <div class="menu-item-price">8.90 €</div>
            </div>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/chocolate.jpg" alt="Fondant" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Fondant au Chocolat</div>
                        <div class="menu-item-description">Fondant au chocolat noir, cœur coulant, glace vanille</div>
                    </div>
                </div>
                <div class="menu-item-price">9.50 €</div>
            </div>
            
            <div class="menu-item">
                <div class="menu-item-details">
                    <img src="assets/img/menu/fruit.jpg" alt="Salade de Fruits" class="menu-img">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Salade de Fruits Frais</div>
                        <div class="menu-item-description">Assortiment de fruits frais de saison, sirop léger à la menthe</div>
                    </div>
                </div>
                <div class="menu-item-price">7.90 €</div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
</body>
</html>