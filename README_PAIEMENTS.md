# Système de Paiement - Restaurant La Mangeoire

Ce document décrit le système de paiement intégré au site web du restaurant La Mangeoire. Le système permet de gérer deux types de paiements :
1. Paiements de commandes
2. Paiements de réservations

## Structure de la Base de Données

### Table `Paiements`

```sql
CREATE TABLE IF NOT EXISTS Paiements (
    PaiementID INT AUTO_INCREMENT PRIMARY KEY,
    CommandeID INT,
    ReservationID INT NULL,
    Montant DECIMAL(10,2) NOT NULL,
    MethodePaiement VARCHAR(50) NOT NULL,
    NumeroTransaction VARCHAR(100),
    DatePaiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CommandeID) REFERENCES Commandes (CommandeID) ON DELETE CASCADE,
    FOREIGN KEY (ReservationID) REFERENCES Reservations (ReservationID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Modification de la Table `Commandes`

Un champ `DatePaiement` a été ajouté à la table `Commandes` pour suivre la date à laquelle une commande a été payée.

## Flux de Paiement

### Flux de Paiement pour les Commandes

1. Le client passe une commande
2. La commande est créée avec le statut "En attente"
3. Le client procède au paiement via la page `payer-commande.php`
4. Une fois le paiement effectué :
   - Un enregistrement est créé dans la table `Paiements` avec le `CommandeID` correspondant
   - Le statut de la commande est mis à jour à "Payé"
   - La `DatePaiement` de la commande est mise à jour
5. Le client est redirigé vers `confirmation-paiement.php` avec les détails du paiement

### Flux de Paiement pour les Réservations

1. Le client fait une réservation
2. La réservation est créée avec le statut "En attente"
3. Le client procède au paiement des arrhes (ou du montant complet) via la page `payer-commande.php`
4. Une fois le paiement effectué :
   - Un enregistrement est créé dans la table `Paiements` avec le `ReservationID` correspondant
   - Le statut de la réservation est mis à jour à "Confirmé"
5. Le client est redirigé vers `confirmation-paiement.php` avec les détails du paiement

## Fonctionnalités Principales

### Côté Client

1. **Page Mon Compte** (`mon-compte.php`)
   - Affiche l'historique des paiements du client
   - Permet de filtrer les paiements par type (commande/réservation) et par date
   - Offre des liens vers les détails des commandes ou réservations

2. **Page Détail Commande** (`detail-commande.php`)
   - Affiche les informations de paiement si la commande est payée
   - Propose un bouton "Payer maintenant" si la commande n'est pas encore payée

3. **Page de Paiement** (`payer-commande.php`)
   - Gère le processus de paiement pour les commandes et les réservations
   - Affiche un formulaire pour saisir les informations de carte bancaire
   - Simule une transaction et met à jour le statut

4. **Page de Confirmation** (`confirmation-paiement.php`)
   - Affiche les détails du paiement effectué
   - Propose des liens pour retourner à l'accueil ou consulter ses commandes/réservations

### Côté Administrateur

1. **Gestion des Paiements** (`admin/paiements.php`)
   - Liste tous les paiements effectués
   - Permet d'ajouter manuellement un paiement (pour les paiements en personne)
   - Permet de supprimer un paiement (avec confirmation)
   - Affiche des informations détaillées sur chaque paiement

## Test du Système

Un script de test complet est disponible pour vérifier le bon fonctionnement du système de paiement :
- `test-paiements-complet.php` : teste le flux de paiement complet, y compris la création de commandes/réservations, l'ajout de paiements et la vérification des statuts

## Notes d'Implémentation

- Le système utilise une simulation de paiement par carte pour les besoins de la démonstration
- Dans un environnement de production, il faudrait intégrer un prestataire de paiement sécurisé
- Les numéros de cartes ne sont jamais stockés dans la base de données
- Seules les 4 derniers chiffres du numéro de carte sont utilisés pour référence

## Évolutions Futures

- Intégration d'une passerelle de paiement réelle (Stripe, PayPal, etc.)
- Ajout de méthodes de paiement supplémentaires (PayPal, virement bancaire, etc.)
- Système d'envoi automatique de factures par email
- Remboursements et annulations de paiements
- Statistiques de paiement pour l'administration
