<?php
include_once "database.php";

$tableName = $_POST['tableName'] ?? '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name')");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

// print_r($columns);
// exit;

$outputHtml = '<button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 217px;"> Select Columns</span></button><ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1" style="width: 95%;">';

foreach($columns as $column) {
    $outputHtml.='<li><a class="dropdown-item"><input class="form-check-input column-checkbox" type="checkbox" name="joinTable1Columns[]" value="'.$column.'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'.$column.'</label></a></li>';
}

$outputHtml.='</ul>';

echo $outputHtml;