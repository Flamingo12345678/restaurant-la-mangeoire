<?php
// Test de navigation et de liens
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Navigation - La Mangeoire</title>
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
        .test-link {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            margin: 10px;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .test-link:hover {
            background-color: #c0392b;
            color: white;
            text-decoration: none;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test de Navigation - La Mangeoire</h1>
        
        <div class="status">
            ✅ <strong>Corrections appliquées :</strong>
            <ul>
                <li>Liens admin : ../index.html → ../index.php</li>
                <li>Navigation principale : admin/login.php → connexion-unifiee.php</li>
                <li>Formulaire de réservation : index.html → index.php</li>
            </ul>
        </div>

        <h3>Tests à effectuer :</h3>
        
        <h4>🌐 Navigation publique</h4>
        <a href="index.php" class="test-link">Accueil (index.php)</a>
        <a href="connexion-unifiee.php" class="test-link">Connexion Unifiée</a>
        <a href="panier.php" class="test-link">Panier</a>
        
        <h4>👑 Interface Admin</h4>
        <a href="admin/index.php" class="test-link">Admin Dashboard</a>
        <a href="admin/login.php" class="test-link">Admin Login</a>
        
        <h4>📝 Formulaires</h4>
        <a href="forms/book-a-table.php" class="test-link">Réserver une table</a>
        
        <h3>🎯 Scénario de test :</h3>
        <ol>
            <li><strong>Accès admin :</strong> Connectez-vous à l'interface admin</li>
            <li><strong>Navigation :</strong> Cliquez sur "Retour au site" dans la sidebar admin</li>
            <li><strong>Vérification :</strong> Vous devriez arriver sur index.php (page d'accueil PHP dynamique)</li>
            <li><strong>Navigation publique :</strong> Testez le lien "Connexion" dans la nav principale</li>
            <li><strong>Formulaire :</strong> Testez une réservation et vérifiez la redirection finale</li>
        </ol>
        
        <div class="status">
            <strong>Note :</strong> Si vous voyez encore des redirections vers des fichiers HTML, 
            vérifiez la configuration de votre serveur web (Apache/Nginx) et le fichier .htaccess 
            s'il existe.
        </div>
        
        <h3>📊 État actuel des sessions :</h3>
        <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px;">
<?php
if (!empty($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'user_') === 0 || strpos($key, 'client_') === 0 || strpos($key, 'admin_') === 0) {
            echo "$key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
        }
    }
} else {
    echo "Aucune session active\n";
}
?>
        </pre>
    </div>
</body>
</html>
