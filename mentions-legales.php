<?php
// Inclure les fichiers nécessaires
require_once 'includes/common.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions Légales - Restaurant La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/cookie-consent.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #444;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        h1 {
            color: #ce1212;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        
        h2 {
            color: #ce1212;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        h3 {
            color: #333;
            margin-top: 25px;
            font-size: 1.4rem;
        }
        
        p {
            margin-bottom: 15px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #ce1212;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .content-box {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .content-box ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .content-box li {
            margin-bottom: 8px;
        }
        
        .footer-note {
            text-align: center;
            margin-top: 50px;
            font-size: 0.9rem;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="bi bi-arrow-left-circle"></i> Retour à l'accueil
        </a>
        
        <h1>Mentions Légales</h1>
        
        <div class="content-box">
            <h2>1. Informations légales</h2>
            <p>Le site internet Restaurant La Mangeoire est édité par :</p>
            <p>
                <strong>SARL LA MANGEOIRE</strong><br>
                123 Rue de la Gastronomie<br>
                75000 Paris, France<br>
                N° SIRET : 123 456 789 00012<br>
                Capital social : 10 000 €<br>
                N° TVA intracommunautaire : FR 12 345678900<br>
                Email : contact@la-mangeoire.fr<br>
                Téléphone : +33 1 23 45 67 89
            </p>
            
            <h3>Directeur de la publication :</h3>
            <p>Jean Dupont, Gérant</p>
            
            <h3>Hébergeur du site :</h3>
            <p>
                OVH SAS<br>
                2 rue Kellermann<br>
                59100 Roubaix, France<br>
                Téléphone : +33 9 72 10 10 10
            </p>
        </div>
        
        <div class="content-box">
            <h2>2. Conditions générales d'utilisation</h2>
            <p>L'utilisation du site Restaurant La Mangeoire implique l'acceptation pleine et entière des conditions générales d'utilisation décrites ci-après.</p>
            
            <h3>Accès au site</h3>
            <p>Le site est accessible gratuitement à tout utilisateur disposant d'un accès à Internet. Tous les frais nécessaires pour l'accès aux services (matériel informatique, connexion Internet…) sont à la charge de l'utilisateur.</p>
            <p>L'accès aux services dédiés aux membres s'effectue à l'aide d'un identifiant et d'un mot de passe.</p>
            <p>Pour des raisons de maintenance ou autres, l'accès au site peut être interrompu ou suspendu par l'éditeur sans préavis ni justification.</p>
            
            <h3>Propriété intellectuelle</h3>
            <p>Tous les éléments du site Restaurant La Mangeoire (structure, textes, logos, images, éléments graphiques, sonores, logiciels, etc.) sont protégés par le droit d'auteur, des marques ou des brevets. Leur reproduction ou utilisation, même partielle, est strictement interdite sans autorisation écrite préalable.</p>
            
            <h3>Responsabilité</h3>
            <p>Le restaurant La Mangeoire ne pourra être tenu responsable des dommages directs ou indirects causés au matériel de l'utilisateur lors de l'accès au site, et résultant de l'apparition d'un bug ou d'une incompatibilité.</p>
            <p>Le restaurant La Mangeoire ne pourra également être tenue responsable des dommages indirects consécutifs à l'utilisation du site.</p>
        </div>
        
        <div class="content-box">
            <h2>3. Gestion des données personnelles</h2>
            <p>Les informations collectées sur le site font l'objet d'un traitement informatique destiné à la gestion des commandes, réservations et demandes de contact.</p>
            <p>Conformément à la loi « Informatique et Libertés » du 6 janvier 1978 modifiée, et au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification, et de suppression de vos données.</p>
            <p>Pour exercer ces droits ou pour toute question sur le traitement de vos données, vous pouvez contacter notre délégué à la protection des données à l'adresse : dpo@la-mangeoire.fr</p>
            <p>Pour plus d'informations sur la gestion de vos données personnelles, veuillez consulter notre <a href="politique-confidentialite.php" style="color: #ce1212;">Politique de confidentialité</a>.</p>
        </div>
        
        <div class="content-box">
            <h2>4. Liens hypertextes</h2>
            <p>Le site Restaurant La Mangeoire contient des liens hypertextes vers d'autres sites et dégage toute responsabilité à propos de ces liens externes ou des liens créés par d'autres sites vers Restaurant La Mangeoire.</p>
            <p>La création de liens vers notre site est soumise à notre autorisation préalable.</p>
        </div>
        
        <div class="content-box">
            <h2>5. Droit applicable et juridiction compétente</h2>
            <p>Tout litige en relation avec l'utilisation du site Restaurant La Mangeoire est soumis au droit français. Il est fait attribution exclusive de juridiction aux tribunaux compétents de Paris.</p>
        </div>
        
        <p class="footer-note">Les présentes mentions légales peuvent être modifiées à tout moment, sans préavis.</p>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
</body>
</html>
