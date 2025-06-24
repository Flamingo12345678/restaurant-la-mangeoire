# 📧 GUIDE DE CONFIGURATION EMAIL - LA MANGEOIRE

## 🚨 PROBLÈME IDENTIFIÉ
Vous ne recevez pas d'emails car le **mot de passe SMTP est manquant** dans la configuration.

---

## ✅ SOLUTION RAPIDE - UTILISER MAILTRAP (pour tests)

### 1. Créer un compte Mailtrap (GRATUIT)
- Allez sur https://mailtrap.io
- Créez un compte gratuit
- Accédez à votre inbox de test

### 2. Récupérer les identifiants SMTP
Dans votre Mailtrap inbox, vous verrez :
```
Host: sandbox.smtp.mailtrap.io
Username: [votre_username]
Password: [votre_password]  
Port: 2525
```

### 3. Mettre à jour la configuration
Éditez le fichier `config/email_config.php` :

```php
// Mode de test (true = utilise Mailtrap, false = utilise SMTP réel)
'test_mode' => true,  // ← Changer à true

'mailtrap' => [
    'host' => 'sandbox.smtp.mailtrap.io',
    'username' => 'VOTRE_USERNAME_MAILTRAP',  // ← Remplacer
    'password' => 'VOTRE_PASSWORD_MAILTRAP',  // ← Remplacer
    'port' => 2525,
    'encryption' => 'tls',
    'auth' => true
],
```

### 4. Tester immédiatement
```bash
php test-email-config.php?test=email
```

**Avec Mailtrap, les emails n'arrivent PAS dans votre vraie boîte mail, mais dans l'interface Mailtrap où vous pouvez les voir.**

---

## 🔧 SOLUTION PRODUCTION - GMAIL SMTP

### 1. Activer l'authentification à 2 facteurs
- Allez dans votre compte Google
- Sécurité → Authentification à 2 facteurs
- Activez l'A2F si pas déjà fait

### 2. Générer un mot de passe d'application
- Allez sur https://myaccount.google.com/apppasswords
- Sélectionnez "Mail" ou "Autre"
- Nommez-le "La Mangeoire Website"
- **COPIEZ le mot de passe généré** (16 caractères)

### 3. Mettre à jour la configuration
Dans `config/email_config.php` :

```php
'smtp' => [
    'host' => 'smtp.gmail.com',
    'username' => 'la-mangeoire@gmail.com',
    'password' => 'VOTRE_MOT_DE_PASSE_APPLICATION',  // ← 16 caractères de Google
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true
],

// Mode de test (true = utilise Mailtrap, false = utilise SMTP réel)
'test_mode' => false,  // ← false pour utiliser Gmail
```

### 4. Tester
```bash
php test-email-config.php?test=email
```

---

## 🚀 INSTRUCTIONS ÉTAPE PAR ÉTAPE

### Option A : Test rapide avec Mailtrap (5 minutes)

1. **Créer compte Mailtrap** : https://mailtrap.io
2. **Copier identifiants** depuis votre inbox Mailtrap
3. **Modifier config** : `config/email_config.php` → `test_mode = true`
4. **Remplir mailtrap section** avec vos identifiants
5. **Tester** : `php test-email-config.php?test=email`
6. **Vérifier** dans l'interface Mailtrap

### Option B : Production avec Gmail (10 minutes)

1. **Activer A2F** sur votre compte Google
2. **Générer mot de passe app** : https://myaccount.google.com/apppasswords
3. **Modifier config** : `config/email_config.php` → `smtp.password`
4. **Tester** : `php test-email-config.php?test=email`
5. **Vérifier** votre vraie boîte mail Gmail

---

## 🔍 DIAGNOSTIC ACTUEL

```
✅ PHPMailer installé
✅ Configuration trouvée
✅ Email admin configuré
❌ Mot de passe SMTP manquant ← PROBLÈME ICI
```

---

## 📞 SUPPORT

### Si ça ne marche toujours pas :

1. **Vérifiez les logs** :
   - `logs/email_notifications.log`
   - Logs PHP de votre serveur

2. **Testez avec le script** :
   ```bash
   php test-email-config.php?test=email
   ```

3. **Activez le debug SMTP** :
   Dans `email_notifications.php`, décommentez :
   ```php
   $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
   ```

---

## ⚡ SOLUTION EXPRESS

**Pour recevoir des emails MAINTENANT** :

1. ➡️ https://mailtrap.io (créer compte)
2. ➡️ Copier username/password de l'inbox
3. ➡️ Modifier `config/email_config.php` :
   ```php
   'test_mode' => true,
   'mailtrap' => [
       'username' => 'VOTRE_USERNAME',
       'password' => 'VOTRE_PASSWORD'
   ]
   ```
4. ➡️ Tester avec votre formulaire de contact
5. ➡️ Voir l'email dans Mailtrap

**Temps estimé : 5 minutes ⏰**
