# 📧 GUIDE DU SYSTÈME D'EMAILS - RESTAURANT LA MANGEOIRE

## 🎯 Fonctionnalités

Le système d'emails automatique fonctionne avec votre formulaire de contact et envoie :

### ✅ Email à l'administrateur (vous)
- **Destinataire :** `ernestyombi20@gmail.com`
- **Contenu :** Détails complets du message client
- **Format :** Template HTML moderne avec toutes les informations

### ✅ Email de confirmation au client  
- **Destinataire :** Email du client qui a écrit
- **Contenu :** Confirmation de réception du message
- **Format :** Template HTML professionnel avec vos coordonnées

## 🔧 Configuration Active

Vos paramètres actuels dans `.env` :

```
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=ernestyombi20@gmail.com  
SMTP_PASSWORD=ptihyioqshfdqykb (configuré)
SMTP_PORT=587
SMTP_ENCRYPTION=tls

ADMIN_EMAIL=ernestyombi20@gmail.com
FROM_EMAIL=ernestyombi20@gmail.com
FROM_NAME="Restaurant La Mangeoire"

EMAIL_TEST_MODE=false (emails réels)
EMAIL_DEBUG=true (logs activés)
```

## 🚀 Comment ça fonctionne

1. **Client remplit le formulaire** sur `/contact.php`
2. **Message sauvegardé** en base de données Railway  
3. **Email admin envoyé** automatiquement à votre Gmail
4. **Email confirmation** envoyé au client
5. **Logs enregistrés** pour suivi

## 📋 Tests Effectués

- ✅ Connexion SMTP Gmail fonctionnelle
- ✅ Envoi email admin : **SUCCÈS**
- ✅ Envoi email client : **SUCCÈS**  
- ✅ Integration base de données : **SUCCÈS**
- ✅ Templates HTML : **OPÉRATIONNELS**

## 🎨 Templates Email

### Email Admin (notification)
- Titre : "🍽️ Nouveau message de contact"
- Infos : Nom, email, sujet, date, message complet
- Style : Design moderne avec couleurs du restaurant

### Email Client (confirmation)  
- Titre : "🍽️ Restaurant La Mangeoire"
- Contenu : Confirmation personnalisée avec vos coordonnées
- Style : Professionnel avec header rouge (#ce1212)

## 🔍 Mode Debug

Avec `EMAIL_DEBUG=true`, vous verrez dans les logs :
```
EmailManager: Tentative d'envoi email vers client@example.com
EmailManager: Email envoyé avec succès via mail() vers client@example.com
```

## 🧪 Mode Test

Pour tester sans envoyer de vrais emails :
```
EMAIL_TEST_MODE=true
```

Les emails seront simulés et loggés uniquement.

## 📱 Utilisation en Production

Le système est **prêt pour la production** :

1. **Formulaire contact** : http://localhost:8000/contact.php
2. **Emails automatiques** envoyés à chaque soumission
3. **Base Railway** mise à jour en temps réel
4. **Logs d'activité** pour monitoring

## 🛠️ Dépannage

### Si les emails n'arrivent pas :

1. **Vérifiez le dossier spam** de Gmail
2. **Consultez les logs** PHP
3. **Testez avec** : `php test-email-system.php`

### Variables importantes :
- `SMTP_PASSWORD` : Mot de passe d'application Gmail
- `ADMIN_EMAIL` : Votre adresse de réception
- `EMAIL_DEBUG` : Active les logs détaillés

## 🎉 Résumé

**SYSTÈME 100% OPÉRATIONNEL !**

- ✅ Formulaire de contact fonctionnel
- ✅ Sauvegarde en base Railway  
- ✅ Emails automatiques Gmail
- ✅ Templates HTML professionnels
- ✅ Logs et monitoring actifs

**Vos clients peuvent maintenant vous contacter et vous recevrez automatiquement leurs messages par email !**
