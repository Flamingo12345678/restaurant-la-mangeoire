<?php
require_once 'db_connexion.php';
if ($conn) {
  echo "Connexion réussie !";
} else {
  echo "Échec connexion.";
}