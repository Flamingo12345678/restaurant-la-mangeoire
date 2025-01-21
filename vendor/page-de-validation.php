<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produits'])) {
    $produitsCommandes = $_POST['produits'];
    $clientId = 1; // Exemple d'identifiant client

    try {
        $conn = new PDO("sqlsrv:Server=localhost;Database=restaurant", "username", "password");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        foreach ($produitsCommandes as $produitId) {
            // Enregistrer la commande
            $sql = "INSERT INTO commandes (client_id, produit_id) VALUES (:client_id, :produit_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->bindParam(':produit_id', $produitId);
            $stmt->execute();
        }

        echo "Commande enregistrée avec succès!";
    } catch (PDOException $e) {
        echo "Erreur de commande : " . $e->getMessage();
    }
} else {
    echo "Aucun produit sélectionné.";
}
?>
