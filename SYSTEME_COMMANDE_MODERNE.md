# Système de Commandes et Paiements Modernes
## Restaurant La Mangeoire - Implémentation Finale

### 📋 Vue d'ensemble

Le nouveau système de commandes et paiements remplace l'ancien système fragmenté par une architecture moderne, unifiée et sécurisée basée sur :

- **Panier client-side** : localStorage pour persistance multi-onglets/appareils
- **API REST** : Endpoints pour commandes, paiements et synchronisation
- **Gestionnaires PHP** : OrderManager et PaymentManager pour la logique métier
- **Base de données moderne** : Nouveau schéma optimisé pour les commandes
- **Interface unifiée** : commande-moderne.php pour finaliser les achats

### 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   API REST      │    │   Backend       │
│                 │    │                 │    │                 │
│ • menu.php      │◄──►│ /api/orders/    │◄──►│ OrderManager    │
│ • panier.php    │    │ /api/payments/  │    │ PaymentManager  │
│ • commande-     │    │ /api/cart/      │    │ CurrencyManager │
│   moderne.php   │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
        │                       │                       │
        ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  localStorage   │    │  Validation &   │    │  Base de données│
│                 │    │  Transformation │    │                 │
│ • Panier        │    │                 │    │ • CommandesModernes│
│ • Persistance   │    │ • Sécurité      │    │ • PaiementsModernes│
│ • Multi-device  │    │ • Formatage     │    │ • ArticlesCommande │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 📁 Structure des fichiers

#### Nouveaux fichiers créés
```
includes/
├── order-manager.php         # Gestionnaire de commandes
├── payment-manager.php       # Gestionnaire de paiements
└── currency_manager.php      # Gestion des devises (existant)

api/
├── orders/
│   └── index.php            # API REST commandes
├── payments/
│   └── index.php            # API REST paiements
└── cart/
    └── index.php            # API REST panier

sql/
└── schema_commandes_modernes.sql  # Nouveau schéma DB

commande-moderne.php         # Interface de commande unifiée
SYSTEME_COMMANDE_MODERNE.md  # Cette documentation
```

#### Fichiers modifiés
```
menu.php                     # Système d'ajout au panier moderne
panier.php                   # Affichage panier moderne (client-side)
assets/css/main.css          # Styles pour les nouvelles interfaces
```

### 🗄️ Base de données

Le nouveau schéma utilise 5 tables principales :

#### CommandesModernes
```sql
- CommandeID (PK, auto-increment)
- NumeroCommande (unique, format: CMD-YYYY-XXXXXX)
- ClientID (FK nullable pour invités)
- NomClient, EmailClient, TelephoneClient (pour invités)
- TypeCommande (emporter, livraison, sur_place)
- StatutCommande (en_attente, confirmee, en_preparation, etc.)
- MontantTotal, SousTotal, TaxesTotal, FraisLivraison (en centimes)
- DeviseCode, TauxConversion
- AdresseLivraison, VilleLivraison, CodePostalLivraison
- DateCommande, DateConfirmation, DateLivraison
- NotesSpeciales, IPClient, UserAgent
```

#### ArticlesCommande
```sql
- ArticleID (PK)
- CommandeID (FK)
- MenuID (FK)
- NomArticle, DescriptionArticle
- PrixUnitaire, Quantite, SousTotal (en centimes)
- PersonnalisationsJSON, NotesSpeciales
```

#### PaiementsModernes
```sql
- PaiementID (PK)
- CommandeID (FK)
- MethodePaiement (stripe_card, paypal, especes, virement)
- MontantCentimes, DeviseCode, StatutPaiement
- DateCreation, DateReussite, DateEchec
- MetadataJSON (données spécifiques au fournisseur)
```

#### LogsCommandes & LogsPaiements
Tables d'audit pour traçabilité complète.

### 🔄 Workflow utilisateur

#### 1. Navigation et sélection
```
menu.php → Affichage dynamique des plats
         → Ajout au panier (localStorage)
         → Notifications toasts
         → Persistance multi-onglets
```

#### 2. Révision du panier
```
panier.php → Affichage des articles (JS)
           → Modification des quantités
           → Suppression d'articles
           → Calcul des totaux en temps réel
           → Bouton "Passer commande"
```

#### 3. Finalisation de commande
```
commande-moderne.php → Formulaire unifié
                     → Informations client
                     → Type de livraison
                     → Notes spéciales
                     → Sélection paiement
                     → Résumé commande
```

#### 4. Traitement backend
```
API /orders/ → Validation du panier
             → Création de la commande
             → Génération du numéro
             → Calcul des totaux

API /payments/ → Création intention paiement
               → Intégration Stripe/PayPal
               → Gestion des webhooks
               → Confirmation/échec
```

#### 5. Confirmation
```
confirmation-commande.php → Affichage récapitulatif
                          → Instructions paiement
                          → Statut de la commande
                          → Actions client
```

### 💳 Intégration paiements

#### Méthodes supportées

1. **Carte bancaire (Stripe)**
   - PaymentIntents API
   - SCA (Strong Customer Authentication)
   - Webhooks pour confirmation automatique
   - Gestion des erreurs et retry

2. **PayPal**
   - Orders API v2
   - Redirections sécurisées
   - Capture automatique
   - Notifications IPN

3. **Espèces**
   - Confirmation manuelle par le personnel
   - Statut "en attente" jusqu'à réception
   - Interface admin pour validation

4. **Virement bancaire**
   - Génération référence unique
   - Instructions de paiement
   - Rapprochement manuel

#### Configuration
Variables d'environnement dans `.env` :
```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_SANDBOX=true
```

### 🛡️ Sécurité

#### Validation des données
- Validation côté client (UX) ET serveur (sécurité)
- Vérification prix/disponibilité en temps réel
- Protection CSRF avec tokens
- Validation des types et formats

#### Gestion des erreurs
- Logs détaillés pour debugging
- Messages d'erreur utilisateur-friendly
- Rollback automatique des transactions
- Monitoring des tentatives de fraude

#### Protection des données
- Chiffrement des données sensibles
- Conformité PCI DSS pour les paiements
- Audit trail complet
- RGPD compliance

### 📊 API REST

#### Endpoints commandes
```
GET    /api/orders/           # Rechercher commandes
GET    /api/orders/{id}       # Détails d'une commande
POST   /api/orders/           # Créer une commande
PUT    /api/orders/{id}/status # Mettre à jour statut
POST   /api/orders/validate-cart # Valider un panier
```

#### Endpoints paiements
```
GET    /api/payments/         # Rechercher paiements
GET    /api/payments/{id}     # Détails d'un paiement
POST   /api/payments/create-intent # Créer intention paiement
POST   /api/payments/webhook/{provider} # Webhooks
POST   /api/payments/confirm  # Confirmation manuelle
GET    /api/payments/methods  # Méthodes disponibles
```

#### Endpoints panier
```
GET    /api/cart/validate     # Valider articles
GET    /api/cart/totals       # Calculer totaux
GET    /api/cart/items        # Détails articles menu
POST   /api/cart/sync         # Synchroniser panier client
POST   /api/cart/validate     # Validation complète
```

### 🔧 Administration

#### Interface admin
- Liste des commandes avec filtres
- Détails commande avec historique
- Gestion des statuts
- Confirmation paiements manuels
- Rapports et statistiques

#### Monitoring
- Logs d'erreurs centralisés
- Métriques de performance
- Alertes paiements échoués
- Rapports de réconciliation

### 🚀 Déploiement

#### Prérequis
1. PHP 7.4+ avec PDO
2. MySQL 5.7+ ou MariaDB 10.3+
3. HTTPS obligatoire (SSL/TLS)
4. Cron jobs pour tâches asynchrones

#### Installation
1. Exécuter le nouveau schéma SQL
2. Configurer les variables d'environnement
3. Installer les dépendances (Stripe SDK, etc.)
4. Configurer webhooks chez les fournisseurs
5. Tester en mode sandbox

#### Migration données
Script de migration pour l'existant :
```sql
-- Migration des anciennes commandes vers le nouveau format
-- Migration des paiements historiques
-- Nettoyage des tables obsolètes
```

### 📈 Performances

#### Optimisations
- Index sur les colonnes de recherche fréquente
- Cache Redis pour données fréquentes
- Pagination pour les listes longues
- Lazy loading des images

#### Monitoring
- Temps de réponse API
- Taux de conversion panier → commande
- Taux de succès des paiements
- Erreurs et exceptions

### 🔄 Maintenance

#### Tâches récurrentes
- Nettoyage des paniers expirés
- Réconciliation des paiements
- Archivage des anciennes commandes
- Mise à jour des taux de change

#### Backup et récupération
- Sauvegarde quotidienne de la base
- Tests de restauration mensuels
- Plan de continuité d'activité
- Procédures de rollback

### 📝 Tests

#### Tests automatisés
- Tests unitaires pour les managers
- Tests d'intégration pour les APIs
- Tests end-to-end du workflow
- Tests de charge et performance

#### Tests manuels
- Scenarios utilisateur complets
- Tests cross-browser
- Tests mobile/responsive
- Tests des webhooks

### 🆘 Dépannage

#### Problèmes courants

1. **Panier vide après rafraîchissement**
   - Vérifier localStorage activé
   - Problème de session PHP
   - Erreur JavaScript bloquante

2. **Erreur création commande**
   - Vérifier connexion DB
   - Valider données panier
   - Contrôler les logs d'erreur

3. **Paiement non confirmé**
   - Vérifier webhooks configurés
   - Contrôler les logs fournisseur
   - Confirmation manuelle si nécessaire

#### Logs importants
```
logs/orders.log          # Création/modification commandes
logs/payments.log        # Transactions et webhooks
logs/api.log            # Erreurs API REST
logs/cart.log           # Problèmes panier
```

### 🔮 Évolutions futures

#### Fonctionnalités prévues
- Programme de fidélité
- Commandes récurrentes
- Chat client intégré
- Application mobile
- Intelligence artificielle pour recommandations

#### Améliorations techniques
- Migration vers PostgreSQL
- Microservices architecture
- Intégration GraphQL
- PWA (Progressive Web App)
- Containerisation (Docker)

---

**Date de création :** 21 juin 2025  
**Version :** 1.0  
**Auteur :** Assistant IA  
**Statut :** Implémentation terminée
