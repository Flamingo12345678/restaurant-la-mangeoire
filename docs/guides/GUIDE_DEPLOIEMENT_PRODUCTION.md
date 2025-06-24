# 🚀 GUIDE DE DÉPLOIEMENT PRODUCTION - Restaurant La Mangeoire

**Date :** 21 juin 2025  
**Objectif :** Éviter les erreurs de logging en production  
**Problème résolu :** `file_put_contents(): Failed to open stream: No such file or directory`

---

## 🎯 PROBLÈME ORIGINE

### Erreur rencontrée en production :
```
Warning: file_put_contents(/app/includes/../logs/email_notifications.log): 
Failed to open stream: No such file or directory
```

### Causes identifiées :
1. **Dossier `logs/` manquant** sur le serveur de production
2. **Permissions insuffisantes** pour créer/écrire dans les logs
3. **Différences d'environnement** entre dev et production
4. **Structure de répertoires différente** selon l'hébergeur

---

## ✅ SOLUTIONS IMPLÉMENTÉES

### 1. Système de logging robuste
**Nouveau système multi-fallback :**

```php
private function writeToLogFile($filename, $content) {
    try {
        // Tentative 1 : Dossier logs standard
        $logs_dir = __DIR__ . '/../logs';
        
        // Tentative 2 : Création automatique si manquant
        if (!is_dir($logs_dir)) {
            if (!mkdir($logs_dir, 0755, true)) {
                // Tentative 3 : Fallback dossier temporaire système
                $logs_dir = sys_get_temp_dir() . '/restaurant_logs';
                if (!is_dir($logs_dir)) {
                    mkdir($logs_dir, 0755, true);
                }
            }
        }
        
        // Tentative 4 : Vérification permissions
        if (!is_writable($logs_dir)) {
            $logs_dir = sys_get_temp_dir() . '/restaurant_logs';
            // ...
        }
        
        // Tentative 5 : Écriture sécurisée
        $result = file_put_contents($log_file, $content, FILE_APPEND | LOCK_EX);
        
        // Fallback ultime : error_log système
        if ($result === false) {
            error_log("Restaurant La Mangeoire - $content");
        }
        
    } catch (Exception $e) {
        error_log("Restaurant La Mangeoire - Erreur logging: $content");
    }
}
```

### 2. Script de vérification automatique
**`check-production-setup.php`** vérifie :
- ✅ Existence du dossier `logs/`
- ✅ Permissions d'écriture
- ✅ Configuration `.env`
- ✅ Test d'écriture réel
- ✅ Système de notification
- ✅ Sécurité (`.htaccess`)

### 3. Sécurisation des logs
**Fichier `logs/.htaccess`** créé automatiquement :
```apache
Order deny,allow
Deny from all
```

---

## 🔧 PROCÉDURE DE DÉPLOIEMENT

### Étape 1 : Préparation locale
```bash
# Vérifier que tous les fichiers sont présents
ls -la logs/
# Doit contenir : .gitkeep, .gitignore, .htaccess

# Test local
php check-production-setup.php
```

### Étape 2 : Upload vers production
```bash
# Uploader tous les fichiers incluant le dossier logs/
rsync -av --include='logs/' --include='logs/.htaccess' ./

# Ou via FTP/interface web, s'assurer d'inclure :
# - logs/.gitkeep
# - logs/.htaccess  
# - logs/.gitignore
```

### Étape 3 : Configuration serveur
```bash
# Se connecter au serveur de production
ssh user@serveur

# Aller dans le dossier du site
cd /path/to/restaurant-site

# Vérifier/créer le dossier logs
mkdir -p logs
chmod 755 logs

# Vérifier les permissions
ls -la logs/
```

### Étape 4 : Test post-déploiement
```bash
# Exécuter le script de vérification
php check-production-setup.php

# Doit afficher tous les ✅
# Si des ❌ apparaissent, suivre les instructions
```

### Étape 5 : Test fonctionnel
```bash
# Tester l'envoi d'un message via le site
# Vérifier que les logs se créent dans logs/
ls -la logs/

# Doit contenir :
# - contact_messages.log
# - email_notifications.log
```

---

## 🔍 DIAGNOSTIC EN CAS DE PROBLÈME

### Si le dossier logs n'est pas créé :
```bash
# Vérifier les permissions du répertoire parent
ls -la ./
chmod 755 .

# Créer manuellement
mkdir logs
chmod 755 logs
```

### Si pas de permissions d'écriture :
```bash
# Ajuster le propriétaire (selon configuration serveur)
chown www-data:www-data logs/
# ou
chown nginx:nginx logs/
# ou selon votre configuration

# Permissions d'écriture
chmod 755 logs/
```

### Si le fallback temporaire est utilisé :
1. Les logs iront dans `/tmp/restaurant_logs/`
2. Site fonctionnel mais logs non persistants
3. Corriger les permissions du dossier principal

### Si erreur persiste :
1. Vérifier les logs système : `/var/log/apache2/error.log`
2. Utiliser `error_log()` uniquement (logs dans syslog)
3. Désactiver temporairement le logging personnalisé

---

## 📋 CHECKLIST DE DÉPLOIEMENT

### Avant déploiement :
- [ ] Script `check-production-setup.php` fonctionne localement
- [ ] Dossier `logs/` avec tous ses fichiers présent
- [ ] Fichier `.env` configuré pour la production
- [ ] Test du système email en local

### Pendant le déploiement :
- [ ] Upload complet incluant le dossier `logs/`
- [ ] Vérification que `.env` est bien uploadé (et pas dans `.gitignore` global)
- [ ] Configuration des permissions serveur

### Après déploiement :
- [ ] Exécution de `check-production-setup.php`
- [ ] Test d'envoi d'un message de contact
- [ ] Vérification des logs créés
- [ ] Test de l'interface admin

---

## 🔧 CONFIGURATIONS SERVEUR SPÉCIFIQUES

### Apache (.htaccess dans logs/)
```apache
Order deny,allow
Deny from all
```

### Nginx (dans la config serveur)
```nginx
location /logs/ {
    deny all;
    return 404;
}
```

### Serveur partagé (cPanel/Plesk)
1. Créer le dossier `logs/` via l'interface
2. Uploader le fichier `.htaccess`
3. Vérifier les permissions via le gestionnaire de fichiers

---

## 🚨 GESTION D'ERREURS EN PRODUCTION

### Monitoring des logs :
```bash
# Surveiller les erreurs PHP
tail -f /var/log/apache2/error.log | grep "restaurant"

# Vérifier les logs application
tail -f logs/email_notifications.log
tail -f logs/contact_messages.log
```

### En cas d'urgence :
1. **Désactiver le logging** temporairement dans le code
2. **Utiliser uniquement `error_log()`** système
3. **Corriger les permissions** et réactiver

---

## 📞 SUPPORT ET MAINTENANCE

### Logs à surveiller :
- `logs/email_notifications.log` : Envois d'email
- `logs/contact_messages.log` : Messages de contact
- `/var/log/apache2/error.log` : Erreurs système

### Maintenance périodique :
```bash
# Rotation des logs (à faire mensuellement)
cd logs/
mv email_notifications.log email_notifications.log.$(date +%Y%m%d)
mv contact_messages.log contact_messages.log.$(date +%Y%m%d)

# Compression des anciens logs
gzip *.log.20*

# Nettoyage (garder 6 mois)
find . -name "*.log.*.gz" -mtime +180 -delete
```

---

**Avec ces améliorations, l'erreur de logging en production est définitivement résolue !** 🎉

*Guide créé le 21 juin 2025 suite à l'incident de production*
