# Guide d'harmonisation des styles CSS pour Restaurant La Mangeoire

## Introduction

Ce document explique comment nous avons harmonisé les styles CSS du site Restaurant La Mangeoire pour éliminer les styles inline et maintenir une cohérence visuelle sur l'ensemble du site.

## Architecture des fichiers CSS

Le site utilise désormais une architecture CSS modulaire :

1. **main.css** - Styles principaux pour tout le site
2. **admin.css** - Styles pour le tableau de bord administratif
3. **admin-inline-fixes.css** - Remplacements des styles inline dans les pages administratives
4. **auth-pages.css** - Styles pour les pages d'authentification (connexion, inscription, mon compte)
5. **employes-admin.css** - Styles spécifiques aux pages employés et administrateurs

## Scripts d'harmonisation JavaScript

Nous avons créé deux scripts JavaScript qui s'exécutent automatiquement pour harmoniser les styles :

1. **harmonize-admin-styles.js** - Pour les pages d'administration
2. **harmonize-auth-styles.js** - Pour les pages d'authentification

Ces scripts convertissent automatiquement les attributs style="..." en classes CSS, assurant ainsi une cohérence visuelle et facilitant la maintenance.

## Classes CSS harmonisées

### Pour les boutons
- `.add-button`, `.btn-primary` - Pour les boutons d'ajout et d'action principale
- `.cancel-button`, `.btn-secondary` - Pour les boutons d'annulation
- `.delete-btn` - Pour les boutons de suppression
- `.edit-btn` - Pour les boutons d'édition

### Pour les tableaux
- `.admin-table`, `.data-table` - Styles de tableau harmonisés
- `.actions-cell` - Pour les cellules d'actions dans les tableaux

### Pour les formulaires
- `.form-grid` - Disposition en grille pour les formulaires
- `.admin-form` - Style général pour les formulaires d'administration
- `.form-group` - Groupes de champs de formulaire
- `.form-control` - Style pour les champs de saisie
- `.required-field` - Indication des champs obligatoires
- `.checkbox-group` - Groupes de cases à cocher
- `.form-actions` - Conteneur pour les boutons d'actions de formulaire

### Pour les pages d'authentification
- `.info-paragraph` - Paragraphes d'information
- `.auth-link` - Liens dans les pages d'authentification
- `.auth-links-container` - Conteneurs de liens
- `.error-container` - Messages d'erreur
- `.form-row-flex` - Disposition flex pour les formulaires
- `.form-group-flex` - Groupes flex pour les formulaires
- `.section-title` - Titres de section
- `.filters-container` - Conteneurs de filtres
- `.filter-title` - Titres pour les filtres
- `.filter-form` - Formulaires de filtres
- `.form-select` - Sélecteurs dans les formulaires
- `.filter-button` - Boutons de filtrage
- `.btn-disabled` - Boutons désactivés

## Comment ajouter de nouveaux styles

1. **Ne jamais utiliser d'attributs style="..."** - Créez toujours des classes CSS pour les nouveaux styles

2. **Ajouter de nouveaux styles** :
   - Si ce sont des styles généraux : ajoutez-les à main.css
   - Si ce sont des styles pour l'administration : ajoutez-les à admin.css ou admin-inline-fixes.css
   - Si ce sont des styles pour les pages d'authentification : ajoutez-les à auth-pages.css
   - Si ce sont des styles pour les employés : ajoutez-les à employes-admin.css

3. **Mettre à jour les scripts d'harmonisation** :
   - Si vous ajoutez de nouvelles classes CSS qui remplacent des styles inline, mettez à jour les scripts harmonize-admin-styles.js ou harmonize-auth-styles.js en conséquence

## Bonnes pratiques

- Utilisez des noms de classe descriptifs qui indiquent clairement la fonction
- Privilégiez la réutilisation des classes existantes
- Testez les modifications sur différentes tailles d'écran pour assurer la réactivité
- Groupez les styles par fonctionnalité plutôt que par type de sélecteur

## Plan d'action pour éliminer les styles inline restants

1. **Phase 1 : Audit des styles non harmonisés**
   - Utilisez le script `tools/audit_inline_styles.sh` pour identifier tous les fichiers contenant des styles inline ou des balises `<style>`
   - Triez les fichiers par priorité (commencez par les pages les plus utilisées)

2. **Phase 2 : Correction des styles inline**
   - Pour chaque fichier, remplacez les attributs `style="..."` par des classes CSS appropriées
   - Mettez à jour le script `harmonize-admin-styles.js` pour détecter et remplacer automatiquement les nouveaux styles inline

3. **Phase 3 : Élimination des balises `<style>`**
   - Pour chaque fichier, extrayez le contenu des balises `<style>` vers le fichier CSS approprié
   - Organisez les styles extraits par composant ou fonctionnalité
   - Assurez-vous que chaque page contient les liens vers les fichiers CSS nécessaires

4. **Phase 4 : Vérification et tests**
   - Vérifiez que toutes les pages s'affichent correctement après les modifications
   - Testez sur différents navigateurs et tailles d'écran
   - Exécutez à nouveau le script d'audit pour confirmer l'élimination des styles inline

## Maintenance

Pour vérifier s'il reste des styles inline dans le site :

```bash
./tools/audit_inline_styles.sh
```

Cela vous aidera à identifier tous les styles inline et balises `<style>` qui n'ont pas encore été harmonisés.
