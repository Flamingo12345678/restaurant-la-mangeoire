<?php
// Script d'installation pour mettre à jour toutes les pages admin
// Ce script va mettre à jour les fichiers de l'admin pour inclure la nouvelle barre latérale et l'en-tête

$files_to_update = [
  'commandes.php',
  'menus.php',
  'paiements.php',
  'clients.php',
  'employes.php',
  'tables.php',
  'reservations.php'
];

$header_code = <<<'EOT'
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>%%PAGE_TITLE%% - Administration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
<?php 
// Définir le titre de la page
$page_title = "%%PAGE_TITLE%%";

// Indiquer que ce fichier est inclus dans une page
define('INCLUDED_IN_PAGE', true);
include 'header_template.php'; 
?>

  <!-- Contenu spécifique de la page -->
  <div style="padding:20px;">
EOT;

$footer_code = <<<'EOT'
  </div>

<?php 
include 'footer_template.php'; 
?>
</body>
</html>
EOT;

foreach ($files_to_update as $file) {
  $filepath = __DIR__ . '/' . $file;

  if (!file_exists($filepath)) {
    echo "Le fichier $file n'existe pas.<br>";
    continue;
  }

  $content = file_get_contents($filepath);

  // Extraire la partie PHP avant le DOCTYPE
  preg_match('/^<\?php(.*?)(?=<!DOCTYPE|<html)/s', $content, $php_matches);

  if (!empty($php_matches[1])) {
    $php_code = $php_matches[1];

    // Titre de la page basé sur le nom du fichier
    $page_title = ucfirst(str_replace('.php', '', $file));

    // Extraire la partie entre les balises <body> et </body>
    preg_match('/<body>(.*?)<\/body>/s', $content, $body_matches);

    if (!empty($body_matches[1])) {
      $body_content = $body_matches[1];

      // Supprimer les parties de navigation si elles existent
      $body_content = preg_replace('/<div class="sidebar.*?<\/div>\s*<div class="main-content">/s', '', $body_content);
      $body_content = preg_replace('/<button.*?admin-burger-btn.*?<\/button>/s', '', $body_content);

      // Construire le nouveau contenu
      $new_header = str_replace('%%PAGE_TITLE%%', $page_title, $header_code);
      $new_content = "<?php" . $php_code . $new_header . $body_content . $footer_code;

      // Écrire le nouveau contenu dans un fichier temporaire
      $temp_file = $filepath . '.new';
      file_put_contents($temp_file, $new_content);

      echo "Le fichier $file a été mis à jour avec succès.<br>";
    } else {
      echo "Impossible de trouver le contenu du body dans $file.<br>";
    }
  } else {
    echo "Impossible de trouver le code PHP dans $file.<br>";
  }
}

echo "Terminé! Les fichiers ont été mis à jour et sauvegardés avec l'extension .new";
