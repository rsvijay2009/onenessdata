<?php
include_once "database.php";
include_once "sidebar.php";

$oldTableName = $_POST['oldTableName'] ?? null;
$newTableName = $_POST['newTableName'] ?? null;
$message = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = '$dbname'
        AND table_name NOT LIKE '%_dashboard'
        AND table_name NOT LIKE '%_datatype'
        AND table_name NOT LIKE '%_data_verification'
        AND table_name NOT IN('datatypes', 'users', 'projects', 'temp_table_ids', 'tables_list', 'error_log')";

    $stmt = $pdo->query($sql);
    $stmt->execute();
    $dbTables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbTables = [];
}
if($oldTableName && $newTableName) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$newTableName]);
    if ($stmt->rowCount() > 0) {
        $messageId =  0;
        $errorMessageColor = 'red';
        $message =  "Error: Table with name '$newTableName' already exists.";
    } else {
        $renameSql = "RENAME TABLE $oldTableName TO $newTableName";
        $pdo->exec($renameSql);
        
        if (strpos($oldTableName, 'join_data') === false && strpos($oldTableName, 'compare_data') === false && strpos($oldTableName, 'reconcile_data') === false) {
            //check and update the table name in table_list also
            $updateTable = "UPDATE tables_list SET name = '$newTableName' WHERE name = '$oldTableName'";
            $pdo->exec($updateTable);

            $oldTableNameDatatype = $oldTableName.'_datatype';
            $oldTableNameDataVerification = $oldTableName.'_data_verification';

            $updateOldTableDatatype = "UPDATE $oldTableNameDatatype SET table_name = '$newTableName' WHERE table_name = '$oldTableName'";
            $pdo->exec($updateOldTableDatatype);

            $updateOldTableDataVerification = "UPDATE $oldTableNameDataVerification SET table_name = '$newTableName' WHERE table_name = '$oldTableName'";
            $pdo->exec($updateOldTableDataVerification);

            $oldTableNameList = [
                '_datatype' => $oldTableName.'_datatype',
                '_dashboard' =>  $oldTableName.'_dashboard',
                '_data_verification' => $oldTableName.'_data_verification'
            ];
            foreach ($oldTableNameList as $key => $table) {
                $checkTableQuery = "SHOW TABLES LIKE :table";
                $stmt = $pdo->prepare($checkTableQuery);
                $stmt->execute(['table' => $table]);
                $tableNameToUpdate = $newTableName.$key;

                if ($stmt->rowCount() > 0) {
                    $renameTableQuery = "ALTER TABLE `$table` RENAME TO `$tableNameToUpdate`";
                    $pdo->query($renameTableQuery);
                }
            }
        }
        $messageId =  1;
        $errorMessageColor = '#258B27';
        $message =  "Table '$oldTableName' renamed to '$newTableName' successfully.";
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <?php if(empty($dbTables)) { ?>
        <div class="col-md-10">
        <?php } else {?>
            <div class="col-md-5">
        <?php } ?>
            <!-- Table Below Cards -->
            <div style="padding:10px;">
                <h2 style="margin-bottom:25px;">Rename table</h2>
                <?php if (!empty($message)) { ?>
                    <p class="notificationMsg" id="notificationMsg" style="color:<?=$errorMessageColor?>;padding-bottom:10px;font-weight:bold;"><?=$message?></p>
                <?php } ?>
                <?php if(!empty($dbTables)) { ?>
                    <form action="rename_table.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="oldTableName" class="form-label">Choose table</label>
                            <select class="form-select" id="oldTableName" name="oldTableName" required>
                                <option selected value="">Choose project</option>
                                <?php foreach ($dbTables as $dbTable) {?>
                                    <option value=<?=$dbTable['TABLE_NAME']?>><?=$dbTable['TABLE_NAME']?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newTableName" class="form-label">Enter new table name</label>
                            <input type="text" class="form-control" id="newTableName" name="newTableName" placeholder="Enter new table name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                <?php } else {?>
                    <div class="container">
                        <div class="alert alert-warning text-center" role="alert">
                            <h4 class="alert-heading">No Data Found</h4>
                            <p>Sorry, there is no table available at the moment</p>
                        </div>
                    </div>
                <?php } ?>
                </div>
             </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(function() {
        notificationMsg.style.display = 'none';
    }, 2000);
</script>
</body>
</html>
