<?php
// Connexion à SQL Server avec PDO
try {
    $conn = new PDO("sqlsrv:Server=localhost;Database=restaurant", "username", "password");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer la liste des produits
    $sql = "SELECT * FROM produits";
    $stmt = $conn->query($sql);

    echo "<form action='valider_commande.php' method='POST'>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div>";
        echo "<input type='checkbox' name='produits[]' value='{$row['id']}'> {$row['nom']} - {$row['prix']}€";
        echo "</div>";
    }
    echo "<button type='submit'>Passer la commande</button>";
    echo "</form>";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
?>
