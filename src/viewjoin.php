<?php

include_once "database.php";
include_once "sidebar.php";

if(empty($_REQUEST)) {
    header("Location:join.php");
}
$sucessMsg = '';
$joinType = $_REQUEST['joinType'];
$table1 = $_REQUEST['table1'];
$table2 = $_REQUEST['table2'];
$joinTable1Columns = explode(',', $_REQUEST['table1Columns']);
$joinTable2Columns = explode(',', $_REQUEST['table2Columns']);
$joinTable1Columns_relationship = $_REQUEST['table1Relationship'];
$joinTable2Columns_relationship = $_REQUEST['table2Relationship'];

$qualifiedColumns1 = array_map(function($column) use ($table1) {
    return $table1 . '.' . $column;
}, $joinTable1Columns);
$qualifiedColumnsString1 = implode(', ', $qualifiedColumns1);
$joinTable1ColumnsStr = implode(', ', $joinTable1Columns);

$qualifiedColumns2 = array_map(function($column) use ($table2) {
    return $table2 . '.' . $column;
}, $joinTable2Columns);
$qualifiedColumnsString2 = implode(', ', $qualifiedColumns2);
$joinTable2ColumnsStr = '';
foreach ($joinTable2Columns as $v) {
   if(in_array($v, $joinTable1Columns)) {
        $joinTable2ColumnsStr .= $table2.'_'.$v.',';
   } else {
     $joinTable2ColumnsStr .= $v.',';
   }
}
$joinTable2ColumnsStr = rtrim($joinTable2ColumnsStr, ',');

if($joinType == 'FULL JOIN') {
   $tableColumns = $joinTable1Columns;
   $joinQueryStr = "SELECT $joinTable1ColumnsStr FROM $table1 LEFT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship UNION SELECT $joinTable2ColumnsStr FROM $table1 RIGHT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship";

    $joinQuery = $pdo->prepare("SELECT $joinTable1ColumnsStr FROM $table1 LEFT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship UNION SELECT $joinTable2ColumnsStr FROM $table1 RIGHT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship");

    // $joinQueryStr = "SELECT * FROM $table1 LEFT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship UNION SELECT * FROM $table1 RIGHT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship";

    // $joinQuery = $pdo->prepare("SELECT * FROM $table1 LEFT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship UNION SELECT * FROM $table1 RIGHT JOIN $table2 ON $table1.$joinTable1Columns_relationship = $table2.$joinTable2Columns_relationship");
} else {
    $tableColumns = array_merge($joinTable1Columns, $joinTable2Columns);
    if($table1 == $table2) {
        $tableAliasQuery = $table2.' AS '.$table2.'_alias';
        $tableAlias = $table2.'_alias';
    } else {
        $tableAliasQuery = $tableAlias = $table2;
    }
    $joinQueryStr = "SELECT $qualifiedColumnsString1, $qualifiedColumnsString2 FROM $table1 $joinType $tableAliasQuery ON $table1.$joinTable1Columns_relationship = $tableAlias.$joinTable2Columns_relationship";
    $joinQuery = $pdo->prepare("
        SELECT $qualifiedColumnsString1, $qualifiedColumnsString2 FROM $table1 $joinType $tableAliasQuery ON $table1.$joinTable1Columns_relationship = $tableAlias.$joinTable2Columns_relationship
    ");
}
$joinQuery->execute();
$results = $joinQuery->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST['saveTable']) && $_POST['saveTable'] == true) {
    $saveTableName = 'join_data_'.time();

    $createTableSQL = "CREATE TABLE $saveTableName AS $joinQueryStr";
    $pdo->exec($createTableSQL);

    //Insert the table name into tables_list
    $insertSql = "INSERT INTO tables_list (name, original_table_name, table_type) VALUES('$saveTableName', '$saveTableName', 'join')";
    $pdo->exec($insertSql);
    $sucessMsg = 'Data saved successfully';
}
include_once "header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
                <div style="padding:10px;">
                    <h2 style="margin-bottom:25px;"><?=ucfirst(strtolower($joinType))?> result</h2>
                    <span style="font-weight:bold;color:green" id="notificationMsg"><?=$sucessMsg?></span>
                    <div class="dropdown" style="display: flex; justify-content: flex-end; margin-top:25px;">
                        <a href="join.php" class="btn btn-primary" style="margin-right: 5px;height: 40px;">Back</a>
                        <input type="submit" value="Save table" id="submitBtn" style="line-height: 20px;">
                    </div>
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
                                if(count($results) > 0) {
                                    foreach ($results as $index => $result) { ?>
                                        <tr>
                                        <?php foreach ($tableColumns as $tableColumn) { ?>
                                            <td><?=$result[$tableColumn] == null ? 'NULL' : $result[$tableColumn]?></td>
                                        <?php } ?>
                                        </tr>
                                    <?php }
                                } else { ?>
                                    <tr>
                                        <td colspan="<?=count($tableColumns)?>" style="margin-top:30px;text-align:center; color:red; font-weight:bold;font-size:14px;">No results found</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>
    <form name="formSaveTable" id="formSaveTable" method="post">
        <input type="hidden" name="saveTable" id="saveTable" value="">
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#submitBtn').click(function() {
        event.preventDefault();
        document.getElementById("saveTable").value = true;
        let currentUrl = window.location.href;
        let url = new URL(currentUrl);

        $("#formSaveTable").action = url.href;
        $("#formSaveTable").submit();
    });
    setTimeout(function() {
        var notificationMsgDiv = document.getElementById('notificationMsg');
        notificationMsgDiv.style.display = 'none';
    }, 3000);
});
</script>
