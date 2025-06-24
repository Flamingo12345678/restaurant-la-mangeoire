<?php
echo "🔒 CONFIGURATION HTTPS LOCALE POUR STRIPE\n";
echo "=======================================\n\n";

// Vérifier si HTTPS est actif
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || $_SERVER['SERVER_PORT'] == 443 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

echo "🌐 ÉTAT ACTUEL DE LA CONNEXION\n";
echo "=============================\n";
echo "Protocole : " . ($is_https ? "✅ HTTPS (Sécurisé)" : "❌ HTTP (Non sécurisé)") . "\n";
echo "Port : " . $_SERVER['SERVER_PORT'] . "\n";
echo "Host : " . $_SERVER['HTTP_HOST'] . "\n";
echo "URL complète : " . (($is_https) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n\n";

if (!$is_https) {
    echo "⚠️  PROBLÈME DÉTECTÉ\n";
    echo "==================\n";
    echo "Stripe nécessite HTTPS pour fonctionner correctement.\n";
    echo "Votre site fonctionne actuellement en HTTP.\n\n";
    
    echo "🛠️  SOLUTIONS DISPONIBLES\n";
    echo "========================\n";
    echo "1. 🔧 DÉVELOPPEMENT LOCAL :\n";
    echo "   - Utiliser un serveur local avec HTTPS\n";
    echo "   - Utiliser ngrok pour tunnel HTTPS\n";
    echo "   - Configurer Apache/Nginx avec SSL\n\n";
    
    echo "2. 🚀 PRODUCTION :\n";
    echo "   - Obtenir un certificat SSL (Let's Encrypt gratuit)\n";
    echo "   - Configurer HTTPS sur votre hébergeur\n";
    echo "   - Forcer la redirection HTTP → HTTPS\n\n";
    
    echo "3. 🧪 TEST IMMÉDIAT :\n";
    echo "   - Utiliser les clés de test Stripe\n";
    echo "   - Désactiver temporairement la vérification SSL\n\n";
} else {
    echo "✅ CONNEXION SÉCURISÉE\n";
    echo "=====================\n";
    echo "Votre site utilise HTTPS. Stripe devrait fonctionner correctement.\n\n";
}

echo "📋 COMMANDES UTILES\n";
echo "==================\n";
echo "# Installer ngrok (tunnel HTTPS)\n";
echo "brew install ngrok  # macOS\n";
echo "# ou télécharger depuis https://ngrok.com\n\n";

echo "# Démarrer tunnel HTTPS sur port 8000\n";
echo "ngrok http 8000\n\n";

echo "# Générer certificat SSL local (pour Apache/Nginx)\n";
echo "openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes\n\n";

echo "🔧 CONFIGURATION TEMPORAIRE\n";
echo "===========================\n";
echo "Pour tester immédiatement, vous pouvez :\n";
echo "1. Utiliser ngrok pour créer un tunnel HTTPS\n";
echo "2. Modifier le .env pour utiliser les clés de test Stripe\n";
echo "3. Noter que les vrais paiements nécessitent HTTPS en production\n\n";

echo "🎯 PROCHAINES ÉTAPES\n";
echo "===================\n";
echo "1. Configurer HTTPS (voir solutions ci-dessus)\n";
echo "2. Tester à nouveau le paiement Stripe\n";
echo "3. Vérifier que l'autocomplétion fonctionne\n";
?>
