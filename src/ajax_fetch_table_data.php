<?php
include_once "database.php";

// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$query = $pdo->query("SHOW COLUMNS FROM customers");
$columns = $query->fetchAll(PDO::FETCH_COLUMN);

foreach ($columns as $column) {
    echo "<li><input type='checkbox' class='form-check-input column-checkbox' data-column-name='$column'>$column</li>";
}
?>
