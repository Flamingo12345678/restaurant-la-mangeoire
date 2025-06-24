<?php
/**
 * CORRECTION AUTOMATIQUE DES FICHIERS PHP - UTILIISATEURID -> CLIENTID
 * 
 * Ce script corrige automatiquement tous les fichiers PHP qui utilisent encore
 * UtilisateurID au lieu de ClientID
 */

echo "🔧 CORRECTION AUTOMATIQUE FICHIERS PHP\n";
echo "======================================\n\n";

// Liste des fichiers à corriger (basée sur la recherche précédente)
$fichiers_a_corriger = [
    'reinitialiser-mot-de-passe.php',
    'reset-password.php',
    'verification-email.php',
    'mon-compte.php',
    'supprimer-compte.php',
    'modifier-profil.php'
];

$corrections_effectuees = 0;
$erreurs = 0;

foreach ($fichiers_a_corriger as $fichier) {
    $chemin_complet = __DIR__ . '/' . $fichier;
    
    if (!file_exists($chemin_complet)) {
        echo "⚠️  Fichier non trouvé: $fichier\n";
        continue;
    }
    
    echo "🔄 Traitement: $fichier\n";
    
    // Lire le contenu du fichier
    $contenu = file_get_contents($chemin_complet);
    $contenu_original = $contenu;
    
    // Corrections principales
    $corrections = [
        // Colonnes SQL
        'UtilisateurID' => 'ClientID',
        'utilisateur_id' => 'client_id',
        
        // Tables SQL
        'FROM Utilisateurs' => 'FROM Clients',
        'JOIN Utilisateurs' => 'JOIN Clients',
        'UPDATE Utilisateurs' => 'UPDATE Clients',
        'INSERT INTO Utilisateurs' => 'INSERT INTO Clients',
        'DELETE FROM Utilisateurs' => 'DELETE FROM Clients',
        
        // Variables PHP
        '$utilisateurID' => '$clientID',
        '$utilisateur_id' => '$client_id',
        '$user_id' => '$client_id',
        
        // Paramètres POST/GET
        "['UtilisateurID']" => "['ClientID']",
        "['utilisateur_id']" => "['client_id']",
        "['user_id']" => "['client_id']",
        
        // Sessions
        '$_SESSION[\'UtilisateurID\']' => '$_SESSION[\'ClientID\']',
        '$_SESSION[\'utilisateur_id\']' => '$_SESSION[\'client_id\']',
        '$_SESSION["UtilisateurID"]' => '$_SESSION["ClientID"]',
        '$_SESSION["utilisateur_id"]' => '$_SESSION["client_id"]',
        
        // Commentaires et noms de fonctions/variables communes
        'getUtilisateurID' => 'getClientID',
        'setUtilisateurID' => 'setClientID',
        'utilisateurId' => 'clientId',
        'UtilisateurId' => 'ClientId'
    ];
    
    $modifications = 0;
    foreach ($corrections as $ancien => $nouveau) {
        $nouveau_contenu = str_replace($ancien, $nouveau, $contenu);
        if ($nouveau_contenu !== $contenu) {
            $modifications += substr_count($contenu, $ancien);
            $contenu = $nouveau_contenu;
        }
    }
    
    if ($modifications > 0) {
        // Sauvegarder le fichier modifié
        if (file_put_contents($chemin_complet, $contenu)) {
            echo "   ✅ $modifications correction(s) effectuée(s)\n";
            $corrections_effectuees += $modifications;
        } else {
            echo "   ❌ Erreur lors de la sauvegarde\n";
            $erreurs++;
        }
    } else {
        echo "   ℹ️  Aucune correction nécessaire\n";
    }
}

echo "\n📊 RÉSUMÉ DES CORRECTIONS\n";
echo "=========================\n";
echo "Corrections totales effectuées: $corrections_effectuees\n";
echo "Erreurs rencontrées: $erreurs\n";

if ($corrections_effectuees > 0) {
    echo "\n✅ Fichiers corrigés avec succès!\n";
    echo "⚠️  IMPORTANT: Testez les fonctionnalités modifiées\n";
} else {
    echo "\nℹ️  Aucune correction nécessaire\n";
}

echo "\n🔍 VÉRIFICATION FINALE\n";
echo "======================\n";

// Recherche de références restantes
$extensions = ['*.php'];
$motifs_recherche = ['UtilisateurID', 'utilisateur_id', 'FROM Utilisateurs', 'JOIN Utilisateurs'];

foreach ($motifs_recherche as $motif) {
    echo "Recherche: '$motif'\n";
    
    $commande = "grep -r --include='*.php' '$motif' . 2>/dev/null | head -5";
    $resultats = shell_exec($commande);
    
    if (!empty(trim($resultats))) {
        echo "   ⚠️  Références trouvées:\n";
        $lignes = explode("\n", trim($resultats));
        foreach ($lignes as $ligne) {
            if (!empty($ligne)) {
                echo "      $ligne\n";
            }
        }
    } else {
        echo "   ✅ Aucune référence trouvée\n";
    }
    echo "\n";
}

?>
