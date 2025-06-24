#!/bin/bash

# 🧹 NETTOYAGE COMPLET DES BRANCHES GIT
# Script de suppression de toutes les branches sauf main
# Date: 24 juin 2025

echo "🧹 Nettoyage complet des branches Git"
echo "====================================="
echo ""
echo "⚠️  Ce script supprime TOUTES les branches sauf 'main'"
echo "    Assurez-vous d'être sur la branche main avant de continuer"
echo ""

# Vérifier qu'on est sur main
current_branch=$(git branch --show-current)
if [ "$current_branch" != "main" ]; then
    echo "❌ Erreur: Vous devez être sur la branche 'main'"
    echo "   Branche actuelle: $current_branch"
    echo "   Commande: git checkout main"
    exit 1
fi

echo "✅ Branche actuelle: $current_branch"
echo ""

# Lister les branches avant suppression
echo "📋 Branches avant nettoyage:"
echo "----------------------------"
git branch -a
echo ""

# Supprimer toutes les branches locales sauf main
echo "🗑️  Suppression des branches locales..."
local_branches=$(git branch | grep -v "main" | grep -v "*" | xargs)
if [ -n "$local_branches" ]; then
    echo "Suppression: $local_branches"
    echo $local_branches | xargs git branch -D
    echo "✅ Branches locales supprimées"
else
    echo "ℹ️  Aucune branche locale à supprimer"
fi
echo ""

# Supprimer toutes les branches distantes sauf main
echo "🌐 Suppression des branches distantes..."
remote_branches=$(git branch -r | grep -v "origin/main" | grep -v "origin/HEAD" | sed 's/origin\///' | xargs)
if [ -n "$remote_branches" ]; then
    echo "Suppression: $remote_branches"
    echo $remote_branches | xargs git push origin --delete
    echo "✅ Branches distantes supprimées"
else
    echo "ℹ️  Aucune branche distante à supprimer"
fi
echo ""

# Nettoyer les références
echo "🧽 Nettoyage des références..."
git remote prune origin
echo "✅ Références nettoyées"
echo ""

# Résultat final
echo "🎯 Résultat final:"
echo "------------------"
git branch -a
echo ""
echo "✅ Nettoyage terminé avec succès !"
echo "   Seule la branche 'main' est conservée"
echo ""

# Statistiques
echo "📊 Statistiques:"
total_local=$(echo "$local_branches" | wc -w)
total_remote=$(echo "$remote_branches" | wc -w)
echo "   Branches locales supprimées: $total_local"
echo "   Branches distantes supprimées: $total_remote"
echo "   Total supprimé: $((total_local + total_remote))"
echo ""
echo "🎉 Dépôt Git maintenant propre et optimisé !"
