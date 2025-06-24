<?php
/**
 * Script pour installer le SDK PayPal pour PHP
 * 
 * Ce script vérifie si Composer est installé, puis installe le SDK PayPal
 * via Composer. Si Composer n'est pas disponible, il affiche des instructions
 * pour l'installation manuelle.
 */

echo "Installation du SDK PayPal pour PHP...\n\n";

// Vérifier si Composer est installé
$composerExists = false;
$output = [];
$returnCode = 0;

exec('composer --version', $output, $returnCode);
$composerExists = ($returnCode === 0);

if ($composerExists) {
    echo "Composer est installé. Installation du SDK PayPal via Composer...\n";
    
    // Vérifier si le fichier composer.json existe
    if (file_exists('composer.json')) {
        echo "Mise à jour du fichier composer.json existant...\n";
        $composerJson = json_decode(file_get_contents('composer.json'), true);
        
        // Ajouter ou mettre à jour la dépendance PayPal
        if (!isset($composerJson['require'])) {
            $composerJson['require'] = [];
        }
        
        $composerJson['require']['paypal/rest-api-sdk-php'] = '^1.14';
        
        // Sauvegarder les modifications
        file_put_contents('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        echo "Création d'un nouveau fichier composer.json...\n";
        $composerJson = [
            'require' => [
                'paypal/rest-api-sdk-php' => '^1.14'
            ]
        ];
        
        file_put_contents('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    // Exécuter composer update pour installer le SDK
    echo "Exécution de composer update...\n";
    system('composer update');
    
    echo "\nLe SDK PayPal a été installé avec succès via Composer.\n";
} else {
    echo "Composer n'est pas installé sur ce système.\n\n";
    echo "Instructions pour l'installation manuelle du SDK PayPal :\n";
    echo "1. Téléchargez et installez Composer depuis https://getcomposer.org/\n";
    echo "2. Ouvrez un terminal et accédez au répertoire du projet\n";
    echo "3. Exécutez la commande : composer require paypal/rest-api-sdk-php\n";
    echo "4. Assurez-vous que la ligne 'require_once \"vendor/autoload.php\";' est présente dans vos fichiers PHP\n\n";
}

echo "Note : Le SDK REST de PayPal est obsolète, mais il est toujours fonctionnel pour les cas d'utilisation de base.\n";
echo "Pour les projets futurs, envisagez d'utiliser l'API PayPal Checkout v2 directement via des requêtes HTTP.\n";
