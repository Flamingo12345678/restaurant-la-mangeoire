#!/bin/bash

echo "=== DIAGNOSTIC API PAIEMENTS ==="
echo ""

echo "🔍 Vérification de l'environnement :"

echo -n "  - Extension cURL PHP : "
if php -m | grep -q curl; then
    echo "✅ INSTALLÉE"
else
    echo "❌ MANQUANTE"
fi

echo -n "  - Fonction allow_url_fopen : "
if php -r "echo ini_get('allow_url_fopen') ? 'Activée' : 'Désactivée';" | grep -q "Activée"; then
    echo "✅ ACTIVÉE"
else
    echo "❌ DÉSACTIVÉE"
fi

echo -n "  - Connectivité internet : "
if ping -c 1 google.com >/dev/null 2>&1; then
    echo "✅ OK"
else
    echo "❌ PROBLÈME"
fi

echo ""
echo "🌐 Test de connectivité API :"

echo -n "  - PayPal API : "
if curl -s --connect-timeout 5 https://api.paypal.com/v1/oauth2/token >/dev/null 2>&1; then
    echo "✅ ACCESSIBLE"
else
    echo "❌ INACCESSIBLE"
fi

echo -n "  - Stripe API : "
if curl -s --connect-timeout 5 https://api.stripe.com/v1 >/dev/null 2>&1; then
    echo "✅ ACCESSIBLE"
else
    echo "❌ INACCESSIBLE"
fi

echo ""
echo "📋 Solutions possibles :"
echo "  1. Vérifier la connexion internet du serveur"
echo "  2. Configurer le firewall pour autoriser les connexions HTTPS sortantes"
echo "  3. Installer/activer l'extension cURL si manquante"
echo "  4. Vérifier les logs du serveur web pour plus de détails"
echo "  5. Tester manuellement : curl -v https://api.paypal.com/v1/oauth2/token"
echo ""

echo "🔧 Test avancé des paiements :"
echo "Exécution du test PHP..."

php -r "
\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, 'https://api.paypal.com/v1/oauth2/token');
curl_setopt(\$ch, CURLOPT_TIMEOUT, 10);
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_NOBODY, true);
curl_setopt(\$ch, CURLOPT_VERBOSE, false);
\$result = curl_exec(\$ch);
\$http_code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
\$curl_error = curl_error(\$ch);
curl_close(\$ch);

echo \"Code HTTP PayPal: \" . \$http_code . \"\n\";
if (\$curl_error) {
    echo \"Erreur cURL: \" . \$curl_error . \"\n\";
}

if (\$http_code >= 200 && \$http_code < 300) {
    echo \"✅ API PayPal : FONCTIONNELLE\n\";
} else if (\$http_code == 0) {
    echo \"❌ API PayPal : TIMEOUT ou CONNEXION REFUSÉE\n\";
} else {
    echo \"⚠️  API PayPal : RÉPONSE INATTENDUE (code \$http_code)\n\";
}
"
