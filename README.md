# restaurant-la-mangeoire

## Refonte CSS et Harmonisation des Styles

Le site a bénéficié d'une refonte CSS pour améliorer la cohérence visuelle et faciliter la maintenance :

- Élimination des styles inline et centralisation dans des fichiers CSS
- Création d'une architecture CSS modulaire et réutilisable
- Mise en place de scripts d'harmonisation automatique des styles
- Documentation complète des standards CSS avec guide de style

### Fichiers CSS principaux

- `main.css` - Styles principaux pour tout le site
- `admin.css` - Styles pour le tableau de bord administratif
- `admin-inline-fixes.css` - Remplacements des styles inline dans les pages administratives
- `auth-pages.css` - Styles pour les pages d'authentification
- `employes-admin.css` - Styles spécifiques aux pages employés et administrateurs

### Outils de maintenance

- Script d'audit des styles inline (`tools/audit_inline_styles.sh`)
- Guide d'harmonisation des styles (`docs/CSS_STYLE_GUIDE.md`)
- Scripts JavaScript d'harmonisation automatique (`harmonize-admin-styles.js`, `harmonize-auth-styles.js`)
