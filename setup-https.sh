#!/bin/bash

echo "🚀 CONFIGURATION HTTPS POUR STRIPE - RESTAURANT LA MANGEOIRE"
echo "============================================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}1. Démarrage du serveur PHP local...${NC}"
# Démarrer le serveur PHP en arrière-plan
php -S localhost:8000 > /dev/null 2>&1 &
PHP_PID=$!
echo -e "${GREEN}✅ Serveur PHP démarré sur localhost:8000 (PID: $PHP_PID)${NC}"

echo ""
echo -e "${BLUE}2. Configuration du tunnel HTTPS avec ngrok...${NC}"
echo -e "${YELLOW}⚠️  ngrok va s'ouvrir dans une nouvelle fenêtre de terminal${NC}"
echo -e "${YELLOW}⚠️  Gardez cette fenêtre ouverte pendant vos tests${NC}"
echo ""

# Attendre un peu pour que le serveur PHP démarre
sleep 2

echo -e "${BLUE}3. Lancement de ngrok...${NC}"
echo -e "${GREEN}🔗 Votre site sera accessible via une URL HTTPS sécurisée${NC}"
echo -e "${GREEN}📱 Cette URL sera compatible avec Stripe et les paiements${NC}"
echo ""

# Créer un script temporaire pour ngrok
cat > start_ngrok.sh << 'EOF'
#!/bin/bash
echo "🌐 TUNNEL HTTPS ACTIF"
echo "===================="
echo ""
echo "🔒 Votre site est maintenant accessible via HTTPS !"
echo "📋 Copiez l'URL HTTPS affichée ci-dessous et utilisez-la pour tester les paiements Stripe"
echo ""
ngrok http 8000
EOF

chmod +x start_ngrok.sh

# Ouvrir ngrok dans une nouvelle fenêtre de terminal
if command -v osascript &> /dev/null; then
    # macOS - ouvrir dans une nouvelle fenêtre Terminal
    osascript -e 'tell app "Terminal" to do script "cd \"'$(pwd)'\" && ./start_ngrok.sh"'
else
    # Linux - essayer gnome-terminal ou xterm
    if command -v gnome-terminal &> /dev/null; then
        gnome-terminal -- bash -c "cd $(pwd) && ./start_ngrok.sh; bash"
    elif command -v xterm &> /dev/null; then
        xterm -e "cd $(pwd) && ./start_ngrok.sh; bash" &
    else
        echo -e "${RED}❌ Impossible d'ouvrir une nouvelle fenêtre de terminal${NC}"
        echo -e "${YELLOW}Exécutez manuellement : ./start_ngrok.sh${NC}"
    fi
fi

echo ""
echo -e "${GREEN}✅ Configuration terminée !${NC}"
echo ""
echo -e "${BLUE}📋 INSTRUCTIONS D'UTILISATION :${NC}"
echo -e "${BLUE}================================${NC}"
echo "1. 🔗 Copiez l'URL HTTPS affichée dans la fenêtre ngrok"
echo "2. 🌐 Utilisez cette URL au lieu de localhost:8000"
echo "3. 💳 Testez les paiements Stripe (l'autocomplétion fonctionnera)"
echo "4. 🛑 Pour arrêter : fermez la fenêtre ngrok et tapez 'kill $PHP_PID'"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT :${NC}"
echo -e "${YELLOW}- Gardez la fenêtre ngrok ouverte pendant vos tests${NC}"
echo -e "${YELLOW}- L'URL HTTPS change à chaque redémarrage de ngrok${NC}"
echo -e "${YELLOW}- Pour la production, configurez un vrai certificat SSL${NC}"
echo ""

# Fonction de cleanup
cleanup() {
    echo ""
    echo -e "${BLUE}🛑 Arrêt des services...${NC}"
    kill $PHP_PID 2>/dev/null
    rm -f start_ngrok.sh
    echo -e "${GREEN}✅ Nettoyage terminé${NC}"
    exit 0
}

# Gérer l'interruption (Ctrl+C)
trap cleanup SIGINT SIGTERM

# Attendre que l'utilisateur termine
echo -e "${GREEN}🎯 Appuyez sur Ctrl+C pour arrêter tous les services${NC}"
wait
