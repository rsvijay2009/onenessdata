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

    $stmt = $pdo->prepare("CALL CompareTables('$table1', '$table2', '$relationship1', '$relationship2', '$compareColumn1', '$compareColumn2')");
    $stmt->execute();
    $comparedDataItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tableColumns = array_keys($comparedDataItems[0]) ?? [];
    //$successMsg = 'Compared data saved successfully';
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
            <div id="tableNameContainer" style="font-weight: bold;"> Table Name: <span id="tableName">compare_data_1718536225</span>
            </div>
            <div style="display: flex; align-items: center;">
              <a href="reconcile.php" class="btn btn-primary" style="margin-right: 5px; height: 40px;">Back</a>
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
    document.getElementById('submitBtn').addEventListener('click', function() {
        var tableNameSpan = document.getElementById('tableName');
        var tableName = tableNameSpan.textContent;
        var input = document.createElement('input');
        input.type = 'text';
        input.id = 'tableNameInput';
        input.value = tableName;
        input.style.fontWeight = 'bold';

        // Replace the span with the input element
        var container = document.getElementById('tableNameContainer');
        container.innerHTML = 'Table Name: ';
        container.appendChild(input);
    });
    setTimeout(function() {
        var notificationMsgDiv = document.getElementById('notificationMsg');
        notificationMsgDiv.style.display = 'none';
    }, 3000);
});
</script>
</body>
</html>
