#!/bin/bash

# 🧹 NETTOYAGE FINAL - Suppression des fichiers dupliqués
# Script pour nettoyer les fichiers générés lors des conflits de fusion

echo "🧹 NETTOYAGE FINAL DES FICHIERS DUPLIQUÉS"
echo "========================================"

# Compter les fichiers à supprimer
count=$(find . -name "* 2.*" -o -name "* 2" | wc -l)
echo "📊 Fichiers dupliqués détectés: $count"

if [ $count -eq 0 ]; then
    echo "✅ Aucun fichier dupliqué trouvé !"
    exit 0
fi

echo ""
echo "🗑️  Suppression des fichiers dupliqués en cours..."

# Supprimer tous les fichiers avec " 2" dans le nom
find . -name "* 2.*" -delete
find . -name "* 2" -delete

# Vérifier le résultat
remaining=$(find . -name "* 2.*" -o -name "* 2" | wc -l)
removed=$((count - remaining))

echo ""
echo "📈 RÉSULTATS:"
echo "   - Fichiers supprimés: $removed"
echo "   - Fichiers restants: $remaining"

if [ $remaining -eq 0 ]; then
    echo "✅ NETTOYAGE TERMINÉ AVEC SUCCÈS!"
    echo "Le dépôt est maintenant propre et organisé."
else
    echo "⚠️  Quelques fichiers n'ont pas pu être supprimés."
    echo "Fichiers restants:"
    find . -name "* 2.*" -o -name "* 2"
fi

echo ""
echo "🎯 Le projet Restaurant La Mangeoire est maintenant prêt !"
