<?php
session_start();
require_once 'db_connexion.php';
// Vérifiez que $pdo est bien un objet mysqli
if (!($pdo instanceof mysqli)) {
    die("La connexion à la base de données n'est pas valide. Vérifiez db_connexion.php pour utiliser mysqli.");
}

// Vérifier si l'utilisateur est connecté en tant que client
if (!isset($_SESSION['client_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: connexion-unifiee.php");
    exit;
}

$client_id = $_SESSION['client_id'];
$commande_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Vérifier si la commande existe et appartient au client
$check_query = "SELECT * FROM commandes WHERE id = ? AND client_id = ? AND statut = 'En attente'";
$check_stmt = $pdo->prepare($check_query);
if (!$check_stmt) {
    die("Erreur de préparation de la requête : " . $pdo->error);
}
$check_stmt->bind_param("ii", $commande_id, $client_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    header("Location: mon-compte.php");
    exit;
}

$commande = $check_result->fetch_assoc();

// Récupérer les détails de la commande
$details_query = "SELECT d.*, m.nom AS menu_nom, m.prix AS menu_prix 
                 FROM details_commande d 
                 JOIN menus m ON d.menu_id = m.id 
                 WHERE d.commande_id = ?";
$details_stmt = $pdo->prepare($details_query);
$details_stmt->bind_param("i", $commande_id);
$details_stmt->execute();
$details_result = $details_stmt->get_result();

// Récupérer tous les menus disponibles
$menus_query = "SELECT * FROM menus WHERE disponible = 1";
$menus_stmt = $pdo->prepare($menus_query);
$menus_stmt->execute();
$menus_result = $menus_stmt->get_result();
$menus = [];
while ($menu = $menus_result->fetch_assoc()) {
    $menus[$menu['id']] = $menu;
}

// Traitement de la mise à jour de la commande
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_commande'])) {
        // Commencer une transaction
        $pdo->begin_transaction();
        
        try {
            // Supprimer tous les détails actuels
            $delete_details = "DELETE FROM details_commande WHERE commande_id = ?";
            $delete_stmt = $pdo->prepare($delete_details);
            $delete_stmt->bind_param("i", $commande_id);
            $delete_stmt->execute();
            
            // Insérer les nouveaux détails
            $montant_total = 0;
            
            if (isset($_POST['menu_id']) && isset($_POST['quantite'])) {
                $insert_detail = "INSERT INTO details_commande (commande_id, menu_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
                $insert_stmt = $pdo->prepare($insert_detail);
                
                for ($i = 0; $i < count($_POST['menu_id']); $i++) {
                    $menu_id = $_POST['menu_id'][$i];
                    $quantite = $_POST['quantite'][$i];
                    
                    if ($menu_id > 0 && $quantite > 0) {
                        $prix_unitaire = $menus[$menu_id]['prix'];
                        $montant_total += ($prix_unitaire * $quantite);
                        
                        $insert_stmt->bind_param("iiid", $commande_id, $menu_id, $quantite, $prix_unitaire);
                        $insert_stmt->execute();
                    }
                }
            }
            
            // Mettre à jour le montant total de la commande
            $update_commande = "UPDATE commandes SET montant_total = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_commande);
            $update_stmt->bind_param("di", $montant_total, $commande_id);
            $update_stmt->execute();
            
            // Valider la transaction
            $pdo->commit();
            
            $success_message = "Votre commande a été mise à jour avec succès.";
            
            // Recharger les détails de la commande
            $details_stmt->execute();
            $details_result = $details_stmt->get_result();
            
            // Recharger la commande
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $commande = $check_result->fetch_assoc();
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollback();
            $error_message = "Erreur lors de la mise à jour de la commande : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier ma commande - Restaurant La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .form-row select, .form-row input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-row select {
            flex: 3;
        }
        .form-row input[type="number"] {
            flex: 1;
            width: 80px;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-submit {
            padding: 10px 15px;
            background-color: #9E2A2B;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
    <?php include 'includes/common.php'; ?>
    
    <div class="container">
        <h1>Modifier ma commande #<?php echo $commande_id; ?></h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="commande-form">
            <div id="menu-items">
                <?php 
                $i = 0;
                while ($detail = $details_result->fetch_assoc()): 
                ?>
                <div class="form-row" id="row-<?php echo $i; ?>">
                    <select name="menu_id[]" class="menu-select" required>
                        <option value="">Sélectionnez un plat</option>
                        <?php foreach ($menus as $menu): ?>
                            <option value="<?php echo $menu['id']; ?>" data-price="<?php echo $menu['prix']; ?>" <?php echo ($menu['id'] == $detail['menu_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($menu['nom'] . ' - ' . number_format($menu['prix'], 2, ',', ' ') . ' €'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantite[]" min="1" value="<?php echo $detail['quantite']; ?>" class="quantite" required>
                    <?php if ($i > 0): ?>
                        <button type="button" class="btn-remove" onclick="removeRow(<?php echo $i; ?>)">Supprimer</button>
                    <?php endif; ?>
                </div>
                <?php 
                $i++;
                endwhile; 
                
                // Si aucun détail n'a été trouvé, afficher une ligne vide
                if ($details_result->num_rows == 0):
                ?>
                <div class="form-row" id="row-0">
                    <select name="menu_id[]" class="menu-select" required>
                        <option value="">Sélectionnez un plat</option>
                        <?php foreach ($menus as $menu): ?>
                            <option value="<?php echo $menu['id']; ?>" data-price="<?php echo $menu['prix']; ?>">
                                <?php echo htmlspecialchars($menu['nom'] . ' - ' . number_format($menu['prix'], 2, ',', ' ') . ' €'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantite[]" min="1" value="1" class="quantite" required>
                </div>
                <?php endif; ?>
            </div>
            
            <button type="button" id="add-item" class="btn-add">Ajouter un plat</button>
            
            <div class="total">
                Total: <span id="total-amount"><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?></span> €
            </div>
            
            <button type="submit" name="update_commande" class="btn-submit">Mettre à jour ma commande</button>
            <a href="mon-compte.php" class="btn-submit btn-cancel">Annuler</a>
        </form>
    </div>
    
    <script>
        let rowIndex = <?php echo $i; ?>;
        
        document.getElementById('add-item').addEventListener('click', function() {
            const menuItems = document.getElementById('menu-items');
            const newRow = document.createElement('div');
            newRow.className = 'form-row';
            newRow.id = 'row-' + rowIndex;
            
            const selectOptions = document.querySelector('.menu-select').innerHTML;
            
            newRow.innerHTML = `
                <select name="menu_id[]" class="menu-select" required>
                    ${selectOptions}
                </select>
                <input type="number" name="quantite[]" min="1" value="1" class="quantite" required>
                <button type="button" class="btn-remove" onclick="removeRow(${rowIndex})">Supprimer</button>
            `;
            
            menuItems.appendChild(newRow);
            rowIndex++;
            
            // Mettre à jour le calcul du total
            updateTotal();
        });
        
        function removeRow(index) {
            const row = document.getElementById('row-' + index);
            row.parentNode.removeChild(row);
            updateTotal();
        }
        
        // Calculer le total de la commande
        function updateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.form-row');
            
            rows.forEach(row => {
                const select = row.querySelector('.menu-select');
                const quantite = row.querySelector('.quantite');
                
                if (select.selectedIndex > 0) {
                    const selectedOption = select.options[select.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price'));
                    const qty = parseInt(quantite.value);
                    
                    if (!isNaN(price) && !isNaN(qty)) {
                        total += price * qty;
                    }
                }
            });
            
            document.getElementById('total-amount').textContent = total.toFixed(2).replace('.', ',');
        }
        
        // Ajouter des écouteurs d'événements pour mettre à jour le total
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('menu-select') || event.target.classList.contains('quantite')) {
                updateTotal();
            }
        });
        
        // Calculer le total initial
        updateTotal();
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
