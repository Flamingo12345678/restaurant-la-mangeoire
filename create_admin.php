<?php
// Script pour créer un nouvel administrateur avec les identifiants spécifiés
require_once __DIR__ . '/db_connexion.php';

// Identifiants pour le nouvel administrateur
$username = 'Admin';
$email = 'admin@mangeoire.fr'; // Email uniquement pour respecter la structure de la BDD
$password = 'D@@mso_237*';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$nom = 'Administrateur';
$prenom = 'Principal';
$role = 'superadmin';

try {
  // Vérifier si l'utilisateur existe déjà
  $stmt = $conn->prepare("SELECT * FROM Administrateurs WHERE Email = ?");
  $stmt->execute([$email]);
  $admin = $stmt->fetch();

  if ($admin) {
    // Mettre à jour le mot de passe si l'utilisateur existe
    $stmt = $conn->prepare("UPDATE Administrateurs SET MotDePasse = ?, Nom = ?, Prenom = ?, Role = ? WHERE Email = ?");
    $stmt->execute([$hashed_password, $nom, $prenom, $role, $email]);
    echo "L'administrateur existant a été mis à jour avec les nouveaux identifiants.<br>";
  } else {
    // Créer un nouvel administrateur
    $stmt = $conn->prepare("INSERT INTO Administrateurs (Email, MotDePasse, Nom, Prenom, Role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $hashed_password, $nom, $prenom, $role]);
    echo "Un nouvel administrateur a été créé avec succès.<br>";
  }

  echo "<h3>Informations d'identification</h3>";
  echo "<p>Nom d'utilisateur: " . htmlspecialchars($username) . "</p>";
  echo "<p>Mot de passe: " . htmlspecialchars($password) . "</p>";
  echo "<p>Email: " . htmlspecialchars($email) . "</p>";
  echo "<p>Rôle: " . htmlspecialchars($role) . "</p>";
  echo "<p><a href='connexion.php'>Aller à la page de connexion</a></p>";
} catch (PDOException $e) {
  echo "Erreur: " . $e->getMessage();
}
