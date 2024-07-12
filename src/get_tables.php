<?php
include_once "database.php";

$projectId = $_POST['projectId'] ?? '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT name, original_table_name, table_type FROM tables_list where project_id = '$projectId'");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT name, original_table_name, table_type FROM tables_list WHERE status = 'ACTIVE' and table_type IN('join', 'reconcile')");
$stmt->execute();
$otherTables = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tablesList = [];
$otherTablesList = [];

foreach($tables as $table) {
    $tablesList[] = [
        'name' => $table['name'],
        'original_table_name' => $table['original_table_name'],
        'table_type' => $table['table_type'],
    ];
}

foreach($otherTables as $otherTable) {
    $tablesList[] = [
        'name' => $otherTable['name'],
        'original_table_name' => $otherTable['name'],
        'table_type' => $otherTable['table_type'],
    ];
}

echo json_encode($tablesList);