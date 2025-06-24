<?php
// Script simple pour tester le panier rapidement
session_start();

// Ajouter un article de test au panier de session
$_SESSION['panier'] = [
    [
        'MenuID' => 1,
        'NomItem' => 'Ndole (Test)',
        'Prix' => 15.50,
        'Quantite' => 1,
        'Description' => 'Plat traditionnel camerounais',
        'Image' => 'assets/img/menu/menu-item-1.png'
    ],
    [
        'MenuID' => 2, 
        'NomItem' => 'Eru (Test)',
        'Prix' => 14.80,
        'Quantite' => 2,
        'Description' => 'Délicieux légume du Cameroun',
        'Image' => 'assets/img/menu/menu-item-2.png'
    ]
];

header('Location: panier.php');
exit();
?>
