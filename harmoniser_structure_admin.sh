#!/bin/bash

# Script d'harmonisation de la structure des pages admin
echo "=== HARMONISATION DES PAGES ADMIN ==="
echo "Uniformisation de la structure HTML et CSS"
echo

# Liste des pages admin à traiter
ADMIN_FILES=(
    "admin/index.php"
    "admin/clients.php"
    "admin/reservations.php"
    "admin/employes.php"
    "admin/paiements.php"
)

for FILE in "${ADMIN_FILES[@]}"; do
    if [[ -f "$FILE" ]]; then
        echo "📝 Traitement de $FILE..."
        
        # Ajouter le script sidebar si manquant
        if ! grep -q "admin-sidebar.js" "$FILE"; then
            # Chercher la position avant </body> et ajouter le script
            if grep -q "</body>" "$FILE"; then
                sed -i '' 's|</body>|    <script src="../assets/js/admin-sidebar.js"></script>\n</body>|' "$FILE"
                echo "   ✅ Script sidebar ajouté"
            fi
        fi
        
        # Vérifier si la structure admin-main-content est présente
        if ! grep -q "admin-main-content" "$FILE"; then
            echo "   ⚠️ Structure admin-main-content manquante (nécessite modification manuelle)"
        fi
        
    else
        echo "❌ $FILE non trouvé"
    fi
done

echo
echo "🎯 RÉSUMÉ:"
echo "• Scripts sidebar ajoutés aux pages manquantes"
echo "• Structure HTML à vérifier manuellement pour cohérence"
echo "• Utiliser header_template.php pour ouvrir admin-main-content"
echo "• Fermer admin-main-content avant footer_template.php"
echo
echo "✅ Harmonisation des scripts terminée!"
