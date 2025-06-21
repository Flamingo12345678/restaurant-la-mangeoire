# Intégration Stripe - Restaurant La Mangeoire

Ce document décrit l'intégration de Stripe pour le système de paiement du Restaurant La Mangeoire.

## Prérequis

1. Créer un compte sur [Stripe](https://stripe.com)
2. Obtenir les clés API de test et de production
3. Configurer les webhooks pour le traitement des événements de paiement

## Installation

1. Installer la bibliothèque PHP Stripe via Composer:
   ```bash
   composer require stripe/stripe-php
   ```

2. Copier le fichier `.env.example` en `.env` et mettre à jour les clés API:
   ```bash
   cp .env.example .env
   ```

3. Modifier les clés dans le fichier `.env`:
   ```
   STRIPE_PUBLISHABLE_KEY=votre_cle_publique
   STRIPE_SECRET_KEY=votre_cle_secrete
   STRIPE_WEBHOOK_SECRET=votre_cle_webhook
   ```

4. Vérifier l'installation avec le script de test:
   ```bash
   php test-stripe.php
   ```

## Résolution de problèmes

Si vous rencontrez l'erreur "Class Stripe\Stripe not found", assurez-vous que:

1. L'autoloader de Composer est chargé avant d'utiliser Stripe:
   ```php
   require_once 'vendor/autoload.php'; // Doit être chargé en premier
   require_once 'includes/stripe-config.php';
   ```

2. Exécutez `composer dump-autoload` pour reconstruire l'autoloader
3. Vérifiez que le package Stripe est correctement installé avec `composer show stripe/stripe-php`
4. Si les problèmes persistent, réinstallez le package:
   ```bash
   composer remove stripe/stripe-php && composer require stripe/stripe-php
   ```

## Configuration

La configuration de Stripe se trouve dans le fichier `includes/stripe-config.php`. Ce fichier définit:

- Les clés API (récupérées depuis les variables d'environnement)
- La devise par défaut (XAF - Franc CFA)
- La langue par défaut (fr)
- Les URLs de redirection après paiement
- La version de l'API Stripe à utiliser

## Fonctionnement

### Flux de paiement

1. **Choix du mode de paiement**
   - Dans `payer-commande.php`, l'utilisateur peut choisir entre le paiement par Stripe ou le paiement manuel
   
2. **Paiement par Stripe**
   - Une session Stripe Checkout est créée
   - L'utilisateur est redirigé vers la page de paiement Stripe
   - Après paiement, Stripe redirige vers `confirmation-stripe.php`
   
3. **Confirmation et gestion des paiements**
   - `confirmation-stripe.php` vérifie le statut du paiement auprès de Stripe
   - Si le paiement est réussi, la commande/réservation est mise à jour et un enregistrement est créé dans la table Paiements
   
4. **Webhooks Stripe**
   - `stripe-webhook.php` reçoit les notifications de Stripe
   - Les événements sont traités et journalisés
   - Les paiements sont confirmés même si l'utilisateur ne revient pas sur le site

### Types de paiements supportés

1. **Paiement de commandes**
   - Lie le paiement à une commande dans la table `Commandes`
   - Met à jour le statut de la commande à "Payé"
   
2. **Paiement de réservations**
   - Lie le paiement à une réservation dans la table `Reservations`
   - Met à jour le statut de la réservation à "Confirmé"

## Fichiers importants

- `includes/stripe-config.php` - Configuration Stripe
- `payer-commande.php` - Page de paiement avec intégration Stripe
- `confirmation-stripe.php` - Page de confirmation après paiement Stripe
- `stripe-webhook.php` - Gestionnaire des webhooks Stripe

## Sécurité

- Les clés API sont stockées dans des variables d'environnement
- Les données de carte ne transitent jamais par notre serveur (elles sont traitées directement par Stripe)
- Les webhooks sont sécurisés par signature

## Mode test vs Mode production

Par défaut, l'application utilise les clés de test Stripe. Pour passer en production:

1. Remplacer les clés de test par les clés de production dans le fichier `.env`
2. Mettre à jour l'URL du webhook dans le dashboard Stripe avec l'URL de production
3. Créer une nouvelle clé de signature webhook pour l'environnement de production

## Dépannage

En cas de problème avec les paiements Stripe:

1. Vérifier les logs dans le dossier `logs/stripe_webhooks.log`
2. Consulter le dashboard Stripe pour voir le statut des paiements
3. Vérifier que les webhooks sont correctement configurés
4. S'assurer que les clés API sont correctes dans le fichier `.env`
