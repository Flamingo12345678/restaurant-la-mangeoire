# 🔒 CONFIGURATION HTTPS POUR PRODUCTION - La Mangeoire

## 🚨 PROBLÈME IDENTIFIÉ
Stripe exige une connexion HTTPS sécurisée pour traiter les paiements. Le message d'erreur "La saisie automatique des modes de paiement est désactivée, car la connexion utilisée par ce formulaire n'est pas sécurisée" indique que votre site fonctionne en HTTP.

---

## 🎯 SOLUTIONS DE PRODUCTION

### 🏆 **SOLUTION 1: Hébergeur avec SSL/TLS automatique (RECOMMANDÉE)**

#### **Option A: Hébergeurs avec SSL gratuit**
- **OVH Web Hosting** (France) - SSL Let's Encrypt gratuit
- **Hostinger** - SSL gratuit inclus
- **SiteGround** - SSL gratuit
- **Cloudflare Pages** - SSL automatique

#### **Configuration type chez OVH:**
1. Commander l'hébergement Web
2. Activer SSL/TLS dans le panel
3. Forcer HTTPS dans `.htaccess`
4. Configurer les clés Stripe en mode production

---

### 🔧 **SOLUTION 2: Serveur VPS avec Let's Encrypt**

#### **Prérequis:**
- Serveur Ubuntu/Debian
- Nom de domaine pointant vers le serveur
- Apache/Nginx installé

#### **Installation automatique:**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-apache

# Générer le certificat SSL
sudo certbot --apache -d votredomaine.com -d www.votredomaine.com

# Renouvellement automatique
sudo crontab -e
# Ajouter: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

### ⚡ **SOLUTION 3: Cloudflare (Gratuit + Performant)**

1. **Créer un compte Cloudflare**
2. **Ajouter votre domaine**
3. **Changer les DNS chez votre registrar**
4. **Activer "Full (strict)" SSL/TLS**
5. **Forcer HTTPS** dans les paramètres

#### **Avantages Cloudflare:**
- ✅ SSL gratuit
- ✅ CDN mondial
- ✅ Protection DDoS
- ✅ Cache automatique
- ✅ Configuration en 10 minutes

---

### 🚀 **SOLUTION 4: Configuration .htaccess pour HTTPS**

Créez ou modifiez le fichier `.htaccess` à la racine :

```apache
# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Sécurité Headers
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Cache statique
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

---

## 🛠️ CONFIGURATION PHP POUR HTTPS

### **Détection HTTPS dans votre code PHP:**

```php
<?php
// Vérifier si HTTPS est actif
function isHTTPS() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] == 443 ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    );
}

// Forcer HTTPS
function forceHTTPS() {
    if (!isHTTPS()) {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url", true, 301);
        exit();
    }
}

// Appeler au début de chaque page critique
forceHTTPS();
?>
```

---

## ⚙️ MISE À JOUR DES CLÉS STRIPE

### **Configuration .env pour production:**
```env
# STRIPE PRODUCTION
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...

# PAYPAL PRODUCTION  
PAYPAL_CLIENT_ID=YOUR_LIVE_CLIENT_ID
PAYPAL_CLIENT_SECRET=YOUR_LIVE_CLIENT_SECRET
PAYPAL_MODE=live

# FORCE HTTPS
FORCE_HTTPS=true
```

---

## 🔒 CHECKLIST DE SÉCURITÉ PRODUCTION

### **Avant mise en ligne:**
- [ ] Certificat SSL/TLS valide
- [ ] Redirection HTTP → HTTPS
- [ ] Headers de sécurité configurés
- [ ] Clés API Stripe en mode LIVE
- [ ] Tests de paiement effectués
- [ ] Webhook Stripe configuré
- [ ] Emails SMTP sécurisés
- [ ] Base de données sécurisée
- [ ] Permissions fichiers restrictives
- [ ] Logs d'erreur configurés

### **Test final:**
1. Ouvrir le site en HTTPS
2. Vérifier le cadenas vert
3. Tester un paiement de 1€
4. Vérifier réception email
5. Confirmer dans Stripe Dashboard

---

## 📞 RECOMMANDATION IMMÉDIATE

**Pour une mise en production rapide, je recommande:**

1. **Cloudflare** (gratuit, 10 minutes de setup)
2. **Ou hébergeur avec SSL inclus** (OVH, Hostinger)
3. **Certificat Let's Encrypt** si vous avez déjà un serveur

---

## 🎯 PROCHAINES ÉTAPES

1. Choisir une solution HTTPS
2. Configurer le certificat SSL
3. Modifier les clés Stripe en mode LIVE
4. Tester les paiements
5. Surveiller les logs

**Votre système de paiement est prêt, il ne manque que HTTPS !** 🔒✨
