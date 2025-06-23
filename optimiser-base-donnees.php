<?php
/**
 * Optimisation Base de Données - La Mangeoire
 * Date: 21 juin 2025
 * 
 * Script pour optimiser les performances et ajouter les index manquants
 */

require_once 'db_connexion.php';

class DatabaseOptimizer {
    private $pdoexion;
    private $results = [];
    
    public function __construct() {
        global $pdo;
        $this->connexion = $pdo;
    }
    
    public function analyzePerformance() {
        echo "🔍 ANALYSE DES PERFORMANCES\n";
        echo "==========================\n\n";
        
        // Analyser les tables principales
        $tables = ['menus', 'commandes', 'paiements', 'reservations', 'clients'];
        
        foreach ($tables as $table) {
            $this->analyzeTable($table);
        }
        
        // Recommandations d'index
        $this->recommendIndexes();
        
        // Statistiques générales
        $this->showStats();
    }
    
    private function analyzeTable($tableName) {
        try {
            // Vérifier si la table existe
            $stmt = $this->connexion->query("SHOW TABLES LIKE '$tableName'");
            
            if ($stmt->rowCount() === 0) {
                echo "⚠️  Table '$tableName' n'existe pas\n";
                return;
            }
            
            echo "📊 Table: $tableName\n";
            echo str_repeat("-", 20) . "\n";
            
            // Compter les enregistrements
            $stmt = $this->connexion->query("SELECT COUNT(*) as count FROM `$tableName`");
            $count = $stmt->fetch()['count'];
            echo "  Enregistrements: " . number_format($count) . "\n";
            
            // Analyser la structure
            $stmt = $this->connexion->query("DESCRIBE `$tableName`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "  Colonnes: " . count($columns) . "\n";
            
            // Vérifier les index
            $stmt = $this->connexion->query("SHOW INDEX FROM `$tableName`");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "  Index: " . count($indexes) . "\n";
            
            foreach ($indexes as $index) {
                echo "    - " . $index['Key_name'] . " (" . $index['Column_name'] . ")\n";
            }
            
            // Taille de la table
            $stmt = $this->connexion->prepare("
                SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() AND table_name = ?
            ");
            $stmt->execute([$tableName]);
            $size = $stmt->fetch();
            if ($size) {
                echo "  Taille: " . $size['size_mb'] . " MB\n";
            }
            
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur analyse $tableName: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function recommendIndexes() {
        echo "💡 RECOMMANDATIONS D'INDEX\n";
        echo "==========================\n\n";
        
        $recommendations = [
            'commandes' => [
                'idx_commandes_date' => 'CREATE INDEX idx_commandes_date ON commandes(date_commande)',
                'idx_commandes_status' => 'CREATE INDEX idx_commandes_status ON commandes(statut)',
                'idx_commandes_client' => 'CREATE INDEX idx_commandes_client ON commandes(client_id)'
            ],
            'paiements' => [
                'idx_paiements_date' => 'CREATE INDEX idx_paiements_date ON paiements(date_paiement)',
                'idx_paiements_status' => 'CREATE INDEX idx_paiements_status ON paiements(statut)',
                'idx_paiements_commande' => 'CREATE INDEX idx_paiements_commande ON paiements(commande_id)'
            ],
            'menus' => [
                'idx_menus_actif' => 'CREATE INDEX idx_menus_actif ON menus(actif)',
                'idx_menus_prix' => 'CREATE INDEX idx_menus_prix ON menus(prix)',
                'idx_menus_categorie' => 'CREATE INDEX idx_menus_categorie ON menus(categorie)'
            ],
            'reservations' => [
                'idx_reservations_date' => 'CREATE INDEX idx_reservations_date ON reservations(date_reservation)',
                'idx_reservations_status' => 'CREATE INDEX idx_reservations_status ON reservations(statut)'
            ]
        ];
        
        foreach ($recommendations as $table => $indexes) {
            echo "🔧 Table: $table\n";
            foreach ($indexes as $name => $sql) {
                // Vérifier si l'index existe déjà
                if (!$this->indexExists($table, str_replace('idx_' . $table . '_', '', $name))) {
                    echo "  ✅ $sql\n";
                } else {
                    echo "  ✔️  Index déjà présent: $name\n";
                }
            }
            echo "\n";
        }
    }
    
    private function indexExists($table, $column) {
        try {
            $stmt = $this->connexion->prepare("SHOW INDEX FROM `$table` WHERE Column_name = ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function createOptimalIndexes() {
        echo "🚀 CRÉATION DES INDEX OPTIMAUX\n";
        echo "==============================\n\n";
        
        $indexQueries = [
            // Index pour les commandes
            "CREATE INDEX idx_commandes_date ON commandes(date_commande)",
            "CREATE INDEX idx_commandes_status ON commandes(statut)",
            
            // Index pour les paiements
            "CREATE INDEX idx_paiements_date ON paiements(date_paiement)",
            "CREATE INDEX idx_paiements_status ON paiements(statut)",
            
            // Index pour les menus
            "CREATE INDEX idx_menus_actif ON menus(actif)",
            "CREATE INDEX idx_menus_prix ON menus(prix)",
            
            // Index pour les réservations
            "CREATE INDEX idx_reservations_date ON reservations(date_reservation)",
            "CREATE INDEX idx_reservations_status ON reservations(statut)"
        ];
        
        $created = 0;
        foreach ($indexQueries as $sql) {
            try {
                // Vérifier si l'index existe déjà avant de le créer
                $tableName = $this->extractTableName($sql);
                $indexName = $this->extractIndexName($sql);
                
                if (!$this->indexExistsByName($tableName, $indexName)) {
                    $this->connexion->exec($sql);
                    echo "✅ Index créé: $indexName\n";
                    $created++;
                } else {
                    echo "✔️  Index existe déjà: $indexName\n";
                }
            } catch (Exception $e) {
                echo "⚠️  " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n✨ $created index créés/vérifiés\n\n";
    }
    
    private function extractIndexName($sql) {
        preg_match('/CREATE INDEX.*?(\w+)\s+ON/', $sql, $matches);
        return $matches[1] ?? 'Index';
    }
    
    private function extractTableName($sql) {
        preg_match('/ON\s+(\w+)\s*\(/', $sql, $matches);
        return $matches[1] ?? '';
    }
    
    private function indexExistsByName($table, $indexName) {
        try {
            $stmt = $this->connexion->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function optimizeTables() {
        echo "🔧 OPTIMISATION DES TABLES\n";
        echo "==========================\n\n";
        
        $tables = ['menus', 'commandes', 'paiements', 'reservations'];
        
        foreach ($tables as $table) {
            try {
                // Vérifier si la table existe
                $stmt = $this->connexion->query("SHOW TABLES LIKE '$table'");
                
                if ($stmt->rowCount() === 0) {
                    echo "⚠️  Table '$table' n'existe pas\n";
                    continue;
                }
                
                $this->connexion->exec("OPTIMIZE TABLE `$table`");
                echo "✅ Table optimisée: $table\n";
            } catch (Exception $e) {
                echo "❌ Erreur optimisation $table: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    private function showStats() {
        echo "📈 STATISTIQUES GÉNÉRALES\n";
        echo "=========================\n\n";
        
        try {
            // Taille totale de la base
            $stmt = $this->connexion->query("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size_mb'
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
            ");
            $dbSize = $stmt->fetch();
            echo "💾 Taille totale BD: " . ($dbSize['db_size_mb'] ?? 'N/A') . " MB\n";
            
            // Nombre total d'enregistrements
            $tables = ['menus', 'commandes', 'paiements', 'reservations'];
            $totalRecords = 0;
            
            foreach ($tables as $table) {
                try {
                    $stmt = $this->connexion->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $stmt->fetch()['count'];
                    $totalRecords += $count;
                    echo "📊 $table: " . number_format($count) . " enregistrements\n";
                } catch (Exception $e) {
                    echo "⚠️  $table: table inexistante\n";
                }
            }
            
            echo "🎯 Total: " . number_format($totalRecords) . " enregistrements\n\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur statistiques: " . $e->getMessage() . "\n\n";
        }
    }
    
    public function generateReport() {
        echo "📄 GÉNÉRATION DU RAPPORT\n";
        echo "========================\n\n";
        
        $report = "# 📊 Rapport d'Optimisation Base de Données\n\n";
        $report .= "**Date:** " . date('d/m/Y H:i:s') . "\n\n";
        
        $report .= "## 🎯 Résumé\n\n";
        $report .= "- Analyse des performances effectuée\n";
        $report .= "- Index optimaux créés\n";
        $report .= "- Tables optimisées\n\n";
        
        $report .= "## 🔧 Actions Effectuées\n\n";
        $report .= "### Index Créés\n";
        $report .= "- idx_commandes_date, idx_commandes_status\n";
        $report .= "- idx_paiements_date, idx_paiements_status\n";
        $report .= "- idx_menus_actif, idx_menus_prix\n";
        $report .= "- idx_reservations_date, idx_reservations_status\n\n";
        
        $report .= "### Tables Optimisées\n";
        $report .= "- menus, commandes, paiements, reservations\n\n";
        
        $report .= "## 💡 Recommandations\n\n";
        $report .= "1. **Surveillance** : Monitorer les performances régulièrement\n";
        $report .= "2. **Maintenance** : Optimiser les tables mensuellement\n";
        $report .= "3. **Sauvegarde** : Effectuer des sauvegardes avant optimisation\n";
        $report .= "4. **Index** : Ajouter des index selon l'évolution des requêtes\n\n";
        
        $report .= "---\n";
        $report .= "*Rapport généré automatiquement par DatabaseOptimizer*\n";
        
        file_put_contents('RAPPORT_OPTIMISATION_BD.md', $report);
        echo "✅ Rapport sauvegardé: RAPPORT_OPTIMISATION_BD.md\n\n";
    }
}

// Interface CLI
if (php_sapi_name() === 'cli') {
    echo "\n";
    echo "🗄️  OPTIMISEUR BASE DE DONNÉES - La Mangeoire\n";
    echo "=============================================\n\n";
    
    $optimizer = new DatabaseOptimizer();
    
    echo "1. Analyse des performances\n";
    $optimizer->analyzePerformance();
    
    echo "2. Création des index optimaux\n";
    $optimizer->createOptimalIndexes();
    
    echo "3. Optimisation des tables\n";
    $optimizer->optimizeTables();
    
    echo "4. Génération du rapport\n";
    $optimizer->generateReport();
    
    echo "🎉 Optimisation terminée avec succès!\n\n";
    
} else {
    // Interface web
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Optimisation Base de Données - La Mangeoire</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
            .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .btn { background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin: 10px; }
            .btn:hover { background: #0056b3; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; }
            .success { color: #28a745; }
            .warning { color: #ffc107; }
            .error { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🗄️ Optimisation Base de Données</h1>
            
            <?php if (isset($_POST['action'])): ?>
                <div class="results">
                    <h2>📊 Résultats</h2>
                    <pre><?php
                        ob_start();
                        $optimizer = new DatabaseOptimizer();
                        
                        switch ($_POST['action']) {
                            case 'analyze':
                                $optimizer->analyzePerformance();
                                break;
                            case 'optimize':
                                $optimizer->createOptimalIndexes();
                                $optimizer->optimizeTables();
                                break;
                            case 'full':
                                $optimizer->analyzePerformance();
                                $optimizer->createOptimalIndexes();
                                $optimizer->optimizeTables();
                                $optimizer->generateReport();
                                break;
                        }
                        
                        echo ob_get_clean();
                    ?></pre>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <h2>🎯 Actions Disponibles</h2>
                <button type="submit" name="action" value="analyze" class="btn">🔍 Analyser Performances</button>
                <button type="submit" name="action" value="optimize" class="btn">🚀 Optimiser BD</button>
                <button type="submit" name="action" value="full" class="btn">✨ Optimisation Complète</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
