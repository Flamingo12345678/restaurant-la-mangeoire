#!/bin/bash
# Script d'audit des styles inline dans le site Restaurant La Mangeoire
# Ce script identifie tous les fichiers PHP qui contiennent encore des attributs style="..." ou des balises <style>

echo "=== Audit des styles inline dans le site Restaurant La Mangeoire ==="
echo ""

# Recherche des attributs style dans tous les fichiers PHP
echo "=== Recherche des attributs style=\"...\" ==="
grep -r "style=" --include="*.php" . | grep -v "harmonize" > inline_styles_audit.txt

# Compte le nombre de résultats
INLINE_COUNT=$(wc -l < inline_styles_audit.txt)

echo "$INLINE_COUNT occurrences de style=\"...\" trouvées."
echo ""

# Recherche des balises <style> dans tous les fichiers PHP
echo "=== Recherche des balises <style> ==="
grep -r "<style" --include="*.php" . | grep -v "harmonize" > style_tags_audit.txt

# Compte le nombre de résultats
STYLE_TAG_COUNT=$(wc -l < style_tags_audit.txt)

echo "$STYLE_TAG_COUNT occurrences de balises <style> trouvées."
echo ""

TOTAL_COUNT=$((INLINE_COUNT + STYLE_TAG_COUNT))

echo "=== Résultats de l'audit ==="
echo "Total : $TOTAL_COUNT occurrences de styles non harmonisés."
echo "- $INLINE_COUNT occurrences de style=\"...\" (détails dans inline_styles_audit.txt)"
echo "- $STYLE_TAG_COUNT occurrences de balises <style> (détails dans style_tags_audit.txt)"
echo ""

if [ $TOTAL_COUNT -gt 0 ]; then
  if [ $INLINE_COUNT -gt 0 ]; then
    echo "Les 5 premières occurrences de style=\"...\" :"
    head -n 5 inline_styles_audit.txt
    echo "..."
    echo ""
  fi
  
  if [ $STYLE_TAG_COUNT -gt 0 ]; then
    echo "Les 5 premières occurrences de balises <style> :"
    head -n 5 style_tags_audit.txt
    echo "..."
    echo ""
  fi
  
  echo "Suggestions pour corriger ces styles non harmonisés :"
  echo "1. Utiliser les classes CSS existantes dans main.css, admin.css, auth-pages.css, etc."
  echo "2. Ajouter de nouvelles classes CSS dans le fichier approprié"
  echo "3. Mettre à jour les scripts d'harmonisation (harmonize-admin-styles.js, harmonize-auth-styles.js)"
  echo "4. Supprimer les balises <style> et déplacer leur contenu dans les fichiers CSS externes"
else
  echo "Félicitations ! Aucun style non harmonisé trouvé dans le site."
fi

echo ""
echo "=== Fin de l'audit ==="
