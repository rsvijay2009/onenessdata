<?php

include_once "database.php";
include_once "sidebar.php";
include_once "utilities/common_utils.php";

$errorMsg = "";
$successMsg = "";
if (isset($_POST["selected_tables"]) && $_POST["selected_tables"] != "") {
    $selectedTables = trim($_POST["selected_tables"]);

    $selectedTablesArr = explode(",", $selectedTables);
    $tablesCount = count($selectedTablesArr);

    if ($tablesCount <= 1) {
        $errorMsg = "Select atleast two data to merge";
    } else {
        $columnCountArr = [];
        $columnsArr = [];

        foreach ($selectedTablesArr as $key => $value) {
            $value = trim($value);
            $stmt = $pdo->prepare("SELECT * FROM `$value` LIMIT 1");
            $stmt->execute();
            $columnCountArr[] = $stmt->columnCount();

            if($key == 0) {
                /*To insert the first table datatypes alone in datatypes table otherwise we will face issue
                    For example if customers table have first_name, last_name and email columns and
                    sales table have product, prodcut_code, price columns
                    If we merge these two tables we will create a table like customers_sales_79799 and we have only three columns
                    like first_name, last_name and email and we lose the sales table column
                */
                $stmt = $pdo->prepare("SHOW COLUMNS FROM $value");
                $stmt->execute();
                $columnsArr[] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $columnsArr[$key]['table_name'] = $value;
            }
        }
        if (count(array_unique($columnCountArr)) === 1) {
            $unionSQL = "";
            $newTableName = "";
            $tableNameList = [];
            foreach ($selectedTablesArr as $index => $tableName) {
                $tableName = trim($tableName);
                if ($index > 0) {
                    $unionSQL .= " UNION ALL ";
                }
                $newTableName .= ($index == 0) ? $tableName : "_".$tableName;
                $unionSQL .= "SELECT * FROM $tableName";
                $tableNameList[] =  $tableName;
            }

            try {
                $newTableName = strtolower($newTableName).time();
                $newTableName = (strlen($newTableName) > 64) ? substr($newTableName, 0, 64) : $newTableName;
                $pdo->exec("CREATE TABLE `$newTableName` AS $unionSQL");

                //Delete table_name & original_table_column from merged table
                $pdo->exec("ALTER TABLE `$newTableName` DROP COLUMN table_name");
                $pdo->exec("ALTER TABLE `$newTableName` DROP COLUMN original_table_name");

                //Insert a new row for mergerd table in tables_list table
                $firstTableName = $tableNameList[0] ?? null;

                if($firstTableName) {
                    $stmt = $pdo->prepare("SELECT project_id FROM tables_list WHERE name = '$firstTableName' LIMIT 1");
                    $stmt->execute();
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    $projectId = $project['project_id'] ?? null;

                    if($projectId) {
                        $stmt = $pdo->prepare("INSERT INTO `tables_list` (name, project_id, original_table_name, table_type) VALUES ('$newTableName', $projectId, '$newTableName', 'merge')");
                        $stmt->execute();
                        $mergedtableId = $pdo->lastInsertId();

                        $stmt = $pdo->prepare("UPDATE $newTableName SET table_id =  $mergedtableId");
                        $stmt->execute();

                        $isDataTypeTableCreated = createDynamicTableTypes($newTableName.'_datatype', $pdo);
                        if($isDataTypeTableCreated) {
                            //Get the first table datatypes
                            $firstTableNameDatatype = $firstTableName.'_datatype';
                            $dashBoardTableName = $newTableName."_dashboard";
                            $selectedFirstTable = "SELECT * FROM $firstTableNameDatatype";
                            $stmt = $pdo->query($selectedFirstTable);
                            $selectedFirstTableRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            insertIntoDynamicDatatypeTable($newTableName, $newTableName, $selectedFirstTableRows, $pdo);
                            //create dynamic table to store dashboard data
                            createDynamicTableForDashboard($dashBoardTableName, $pdo);
                            insertIntoDynamicDashboardTable($dashBoardTableName, $pdo);

                            //Add auto increment id to show the top and bottom 5 stats of data quality dimensions
                            // Step 1: Add a new column
                            $addColumnSql = "ALTER TABLE $newTableName ADD COLUMN id INT";
                            $pdo->exec($addColumnSql);

                            // Step 2: Set the new column as primary key and auto-increment
                            $modifyColumnSql = "ALTER TABLE $newTableName MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY NOT NULL";
                            $pdo->exec($modifyColumnSql);

                            //Create data_verification table for merged tables
                            createDynamicTableForDataVerification($newTableName, $pdo);
                        }
                    }
                }
                $successMsg = "Data merged successfully!!";
            } catch (PDOException $e) {
                die("Error fetching tables: " . $e->getMessage());
            }
        } else {
            $errorMsg = "Columns are not equal to merge";
        }
    }
}
include_once "header.php";
?>
<link rel="stylesheet" href="styles/merge.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <div class="col-md-10">
            <form action="merge.php" method="post">
                    <input type="hidden" id="selected_tables" name="selected_tables" value="">
                    <!-- <input type="hidden" id="errMsg" name="errMsg" value=<?= $errorMsg ?>>
                    <input type="hidden" id="successMsg" name="successMsg" value=<?= $successMsg ?>> -->

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)">
                                <div class="plus-icon">+</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)">
                                <div class="plus-icon">+</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)">
                                <div class="plus-icon">+</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                    </div>
                <p>
                    <input type="submit" value="Merge data" name="submit" style="margin:20px; width:120px;">
                    <a href="home.php" class="btn btn-primary" style="width:10%; height:45px; padding:10px;margin-bottom:4px;margin-left:-15px;">Back</a>
                    <span id="successMsg" class="successMsg"><?= $successMsg ?></span>
                    <span id="errorMsg" class="errorMsg"><?= $errorMsg ?></span>
                </p>
            </form>
        </div>
    </div>
</div>
<script src="scripts/merge.js"></script>
</body>
</html>
