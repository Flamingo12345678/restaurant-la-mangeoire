<?php
require_once 'admin/utils.php';
require_once 'db_connexion.php';
if ($conn) {
  set_message('Connexion réussie !', 'success');
  echo e(get_message()['text']);
} else {
  set_message('Échec connexion.', 'danger');
  echo e(get_message()['text']);
}
