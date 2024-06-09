<?php
include_once "database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableName = $_POST['tableName'] ?? '';
    $selectBoxId = $_POST['selectBoxId'] ?? '';
    $selectBoxName = $_POST['selectBoxName'] ?? '';
}

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name', 'id')");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);


$outputHtmlColumns = '<button class="btn dropdown-toggle" type="button" id="'.$selectBoxName.'" data-bs-toggle="dropdown" aria-expanded="false" style="margin-left:-120px;color:#000; border: 1px solid #c9c5c5;width:237px;"><span style="margin-right: 90px;"> Select Columns</span></button><ul class="dropdown-menu" aria-labelledby="'.$selectBoxName.'" style="width: 95%; width:237px;">';

foreach($columns as $column) {
    $outputHtmlColumns.='<li><a class="dropdown-item"><input class="form-check-input column-checkbox tableColsChkBox" type="checkbox" name="'.$selectBoxName.'[]" value="'.$column.'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'.$column.'</label></a></li>';
}

$outputHtmlColumns.='</ul>';

echo trim($outputHtmlColumns);