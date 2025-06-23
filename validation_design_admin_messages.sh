#!/bin/bash

# Script de validation du design moderne des cartes statistiques - admin-messages.php
# Auteur: Assistant IA
# Date: $(date)

echo "🎨 Validation du design moderne des cartes statistiques dans admin-messages.php"
echo "=================================================================="

FICHIER="/Users/flamingo/Documents/GitHub/restaurant-la-mangeoire/admin-messages.php"

if [ ! -f "$FICHIER" ]; then
    echo "❌ Erreur: Le fichier admin-messages.php n'existe pas!"
    exit 1
fi

echo "📁 Fichier: $FICHIER"
echo ""

# Vérification des variables CSS
echo "🎯 Vérification des variables CSS..."
if grep -q ":root" "$FICHIER"; then
    echo "✅ Variables CSS définies"
    
    # Vérifier les gradients
    if grep -q "primary-gradient" "$FICHIER"; then
        echo "✅ Gradient primary défini"
    else
        echo "❌ Gradient primary manquant"
    fi
    
    if grep -q "success-gradient" "$FICHIER"; then
        echo "✅ Gradient success défini"
    else
        echo "❌ Gradient success manquant"
    fi
    
    if grep -q "warning-gradient" "$FICHIER"; then
        echo "✅ Gradient warning défini"
    else
        echo "❌ Gradient warning manquant"
    fi
    
    if grep -q "danger-gradient" "$FICHIER"; then
        echo "✅ Gradient danger défini"
    else
        echo "❌ Gradient danger manquant"
    fi
else
    echo "❌ Variables CSS manquantes"
fi

echo ""

# Vérification de la structure moderne
echo "🏗️ Vérification de la structure moderne..."
if grep -q "stats-container" "$FICHIER"; then
    echo "✅ Conteneur stats-container présent"
else
    echo "❌ Conteneur stats-container manquant"
fi

if grep -q "grid-template-columns" "$FICHIER"; then
    echo "✅ Grid CSS utilisé pour le responsive"
else
    echo "❌ Grid CSS manquant"
fi

echo ""

# Vérification des classes modernes
echo "🎨 Vérification des classes de style..."
classes_requises=("card-icon" "card-value" "card-label" "primary" "danger" "warning" "success")

for classe in "${classes_requises[@]}"; do
    if grep -q "$classe" "$FICHIER"; then
        echo "✅ Classe '$classe' présente"
    else
        echo "❌ Classe '$classe' manquante"
    fi
done

echo ""

# Vérification des animations
echo "🎭 Vérification des animations..."
animations=("slideInUp" "countUp" "pulse")

for animation in "${animations[@]}"; do
    if grep -q "$animation" "$FICHIER"; then
        echo "✅ Animation '$animation' présente"
    else
        echo "❌ Animation '$animation' manquante"
    fi
done

echo ""

# Vérification du responsive design
echo "📱 Vérification du responsive design..."
breakpoints=("max-width: 1200px" "max-width: 768px" "max-width: 480px")

for breakpoint in "${breakpoints[@]}"; do
    if grep -q "$breakpoint" "$FICHIER"; then
        echo "✅ Breakpoint '$breakpoint' présent"
    else
        echo "❌ Breakpoint '$breakpoint' manquant"
    fi
done

echo ""

# Vérification du JavaScript
echo "🚀 Vérification du JavaScript..."
js_fonctions=("animateCountUp" "addEventListener" "requestAnimationFrame")

for fonction in "${js_fonctions[@]}"; do
    if grep -q "$fonction" "$FICHIER"; then
        echo "✅ Fonction JS '$fonction' présente"
    else
        echo "❌ Fonction JS '$fonction' manquante"
    fi
done

echo ""

# Vérification des effets visuels
echo "✨ Vérification des effets visuels..."
effets=("box-shadow" "transition" "transform" "cubic-bezier")

for effet in "${effets[@]}"; do
    if grep -q "$effet" "$FICHIER"; then
        echo "✅ Effet '$effet' utilisé"
    else
        echo "❌ Effet '$effet' manquant"
    fi
done

echo ""

# Vérification de la cohérence avec le dashboard système
echo "🔗 Vérification de la cohérence avec le dashboard système..."
if grep -q "border-radius.*20px" "$FICHIER"; then
    echo "✅ Border-radius cohérent (20px)"
else
    echo "❌ Border-radius non cohérent"
fi

if grep -q "cubic-bezier(0.4, 0, 0.2, 1)" "$FICHIER"; then
    echo "✅ Fonction de transition cohérente"
else
    echo "❌ Fonction de transition différente"
fi

echo ""

# Statistiques du fichier
echo "📊 Statistiques du fichier:"
echo "📄 Lignes totales: $(wc -l < "$FICHIER")"
echo "🎨 Lignes CSS: $(grep -c -E "(\.admin-messages|@media|@keyframes)" "$FICHIER")"
echo "🚀 Lignes JS: $(grep -c -E "(function|addEventListener|setTimeout)" "$FICHIER")"

echo ""
echo "✅ Validation terminée!"
echo "=================================================================="
