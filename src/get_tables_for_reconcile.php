<?php
include_once "database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $tableName = $data['tableName'] ?? '';
    $selectBoxId = $data['selectBoxId'] ?? '';
    $selectBoxName = $data['selectBoxName'] ?? '';
    $selectBoxButtonStyle = 'margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5';
}
if(empty($data)) {
    $tableName = $_POST['tableName'] ?? '';
    $selectBoxId = $_POST['selectBoxId'] ?? '';
    $selectBoxName = $_POST['selectBoxName'] ?? '';
    $selectBoxButtonStyle = 'margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5';
}

if($selectBoxName == 'joinTable2Columns') {
    $selectBoxButtonStyle = 'margin-top:20px; margin-left:35px; width:97%; color:#000; border: 1px solid #c9c5c5';
}

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name')");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);


$outputHtmlColumns = '<button class="btn dropdown-toggle" type="button" id="'.$selectBoxName.'" data-bs-toggle="dropdown" aria-expanded="false" style="margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 217px;"> Select Columns</span></button><ul class="dropdown-menu" aria-labelledby="'.$selectBoxName.'" style="width: 95%;">';

foreach($columns as $column) {
    $outputHtmlColumns.='<li><a class="dropdown-item"><input class="form-check-input column-checkbox" type="checkbox" name="'.$selectBoxName.'[]" value="'.$column.'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'.$column.'</label></a></li>';
}

$outputHtmlColumns.='</ul>';

$outputHtmlRelationShip = '<button class="btn dropdown-toggle" type="button" id="'.$selectBoxName.'_relationship" data-bs-toggle="dropdown" aria-expanded="false" style="'.$selectBoxButtonStyle.'"><span style="margin-right: 173px;"> Select Relationship</span></button><ul class="dropdown-menu" aria-labelledby="'.$selectBoxName.'_relationship" style="width: 100%;">';

foreach($columns as $column) {
    $outputHtmlRelationShip.='<li><a class="dropdown-item"><input class="form-check-input column-checkbox" type="checkbox" name="'.$selectBoxName.'_relationship[]" value="'.$column.'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'.$column.'</label></a></li>';
}

$outputHtmlRelationShip.='</ul>';

echo trim($outputHtmlColumns)."||".trim($outputHtmlRelationShip);