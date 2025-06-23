<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier Simple - Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="bi bi-cart"></i> Mon Panier</h1>
        
        <div id="cart-content" class="mt-4">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>

    <script>
        console.log('Script chargé');
        
        // Test simple : récupérer et afficher le panier
        function loadAndDisplayCart() {
            console.log('Fonction loadAndDisplayCart appelée');
            
            try {
                const cartData = localStorage.getItem('restaurant_cart');
                console.log('Données brutes du localStorage:', cartData);
                
                const cart = cartData ? JSON.parse(cartData) : [];
                console.log('Panier parsé:', cart);
                console.log('Nombre d\'articles:', cart.length);
                
                const cartContent = document.getElementById('cart-content');
                
                if (!cartContent) {
                    console.error('Élément cart-content non trouvé');
                    return;
                }
                
                if (cart.length === 0) {
                    cartContent.innerHTML = `
                        <div class="alert alert-info text-center">
                            <i class="bi bi-cart-x fs-1"></i>
                            <h3>Votre panier est vide</h3>
                            <p>Aucun article n'a été ajouté au panier</p>
                        </div>
                    `;
                } else {
                    let html = '<div class="row">';
                    cart.forEach(item => {
                        html += `
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">${item.name}</h5>
                                        <p class="card-text">Prix: ${item.priceFormatted}</p>
                                        <p class="card-text">Quantité: ${item.quantity}</p>
                                        <p class="card-text"><strong>Total: ${item.total.toFixed(2)}€</strong></p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    
                    const total = cart.reduce((sum, item) => sum + item.total, 0);
                    html += `
                        <div class="alert alert-success">
                            <h4>Total général: ${total.toFixed(2)}€</h4>
                        </div>
                    `;
                    
                    cartContent.innerHTML = html;
                }
                
            } catch (error) {
                console.error('Erreur lors du chargement du panier:', error);
                document.getElementById('cart-content').innerHTML = `
                    <div class="alert alert-danger">
                        <h4>Erreur</h4>
                        <p>Impossible de charger le panier: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Charger le panier quand la page est prête
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM prêt, chargement du panier');
            loadAndDisplayCart();
        });
        
        // Aussi charger immédiatement au cas où
        if (document.readyState === 'loading') {
            console.log('Document en cours de chargement');
        } else {
            console.log('Document déjà chargé, chargement immédiat');
            loadAndDisplayCart();
        }
    </script>
</body>
</html>
