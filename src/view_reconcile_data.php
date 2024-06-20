<?php
include_once "database.php";
include_once "sidebar.php";

try {
    $table = $_REQUEST['table'];
    $selectedColumnsArr = explode(",", $_REQUEST['selectedColumns']);
    $selectedColumnsToSumArr = explode(",", $_REQUEST['selectedColumnsToSum']);
    $selectedColumnsToGroupByArr = explode(",", $_REQUEST['selectedColumnsToGroupBy']);
    $selectedColumns = array_diff($selectedColumnsArr, $selectedColumnsToSumArr);

    if(isset($_REQUEST['uniqueKeyGenColumns']) && !empty($_REQUEST['uniqueKeyGenColumns'])) {
        $uniqueKeyGenColumns = $_REQUEST['uniqueKeyGenColumns'];
    }
    $columnsToSelectFromTable = implode(', ', $selectedColumns);
    $sumColumn = implode(',', $selectedColumnsToSumArr);
    $groupByColumns = implode(', ', $selectedColumnsToGroupByArr);

    // PDO connection setup
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "SELECT $columnsToSelectFromTable, SUM($sumColumn) as $sumColumn FROM $table GROUP BY $groupByColumns";
    // exit;
    $queryToGetData = "SELECT $columnsToSelectFromTable, SUM($sumColumn) as $sumColumn FROM $table GROUP BY $groupByColumns";
    $stmt = $pdo->prepare($queryToGetData);
    $stmt->execute();
    $reconcileData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error :" . $e->getMessage());
}
$columns = array_keys($reconcileData[0]);
include_once "header.php";

function generateUserKey($data) {
    $uniqueKeyGenColumns = urldecode($_REQUEST['uniqueKeyGenColumns']) ?? '';
    $uniqueKeyGenColumnsArr = array_map('trim', explode(',', $uniqueKeyGenColumns));
    $valuesArray = array();
    foreach ($uniqueKeyGenColumnsArr as $key) {
        if (isset($data[$key])) {
            $valuesArray[] = $data[$key];
        }
    }
    $result = implode('_', $valuesArray);

    return $result;
}
$sucessMsg = '';
if(isset($_POST['saveTable']) && $_POST['saveTable'] == true) {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $saveTableName = 'reconcile_data_'.time();
    if(isset($uniqueKeyGenColumns) && !empty($uniqueKeyGenColumns)) {
        $decodedString = urldecode($uniqueKeyGenColumns);
        $parts = explode(',', $decodedString);
        $trimmedParts = array_map('trim', $parts);
        $concatColumnStr = 'CONCAT(' . implode(', "_", ', $trimmedParts) . ')';
        $columnsToSelectFromTable = urldecode($_REQUEST['selectedColumns']);

        $queryToCopyThePreparedData = "SELECT $columnsToSelectFromTable, $concatColumnStr as user_key FROM $table";
        $createTableSQL = "CREATE TABLE $saveTableName AS $queryToCopyThePreparedData";
    } else {
        $createTableSQL = "CREATE TABLE $saveTableName AS $queryToGetData";
    }

    $pdo->exec($createTableSQL);
    $alterQuery = "ALTER TABLE $saveTableName ADD COLUMN original_table_name VARCHAR(255)";
    $pdo->exec($alterQuery);

    $updateQuery = "UPDATE $saveTableName SET original_table_name = '$table'";
    $pdo->exec($updateQuery);
    $sucessMsg = 'Prepared data saved successfully';
}
?>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
                <div style="padding:10px;">
                    <h2 style="margin-bottom:25px;">Prepared data </h2>
                    <span style="font-weight:bold;color:green" id="notificationMsg"><?=$sucessMsg?></span>
                    <div class="dropdown" style="display: flex; justify-content: flex-end; margin-top:25px;">
                        <a href="reconcile.php" class="btn btn-primary" style="margin-right: 5px;height: 40px;">Back</a>
                        <input type="submit" value="Save table" id="submitBtn" style="line-height: 20px;">
                    </div>
                    <div class="">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php if(!empty($uniqueKeyGenColumns)) { ?>
                                        <th>User key</th>
                                    <?php } ?>
                                    <?php foreach ($columns as $column) {?>
                                        <th><?=$column?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(count($reconcileData) > 0) {
                                foreach ($reconcileData as $data) {?>
                                    <tr>
                                        <?php if(!empty($uniqueKeyGenColumns)) { ?>
                                            <td><?=generateUserKey($data)?></td>
                                        <?php } ?>
                                        <?php foreach ($columns as $column) { ?>
                                            <td><?=$data[$column]?></td>
                                        <?php } ?>
                                    </tr>
                                <?php }
                            }?>
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
</body>
</html>
