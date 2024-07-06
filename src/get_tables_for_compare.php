<?php
include_once "database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableName = $_POST['tableName'] ?? '';
}

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name', 'id', 'original_table_name')");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$outputHtmlColumns = [];

foreach($columns as $column) {
    $outputHtmlColumns[] = $column;
}

echo implode(",", $outputHtmlColumns);