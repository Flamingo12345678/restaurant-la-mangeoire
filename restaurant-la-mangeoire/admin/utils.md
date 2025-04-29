# Utilitaires PHP – restaurant-la-mangeoire

Ce fichier documente les fonctions utilitaires fournies dans `admin/utils.php` pour la gestion sécurisée et harmonisée de l'application web.

## Fonctions de sécurité

### CSRF

- **get_csrf_token()** :
  - Génère et retourne un token CSRF unique pour la session courante.
  - À inclure dans tous les formulaires sensibles via un champ caché.
- **check_csrf_token($token)** :
  - Vérifie la validité du token CSRF reçu.
  - Invalide le token après usage pour éviter la réutilisation.

### Échappement HTML

- **e($string)** :
  - Retourne la chaîne échappée pour affichage HTML sécurisé (protection XSS).
  - À utiliser pour toute sortie de données utilisateur ou base de données dans le HTML.

## Gestion des messages utilisateur

- **set_message($msg, $type = 'info')** :
  - Stocke un message flash en session, avec un type (`info`, `success`, `warning`, `danger`).
- **get_message()** :
  - Récupère et supprime le message flash courant de la session.
  - Retourne un tableau associatif `['text' => ..., 'type' => ...]` ou `null`.

## Gestion des erreurs PDO

- **handle_pdo_exception($e, $action = '')** :
  - Journalise l'exception PDO et affiche un message d'erreur générique à l'utilisateur.

## Validation

- **validate_length($value, $min, $max)** :
  - Vérifie que la longueur d'une chaîne est comprise entre `$min` et `$max` (inclus).

## Contrôle d'accès

- **require_role($role = 'superadmin')** :
  - Vérifie que l'utilisateur courant possède le rôle requis (par défaut `superadmin`).
  - Redirige vers l'accueil admin en cas d'accès interdit.

---

**Bonnes pratiques :**

- Toujours utiliser `e()` pour afficher des données dynamiques dans le HTML.
- Toujours inclure un token CSRF dans les formulaires et vérifier sa validité côté serveur.
- Utiliser `set_message` et `get_message` pour afficher des retours utilisateur harmonisés.
- Centraliser la gestion des erreurs et des droits via ces utilitaires.
