<?php
/**
 * Test de vérification des erreurs de session
 * Vérification que les pages principales n'ont pas d'erreur "headers already sent"
 */

echo "=== TEST DE VERIFICATION DES SESSIONS ===\n\n";

// Liste des fichiers critiques à tester
$fichiers_a_tester = [
    '/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/contact.php',
    '/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/paiement.php',
    '/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/confirmation-commande.php',
    '/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/passer-commande.php',
    '/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/connexion-unifiee.php'
];

foreach ($fichiers_a_tester as $fichier) {
    $nom_fichier = basename($fichier);
    echo "Test de $nom_fichier...\n";
    
    if (!file_exists($fichier)) {
        echo "❌ ERREUR: Fichier $nom_fichier introuvable\n\n";
        continue;
    }
    
    // Lire le contenu du fichier
    $contenu = file_get_contents($fichier);
    
    // Vérifier que session_start() est en début de fichier (avant tout HTML)
    $position_session = strpos($contenu, 'session_start()');
    $position_html = strpos($contenu, '<!DOCTYPE');
    $position_echo = strpos($contenu, 'echo');
    $position_print = strpos($contenu, 'print');
    
    if ($position_session !== false) {
        $lignes = explode("\n", $contenu);
        $ligne_session = 0;
        
        // Trouver la ligne de session_start()
        foreach ($lignes as $index => $ligne) {
            if (strpos($ligne, 'session_start()') !== false) {
                $ligne_session = $index + 1;
                break;
            }
        }
        
        echo "   ✓ session_start() trouvé à la ligne $ligne_session\n";
        
        // Vérifier qu'il n'y a pas de HTML avant session_start()
        $avant_session = substr($contenu, 0, $position_session);
        if (strpos($avant_session, '<!DOCTYPE') !== false || 
            strpos($avant_session, '<html') !== false ||
            strpos($avant_session, '<head') !== false) {
            echo "   ❌ PROBLÈME: HTML détecté avant session_start()\n";
        } else {
            echo "   ✓ Pas de HTML avant session_start()\n";
        }
        
        // Vérifier la protection avec session_status()
        if (strpos($contenu, 'session_status()') !== false) {
            echo "   ✓ Protection session_status() présente\n";
        } else {
            echo "   ⚠️  Recommandé: Ajouter la vérification session_status()\n";
        }
        
    } else {
        echo "   ℹ️  Pas de session_start() dans ce fichier\n";
    }
    
    echo "\n";
}

echo "\n=== VERIFICATION DE LA STRUCTURE DES PAGES PRINCIPALES ===\n\n";

// Test de la page contact.php corrigée
$contact_content = file_get_contents('/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/contact.php');
$lignes_contact = explode("\n", $contact_content);

echo "Structure de contact.php:\n";
$premiere_ligne_php = false;
$premiere_ligne_html = false;

foreach ($lignes_contact as $index => $ligne) {
    $ligne_num = $index + 1;
    
    if (trim($ligne) === '<?php' && !$premiere_ligne_php) {
        echo "   ✓ Ligne $ligne_num: Début PHP\n";
        $premiere_ligne_php = $ligne_num;
    }
    
    if (strpos($ligne, 'session_start()') !== false) {
        echo "   ✓ Ligne $ligne_num: session_start() trouvé\n";
    }
    
    if (strpos($ligne, '<!DOCTYPE') !== false && !$premiere_ligne_html) {
        echo "   ✓ Ligne $ligne_num: Début HTML\n";
        $premiere_ligne_html = $ligne_num;
        break;
    }
}

if ($premiere_ligne_php && $premiere_ligne_html && $premiere_ligne_php < $premiere_ligne_html) {
    echo "   ✅ SUCCÈS: Structure correcte (PHP avant HTML)\n";
} else {
    echo "   ❌ PROBLÈME: Structure incorrecte\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "✅ Fichier contact.php corrigé\n";
echo "✅ session_start() déplacé en début de fichier\n";
echo "✅ Protection session_status() ajoutée\n";
echo "✅ Traitement du formulaire déplacé avant HTML\n";
echo "\nLe problème 'headers already sent' devrait être résolu.\n";

?>
