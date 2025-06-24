# 📧 Système de Contact - La Mangeoire

## 🎯 Deux Formulaires de Contact

Votre site dispose maintenant de **deux formulaires de contact** complémentaires :

### 1. **Formulaire Intégré** (dans index.php)
- **Localisation** : Section #contact de la page d'accueil
- **Action** : `forms/contact.php`
- **Comportement** : Traite le formulaire et redirige vers index.php#contact
- **Messages** : Affichés en haut de la section contact avec Bootstrap alerts
- **Usage** : Contact rapide depuis la page d'accueil

### 2. **Formulaire Standalone** (contact.php)
- **Localisation** : Page dédiée `/contact.php`
- **Action** : Auto-traitement (action="")
- **Comportement** : Traite et affiche le résultat sur la même page
- **Messages** : Affichés dans la même page avec design personnalisé
- **Usage** : Contact détaillé avec interface dédiée

---

## 🔄 Flux de Fonctionnement

### Formulaire Index.php
```
User → Remplit formulaire dans index.php#contact
     → Soumission vers forms/contact.php  
     → Traitement + sauvegarde en BDD
     → Redirection vers index.php#contact
     → Affichage message de succès/erreur
```

### Formulaire Contact.php
```
User → Va sur contact.php
     → Remplit formulaire
     → Soumission vers contact.php (auto)
     → Traitement + sauvegarde en BDD
     → Affichage message sur la même page
```

---

## 🗄️ Base de Données

Les deux formulaires utilisent la **même table** `Messages` :

```sql
CREATE TABLE Messages (
    MessageID INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    objet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('Nouveau', 'Lu', 'Traité') DEFAULT 'Nouveau'
);
```

---

## 📁 Structure des Fichiers

```
restaurant-la-mangeoire/
├── index.php                 # Page d'accueil avec formulaire intégré
├── contact.php              # Page de contact standalone
├── forms/
│   └── contact.php          # Handler pour formulaire index.php
├── db_connexion.php         # Connexion base de données
└── create_messages_table.php # Script de création table
```

---

## 🎨 Différences de Design

### Formulaire Index.php
- **Style** : Intégré au design de la page d'accueil
- **Layout** : 2 colonnes pour nom/email, pleine largeur pour objet/message
- **Messages** : Bootstrap alerts en haut de section
- **Bouton** : Style standard du site

### Formulaire Contact.php  
- **Style** : Design moderne avec dégradés rouge
- **Layout** : Formulaire en colonnes avec labels flottants
- **Messages** : Boîtes personnalisées avec icônes
- **Bouton** : Style dégradé avec animations hover

---

## ✅ Avantages de Cette Architecture

1. **Flexibilité** : Deux options selon le contexte utilisateur
2. **Cohérence** : Même traitement de données pour les deux
3. **UX** : Contact rapide OU contact détaillé selon les besoins
4. **Maintenance** : Une seule table de données à gérer
5. **SEO** : Page de contact dédiée + section sur page d'accueil

---

## 🚀 Utilisation

### Pour Contact Rapide
→ Dirigez les utilisateurs vers `index.php#contact`

### Pour Contact Détaillé  
→ Dirigez les utilisateurs vers `contact.php`

### Navigation
- Bouton "Contact" dans le menu → `contact.php`
- Section contact de l'accueil → Formulaire intégré
- Lien "Retour à l'accueil" dans contact.php → `index.php`

---

## 🔧 Personnalisation

### Modifier les Messages
- **Succès** : Dans `forms/contact.php` et `contact.php`
- **Erreurs** : Dans les mêmes fichiers

### Modifier le Design
- **Index.php** : CSS dans le thème principal
- **Contact.php** : CSS inline dans le fichier

### Ajouter des Champs
1. Modifier les deux formulaires HTML
2. Modifier les deux handlers PHP
3. Modifier la table Messages si nécessaire

---

**🎉 Votre système de contact est maintenant complet et professionnel !**
