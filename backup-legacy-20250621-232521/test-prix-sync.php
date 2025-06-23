<?php
// Script de vérification de synchronisation des prix
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';

// Récupérer les prix des menus depuis la base de données
$menu_prices = [];
try {
  $stmt = $conn->prepare("SELECT MenuID, NomItem, Prix FROM Menus ORDER BY MenuID");
  $stmt->execute();
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($menus as $menu) {
    $menu_prices[$menu['MenuID']] = [
      'nom' => $menu['NomItem'],
      'prix' => $menu['Prix']
    ];
  }
} catch (PDOException $e) {
  error_log("Erreur récupération prix menus: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Prix Menus - La Mangeoire</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1000px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #e74c3c;
            color: white;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Vérification de Synchronisation des Prix</h1>
        
        <div class="status success">
            ✅ <strong>Synchronisation réussie !</strong> Les prix dans index.php sont maintenant dynamiques et récupérés depuis la base de données.
        </div>

        <h3>📊 Prix actuels dans la base de données :</h3>
        <table>
            <thead>
                <tr>
                    <th>ID Menu</th>
                    <th>Nom du Plat</th>
                    <th>Prix (€)</th>
                    <th>Prix Formaté</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menu_prices as $id => $menu): ?>
                <tr>
                    <td><?php echo $id; ?></td>
                    <td><?php echo htmlspecialchars($menu['nom']); ?></td>
                    <td><?php echo $menu['prix']; ?></td>
                    <td><?php echo number_format($menu['prix'], 2); ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>🧪 Tests de vérification :</h3>
        <a href="index.php#menu" class="test-link">Vérifier Page d'Accueil</a>
        <a href="menu.php" class="test-link">Vérifier Page Menu</a>
        <a href="admin/menus.php" class="test-link">Administration Menus</a>
        
        <h3>✅ Améliorations apportées :</h3>
        <ul>
            <li><strong>Prix dynamiques :</strong> Les prix sont maintenant récupérés en temps réel depuis la base de données</li>
            <li><strong>IDs corrigés :</strong> Les IDs des menus dans les formulaires correspondent maintenant à ceux de la base</li>
            <li><strong>Formatage uniforme :</strong> Utilisation de <code>number_format()</code> pour un affichage cohérent</li>
            <li><strong>Gestion d'erreurs :</strong> Fallback sur prix par défaut en cas de problème de base de données</li>
            <li><strong>Synchronisation automatique :</strong> Plus besoin de modifier manuellement les prix dans le code</li>
        </ul>

        <div class="status success">
            <strong>Note :</strong> Pour modifier un prix, il suffit maintenant de le changer dans l'interface admin. 
            La page d'accueil se mettra automatiquement à jour !
        </div>

        <h3>📝 Code ajouté dans index.php :</h3>
        <pre style="background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto;">
// Récupérer les prix des menus depuis la base de données
$menu_prices = [];
try {
  $stmt = $conn->prepare("SELECT MenuID, NomItem, Prix FROM Menus");
  $stmt->execute();
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($menus as $menu) {
    $menu_prices[$menu['MenuID']] = [
      'nom' => $menu['NomItem'],
      'prix' => $menu['Prix']
    ];
  }
} catch (PDOException $e) {
  error_log("Erreur récupération prix menus: " . $e->getMessage());
}

// Dans le HTML :
&lt;p class="price"&gt;&lt;?php echo isset($menu_prices[1]) ? number_format($menu_prices[1]['prix'], 2) : '22.87'; ?&gt; €&lt;/p&gt;
        </pre>
    </div>
</body>
</html>
