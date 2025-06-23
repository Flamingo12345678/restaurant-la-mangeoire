# 🎯 SYSTÈME DE SCRIPTS JAVASCRIPT OPTIMISÉ

## 📋 Fonctionnement

Le système de templates harmonisés gère automatiquement les scripts JavaScript communs et permet l'ajout de scripts spécifiques par page.

## 🚀 Scripts Communs (Automatiques)

Les scripts suivants sont chargés automatiquement sur toutes les pages admin :

```php
$common_scripts = array(
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    $asset_path . 'js/main.js',
    $asset_path . 'js/admin-sidebar.js', 
    $asset_path . 'js/admin-animations.js',
    $asset_path . 'js/admin-modals.js',
    $asset_path . 'js/harmonize-admin-styles.js'
);
```

### Rôle de chaque script :
- **bootstrap.bundle.min.js** : Framework Bootstrap (modales, dropdowns, etc.)
- **main.js** : Fonctionnalités générales du site
- **admin-sidebar.js** : Navigation sidebar responsive
- **admin-animations.js** : Animations et transitions
- **admin-modals.js** : Gestion des modales de confirmation
- **harmonize-admin-styles.js** : Harmonisation automatique des styles

## 🎨 Scripts Spécifiques par Page

### Dans le <head> (pour l'initialisation)

```php
<?php
// Définir des scripts à charger dans le head (avant le body)
$head_js = array(
    'assets/js/chart.min.js',           // Charts pour dashboard
    'assets/js/custom-config.js'        // Configuration spécifique
);

require_once 'header_template.php';
?>
```

### Dans le footer (standard)

```php
<?php
// Définir des scripts à charger en fin de page
$additional_js = array(
    'assets/js/datatables.min.js',      // DataTables pour tableaux
    'assets/js/page-specific.js'        // Script spécifique à cette page
);

// Le contenu de votre page...

require_once 'footer_template.php';
?>
```

## 🔧 CSS Spécifiques par Page

```php
<?php
// Définir des CSS spécifiques à la page
$additional_css = array(
    'assets/css/charts.css',
    'assets/css/custom-dashboard.css'
);

require_once 'header_template.php';
?>
```

## 📝 Exemple Complet d'Utilisation

### Nouvelle page admin avec scripts personnalisés :

```php
<?php
require_once 'check_admin_access.php';
define('INCLUDED_IN_PAGE', true);
$page_title = "Statistiques Avancées";

// CSS spécifiques à cette page
$additional_css = array(
    'assets/css/charts.css',
    'assets/css/advanced-stats.css'
);

// Scripts à charger dans le head
$head_js = array(
    'assets/js/chart.min.js',
    'assets/js/moment.min.js'
);

// Scripts à charger en fin de page
$additional_js = array(
    'assets/js/advanced-charts.js',
    'assets/js/statistics-page.js'
);

require_once 'header_template.php';
?>

<!-- Contenu de votre page -->
<div class="container-fluid">
    <h1>Statistiques Avancées</h1>
    <div id="chart-container"></div>
</div>

<?php require_once 'footer_template.php'; ?>
```

## ⚡ Optimisations Intégrées

### 1. Chargement Conditionnel
Les scripts ne sont inclus que s'ils sont définis :
```php
if (isset($additional_js) && is_array($additional_js)) {
    // Chargement seulement si nécessaire
}
```

### 2. Chemins Relatifs Intelligents
Le système détecte automatiquement le niveau de répertoire :
```php
$is_in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
$asset_path = $is_in_admin_folder ? '../assets/' : 'assets/';
```

### 3. Scripts d'Optimisation Mobile
Scripts intégrés automatiquement dans le footer :
- Optimisation des cartes stats sur mobile
- Gestion du burger menu
- Prévention du zoom iOS sur les inputs
- Optimisation du scrolling tactile

## 🛠️ Modification des Scripts Communs

Pour modifier la liste des scripts communs, éditez `admin/footer_template.php` :

```php
$common_scripts = array(
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    $asset_path . 'js/main.js',
    $asset_path . 'js/admin-sidebar.js',
    // Ajouter votre nouveau script commun ici
    $asset_path . 'js/nouveau-script-commun.js'
);
```

## 📊 Performance

### Avantages du système :
- ✅ **Centralisation** : Un seul endroit pour gérer les scripts communs
- ✅ **Flexibilité** : Scripts spécifiques par page possibles
- ✅ **Performance** : Chargement conditionnel
- ✅ **Maintenabilité** : Structure claire et documentée
- ✅ **Compatibilité** : Fonctionne avec tous les navigateurs

### Taille des assets :
- Bootstrap CDN : ~59 KB (gzippé)
- Scripts locaux communs : ~15 KB total
- Chargement asynchrone optimisé

## 🎯 Cas d'Usage Courants

### Dashboard avec graphiques :
```php
$head_js = array('assets/js/chart.js');
$additional_js = array('assets/js/dashboard-charts.js');
```

### Page de gestion avec DataTables :
```php
$additional_js = array('assets/js/datatables.min.js', 'assets/js/admin-tables.js');
```

### Page avec éditeur riche :
```php
$head_js = array('assets/js/tinymce.min.js');
$additional_js = array('assets/js/editor-config.js');
```

## 🔄 Migration depuis l'Ancien Système

Si vous avez des pages utilisant l'ancien système, remplacez :

```php
// Ancien système
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
```

Par :

```php
// Nouveau système
<?php
$additional_js = array('assets/js/custom.js');
require_once 'footer_template.php';
?>
```

---

*Système optimisé pour Restaurant La Mangeoire - Interface Admin Harmonisée*
