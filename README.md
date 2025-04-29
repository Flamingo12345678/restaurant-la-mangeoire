# Restaurant La Mangeoire – Application de gestion

Ce projet est une application web PHP pour la gestion d’un restaurant (clients, réservations, tables, menus, commandes, paiements, employés, etc.).

## Fonctionnalités principales

- Gestion des clients, réservations, tables, menus, commandes, paiements, employés.
- Interface d’administration sécurisée (accès par rôle, CSRF, XSS, PDO).
- Utilitaires centralisés pour la sécurité, la validation et la gestion des messages.
- Utilisation de PDO pour toutes les requêtes SQL (protection contre les injections).
- Protection CSRF sur tous les formulaires et actions destructives.
- Échappement HTML systématique via la fonction `e()`.
- Gestion harmonisée des messages utilisateur (succès, erreur, etc.).

## Structure du projet

- **/admin/** : scripts d’administration (CRUD, listing, login, etc.)
- **/assets/** : ressources statiques (CSS, JS, images)
- **/vendor/** : dépendances PHP (Composer)
- **db_connexion.php** : connexion PDO à la base de données
- **utils.php** : utilitaires de sécurité et de gestion
- **clients.php, reservations.php, ...** : pages publiques

## Installation

1. Cloner le dépôt ou copier les fichiers sur votre serveur PHP.
2. Installer les dépendances :
   ```sh
   composer install
   ```
3. Configurer la base de données dans `db_connexion.php` ou via `.env` si utilisé.
4. Importer le schéma SQL (`restaurant_la_mangeoire.sql`).
5. Accéder à `/admin/login.php` pour l’administration.

## Sécurité

- **CSRF** : tous les formulaires utilisent un token CSRF généré et vérifié via `admin/utils.php`.
- **XSS** : toutes les sorties HTML passent par la fonction `e()`.
- **PDO** : toutes les requêtes SQL utilisent des requêtes préparées.
- **Contrôle d’accès** : les pages sensibles requièrent un rôle administrateur.

## Documentation des utilitaires

Voir [`admin/utils.md`](admin/utils.md).

## Auteurs

Projet La Mangeoire – 2025

---

Pour toute question ou contribution, merci de contacter l’équipe projet.
