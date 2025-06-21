# Documentation d'Intégration PayPal - Restaurant La Mangeoire

Ce document détaille l'intégration de PayPal comme méthode de paiement pour le Restaurant La Mangeoire.

## Configuration

L'intégration PayPal utilise les identifiants fournis stockés dans le fichier `.env` pour permettre aux clients de payer leurs commandes et réservations en utilisant PayPal.

### Configuration dans le fichier .env

Ajoutez les lignes suivantes à votre fichier `.env` :

```
# Configuration PayPal
PAYPAL_CLIENT_ID=AR7B2Pm1rhiX1ZiHHapFBxB9WjBNx6rEakKYj-BD6Hc8O8WY5dv5KKWpqxtbD1nxmIWc_nH-FfHZn5nb
PAYPAL_SECRET_KEY=EBFf91y4FdKvcsWEZ9zwu24Y5jk5s209Zr83juNV1vlqpZen1Dr7KTTFPvcXGkueTTC8WSrrOekJOrKP
PAYPAL_MODE=sandbox
PAYPAL_WEBHOOK_ID=your_paypal_webhook_id
```

**Important :** Ces identifiants sont actuellement configurés pour le mode sandbox (test). En production, remplacez-les par vos identifiants de production PayPal.

## Fichiers concernés

L'intégration PayPal implique les fichiers suivants :

1. `includes/paypal-config.php` - Configuration et fonctions utilitaires pour PayPal
2. `payer-commande.php` - Interface de paiement avec l'option PayPal
3. `confirmation-paypal.php` - Page de confirmation après paiement PayPal

## Fonctionnement

### Processus de paiement

1. L'utilisateur choisit PayPal comme méthode de paiement sur la page de paiement
2. Le système crée une commande PayPal via l'API PayPal
3. L'utilisateur est redirigé vers PayPal pour compléter son paiement
4. Après le paiement, PayPal redirige l'utilisateur vers la page de confirmation
5. La page de confirmation vérifie et enregistre le paiement dans la base de données

### Mode Sandbox vs Production

Actuellement, l'intégration est configurée en mode sandbox (test). Pour passer en production :

1. Ouvrir `includes/paypal-config.php`
2. Changer `define('PAYPAL_MODE', 'sandbox');` en `define('PAYPAL_MODE', 'live');`
3. S'assurer que les identifiants de production sont utilisés

## Installation des dépendances

Un script d'installation a été créé pour faciliter l'ajout de la bibliothèque PayPal :

```
php install_paypal_sdk.php
```

## Amélioration futures possibles

1. Ajout des Webhooks PayPal pour une meilleure gestion des événements de paiement
2. Gestion des paiements récurrents pour les abonnements
3. Support des remboursements via l'interface d'administration
4. Mémorisation des préférences de paiement des clients

## Dépannage

### Problèmes courants

1. **Erreur d'authentification :** Vérifier que les identifiants PayPal sont corrects
2. **Redirection échouée :** Vérifier les URL de succès et d'annulation
3. **Paiement non enregistré :** Vérifier la connexion à la base de données et les requêtes SQL

### Journalisation

Les erreurs PayPal sont enregistrées dans le journal d'erreurs PHP standard. Pour un débogage plus détaillé, vous pouvez ajouter des instructions `error_log()` supplémentaires dans les fichiers de traitement PayPal.

## Support

Pour toute assistance technique concernant l'intégration PayPal, contactez l'équipe de développement à dev@la-mangeoire.com.
