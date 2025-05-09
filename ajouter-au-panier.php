<?php
session_start();
require_once 'includes/common.php';
require_once 'db_connexion.php';

// Si l'utilisateur n'est pas connecté, on utilise un panier de session temporaire
// Sinon, on utilise la table Panier de la base de données
$item_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
$quantite = isset($_POST['quantite']) ? intval($_POST['quantite']) : 1;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Vérifier si l'item existe
if ($item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM Menus WHERE MenuID = ?");
    $stmt->execute([$item_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($menu) {
        // Si l'utilisateur est connecté, ajouter au panier dans la BDD
        if (isset($_SESSION['client_id'])) {
            $client_id = $_SESSION['client_id'];
            
            if ($action == 'add') {
                // Vérifier si le produit est déjà dans le panier
                $stmt = $conn->prepare("SELECT * FROM Panier WHERE UtilisateurID = ? AND MenuID = ?");
                $stmt->execute([$client_id, $item_id]);
                $item_panier = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($item_panier) {
                    // Mettre à jour la quantité
                    $nouvelle_quantite = $item_panier['Quantite'] + $quantite;
                    $stmt = $conn->prepare("UPDATE Panier SET Quantite = ? WHERE PanierID = ?");
                    $stmt->execute([$nouvelle_quantite, $item_panier['PanierID']]);
                } else {
                    // Ajouter le produit au panier
                    $stmt = $conn->prepare("INSERT INTO Panier (UtilisateurID, MenuID, Quantite) VALUES (?, ?, ?)");
                    $stmt->execute([$client_id, $item_id, $quantite]);
                }
                
                $_SESSION['message'] = "Le plat a été ajouté à votre panier.";
                $_SESSION['message_type'] = "success";
            } elseif ($action == 'remove') {
                // Supprimer du panier
                $stmt = $conn->prepare("DELETE FROM Panier WHERE UtilisateurID = ? AND MenuID = ?");
                $stmt->execute([$client_id, $item_id]);
                
                $_SESSION['message'] = "Le plat a été retiré de votre panier.";
                $_SESSION['message_type'] = "success";
            } elseif ($action == 'update') {
                // Mettre à jour la quantité
                if ($quantite > 0) {
                    $stmt = $conn->prepare("UPDATE Panier SET Quantite = ? WHERE UtilisateurID = ? AND MenuID = ?");
                    $stmt->execute([$quantite, $client_id, $item_id]);
                    
                    $_SESSION['message'] = "La quantité a été mise à jour.";
                    $_SESSION['message_type'] = "success";
                } else {
                    // Si la quantité est 0 ou négative, supprimer l'article
                    $stmt = $conn->prepare("DELETE FROM Panier WHERE UtilisateurID = ? AND MenuID = ?");
                    $stmt->execute([$client_id, $item_id]);
                    
                    $_SESSION['message'] = "Le plat a été retiré de votre panier.";
                    $_SESSION['message_type'] = "success";
                }
            }
        } else {
            // Panier en session pour les utilisateurs non connectés
            if (!isset($_SESSION['panier'])) {
                $_SESSION['panier'] = [];
            }
            
            if ($action == 'add') {
                // Vérifier si le produit est déjà dans le panier
                $found = false;
                foreach ($_SESSION['panier'] as $key => $item) {
                    if ($item['MenuID'] == $item_id) {
                        $_SESSION['panier'][$key]['Quantite'] += $quantite;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    // Ajouter le produit au panier
                    $_SESSION['panier'][] = [
                        'MenuID' => $item_id,
                        'NomItem' => $menu['NomItem'],
                        'Prix' => $menu['Prix'],
                        'Quantite' => $quantite
                    ];
                }
                
                $_SESSION['message'] = "Le plat a été ajouté à votre panier.";
                $_SESSION['message_type'] = "success";
            } elseif ($action == 'remove') {
                // Supprimer du panier
                foreach ($_SESSION['panier'] as $key => $item) {
                    if ($item['MenuID'] == $item_id) {
                        unset($_SESSION['panier'][$key]);
                        break;
                    }
                }
                
                // Réindexer le tableau
                $_SESSION['panier'] = array_values($_SESSION['panier']);
                
                $_SESSION['message'] = "Le plat a été retiré de votre panier.";
                $_SESSION['message_type'] = "success";
            } elseif ($action == 'update') {
                // Mettre à jour la quantité
                if ($quantite > 0) {
                    foreach ($_SESSION['panier'] as $key => $item) {
                        if ($item['MenuID'] == $item_id) {
                            $_SESSION['panier'][$key]['Quantite'] = $quantite;
                            break;
                        }
                    }
                    
                    $_SESSION['message'] = "La quantité a été mise à jour.";
                    $_SESSION['message_type'] = "success";
                } else {
                    // Si la quantité est 0 ou négative, supprimer l'article
                    foreach ($_SESSION['panier'] as $key => $item) {
                        if ($item['MenuID'] == $item_id) {
                            unset($_SESSION['panier'][$key]);
                            break;
                        }
                    }
                    
                    // Réindexer le tableau
                    $_SESSION['panier'] = array_values($_SESSION['panier']);
                    
                    $_SESSION['message'] = "Le plat a été retiré de votre panier.";
                    $_SESSION['message_type'] = "success";
                }
            }
        }
    } else {
        $_SESSION['message'] = "Ce plat n'existe pas.";
        $_SESSION['message_type'] = "error";
    }
}

// Rediriger vers la page précédente ou vers le panier
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'panier.php');
header("Location: $redirect");
exit;
