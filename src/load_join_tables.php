<?php
include_once "database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tableName = $data['tableName'] ?? '';
    $selectBoxId = $data['selectBoxId'] ?? '';
    $selectBoxName = $data['selectBoxName'] ?? '';
    $selectBoxClass = $data['selectBoxClass'] ?? '';
    $selectBoxButtonStyle = 'margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5';
}
if(empty($data)) {
    $tableName = $_POST['tableName'] ?? '';
    $selectBoxId = $_POST['selectBoxId'] ?? '';
    $selectBoxName = $_POST['selectBoxName'] ?? '';
    $selectBoxClass = $_POST['selectBoxClass'] ?? '';
    $selectBoxButtonStyle = 'margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5';
}
if($selectBoxName == 'joinTable2Columns') {
    $selectBoxButtonStyle = 'margin-top:20px; margin-left:35px; width:97%; color:#000; border: 1px solid #c9c5c5';
}

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name', 'original_table_name')");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$outputHtmlColumns = '<button class="btn dropdown-toggle" type="button" id="'.$selectBoxName.'" data-bs-toggle="dropdown" aria-expanded="false" style="margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 217px;"> Select Columns</span></button><ul class="dropdown-menu" aria-labelledby='.$selectBoxName.' style="width: 95%;"><li><a class="dropdown-item"><input class="form-check-input '.$selectBoxName.'All" type="checkbox" id="'.$selectBoxName.'" style="margin-right: 10px;"><label class="form-check-label" for="'.$selectBoxName.'">Select all</label></a></li>';

foreach($columns as $column) {
    $outputHtmlColumns.='<li><a class="dropdown-item"><input class="form-check-input '.$selectBoxName.'" type="checkbox" name="'.$selectBoxName.'[]" value="'.$column.'" id="'.$column.'_id" style="margin-right: 10px;"><label class="form-check-label" for="'.$column.'_id">'.$column.'</label></a></li>';
}

$outputHtmlColumns.='</ul>';

// $outputHtmlRelationShip = '<button class="btn dropdown-toggle" type="button" id="'.$selectBoxName.'_relationship" data-bs-toggle="dropdown" aria-expanded="false" style="'.$selectBoxButtonStyle.'"><span style="margin-right: 173px;"> Select Relationship</span></button><ul class="dropdown-menu" aria-labelledby="'.$selectBoxName.'_relationship" style="width: 100%;"><li><a class="dropdown-item"><input class="form-check-input '.$selectBoxName.'_relationshipAll" type="checkbox" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">Select all</label></a></li>';

// foreach($columns as $column) {
//     $outputHtmlRelationShip.='<li><a class="dropdown-item"><input class="form-check-input '.$selectBoxName.'_relationship" type="checkbox" name="'.$selectBoxName.'_relationship[]" value="'.$column.'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'.$column.'</label></a></li>';
// }

$id = $name = $selectBoxName.'_relationship';

$outputHtmlRelationShip = "<select class='form-select' id='$id' name='$name' style='margin-right:20px;'><option value=''>Select Relationship</option>";

foreach($columns as $column) {
    $outputHtmlRelationShip.="<option value=".$column.">$column</option>";
}

$outputHtmlRelationShip.='</select>';

echo trim($outputHtmlColumns)."||".trim($outputHtmlRelationShip);