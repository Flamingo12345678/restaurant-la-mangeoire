<?php
// Page de maintenance pour le panier et correction du problème "Field 'Quantite' doesn't have a default value"
require_once __DIR__ . '/includes/common.php';
require_admin();
require_once 'db_connexion.php';

$message = '';
$message_type = '';

// Traitement des actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'add_default':
            try {
                // Vérifier si la colonne Quantite existe déjà dans la table Panier
                $stmt = $conn->prepare("SHOW COLUMNS FROM Panier LIKE 'Quantite'");
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // La colonne existe, nous allons modifier sa définition pour ajouter une valeur par défaut
                    $conn->exec("ALTER TABLE Panier MODIFY COLUMN Quantite INT NOT NULL DEFAULT 1");
                    $message = "La colonne 'Quantite' de la table 'Panier' a été modifiée avec succès pour avoir une valeur par défaut de 1.";
                    $message_type = "success";
                } else {
                    $message = "La colonne 'Quantite' n'existe pas dans la table 'Panier'.";
                    $message_type = "error";
                }
            } catch (PDOException $e) {
                $message = "Une erreur s'est produite lors de la modification de la structure de la table : " . $e->getMessage();
                $message_type = "error";
            }
            break;
            
        case 'fix_data':
            try {
                $conn->beginTransaction();
                
                // Étape 1: Vérifier les entrées avec Quantite NULL ou 0 et les corriger
                $stmt = $conn->prepare("UPDATE Panier SET Quantite = 1 WHERE Quantite IS NULL OR Quantite = 0");
                $stmt->execute();
                $null_records_fixed = $stmt->rowCount();
                
                $conn->commit();
                
                $message = $null_records_fixed . " enregistrement(s) avec Quantite nulle ou égale à zéro ont été corrigés.";
                $message_type = "success";
                
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $message = "Une erreur s'est produite lors de la correction des données : " . $e->getMessage();
                $message_type = "error";
            }
            break;
            
        case 'check_structure':
            // Cette action est traitée directement dans l'affichage
            break;
            
        default:
            $message = "Action non reconnue.";
            $message_type = "error";
    }
}

// Fonction pour afficher la structure d'une table
function display_table_structure($conn, $table_name) {
    $html = '';
    
    try {
        $stmt = $conn->prepare("DESCRIBE " . $table_name);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html .= "<table class='table table-bordered table-striped'>";
        $html .= "<thead class='table-light'>";
        $html .= "<tr>";
        $html .= "<th>Colonne</th>";
        $html .= "<th>Type</th>";
        $html .= "<th>Null</th>";
        $html .= "<th>Clé</th>";
        $html .= "<th>Défaut</th>";
        $html .= "<th>Extra</th>";
        $html .= "</tr>";
        $html .= "</thead>";
        $html .= "<tbody>";
        
        foreach ($columns as $column) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($column['Field']) . "</td>";
            $html .= "<td>" . htmlspecialchars($column['Type']) . "</td>";
            $html .= "<td>" . htmlspecialchars($column['Null']) . "</td>";
            $html .= "<td>" . htmlspecialchars($column['Key']) . "</td>";
            $html .= "<td>" . (is_null($column['Default']) ? '<em>NULL</em>' : htmlspecialchars($column['Default'])) . "</td>";
            $html .= "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            $html .= "</tr>";
        }
        
        $html .= "</tbody>";
        $html .= "</table>";
    } catch (PDOException $e) {
        $html .= "<div class='alert alert-danger'>Erreur lors de la récupération de la structure de la table : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    return $html;
}

// Fonction pour compter les entrées problématiques
function count_problematic_entries($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Panier WHERE Quantite IS NULL OR Quantite <= 0");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 'Erreur : ' . $e->getMessage();
    }
}

// Fonction pour compter le nombre total d'entrées
function count_total_entries($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Panier");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 'Erreur : ' . $e->getMessage();
    }
}

// Vérifier si la valeur par défaut est définie
function check_default_value($conn) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM Panier LIKE 'Quantite'");
        $stmt->execute();
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column && $column['Default'] !== null) {
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

$has_default = check_default_value($conn);
$total_entries = count_total_entries($conn);
$problematic_entries = count_problematic_entries($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance du Panier - La Mangeoire</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .maintenance-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .action-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .action-card-header {
            padding: 12px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        .action-card-body {
            padding: 20px;
        }
        .status-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-ok {
            background-color: #198754;
        }
        .status-warning {
            background-color: #ffc107;
        }
        .status-error {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <h1 class="mb-4">Maintenance du Panier</h1>
        
        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Statistiques du Panier</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre total d'enregistrements :</strong> <?php echo $total_entries; ?></p>
                        <p>
                            <strong>Enregistrements problématiques :</strong> 
                            <?php if ($problematic_entries > 0) : ?>
                                <span class="text-danger"><?php echo $problematic_entries; ?></span>
                            <?php else : ?>
                                <span class="text-success">0</span>
                            <?php endif; ?>
                        </p>
                        <p>
                            <strong>Valeur par défaut pour Quantite :</strong> 
                            <?php if ($has_default) : ?>
                                <span class="text-success">Définie (1)</span>
                            <?php else : ?>
                                <span class="text-danger">Non définie</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Actions recommandées</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$has_default) : ?>
                            <p><i class="bi bi-exclamation-triangle-fill text-warning"></i> <strong>Prioritaire :</strong> Ajouter une valeur par défaut à la colonne Quantite</p>
                        <?php endif; ?>
                        
                        <?php if ($problematic_entries > 0) : ?>
                            <p><i class="bi bi-exclamation-triangle-fill text-warning"></i> <strong>Prioritaire :</strong> Corriger les enregistrements avec Quantite nulle ou invalide</p>
                        <?php endif; ?>
                        
                        <?php if ($has_default && $problematic_entries == 0) : ?>
                            <p><i class="bi bi-check-circle-fill text-success"></i> <strong>Aucune action requise :</strong> Le système est correctement configuré.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="status-indicator <?php echo $has_default ? 'status-ok' : 'status-error'; ?>"></span>
                    Ajouter une valeur par défaut à la colonne Quantite
                </h5>
                <a href="?action=add_default" class="btn btn-primary <?php echo $has_default ? 'disabled' : ''; ?>">Exécuter</a>
            </div>
            <div class="action-card-body">
                <p>Cette action modifie la structure de la table Panier pour ajouter une valeur par défaut (1) à la colonne Quantite. Cela empêchera l'erreur "Field 'Quantite' doesn't have a default value" lors de l'ajout de nouveaux articles au panier.</p>
                
                <?php if ($has_default) : ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i> La colonne Quantite possède déjà une valeur par défaut.
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> La colonne Quantite ne possède pas de valeur par défaut, ce qui peut causer des erreurs.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="status-indicator <?php echo $problematic_entries == 0 ? 'status-ok' : 'status-error'; ?>"></span>
                    Corriger les données existantes
                </h5>
                <a href="?action=fix_data" class="btn btn-primary <?php echo $problematic_entries == 0 ? 'disabled' : ''; ?>">Exécuter</a>
            </div>
            <div class="action-card-body">
                <p>Cette action recherche et corrige tous les enregistrements dans la table Panier où la valeur de Quantite est nulle ou égale à zéro, en leur attribuant la valeur 1.</p>
                
                <?php if ($problematic_entries == 0) : ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i> Aucun enregistrement problématique détecté.
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $problematic_entries; ?> enregistrement(s) avec des valeurs problématiques détectés.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="status-indicator status-ok"></span>
                    Vérifier la structure de la table Panier
                </h5>
                <a data-bs-toggle="collapse" href="#structureCollapse" role="button" aria-expanded="false" aria-controls="structureCollapse" class="btn btn-secondary">Afficher</a>
            </div>
            <div class="collapse" id="structureCollapse">
                <div class="action-card-body">
                    <h6>Structure de la table Panier</h6>
                    <?php echo display_table_structure($conn, 'Panier'); ?>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
            <a href="panier.php" class="btn btn-primary">Voir mon panier <i class="bi bi-cart"></i></a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
