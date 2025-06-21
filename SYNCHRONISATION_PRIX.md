# 💰 Synchronisation des Prix Menus - La Mangeoire

## ❌ Problème initial
Les prix affichés sur la page d'accueil (`index.php`) étaient codés en dur et ne correspondaient pas aux prix réels stockés dans la base de données.

### Exemple de l'incohérence :
- **Base de données** : Ndole = 22.87€, Eru = 22.87€, KOKI = 7.62€
- **index.php** : Bongo = 18€, Eru = 19€, Koki = 17€

## ✅ Solution implémentée

### 1. **Récupération dynamique des prix**
Ajout d'un code PHP dans `index.php` pour récupérer les prix depuis la base de données :

```php
// Récupérer les prix des menus depuis la base de données
$menu_prices = [];
try {
  $stmt = $conn->prepare("SELECT MenuID, NomItem, Prix FROM Menus");
  $stmt->execute();
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($menus as $menu) {
    $menu_prices[$menu['MenuID']] = [
      'nom' => $menu['NomItem'],
      'prix' => $menu['Prix']
    ];
  }
} catch (PDOException $e) {
  error_log("Erreur récupération prix menus: " . $e->getMessage());
}
```

### 2. **Remplacement des prix statiques**
Conversion de tous les prix codés en dur vers des prix dynamiques :

**Avant :**
```html
<p class="price">18 €</p>
```

**Après :**
```php
<p class="price"><?php echo isset($menu_prices[5]) ? number_format($menu_prices[5]['prix'], 2) : '7.62'; ?> €</p>
```

### 3. **Correction des IDs de menu**
Alignement des IDs dans les formulaires avec ceux de la base de données :

| Plat | ID Correct | Prix Correct |
|------|------------|--------------|
| Ndole | 1 | 22.87€ |
| Eru | 2 | 22.87€ |
| KOKI | 3 | 7.62€ |
| OKOK | 4 | 15.24€ |
| BONGO | 5 | 7.62€ |
| Taro | 6 | 7.62€ |
| Poisson Braisé | 7 | 15.24€ |

## 🎯 Avantages de cette approche

### ✅ **Synchronisation automatique**
- Plus besoin de modifier manuellement les prix dans le code
- Changement dans l'admin → Mise à jour immédiate sur le site

### ✅ **Cohérence des données**
- Les prix affichés correspondent exactement à ceux de la base
- Élimination des erreurs de saisie manuelle

### ✅ **Facilité de maintenance**
- Un seul endroit pour modifier les prix (interface admin)
- Gestion centralisée des données

### ✅ **Gestion d'erreurs robuste**
- Fallback sur prix par défaut en cas de problème de base
- Logs d'erreurs pour le débogage

## 🧪 Comment tester

1. **Accédez à** `test-prix-sync.php` pour voir le résumé des prix
2. **Consultez** `index.php#menu` pour voir les prix synchronisés
3. **Modifiez un prix** dans l'admin (`admin/menus.php`)
4. **Rechargez** `index.php` → Le prix doit être mis à jour automatiquement

## 📁 Fichiers modifiés

- ✅ `index.php` - Ajout récupération dynamique + remplacement prix statiques
- ✅ `test-prix-sync.php` - Script de vérification créé

## 🚀 Résultat final

Votre site affiche maintenant les **prix réels et à jour** depuis votre base de données ! 
Fini les prix incohérents entre l'admin et le site public. 💪
