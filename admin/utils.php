<?php

/**
 * utils.php
 * Fonctions utilitaires pour l'administration : CSRF, validation, gestion des erreurs, affichage sécurisé.
 */
// Ne pas démarrer la session ici. La session doit être démarrée UNIQUEMENT dans les fichiers principaux (ex: reservations.php) avant d'inclure utils.php.

// Génère un token CSRF si besoin
define('CSRF_TOKEN_LENGTH', 32);
function get_csrf_token()
{
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
  }
  return $_SESSION['csrf_token'];
}

// Vérifie le token CSRF et l'invalide après usage (optionnel)
function check_csrf_token($token)
{
  if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
    return false;
  }
  // Invalider le token après usage pour plus de sécurité
  unset($_SESSION['csrf_token']);
  return true;
}

// Affichage sécurisé (échappement HTML)
function e($string)
{
  return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Gestion centralisée des messages utilisateur
function set_message($msg, $type = 'info')
{
  $_SESSION['flash_message'] = ['text' => $msg, 'type' => $type];
}
function get_message()
{
  if (!empty($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $msg;
  }
  return null;
}

// Gestion centralisée des erreurs PDO
function handle_pdo_exception($e, $action = '')
{
  error_log('PDOException ' . $action . ': ' . $e->getMessage());
  set_message('Erreur interne, veuillez réessayer plus tard.', 'danger');
}

// Validation générique de champs (exemple)
function validate_length($value, $min, $max)
{
  $len = mb_strlen($value);
  return $len >= $min && $len <= $max;
}

// Vérification des droits d'accès (superadmin par défaut)
function require_role($role = 'superadmin')
{
  if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== $role) {
    header('Location: index.php?error=forbidden');
    exit;
  }
}

// Forcer la suppression de tout overlay ou préchargeur qui bloquerait l'admin
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
  echo "<script>
    // Supprime tout overlay, modal, backdrop, préchargeur
    document.querySelectorAll('#preloader, .overlay, .modal, .backdrop').forEach(e => e.remove());
    // Réactive les clics et le scroll sur tout le body et ses enfants
    document.body.style.pointerEvents = 'auto';
    document.body.style.overflow = 'auto';
    document.body.style.filter = 'none';
    document.body.style.opacity = '1';
    // Réinitialise tous les enfants
    Array.from(document.body.getElementsByTagName('*')).forEach(function(el) {
      el.style.pointerEvents = 'auto';
      el.style.filter = 'none';
      el.style.opacity = '1';
    });
  </script>";
}
