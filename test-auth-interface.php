<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test du flux d'authentification - La Mangeoire</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .test-button {
            background-color: #e74c3c;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .test-button:hover {
            background-color: #c0392b;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test du flux d'authentification</h1>
        
        <div class="test-section">
            <h3>Statut de l'authentification</h3>
            <?php
            session_start();
            if (isset($_SESSION['user_id'])) {
                echo '<div class="status success">✅ Connecté en tant que: ' . ($_SESSION['user_nom'] ?? 'Utilisateur') . ' ' . ($_SESSION['user_prenom'] ?? '') . '</div>';
                echo '<div class="status info">ID utilisateur: ' . $_SESSION['user_id'] . '</div>';
                if (isset($_SESSION['client_id'])) {
                    echo '<div class="status info">ID client (compatibilité): ' . $_SESSION['client_id'] . '</div>';
                }
            } else {
                echo '<div class="status info">❌ Non connecté</div>';
            }
            ?>
        </div>

        <div class="test-section">
            <h3>Tests à effectuer</h3>
            <p><strong>Scénario 1: Utilisateur non connecté</strong></p>
            <a href="deconnexion.php" class="test-button">1. Se déconnecter</a>
            <a href="panier.php" class="test-button">2. Aller au panier</a>
            <p><em>Résultat attendu: Le panier doit afficher "Se connecter pour commander"</em></p>
            
            <p><strong>Scénario 2: Tentative de commande sans connexion</strong></p>
            <a href="passer-commande.php" class="test-button">3. Passer commande directement</a>
            <p><em>Résultat attendu: Redirection vers la page de connexion</em></p>
            
            <p><strong>Scénario 3: Flux complet</strong></p>
            <a href="panier.php?redirect=passer-commande.php" class="test-button">4. Panier → Connexion → Inscription</a>
            <p><em>Résultat attendu: Après inscription, retour à la page de commande</em></p>
        </div>

        <div class="test-section">
            <h3>Liens utiles</h3>
            <a href="index.php" class="test-button">Accueil</a>
            <a href="menu.php" class="test-button">Menu</a>
            <a href="panier.php" class="test-button">Panier</a>
            <a href="mon-compte.php" class="test-button">Mon compte</a>
            <a href="connexion-unifiee.php" class="test-button">Connexion</a>
            <a href="inscription.php" class="test-button">Inscription</a>
        </div>

        <div class="test-section">
            <h3>État des variables de session</h3>
            <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto;">
<?php
$session_vars = [];
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'user_') === 0 || strpos($key, 'client_') === 0 || $key === 'user_type' || strpos($key, 'redirect') !== false) {
        $session_vars[$key] = is_string($value) ? $value : json_encode($value);
    }
}
print_r($session_vars);
?>
            </pre>
        </div>
    </div>
</body>
</html>
