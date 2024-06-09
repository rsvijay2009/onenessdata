<?php

include_once "database.php";
include_once "sidebar.php";

if(empty($_POST)) {
    header("Location:join.php");
}

$joinType = $_POST['joinType'];
$table1 = $_POST['joinTable1'];
$table2 = $_POST['joinTable2'];
$joinTable1Columns = $_POST['joinTable1Columns'];
$joinTable2Columns = $_POST['joinTable2Columns'];
$joinTable1Columns_relationship = $_POST['joinTable1Columns_relationship'][0];
$joinTable2Columns_relationship = $_POST['joinTable2Columns_relationship'][0];

$tableColumns = array_merge($joinTable1Columns, $joinTable2Columns);

// echo '<pre>';
// print_r($tableColumns);
$qualifiedColumns1 = array_map(function($column) use ($table1) {
    return $table1 . '.' . $column;
}, $joinTable1Columns);
$qualifiedColumnsString1 = implode(', ', $qualifiedColumns1);

$qualifiedColumns2 = array_map(function($column) use ($table2) {
    return $table2 . '.' . $column;
}, $joinTable2Columns);
$qualifiedColumnsString2 = implode(', ', $qualifiedColumns2);

if($joinType == 'FULL JOIN') {
    $joinQuery = $pdo->prepare("
        SELECT * FROM $table1 LEFT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship
        UNION
        SELECT * FROM $table1 RIGHT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship
    ");
} else {
    $joinQuery = $pdo->prepare("
        SELECT $qualifiedColumnsString1, $qualifiedColumnsString2 FROM $table1 $joinType $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship
    ");
}
$joinQuery->execute();
$results = $joinQuery->fetchAll(PDO::FETCH_ASSOC);

include_once "header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
                <div style="padding:10px;">
                    <h2 style="margin-bottom:25px;">Join result</h2>
                    <div class="">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php foreach($tableColumns as $tableColumn) {?>
                                        <th><?=$tableColumn?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($results as $index => $result) { ?>
                                    <tr>
                                    <?php foreach ($tableColumns as $tableColumn) { ?>
                                        <td><?=$result[$tableColumn] == null ? 'NULL' : $result[$tableColumn]?></td>
                                    <?php } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <a href="join.php" class="btn btn-primary" style="margin-right:5px;">Back</a>
                    </div>
                </div>
        </div>
    </div>
</div>