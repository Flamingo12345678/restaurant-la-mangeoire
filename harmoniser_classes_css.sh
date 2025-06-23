#!/bin/bash

# Script pour remplacer toutes les occurrences de "alert-error" par "alert-danger"
# dans les fichiers PHP de l'interface admin

echo "=== HARMONISATION DES CLASSES CSS BOOTSTRAP ==="
echo "Remplacement de 'alert-error' par 'alert-danger' (standard Bootstrap)"
echo

FILES_CHANGED=0
TOTAL_REPLACEMENTS=0

# Trouver tous les fichiers PHP dans le dossier admin
for FILE in admin/*.php; do
    if [[ -f "$FILE" ]]; then
        # Compter les occurrences avant remplacement
        BEFORE_COUNT=$(grep -c "alert-error" "$FILE" 2>/dev/null || echo 0)
        
        if [[ $BEFORE_COUNT -gt 0 ]]; then
            echo "📝 Traitement de $FILE..."
            
            # Remplacer alert-error par alert-danger
            sed -i '' 's/alert-error/alert-danger/g' "$FILE"
            
            # Vérifier le remplacement
            AFTER_COUNT=$(grep -c "alert-danger" "$FILE" 2>/dev/null || echo 0)
            
            echo "   ✅ $BEFORE_COUNT occurrences remplacées"
            FILES_CHANGED=$((FILES_CHANGED + 1))
            TOTAL_REPLACEMENTS=$((TOTAL_REPLACEMENTS + BEFORE_COUNT))
        fi
    fi
done

echo
echo "🎯 RÉSUMÉ:"
echo "• Fichiers modifiés: $FILES_CHANGED"
echo "• Total remplacements: $TOTAL_REPLACEMENTS"
echo "• Classes CSS maintenant conformes au standard Bootstrap"
echo
echo "✅ Harmonisation terminée avec succès!"
