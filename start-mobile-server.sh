#!/bin/bash

echo "🚀 Démarrage du serveur Restaurant La Mangeoire"
echo "=" $(printf '=%.0s' {1..50})

# Obtenir l'IP locale
IP=$(ipconfig getifaddr en0 2>/dev/null || ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)

if [ -z "$IP" ]; then
    echo "❌ Impossible de détecter l'IP locale"
    IP="[VOTRE_IP]"
else
    echo "📍 IP locale détectée : $IP"
fi

echo ""
echo "🌐 ACCÈS AU SITE :"
echo "📱 Mobile/Téléphone : http://$IP:8000"
echo "💻 Desktop/Local   : http://localhost:8000"
echo ""
echo "🔧 PAGES ADMIN À TESTER :"
echo "- Messages    : http://$IP:8000/admin-messages.php"
echo "- Dashboard   : http://$IP:8000/admin/index.php"
echo "- Menus       : http://$IP:8000/admin/menus.php"
echo "- Commandes   : http://$IP:8000/admin/commandes.php"
echo "- Tables      : http://$IP:8000/admin/tables.php"
echo ""
echo "📱 Menu burger doit fonctionner sur mobile (largeur < 576px)"
echo ""
echo "🚀 Démarrage du serveur sur 0.0.0.0:8000..."
echo "   (Ctrl+C pour arrêter)"
echo ""

# Démarrer le serveur PHP
php -S 0.0.0.0:8000
