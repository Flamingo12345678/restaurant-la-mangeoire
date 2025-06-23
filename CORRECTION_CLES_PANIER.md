# CORRECTION_CLES_PANIER.md

## Problème résolu : Clés de données inconsistantes

**Date :** 23 juin 2025
**Erreurs :** 
- `Warning: Undefined array key "Prix" in passer-commande.php on line 515`
- `Warning: Undefined array key "Quantite" in passer-commande.php on line 515`

### Diagnostic

Le problème venait d'une inconsistance entre les clés de données retournées par le `CartManager` et celles utilisées dans `passer-commande.php` :

**CartManager retourne :**
- `name` (au lieu de `NomItem`)
- `price` (au lieu de `Prix`) 
- `quantity` (au lieu de `Quantite`)
- `menu_id` (au lieu de `MenuID`)

**passer-commande.php utilisait :**
- `NomItem`, `Prix`, `Quantite`, `MenuID` (anciennes clés BDD)

### Solution appliquée

✅ **Correction dans la boucle d'insertion en base (lignes 113-122) :**
```php
// AVANT
$item['Prix'] * $item['Quantite']
$item['MenuID'], $item['NomItem'], $item['Prix'], $item['Quantite']

// APRÈS  
$item['price'] * $item['quantity']
$item['menu_id'], $item['name'], $item['price'], $item['quantity']
```

✅ **Correction dans l'affichage HTML (lignes 511-515) :**
```php
// AVANT
$item['NomItem'], $item['Quantite'], $item['Prix'] * $item['Quantite']

// APRÈS
$item['name'], $item['quantity'], $item['price'] * $item['quantity']
```

### Architecture des données CartManager

Le `CartManager` normalise les données avec des clés en anglais pour une cohérence :

**Depuis la base de données :**
```sql
SELECT 
    p.MenuID as menu_id,
    m.NomItem as name,
    m.Prix as price,
    p.Quantite as quantity
```

**Session :**
```php
[
    'menu_id' => $id,
    'name' => $nom,
    'price' => $prix,
    'quantity' => $quantite
]
```

### Résultat

✅ Plus d'erreurs PHP "Undefined array key"
✅ Affichage des articles du panier fonctionnel  
✅ Insertion en base de données fonctionnelle
✅ Cohérence des données dans tout le système

Le système de commande est maintenant totalement opérationnel sans warnings PHP.
