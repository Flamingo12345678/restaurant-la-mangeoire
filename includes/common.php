<?php
// Fichier commun pour la gestion des messages, CSRF, contrôle d'accès et validation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la gestion des cookies
require_once __DIR__ . '/cookie-consent.php';

// Gestion des messages flash
function set_message($msg, $type = 'success')
{
  $_SESSION['flash_message'] = [
    'text' => $msg,
    'type' => $type
  ];
}

function display_message()
{
  if (!empty($_SESSION['flash_message'])) {
    $type = $_SESSION['flash_message']['type'] === 'success' ? 'alert-success' : 'alert-error';
    $text = htmlspecialchars($_SESSION['flash_message']['text']);
    echo "<div class='alert $type'>$text</div>";
    unset($_SESSION['flash_message']);
  }
}

// Génération et vérification du token CSRF
function generate_csrf_token()
{
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function check_csrf_token($token)
{
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Contrôle d'accès admin (simple)
if (!function_exists('require_admin')) {
function require_admin()
{
  if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: admin/login.php');
    exit;
  }
}
}

// Validation centralisée
if (!function_exists('validate_email')) {
    function validate_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('validate_nom')) {
    function validate_nom($nom, $max = 100)
    {
        return is_string($nom) && mb_strlen($nom) > 1 && mb_strlen($nom) <= $max;
    }
}

if (!function_exists('validate_prenom')) {
    function validate_prenom($prenom, $max = 100)
    {
        return is_string($prenom) && mb_strlen($prenom) > 1 && mb_strlen($prenom) <= $max;
    }
}

if (!function_exists('validate_telephone')) {
    function validate_telephone($tel)
    {
        return preg_match('/^[0-9]{10,15}$/', $tel);
    }
}

if (!function_exists('validate_code_postal')) {
    function validate_code_postal($code)
    {
        // Validation de code postal français (5 chiffres)
        return preg_match('/^[0-9]{5}$/', $code);
    }
}

if (!function_exists('validate_date')) {
    function validate_date($date)
    {
        return (bool)strtotime($date);
    }
}

if (!function_exists('validate_prix')) {
    function validate_prix($prix)
    {
        return is_numeric($prix) && $prix >= 0;
    }
}

if (!function_exists('validate_numero_table')) {
    function validate_numero_table($num)
    {
        return is_numeric($num) && $num > 0;
    }
}

function validate_places($places)
{
  return is_numeric($places) && $places > 0 && $places <= 20;
}

function validate_salaire($salaire)
{
  return is_numeric($salaire) && $salaire >= 0;
}

function validate_quantite($qte)
{
  return is_numeric($qte) && $qte > 0;
}

function validate_description($desc, $max = 255)
{
  return is_string($desc) && mb_strlen($desc) <= $max;
}

function validate_password_strength($password)
{
  // Vérifier la longueur minimale
  if (strlen($password) < 8) {
    return false;
  }
  
  // Vérifier la présence d'au moins une lettre majuscule
  if (!preg_match('/[A-Z]/', $password)) {
    return false;
  }
  
  // Vérifier la présence d'au moins une lettre minuscule
  if (!preg_match('/[a-z]/', $password)) {
    return false;
  }
  
  // Vérifier la présence d'au moins un chiffre
  if (!preg_match('/[0-9]/', $password)) {
    return false;
  }
  
  return true;
}

// Journalisation centralisée des actions admin
if (!function_exists('log_admin_action')) {
function log_admin_action($action, $details = '')
{
  $logfile = __DIR__ . '/../admin/admin_actions.log';
  $date = date('Y-m-d H:i:s');
  $user = $_SESSION['admin_id'] ?? 'inconnu';
  $entry = "[$date] [$user] $action $details\n";
  file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}
}
?>
