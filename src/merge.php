<?php

include_once "database.php";
include_once "sidebar.php";

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
                $newTableName .= $tableName . "_";
                $unionSQL .= "SELECT * FROM $tableName";
                $tableNameList[] =  $tableName;
            }

            try {
                $newTableName = strtolower($newTableName).time();
                $newTableName = (strlen($newTableName) > 20) ? substr($newTableName, 0, 20) : $newTableName;
                $pdo->exec("CREATE TABLE `$newTableName` AS $unionSQL");

                //Insert a new row for mergerd table in tables_list table
                $firstTableName = $tableNameList[0] ?? null;
                if($firstTableName) {
                    $stmt = $pdo->prepare("SELECT project_id FROM tables_list WHERE name = '$firstTableName' LIMIT 1");
                    $stmt->execute();
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    $projectId = $project['project_id'] ?? null;

                    if($projectId) {
                        $stmt = $pdo->prepare("INSERT INTO `tables_list` (name, project_id) VALUES ('$newTableName', $projectId)");
                        $stmt->execute();
                        $mergedtableId = $pdo->lastInsertId();

                        $stmt = $pdo->prepare("UPDATE $newTableName SET table_id =  $mergedtableId");
                        $stmt->execute();


                        //Insert the datatypes of merged data into table_datatypes table
                        if(count($columnsArr) > 0) {
                            foreach ($columnsArr as $value) {
                                $tableName =  $value['table_name'];

                                // SQL query to copy data and insert into the same table with modified table names
                                $sql = "INSERT INTO table_datatypes (table_name, table_id, column_name, datatype_id, datatype, data_quality, uniqueness)
                                SELECT '$newTableName' AS table_name, $mergedtableId AS table_id, column_name, datatype_id, datatype, data_quality, uniqueness
                                FROM table_datatypes WHERE table_name = '$tableName'";

                                $stmt = $pdo->prepare($sql);
                                $stmt->execute();
                            }
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
