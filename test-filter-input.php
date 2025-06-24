<?php
/**
 * Test filter_input vs $_POST
 */

// Simuler des données POST
$_POST['menu_id'] = '1';
$_POST['quantity'] = '1';
$_POST['ajax'] = 'true';

echo "<h1>Test filter_input vs \$_POST</h1>";

echo "<h2>Données dans \$_POST:</h2>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h2>Tests filter_input:</h2>";

$menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
echo "menu_id via filter_input: ";
var_dump($menu_id);
echo "<br>";

$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
echo "quantity via filter_input: ";
var_dump($quantity);
echo "<br>";

$ajax = filter_input(INPUT_POST, 'ajax', FILTER_VALIDATE_BOOLEAN);
echo "ajax via filter_input: ";
var_dump($ajax);
echo "<br>";

echo "<h2>Tests directs \$_POST:</h2>";

$menu_id_direct = (int) $_POST['menu_id'];
echo "menu_id direct: ";
var_dump($menu_id_direct);
echo "<br>";

$quantity_direct = (int) $_POST['quantity'];
echo "quantity direct: ";
var_dump($quantity_direct);
echo "<br>";

$ajax_direct = filter_var($_POST['ajax'], FILTER_VALIDATE_BOOLEAN);
echo "ajax direct: ";
var_dump($ajax_direct);
echo "<br>";

echo "<h2>Conclusion:</h2>";
if ($menu_id === false) {
    echo "❌ filter_input échoue pour menu_id<br>";
} else {
    echo "✅ filter_input fonctionne pour menu_id<br>";
}

echo "<p>L'utilisation de filter_input() peut poser problème quand les données ne viennent pas d'une vraie requête HTTP.</p>";
?>
