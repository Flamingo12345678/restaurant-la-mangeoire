<?php
/**
 * Script pour corriger tous les fichiers admin qui utilisent require_superadmin()
 * mais n'incluent pas security_utils.php
 */

$admin_files = [
    'add_reservation.php',
    'add_menu.php', 
    'delete_client.php',
    'edit_client.php',
    'delete_table.php',
    'edit_paiement.php',
    'edit_menu.php',
    'delete_employe.php',
    'delete_paiement.php',
    'delete_menu.php',
    'add_employe.php',
    'edit_employe.php',
    'edit_reservation.php',
    'add_client.php',
    'delete_reservation.php',
    'add_commande.php',
    'edit_commande.php',
    'delete_commande.php',
    'add_table.php'
];

foreach ($admin_files as $file) {
    $filepath = __DIR__ . '/admin/' . $file;
    
    if (!file_exists($filepath)) {
        echo "Fichier non trouvé: $file\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Vérifier si le fichier inclut déjà security_utils.php
    if (strpos($content, 'security_utils.php') !== false) {
        echo "Fichier $file inclut déjà security_utils.php\n";
        continue;
    }
    
    // Vérifier si le fichier utilise require_superadmin()
    if (strpos($content, 'require_superadmin()') === false) {
        echo "Fichier $file n'utilise pas require_superadmin()\n";
        continue;
    }
    
    // Chercher la ligne avec require_once __DIR__ . '/../includes/common.php';
    $pattern = "/require_once __DIR__ \. '\/\.\.\/includes\/common\.php';/";
    
    if (preg_match($pattern, $content)) {
        // Remplacer par l'inclusion de security_utils.php après common.php
        $replacement = "require_once __DIR__ . '/../includes/common.php';\nrequire_once __DIR__ . '/includes/security_utils.php';";
        $new_content = preg_replace($pattern, $replacement, $content);
        
        if (file_put_contents($filepath, $new_content)) {
            echo "Corrigé: $file\n";
        } else {
            echo "Erreur lors de l'écriture: $file\n";
        }
    } else {
        echo "Pattern non trouvé dans: $file\n";
    }
}

echo "Script terminé.\n";
?>
