<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Panier - Restaurant La Mangeoire</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        #debug { background: #f5f5f5; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Test du Système de Panier</h1>
    
    <div class="test-section">
        <h2>Ajouter des articles de test</h2>
        <button onclick="addTestItem(1, 'Boeuf Bourguignon', 18.50, '18,50 €')">Ajouter Boeuf Bourguignon</button>
        <button onclick="addTestItem(2, 'Coq au Vin', 16.00, '16,00 €')">Ajouter Coq au Vin</button>
        <button onclick="addTestItem(3, 'Tarte Tatin', 8.50, '8,50 €')">Ajouter Tarte Tatin</button>
    </div>
    
    <div class="test-section">
        <h2>Actions du panier</h2>
        <button onclick="viewCart()">Voir le panier</button>
        <button onclick="clearCart()">Vider le panier</button>
        <a href="panier.php" target="_blank">
            <button>Ouvrir la page panier</button>
        </a>
    </div>
    
    <div class="test-section">
        <h2>Debug</h2>
        <div id="debug"></div>
    </div>
    
    <script>
        // Récupérer le panier depuis localStorage
        let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
        updateDebug();
        
        function addTestItem(id, name, price, priceFormatted) {
            console.log('Ajout de:', {id, name, price, priceFormatted});
            
            // Vérifier si l'article existe déjà
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.quantity += 1;
                existingItem.total = existingItem.quantity * existingItem.price;
                console.log('Article existant mis à jour:', existingItem);
            } else {
                const newItem = {
                    id: id,
                    name: name,
                    price: price,
                    priceFormatted: priceFormatted,
                    quantity: 1,
                    total: price
                };
                cart.push(newItem);
                console.log('Nouvel article ajouté:', newItem);
            }
            
            // Sauvegarder
            localStorage.setItem('restaurant_cart', JSON.stringify(cart));
            console.log('Panier sauvegardé:', cart);
            
            updateDebug();
            alert(`"${name}" ajouté au panier !`);
        }
        
        function viewCart() {
            const cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
            if (cart.length === 0) {
                alert('Le panier est vide');
            } else {
                let message = 'Contenu du panier:\n\n';
                cart.forEach(item => {
                    message += `- ${item.name} x${item.quantity} = ${item.total.toFixed(2)}€\n`;
                });
                alert(message);
            }
        }
        
        function clearCart() {
            localStorage.removeItem('restaurant_cart');
            cart = [];
            updateDebug();
            alert('Panier vidé !');
        }
        
        function updateDebug() {
            const cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
            const debugDiv = document.getElementById('debug');
            debugDiv.innerHTML = `
                <strong>Contenu localStorage:</strong><br>
                <pre>${JSON.stringify(cart, null, 2)}</pre>
                <strong>Nombre d'articles:</strong> ${cart.length}
            `;
        }
    </script>
</body>
</html>
