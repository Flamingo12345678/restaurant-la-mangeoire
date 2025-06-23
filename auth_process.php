<?php
// Ce script traite les actions d'authentification depuis auth_check.php
session_start();
require_once 'db_connexion.php';

function debug_log($message) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Réinitialisation du mot de passe administrateur
if (isset($_POST['reset_password'])) {
    $admin_id = $_POST['admin_id'];
    $new_password = "admin123";
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE Administrateurs SET MotDePasse = ? WHERE AdminID = ?");
        $result = $stmt->execute([$hashed_password, $admin_id]);
        
        if ($result) {
            debug_log("Mot de passe administrateur réinitialisé avec succès");
            header("Location: auth_check.php?msg=password_reset");
        } else {
            debug_log("Échec de la réinitialisation du mot de passe administrateur");
            header("Location: auth_check.php?error=reset_failed");
        }
        exit;
    } catch (PDOException $e) {
        debug_log("Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage());
        header("Location: auth_check.php?error=db_error");
        exit;
    }
}

// Création d'un compte administrateur
if (isset($_POST['create_admin'])) {
    $email = "admin@lamangeoire.fr";
    $password = "admin123";
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $nom = "Admin";
    $prenom = "Principal";
    $role = "superadmin";
    
    try {
        // Vérifier si l'administrateur existe déjà
        $check_stmt = $pdo->prepare("SELECT AdminID FROM Administrateurs WHERE Email = ?");
        $check_stmt->execute([$email]);
        $existing_admin = $check_stmt->fetch();
        
        if ($existing_admin) {
            debug_log("L'administrateur existe déjà, mise à jour...");
            $stmt = $pdo->prepare("UPDATE Administrateurs SET MotDePasse = ?, Nom = ?, Prenom = ?, Role = ? WHERE Email = ?");
            $result = $stmt->execute([$hashed_password, $nom, $prenom, $role, $email]);
        } else {
            debug_log("Création d'un nouvel administrateur");
            $stmt = $pdo->prepare("INSERT INTO Administrateurs (Email, MotDePasse, Nom, Prenom, Role) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$email, $hashed_password, $nom, $prenom, $role]);
        }
        
        if ($result) {
            debug_log("Compte administrateur créé/mis à jour avec succès");
            header("Location: auth_check.php?msg=admin_created");
        } else {
            debug_log("Échec de la création/mise à jour du compte administrateur");
            header("Location: auth_check.php?error=create_failed");
        }
        exit;
    } catch (PDOException $e) {
        debug_log("Erreur lors de la création du compte administrateur: " . $e->getMessage());
        header("Location: auth_check.php?error=db_error");
        exit;
    }
}

// Tentative de connexion
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    debug_log("Tentative de connexion pour: " . $email);
    
    try {
        // Vérifier dans la table Administrateurs
        $admin_stmt = $pdo->prepare("SELECT * FROM Administrateurs WHERE Email = ?");
        $admin_stmt->execute([$email]);
        $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            debug_log("Compte administrateur trouvé, vérification du mot de passe");
            
            if (password_verify($password, $admin['MotDePasse'])) {
                debug_log("Mot de passe correct, création de la session administrateur");
                
                // Mise à jour de la dernière connexion
                $update_stmt = $pdo->prepare("UPDATE Administrateurs SET DerniereConnexion = NOW() WHERE AdminID = ?");
                $update_stmt->execute([$admin['AdminID']]);
                
                // Créer la session administrateur
                $_SESSION['admin_id'] = $admin['AdminID'];
                $_SESSION['admin_nom'] = $admin['Nom'];
                $_SESSION['admin_prenom'] = $admin['Prenom'];
                $_SESSION['admin_email'] = $admin['Email'];
                $_SESSION['admin_role'] = $admin['Role'];
                $_SESSION['user_type'] = 'admin';
                
                debug_log("Session créée: " . json_encode($_SESSION));
                
                // Rediriger vers le tableau de bord
                header("Location: admin/index.php");
                exit;
            } else {
                debug_log("Mot de passe incorrect pour l'administrateur");
                header("Location: auth_check.php?error=invalid_password");
                exit;
            }
        } else {
            // Vérifier dans la table Clients
            $client_stmt = $pdo->prepare("SELECT * FROM Clients WHERE Email = ?");
            $client_stmt->execute([$email]);
            $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                debug_log("Compte client trouvé, vérification du mot de passe");
                
                if (password_verify($password, $client['MotDePasse'])) {
                    debug_log("Mot de passe correct, création de la session client");
                    
                    // Créer la session client
                    $_SESSION['client_id'] = $client['ClientID'];
                    $_SESSION['client_nom'] = $client['Nom'];
                    $_SESSION['client_prenom'] = $client['Prenom'];
                    $_SESSION['client_email'] = $client['Email'];
                    $_SESSION['user_type'] = 'client';
                    
                    debug_log("Session créée: " . json_encode($_SESSION));
                    
                    // Rediriger vers le compte client
                    header("Location: mon-compte.php");
                    exit;
                } else {
                    debug_log("Mot de passe incorrect pour le client");
                    header("Location: auth_check.php?error=invalid_password");
                    exit;
                }
            } else {
                debug_log("Aucun compte trouvé pour: " . $email);
                header("Location: auth_check.php?error=user_not_found");
                exit;
            }
        }
    } catch (PDOException $e) {
        debug_log("Erreur de base de données: " . $e->getMessage());
        header("Location: auth_check.php?error=db_error");
        exit;
    }
}

// Si aucune action n'est effectuée, rediriger vers la page de vérification
header("Location: auth_check.php");
exit;
?>
