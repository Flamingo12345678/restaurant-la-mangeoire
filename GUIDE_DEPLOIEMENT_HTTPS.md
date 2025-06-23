# 🔒 GUIDE DE DÉPLOIEMENT HTTPS - RESTAURANT LA MANGEOIRE

**Date de création :** 23 juin 2025  
**Statut :** ✅ Configuration HTTPS Prête

---

## 🎯 RÉSUMÉ DE VOTRE CONFIGURATION HTTPS

Votre projet est maintenant **ENTIÈREMENT CONFIGURÉ** pour HTTPS avec :
- ✅ `FORCE_HTTPS=true` dans `.env`
- ✅ Fichier `.htaccess` avec redirection HTTPS forcée
- ✅ Headers de sécurité HSTS configurés
- ✅ Configuration SSL pour Stripe et PayPal
- ✅ Emails SMTP sécurisés (TLS)
- ✅ Scripts de test et monitoring HTTPS

---

## 🚀 DÉPLOIEMENT EN PRODUCTION

### 1. Sur votre serveur web (Apache/Nginx)

#### Pour Apache :
```apache
# Votre .htaccess est déjà configuré avec :
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

#### Pour Nginx :
```nginx
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name votre-domaine.com www.votre-domaine.com;
    
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    
    # Headers de sécurité
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    
    root /path/to/restaurant-la-mangeoire;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 2. Obtenir un certificat SSL

#### Option A : Let's Encrypt (Gratuit)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-apache

# Obtenir le certificat
sudo certbot --apache -d votre-domaine.com -d www.votre-domaine.com

# Renouvellement automatique
sudo systemctl enable certbot.timer
```

#### Option B : Certificat commercial
- Achetez un certificat SSL chez un fournisseur (Cloudflare, DigiCert, etc.)
- Installez-le selon les instructions de votre hébergeur

### 3. Configuration finale

#### Mettre à jour votre `.env` :
```env
# URL de production
APP_URL=https://votre-domaine.com

# Environnement production
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true

# Clés Stripe PRODUCTION (remplacez par vos vraies clés)
STRIPE_PUBLISHABLE_KEY=pk_live_your_live_publishable_key
STRIPE_SECRET_KEY=sk_live_your_live_secret_key

# PayPal PRODUCTION
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=your_live_client_id
PAYPAL_SECRET_KEY=your_live_secret_key
```

---

## 🧪 TESTS DE VALIDATION

### 1. Script automatisé
```bash
# Exécuter le script d'activation HTTPS
./enable-https.sh

# Tester la configuration
php test-https-config.php
```

### 2. Tests manuels à effectuer

#### ✅ Test de redirection HTTPS
- Visitez `http://votre-domaine.com`
- Vérifiez la redirection automatique vers `https://`

#### ✅ Test des paiements
- Effectuez un paiement test avec Stripe
- Effectuez un paiement test avec PayPal
- Vérifiez les emails de confirmation

#### ✅ Test de sécurité
- Utilisez [SSL Labs](https://www.ssllabs.com/ssltest/) pour tester votre certificat
- Score visé : A ou A+

#### ✅ Test de performance
- Vérifiez que HTTPS n'impacte pas les performances
- Les fichiers statiques doivent être mis en cache

---

## 🔧 OUTILS DE MONITORING

### 1. Health Check
```bash
# Vérification complète du système
php health-check.php
```

### 2. Test HTTPS spécifique
```bash
# Test de configuration HTTPS
php test-https-config.php
```

### 3. Monitoring automatique
Le système inclut des alertes automatiques qui vous notifieront par email si :
- Le certificat SSL approche de l'expiration
- Des erreurs HTTPS sont détectées
- Les paiements échouent en HTTPS

---

## 🛡️ SÉCURITÉ RENFORCÉE

### Headers de sécurité configurés :
- **HSTS** : Force HTTPS pendant 2 ans
- **CSP** : Autorise uniquement Stripe et PayPal
- **X-Frame-Options** : Protection contre le clickjacking
- **X-Content-Type-Options** : Protection MIME
- **X-XSS-Protection** : Protection XSS

### Fichiers protégés :
- `.env` et `.env.production` : Accès interdit
- `composer.json/lock` : Accès interdit
- Répertoire `logs/` : Accès interdit
- Fichiers `.md` et `.sql` : Accès interdit

---

## 📊 MONITORING EN PRODUCTION

### Dashboard Admin
- Accès : `https://votre-domaine.com/dashboard-admin.php`
- Monitoring des paiements HTTPS en temps réel
- Alertes de sécurité

### API de Monitoring
- Endpoint : `https://votre-domaine.com/api/monitoring.php`
- Statistiques des paiements sécurisés
- Détection d'anomalies

---

## 🔄 MAINTENANCE CONTINUE

### 1. Renouvellement SSL
```bash
# Let's Encrypt - automatique
sudo certbot renew --dry-run

# Vérification manuelle
sudo certbot certificates
```

### 2. Mise à jour de sécurité
```bash
# Vérification mensuelle
php health-check.php

# Test de pénétration simple
php test-https-config.php
```

### 3. Sauvegarde
- Sauvegardez votre certificat SSL
- Sauvegardez la configuration HTTPS
- Testez la restauration

---

## 🚨 DÉPANNAGE

### Problème : Redirection infinie
**Solution :** Vérifiez la configuration proxy de votre hébergeur
```apache
# Ajoutez à .htaccess si nécessaire
RewriteCond %{HTTP_X_FORWARDED_PROTO} !https
```

### Problème : Contenu mixte (HTTP/HTTPS)
**Solution :** Tous les liens doivent être HTTPS ou relatifs
```php
// Correct
echo '<script src="https://js.stripe.com/v3/"></script>';
// ou
echo '<script src="/assets/js/script.js"></script>';
```

### Problème : Emails non envoyés
**Solution :** Vérifiez la configuration SMTP TLS
```env
SMTP_ENCRYPTION=tls
SMTP_PORT=587
```

---

## 📞 SUPPORT

En cas de problème :

1. **Consultez les logs :** `logs/https/`, `logs/security/`
2. **Exécutez les tests :** `php test-https-config.php`
3. **Vérifiez le monitoring :** Dashboard admin
4. **Contactez votre hébergeur** pour les problèmes de certificat

---

## ✅ CHECKLIST FINALE

- [ ] Certificat SSL installé et valide
- [ ] Redirection HTTP → HTTPS active
- [ ] Variables d'environnement mises à jour
- [ ] Clés Stripe/PayPal en mode production
- [ ] Tests de paiement réussis
- [ ] Emails de confirmation fonctionnels
- [ ] Headers de sécurité actifs
- [ ] Monitoring opérationnel
- [ ] Sauvegardes configurées

---

**🎉 VOTRE RESTAURANT LA MANGEOIRE EST MAINTENANT SÉCURISÉ AVEC HTTPS !**

*Configuration terminée le 23 juin 2025 - Système prêt pour la production*
