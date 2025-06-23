<?php
/**
 * CartManager - Gestionnaire de panier moderne et unifié
 * 
 * Fonctionnalités:
 * - Gestion unifiée panier session/base de données
 * - Migration automatique lors connexion/déconnexion
 * - API REST pour interactions JavaScript
 * - Validation robuste des données
 * 
 * @author Restaurant La Mangeoire
 * @version 2.0
 */

class CartManager {
    private $pdo;
    private $session_key = 'cart_items';
    
    public function __construct($pdo_connection) {
        $this->pdo = $pdo_connection;
        $this->ensureSession();
    }
    
    /**
     * S'assurer que la session est démarrée
     */
    private function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            }
        }
    }
    
    /**
     * Ajouter un article au panier
     * 
     * @param int $menu_id ID de l'article du menu
     * @param int $quantity Quantité à ajouter
     * @return array Résultat de l'opération
     */
    public function addItem($menu_id, $quantity = 1) {
        try {
            // Validation
            $validation = $this->validateMenuItem($menu_id, $quantity);
            if (!$validation['success']) {
                return $validation;
            }
            
            $menu_item = $validation['item'];
            $user_id = $this->getUserId();
            
            if ($user_id) {
                // Utilisateur connecté - base de données
                return $this->addItemToDatabase($user_id, $menu_item, $quantity);
            } else {
                // Utilisateur non connecté - session
                return $this->addItemToSession($menu_item, $quantity);
            }
            
        } catch (Exception $e) {
            error_log("CartManager - Erreur addItem: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'ajout au panier: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Mettre à jour la quantité d'un article
     */
    public function updateItem($menu_id, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->removeItem($menu_id);
            }
            
            $user_id = $this->getUserId();
            
            if ($user_id) {
                return $this->updateItemInDatabase($user_id, $menu_id, $quantity);
            } else {
                return $this->updateItemInSession($menu_id, $quantity);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Supprimer un article du panier
     */
    public function removeItem($menu_id) {
        try {
            $user_id = $this->getUserId();
            
            if ($user_id) {
                return $this->removeItemFromDatabase($user_id, $menu_id);
            } else {
                return $this->removeItemFromSession($menu_id);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtenir tous les articles du panier
     */
    public function getItems() {
        try {
            $user_id = $this->getUserId();
            
            if ($user_id) {
                return $this->getItemsFromDatabase($user_id);
            } else {
                return $this->getItemsFromSession();
            }
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtenir le résumé du panier (total, nombre d'articles)
     */
    public function getSummary() {
        $items = $this->getItems();
        $total_amount = 0;
        $total_items = 0;
        
        foreach ($items as $item) {
            $total_amount += $item['price'] * $item['quantity'];
            $total_items += $item['quantity'];
        }
        
        return [
            'total_amount' => $total_amount,
            'total_items' => $total_items,
            'items_count' => count($items),
            'is_empty' => empty($items)
        ];
    }
    
    /**
     * Vider complètement le panier
     */
    public function clear() {
        try {
            $user_id = $this->getUserId();
            
            if ($user_id) {
                $stmt = $this->pdo->prepare("DELETE FROM Panier WHERE ClientID = ?");
                $stmt->execute([$user_id]);
            }
            
            // Toujours nettoyer la session
            unset($_SESSION[$this->session_key]);
            
            return [
                'success' => true,
                'message' => 'Panier vidé avec succès'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du vidage du panier',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migration du panier lors de la connexion
     */
    public function migrateSessionToDatabase($user_id) {
        try {
            $session_items = $this->getItemsFromSession();
            
            if (empty($session_items)) {
                return ['success' => true, 'migrated' => 0];
            }
            
            $migrated = 0;
            
            foreach ($session_items as $item) {
                // Vérifier si l'article existe déjà en base
                $stmt = $this->pdo->prepare("
                    SELECT PanierID, Quantite 
                    FROM Panier 
                    WHERE ClientID = ? AND MenuID = ?
                ");
                $stmt->execute([$user_id, $item['menu_id']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Additionner les quantités
                    $new_quantity = $existing['Quantite'] + $item['quantity'];
                    $stmt = $this->pdo->prepare("
                        UPDATE Panier 
                        SET Quantite = ?, DateAjout = NOW() 
                        WHERE PanierID = ?
                    ");
                    $stmt->execute([$new_quantity, $existing['PanierID']]);
                } else {
                    // Créer nouvel article
                    $stmt = $this->pdo->prepare("
                        INSERT INTO Panier (ClientID, MenuID, Quantite, DateAjout) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$user_id, $item['menu_id'], $item['quantity']]);
                }
                
                $migrated++;
            }
            
            // Vider le panier session après migration réussie
            unset($_SESSION[$this->session_key]);
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'message' => "Panier restauré: $migrated articles"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la migration du panier',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migration du panier lors de la déconnexion
     */
    public function migrateDatabaseToSession($user_id) {
        try {
            $db_items = $this->getItemsFromDatabase($user_id);
            
            if (!empty($db_items)) {
                $_SESSION[$this->session_key] = $db_items;
            }
            
            return [
                'success' => true,
                'migrated' => count($db_items)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // === MÉTHODES PRIVÉES ===
    
    private function getUserId() {
        return isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;
    }
    
    private function validateMenuItem($menu_id, $quantity) {
        // Validation de base
        if (!is_numeric($menu_id) || $menu_id <= 0) {
            return [
                'success' => false,
                'message' => 'Identifiant d\'article invalide'
            ];
        }
        
        if (!is_numeric($quantity) || $quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Quantité invalide'
            ];
        }
        
        // Vérifier que l'article existe
        $stmt = $this->pdo->prepare("
            SELECT MenuID, NomItem, Prix, Description 
            FROM Menus 
            WHERE MenuID = ?
        ");
        $stmt->execute([$menu_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            return [
                'success' => false,
                'message' => 'Article non trouvé'
            ];
        }
        
        return [
            'success' => true,
            'item' => $item
        ];
    }
    
    private function addItemToDatabase($user_id, $menu_item, $quantity) {
        // Vérifier si l'article existe déjà
        $stmt = $this->pdo->prepare("
            SELECT PanierID, Quantite 
            FROM Panier 
            WHERE ClientID = ? AND MenuID = ?
        ");
        $stmt->execute([$user_id, $menu_item['MenuID']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mettre à jour la quantité
            $new_quantity = $existing['Quantite'] + $quantity;
            $stmt = $this->pdo->prepare("
                UPDATE Panier 
                SET Quantite = ?, DateAjout = NOW() 
                WHERE PanierID = ?
            ");
            $stmt->execute([$new_quantity, $existing['PanierID']]);
        } else {
            // Ajouter nouvel article
            $stmt = $this->pdo->prepare("
                INSERT INTO Panier (ClientID, MenuID, Quantite, DateAjout) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $menu_item['MenuID'], $quantity]);
        }
        
        return [
            'success' => true,
            'message' => $menu_item['NomItem'] . ' ajouté au panier'
        ];
    }
    
    private function addItemToSession($menu_item, $quantity) {
        if (!isset($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = [];
        }
        
        // Vérifier si l'article existe déjà
        $found = false;
        foreach ($_SESSION[$this->session_key] as &$item) {
            if ($item['menu_id'] == $menu_item['MenuID']) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION[$this->session_key][] = [
                'menu_id' => $menu_item['MenuID'],
                'name' => $menu_item['NomItem'],
                'price' => $menu_item['Prix'],
                'description' => $menu_item['Description'] ?? '',
                'image' => 'assets/img/menu/default.png', // Image par défaut
                'quantity' => $quantity
            ];
        }
        
        return [
            'success' => true,
            'message' => $menu_item['NomItem'] . ' ajouté au panier'
        ];
    }
    
    private function updateItemInDatabase($user_id, $menu_id, $quantity) {
        $stmt = $this->pdo->prepare("
            UPDATE Panier 
            SET Quantite = ?, DateAjout = NOW() 
            WHERE ClientID = ? AND MenuID = ?
        ");
        $stmt->execute([$quantity, $user_id, $menu_id]);
        
        return [
            'success' => true,
            'message' => 'Quantité mise à jour'
        ];
    }
    
    private function updateItemInSession($menu_id, $quantity) {
        if (!isset($_SESSION[$this->session_key])) {
            return ['success' => false, 'message' => 'Panier vide'];
        }
        
        foreach ($_SESSION[$this->session_key] as &$item) {
            if ($item['menu_id'] == $menu_id) {
                $item['quantity'] = $quantity;
                return [
                    'success' => true,
                    'message' => 'Quantité mise à jour'
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Article non trouvé'];
    }
    
    private function removeItemFromDatabase($user_id, $menu_id) {
        $stmt = $this->pdo->prepare("
            DELETE FROM Panier 
            WHERE ClientID = ? AND MenuID = ?
        ");
        $stmt->execute([$user_id, $menu_id]);
        
        return [
            'success' => true,
            'message' => 'Article supprimé du panier'
        ];
    }
    
    private function removeItemFromSession($menu_id) {
        if (!isset($_SESSION[$this->session_key])) {
            return ['success' => false, 'message' => 'Panier vide'];
        }
        
        foreach ($_SESSION[$this->session_key] as $key => $item) {
            if ($item['menu_id'] == $menu_id) {
                unset($_SESSION[$this->session_key][$key]);
                $_SESSION[$this->session_key] = array_values($_SESSION[$this->session_key]);
                return [
                    'success' => true,
                    'message' => 'Article supprimé du panier'
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Article non trouvé'];
    }
    
    private function getItemsFromDatabase($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.MenuID as menu_id,
                m.NomItem as name,
                m.Prix as price,
                m.Description as description,
                'assets/img/menu/default.png' as image,
                p.Quantite as quantity,
                p.DateAjout as added_at
            FROM Panier p
            JOIN Menus m ON p.MenuID = m.MenuID
            WHERE p.ClientID = ?
            ORDER BY p.DateAjout DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getItemsFromSession() {
        return isset($_SESSION[$this->session_key]) ? $_SESSION[$this->session_key] : [];
    }
}
?>
