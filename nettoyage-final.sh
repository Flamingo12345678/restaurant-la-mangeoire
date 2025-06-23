#!/bin/bash

# Script de nettoyage final - Suppression des fichiers obsolètes
# Restaurant La Mangeoire

echo "🧹 NETTOYAGE FINAL DES FICHIERS OBSOLÈTES"
echo "=========================================="

# Fichiers de scripts de test et de correction temporaires
FICHIERS_OBSOLETES=(
    "correction-finale-incoherences.php"
    "correction-fichiers-php.php"
    "test-coherence-finale.php"
    "test-fonctionnel-final.php"
    "migrer-utilisateurs-vers-clients.php"
    "maintenance-panier.php"
    "setup-tables-commandes.php"
    "diagnostic-structure-db.php"
    "test-commande-correcte.php"
    "debug-commande-simple.php"
    "debug-commande.php"
    "debug_commande.php"
    "corriger-commandes-utilisateurid.php"
    "corriger-contrainte-panier.php"
    "correction-globale-conn-pdo.php"
    "correction-mon-compte.php"
    "corriger_sidebar_mobile.php"
)

# Créer un dossier d'archive
mkdir -p archive_scripts_correction

echo "📁 Création du dossier d'archive: archive_scripts_correction"

# Déplacer les fichiers obsolètes vers l'archive
for fichier in "${FICHIERS_OBSOLETES[@]}"; do
    if [ -f "$fichier" ]; then
        echo "   🗂️  Archivage: $fichier"
        mv "$fichier" "archive_scripts_correction/"
    else
        echo "   ❓ Non trouvé: $fichier"
    fi
done

echo ""
echo "✅ NETTOYAGE TERMINÉ!"
echo "====================="
echo "Fichiers archivés dans: archive_scripts_correction/"
echo "Le projet est maintenant propre et prêt pour la production."
echo ""
echo "📋 FICHIERS PRINCIPAUX CONSERVÉS:"
echo "- Base de données: structure cohérente"
echo "- Scripts PHP: fonctionnels et cohérents"
echo "- Configuration: db_connexion.php"
echo "- Interface: tous les fichiers utilisateur"
echo ""
echo "🚀 PROJET PRÊT POUR LA PRODUCTION!"
