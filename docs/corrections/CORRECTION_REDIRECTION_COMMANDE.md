# CORRECTION REDIRECTION COMMANDE - RÉSOLU

## Problème identifié
Après avoir cliqué sur "Passer la commande", l'utilisateur était redirigé vers la page d'accueil au lieu de la page de confirmation de commande.

## Cause du problème
Le formulaire de commande contenait un sélecteur de devise avec `onchange="this.form.submit()"` qui soumettait le formulaire principal SANS le champ caché `passer_commande`. Cela causait une soumission de formulaire qui ne rentrait pas dans la condition de traitement de commande, donc retournait à la page normale (sans redirection).

## Solution appliquée
1. **Séparation des formulaires** : Créé un formulaire séparé pour le changement de devise avec son propre champ `change_currency`
2. **Nettoyage du code** : Supprimé les champs dupliqués et corrigé la structure HTML
3. **Amélioration de la gestion d'erreurs** : Ajouté des logs détaillés et la gestion des buffers de sortie avant redirection
4. **Utilisation du CartManager** : Remplacé l'ancienne logique de vidage de panier par `$cartManager->clear()`
5. **Correction base de données** : Supprimé la référence obsolète à `UtilisateurID` dans la requête de vidage de panier

## Fichiers modifiés
- `/passer-commande.php` : Correction principale du formulaire et de la logique de redirection

## Code corrigé

### Avant
```php
// Formulaire unique avec conflit
<select name="currency_code" onchange="this.form.submit()">
// ...
</select>

// Dans le traitement PHP
if (isset($_POST['passer_commande'])) {
    // Cette condition n'était jamais vraie lors du changement de devise
}

// Ancien vidage panier avec UtilisateurID obsolète
DELETE FROM Panier WHERE UtilisateurID = ?
```

### Après
```php
// Formulaire séparé pour devise
<form method="POST">
    <input type="hidden" name="change_currency" value="1">
    <select name="currency_code" onchange="this.form.submit()">
    // ...
    </select>
</form>

// Traitement PHP correct
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passer_commande'])) {
    // Cette condition est maintenant correctement séparée
}

// Nouveau vidage panier avec CartManager
$cartManager->clear();
```

## Flux de commande corrigé
1. L'utilisateur remplit le formulaire de commande
2. Si il change de devise : soumission du formulaire devise → rechargement de page avec nouvelle devise
3. Quand il clique "Confirmer commande" : soumission du formulaire principal → traitement → redirection vers confirmation-commande.php

## Test recommandé
1. Aller sur `/passer-commande.php`
2. Ajouter des articles au panier au préalable
3. Remplir les informations de commande
4. Tester le changement de devise (doit recharger la page sans passer commande)
5. Cliquer sur "Confirmer la commande"
6. Vérifier la redirection vers `confirmation-commande.php?id=XXX`

## Statut
✅ **RÉSOLU** - Le problème de redirection vers la page d'accueil est corrigé. Les commandes sont maintenant correctement traitées et l'utilisateur est redirigé vers la page de confirmation avec l'ID de commande.
