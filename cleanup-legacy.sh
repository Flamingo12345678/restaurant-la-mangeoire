#!/bin/bash

# Script de nettoyage des fichiers legacy - La Mangeoire
# Date: 21 juin 2025

echo "🧹 NETTOYAGE DES FICHIERS LEGACY - La Mangeoire"
echo "=============================================="
echo ""

# Créer un dossier de sauvegarde
BACKUP_DIR="backup-legacy-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📦 Création du dossier de sauvegarde: $BACKUP_DIR"
echo ""

# Fichiers de test et debug à supprimer (en production)
TEST_FILES=(
    "test-*.php"
    "test-*.html"
    "debug-*.php"
    "debug-*.html"
    "panier-debug.php"
    "panier-simple.php"
    "panier-standalone.php"
    "panier-test-*.html"
    "diagnostic-*.php"
    "creer-donnees-test*.php"
    "create-test-*.php"
    "instructions_test.php"
    "solution-acces-*.php"
    "validation*.php"
    "verif-*.php"
    "verify_*.php"
    "validate-*.php"
)

# Fichiers obsolètes après modernisation
OBSOLETE_FILES=(
    "ajouter-au-panier.php"
    "maintenance_panier.php"
    "vider-panier*.php"
    "passer-commande.php"
    "passer-commande-securise.php"
    "panier-securise.php"
    "index.html.Backup"
    "panier.php.backup"
    "demo-devises.php"
    "test_*.php"
    "reset_*.php"
    "fix_*.php"
    "force_*.php"
)

# Scripts d'installation et configuration (garder mais archiver)
SETUP_FILES=(
    "create_*.php"
    "setup-*.sh"
    "install_*.php"
    "execute_*.php"
    "executer_*.php"
    "add_*.php"
)

echo "🔍 ANALYSE DES FICHIERS"
echo "======================"

echo "📊 Fichiers de test détectés:"
for pattern in "${TEST_FILES[@]}"; do
    count=$(ls $pattern 2>/dev/null | wc -l)
    if [ $count -gt 0 ]; then
        echo "  - $pattern : $count fichier(s)"
        ls $pattern 2>/dev/null | head -3
        if [ $count -gt 3 ]; then
            echo "    ... et $((count-3)) autres"
        fi
        echo ""
    fi
done

echo "📊 Fichiers obsolètes détectés:"
for pattern in "${OBSOLETE_FILES[@]}"; do
    if [ -f "$pattern" ]; then
        echo "  - $pattern"
    fi
done
echo ""

echo "📊 Scripts de setup détectés:"
for pattern in "${SETUP_FILES[@]}"; do
    count=$(ls $pattern 2>/dev/null | wc -l)
    if [ $count -gt 0 ]; then
        echo "  - $pattern : $count fichier(s)"
    fi
done
echo ""

# Compter les fichiers de documentation
DOC_COUNT=$(ls *.md 2>/dev/null | wc -l)
echo "📚 Documents de documentation: $DOC_COUNT fichier(s)"
echo ""

echo "💾 RECOMMANDATIONS DE NETTOYAGE"
echo "==============================="
echo ""
echo "🟡 FICHIERS À ARCHIVER (déplacer vers $BACKUP_DIR):"
echo "  - Tous les fichiers test-* et debug-*"
echo "  - Fichiers obsolètes après modernisation"
echo "  - Scripts de setup (garder pour maintenance)"
echo ""
echo "🟢 FICHIERS À CONSERVER:"
echo "  - menu.php, panier.php, commande-moderne.php (système moderne)"
echo "  - includes/, assets/, config/ (core system)"
echo "  - api/ (REST endpoints)"
echo "  - Documentation principale (README.md, guides importants)"
echo ""
echo "🔴 FICHIERS À SUPPRIMER DÉFINITIVEMENT:"
echo "  - Fichiers .backup"
echo "  - Logs de debug anciens"
echo "  - Fichiers temporaires"
echo ""

# Fonction pour archiver les fichiers
archive_files() {
    echo "📦 Archivage des fichiers de test..."
    for pattern in "${TEST_FILES[@]}"; do
        if ls $pattern 1> /dev/null 2>&1; then
            mv $pattern "$BACKUP_DIR/" 2>/dev/null || true
        fi
    done
    
    echo "📦 Archivage des fichiers obsolètes..."
    for file in "${OBSOLETE_FILES[@]}"; do
        if [ -f "$file" ]; then
            mv "$file" "$BACKUP_DIR/"
        fi
    done
    
    echo "📦 Archivage des scripts de setup..."
    for pattern in "${SETUP_FILES[@]}"; do
        if ls $pattern 1> /dev/null 2>&1; then
            cp $pattern "$BACKUP_DIR/" 2>/dev/null || true
        fi
    done
}

# Fonction pour nettoyer les logs
clean_logs() {
    echo "🧹 Nettoyage des logs anciens..."
    find . -name "*.log" -mtime +7 -exec mv {} "$BACKUP_DIR/" \;
    find . -name "debug_*.txt" -exec mv {} "$BACKUP_DIR/" \;
}

# Fonction pour optimiser la documentation
optimize_docs() {
    echo "📚 Optimisation de la documentation..."
    
    # Créer un index des documents
    cat > "DOCUMENTATION_INDEX.md" << EOF
# 📚 Index de la Documentation - La Mangeoire

## 🎯 Documents Principaux
- [README.md](README.md) - Documentation générale
- [CORRECTION_PANIER_SYSTEME.md](CORRECTION_PANIER_SYSTEME.md) - Système panier moderne
- [IMPLEMENTATION_COMPLETE.md](IMPLEMENTATION_COMPLETE.md) - Implémentation complète

## 🔧 Guides Techniques
- [SYSTEME_PANIER_MODERNE.md](SYSTEME_PANIER_MODERNE.md) - Architecture panier
- [SYSTEME_COMMANDE_MODERNE.md](SYSTEME_COMMANDE_MODERNE.md) - Système de commande
- [GUIDE_DEPLOIEMENT_PRODUCTION.md](GUIDE_DEPLOIEMENT_PRODUCTION.md) - Déploiement

## 📊 Analyses et Corrections
- [ANALYSE_COMMANDE_PAIEMENT.md](ANALYSE_COMMANDE_PAIEMENT.md) - Analyse système paiement
- [CORRECTION_*.md](CORRECTION_*.md) - Historique des corrections

## 🛠️ Maintenance
- Fichiers archivés: $BACKUP_DIR/
- Scripts de setup conservés pour maintenance future
- Tests automatisés: test-automatique-panier.php, test-workflow-complet.php

---
*Généré automatiquement le $(date)*
EOF
    
    echo "✅ Index de documentation créé: DOCUMENTATION_INDEX.md"
}

echo "❓ VOULEZ-VOUS PROCÉDER AU NETTOYAGE? (y/N)"
read -p "Réponse: " response

if [[ "$response" =~ ^[Yy]$ ]]; then
    echo ""
    echo "🚀 DÉBUT DU NETTOYAGE"
    echo "===================="
    
    archive_files
    clean_logs
    optimize_docs
    
    echo ""
    echo "✅ NETTOYAGE TERMINÉ"
    echo "==================="
    echo "📦 Fichiers archivés dans: $BACKUP_DIR"
    echo "📚 Index documentation: DOCUMENTATION_INDEX.md"
    echo "🎯 Système prêt pour la production!"
    
    # Afficher les statistiques finales
    echo ""
    echo "📊 STATISTIQUES FINALES:"
    echo "- Fichiers PHP actifs: $(ls *.php 2>/dev/null | grep -v test | wc -l)"
    echo "- Fichiers archivés: $(ls $BACKUP_DIR/ 2>/dev/null | wc -l)"
    echo "- Documentation: $(ls *.md 2>/dev/null | wc -l) fichiers"
    echo "- Taille dossier backup: $(du -sh $BACKUP_DIR 2>/dev/null | cut -f1)"
    
else
    echo ""
    echo "❌ Nettoyage annulé. Fichiers conservés."
    rm -rf "$BACKUP_DIR"
fi

echo ""
echo "🎉 Script terminé!"
