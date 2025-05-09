<?php
// filepath: /Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/diagnostic-panier.php
/**
 * Script de diagnostic pour les problèmes de panier
 * Ce script permet d'identifier et de visualiser les problèmes dans les tables Panier et DetailsCommande
 */

session_start();
require_once 'db_connexion.php';
require_once 'includes/common.php';

$is_admin = isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin';
$is_client = isset($_SESSION['client_id']) && $_SESSION['user_type'] === 'client';

// Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
if (!$is_admin && !$is_client) {
    $_SESSION['message'] = "Veuillez vous connecter pour accéder à cette page.";
    $_SESSION['message_type'] = "error";
    header('Location: connexion-unifiee.php');
    exit;
}

// Structure de la table Panier
$panier_structure = [];
$details_commande_structure = [];
$error_messages = [];

try {
    // Vérifier la structure de la table Panier
    $stmt = $conn->prepare("SHOW COLUMNS FROM Panier");
    $stmt->execute();
    $panier_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier la structure de la table DetailsCommande
    $stmt = $conn->prepare("SHOW COLUMNS FROM DetailsCommande");
    $stmt->execute();
    $details_commande_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_messages[] = "Erreur lors de la vérification de la structure des tables : " . $e->getMessage();
}

// Vérifier les données dans le panier
$panier_items = [];
$problematic_items = [];

if ($is_client) {
    // Vérifier le panier du client connecté
    try {
        $stmt = $conn->prepare("
            SELECT p.*, m.NomItem, m.Prix 
            FROM Panier p
            LEFT JOIN Menus m ON p.MenuID = m.MenuID
            WHERE p.UtilisateurID = ?
        ");
        $stmt->execute([$_SESSION['client_id']]);
        $panier_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Identifier les problèmes potentiels
        foreach ($panier_items as $item) {
            if (empty($item['Quantite']) || $item['Quantite'] <= 0) {
                $problematic_items[] = $item;
            }
            if (empty($item['NomItem'])) {
                $problematic_items[] = $item;
            }
        }
        
    } catch (PDOException $e) {
        $error_messages[] = "Erreur lors de la récupération du panier : " . $e->getMessage();
    }
}

// Rechercher les entrées sans quantité
$sql = "SELECT * FROM Panier WHERE Quantite IS NULL OR Quantite <= 0";
$null_quantity_items = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Si bouton de correction cliqué
if (isset($_POST['fix_panier']) && ($is_admin || $is_client)) {
    try {
        // 1. Ajouter une valeur par défaut à la colonne Quantite si elle n'en a pas
        $conn->exec("ALTER TABLE Panier MODIFY COLUMN Quantite INT(11) NOT NULL DEFAULT 1");
        
        // 2. Mettre à jour les enregistrements avec Quantite NULL ou 0
        $conn->exec("UPDATE Panier SET Quantite = 1 WHERE Quantite IS NULL OR Quantite = 0");
        
        // 3. Supprimer les entrées qui font référence à des menus inexistants
        $conn->exec("DELETE FROM Panier WHERE MenuID NOT IN (SELECT MenuID FROM Menus)");
        
        $_SESSION['message'] = "Correction du panier effectuée avec succès.";
        $_SESSION['message_type'] = "success";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error_messages[] = "Erreur lors de la correction du panier : " . $e->getMessage();
    }
}

// Si ajout de contraintes cliqué (admin uniquement)
if (isset($_POST['add_constraints']) && $is_admin) {
    try {
        // Ajouter une contrainte de clé étrangère entre Panier et Menus
        $conn->exec("
            ALTER TABLE Panier 
            ADD CONSTRAINT fk_panier_menu 
            FOREIGN KEY (MenuID) 
            REFERENCES Menus(MenuID)
            ON DELETE CASCADE
        ");
        
        $_SESSION['message'] = "Contraintes ajoutées avec succès.";
        $_SESSION['message_type'] = "success";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error_messages[] = "Erreur lors de l'ajout des contraintes : " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Panier - La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .diagnostic-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .section-title {
            color: #9E2A2B;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .diagnostic-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .diagnostic-table th, .diagnostic-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .diagnostic-table th {
            background-color: #f5f5f5;
        }
        .problem {
            background-color: #ffdddd;
        }
        .ok {
            background-color: #ddffdd;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #9E2A2B;
            color: white;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Diagnostic du Panier</h1>
        
        <?php if (!empty($error_messages)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($error_messages as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'error' ? 'danger' : 'success'; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <div class="diagnostic-section">
            <h2 class="section-title">Structure de la table Panier</h2>
            <table class="diagnostic-table">
                <thead>
                    <tr>
                        <th>Champ</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Clé</th>
                        <th>Défaut</th>
                        <th>Extra</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($panier_structure as $column): ?>
                        <tr class="<?php echo ($column['Field'] === 'Quantite' && $column['Default'] === null && $column['Null'] === 'NO') ? 'problem' : 'ok'; ?>">
                            <td><?php echo $column['Field']; ?></td>
                            <td><?php echo $column['Type']; ?></td>
                            <td><?php echo $column['Null']; ?></td>
                            <td><?php echo $column['Key']; ?></td>
                            <td><?php echo $column['Default'] !== null ? $column['Default'] : 'NULL'; ?></td>
                            <td><?php echo $column['Extra']; ?></td>
                            <td>
                                <?php if ($column['Field'] === 'Quantite'): ?>
                                    <?php if ($column['Default'] === null && $column['Null'] === 'NO'): ?>
                                        <span class="text-danger">❌ Problème: Champ requis sans valeur par défaut</span>
                                    <?php else: ?>
                                        <span class="text-success">✓ OK</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-success">✓ OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($is_client && !empty($panier_items)): ?>
        <div class="diagnostic-section">
            <h2 class="section-title">Contenu de votre panier</h2>
            <table class="diagnostic-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Menu</th>
                        <th>Nom</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Date d'ajout</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($panier_items as $item): ?>
                        <tr class="<?php echo (empty($item['Quantite']) || $item['Quantite'] <= 0 || empty($item['NomItem'])) ? 'problem' : 'ok'; ?>">
                            <td><?php echo $item['PanierID']; ?></td>
                            <td><?php echo $item['MenuID']; ?></td>
                            <td><?php echo htmlspecialchars($item['NomItem'] ?? 'Non disponible'); ?></td>
                            <td><?php echo number_format($item['Prix'] ?? 0, 0, ',', ' '); ?> XAF</td>
                            <td><?php echo $item['Quantite'] ?? 'NULL'; ?></td>
                            <td><?php echo $item['DateAjout'] ? date('d/m/Y H:i', strtotime($item['DateAjout'])) : 'N/A'; ?></td>
                            <td>
                                <?php if (empty($item['Quantite']) || $item['Quantite'] <= 0): ?>
                                    <span class="text-danger">❌ Quantité manquante ou nulle</span>
                                <?php elseif (empty($item['NomItem'])): ?>
                                    <span class="text-warning">⚠️ Menu introuvable</span>
                                <?php else: ?>
                                    <span class="text-success">✓ OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if ($is_admin && !empty($null_quantity_items)): ?>
        <div class="diagnostic-section">
            <h2 class="section-title">Articles avec quantité manquante dans le panier</h2>
            <table class="diagnostic-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Menu</th>
                        <th>Quantité</th>
                        <th>Date d'ajout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($null_quantity_items as $item): ?>
                        <tr class="problem">
                            <td><?php echo $item['PanierID']; ?></td>
                            <td><?php echo $item['UtilisateurID']; ?></td>
                            <td><?php echo $item['MenuID']; ?></td>
                            <td><?php echo $item['Quantite'] ?? 'NULL'; ?></td>
                            <td><?php echo $item['DateAjout'] ? date('d/m/Y H:i', strtotime($item['DateAjout'])) : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="diagnostic-section">
            <h2 class="section-title">Actions de correction</h2>
            <p>Les actions ci-dessous peuvent vous aider à résoudre les problèmes du panier:</p>
            
            <div class="action-buttons">
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <button type="submit" name="fix_panier" class="btn btn-primary">
                        <i class="bi bi-wrench"></i> Réparer le panier
                    </button>
                </form>
                
                <?php if ($is_admin): ?>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <button type="submit" name="add_constraints" class="btn btn-warning">
                        <i class="bi bi-shield-check"></i> Ajouter des contraintes
                    </button>
                </form>
                <?php endif; ?>
                
                <a href="vider-panier.php?redirect=diagnostic-panier.php" class="btn btn-warning">
                    <i class="bi bi-cart-x"></i> Vider mon panier
                </a>
            </div>
            
            <?php if ($is_admin): ?>
            <div style="margin-top: 20px;">
                <a href="repair_panier_table.php" class="btn btn-primary">
                    <i class="bi bi-database-gear"></i> Exécuter le script avancé de réparation
                </a>
                
                <a href="admin/index.php" class="btn btn-primary" style="margin-left: 10px;">
                    <i class="bi bi-speedometer"></i> Retour à l'administration
                </a>
            </div>
            <?php else: ?>
            <div style="margin-top: 20px;">
                <a href="panier.php" class="btn btn-primary">
                    <i class="bi bi-cart"></i> Retour au panier
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
