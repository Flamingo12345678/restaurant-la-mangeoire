<?php
// Script pour créer la table des administrateurs et un compte par défaut
require_once __DIR__ . '/db_connexion.php';

try {
  // Lire le contenu du fichier SQL
  $sql = file_get_contents(__DIR__ . '/init_admin.sql');

  // Exécuter le script SQL
  $conn->exec($sql);
  echo "<div style='font-family:Arial,sans-serif; padding:20px; max-width:800px; margin:0 auto; background:#f8f9fa; border-radius:5px;'>";
  echo "<h2 style='color:#ce1212;'>Création de la table des administrateurs</h2>";
  echo "<p>La table 'Administrateurs' a été créée avec succès.</p>";
  echo "<p>Un compte administrateur par défaut a été créé :</p>";
  echo "<ul>";
  echo "<li><strong>Email :</strong> admin@lamangeoire.fr</li>";
  echo "<li><strong>Mot de passe :</strong> admin</li>";
  echo "</ul>";
  echo "<p style='color:red;'><strong>ATTENTION :</strong> Veuillez changer ce mot de passe après votre première connexion pour des raisons de sécurité.</p>";
  echo "<p><a href='admin/login.php' style='background:#ce1212; color:#fff; padding:10px 15px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:10px;'>Se connecter à l'administration</a></p>";
  echo "<p><a href='index.html' style='color:#ce1212; text-decoration:none;'>Retour à l'accueil</a></p>";
  echo "</div>";
} catch (PDOException $e) {
  echo "<div style='font-family:Arial,sans-serif; padding:20px; max-width:800px; margin:0 auto; background:#fef7f7; border:1px solid #f5c6cb; border-radius:5px;'>";
  echo "<h2 style='color:#721c24;'>Erreur lors de la création de la table des administrateurs</h2>";
  echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
  echo "<p>Vérifiez les messages d'erreur ci-dessus et consultez l'administrateur système si nécessaire.</p>";
  echo "<p><a href='index.html' style='color:#ce1212; text-decoration:none;'>Retour à l'accueil</a></p>";
  echo "</div>";
}
