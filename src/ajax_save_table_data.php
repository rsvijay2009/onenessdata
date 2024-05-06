<?php
include_once "database.php";

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = $_POST['data'];
$id = $_POST['id'];
$tableName = $_POST['table'];

// Constructing SQL dynamically
$sql = "UPDATE `$tableName` SET ";
$params  = [];
foreach ($data as $column => $value) {
    $sql .= "`$column` = :$column, ";
    $params[$column] = $value;
}
$sql = rtrim($sql, ", "); // Remove the last comma
$sql .= " WHERE primary_key =  :id"; // Assuming there's an ID column
$params['id'] = $_POST['id']; // ID of the row

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo "Data updated successfully.";
