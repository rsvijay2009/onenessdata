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

        foreach ($selectedTablesArr as $value) {
            $value = trim($value);
            $stmt = $pdo->prepare("SELECT * FROM `$value` LIMIT 1");
            $stmt->execute();
            $columnCountArr[] = $stmt->columnCount();
        }
        if (count(array_unique($columnCountArr)) === 1) {
            $unionSQL = "";
            $newTableName = "";
            foreach ($selectedTablesArr as $index => $tableName) {
                $tableName = trim($tableName);
                if ($index > 0) {
                    $unionSQL .= " UNION ALL ";
                }
                $newTableName .= $tableName . "_";
                $unionSQL .= "SELECT * FROM $tableName";
            }

            try {
                $newTableName = $newTableName.time();
                $newTableName = (strlen($newTableName) > 64) ? substr($newTableName, 0, 64) : $newTableName;
                $pdo->exec("CREATE TABLE `$newTableName` AS $unionSQL");
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
        <div class="col-md-9">
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
