<?php

include_once "database.php";
include_once "sidebar.php";

$successMsg = "";
// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_REQUEST) && !empty($_REQUEST['selectedTablesForCompare']) && !empty($_REQUEST['relationshipColumns'])) {
    $selecedtables = urldecode(trim($_REQUEST['selectedTablesForCompare']));
    $selecedRelationShips = urldecode(trim($_REQUEST['relationshipColumns']));
    $selectedColumnsToCompare = urldecode(trim($_REQUEST['compareColumns']));
    $selecedtablesArr = explode(",", $selecedtables);
    $selecedRelationShipsArr = explode(",", $selecedRelationShips);
    $selectedColumnsToCompareArr = explode(",", $selectedColumnsToCompare);
    $table1 = $selecedtablesArr[0];
    $table2 = $selecedtablesArr[1];
    $relationship1 = $selecedRelationShipsArr[0];
    $relationship2 = $selecedRelationShipsArr[1];
    $compareColumn1 = $selectedColumnsToCompareArr[0];
    $compareColumn2 = $selectedColumnsToCompareArr[1];

    $stmt = $pdo->query("SELECT DISTINCT $relationship1 FROM $table1
        UNION
        SELECT DISTINCT $relationship2 FROM  $table2
    ");
    $stmt->execute();
    $comparedDataItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tableColumns = array_keys($comparedDataItems[0]) ?? [];
    $saveTableName = 'compare_data_'.time();
}

$successMsg = '';
if(isset($_POST['saveTable']) && $_POST['saveTable'] == true) {
    $createTableSQL = "CREATE TABLE $saveTableName (
      relationship_key VARCHAR(255),
      tableA_$relationship1 VARCHAR(255),
      tableB_$relationship2 VARCHAR(255),
      difference VARCHAR(255)
    )";
    $pdo->exec($createTableSQL);

    //Insert data into compare table
    $insertSql = "INSERT INTO $saveTableName (relationship_key)
      SELECT DISTINCT $relationship1 FROM $table1
      UNION
      SELECT DISTINCT $relationship2 FROM $table2
      ";
    $pdo->exec($insertSql);

    $pdo->exec("INSERT INTO tables_list (name, original_table_name, table_type) VALUES('$saveTableName', '$saveTableName', 'reconcile')");
    $successMsg = 'Compared data saved successfully';
}
include_once "header.php";
?>
<body>
  <div class="container-fluid">
    <div class="row"> <?php include_once "sidebar_template.php"; ?>
      <!-- Content Area -->
      <div class="col-md-10">
        <div style="padding:10px;">
          <h2 style="margin-bottom:25px;">Compared data </h2>
          <span style="font-weight:bold;color:green" id="notificationMsg"> <?=$successMsg?> </span>
          <div class="dropdown" style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px;">
            <div id="tableNameContainer" style="font-weight: bold;"> Table Name: <span id="tableName"><?=$saveTableName?></span>
            </div>
            <div style="display: flex; align-items: center;">
              <a href="reconcile.php" class="btn btn-primary" style="margin-right: 5px; height: 40px;">Back</a>
              <input type="submit" value="Save table" id="submitBtn" style="line-height: 20px; width:100%">
            </div>
          </div>
        </div>
        <div class="">
          <table class="table">
            <thead>
              <tr> <?php foreach ($tableColumns as $column) { ?>
                <th> <?=$column?> </th> <?php } ?> </tr>
            </thead>
            <tbody>
                <?php foreach ($comparedDataItems as $item): ?>
                    <tr>
                        <?php foreach ($tableColumns as $column): ?>
                            <td> <?= $item[$column] ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?> </tbody>
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
    }, 2000);
});
</script>
</body>
</html>
