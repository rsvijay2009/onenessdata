<?php

include_once "database.php";
include_once "utilities/common_utils.php";

$selectedColumns = [];
$columns     = $_POST["columns"] ?? [];
$dataTypes   = $_POST["datatype"] ?? [];
$csvFile     = $_POST["file"];
$projectId   = $_POST["project_id"];
$tableName   = $_POST["table_name"];
$projectName = $_POST["project_name"];
$tableId     = 0;

$filteredArray = array_filter($dataTypes, function ($value) {
    return $value !== null && $value !== false && $value !== "";
});
$dataTypes = array_values($filteredArray);
if (!empty($_POST["columns"])) {
    $selectedColumns = implode(",", $_POST["columns"]);
} else {
    echo "No items were selected.";
    exit();
}
// Variable to track if transaction is active
$transactionActive = false;
$selectedColumns = array_map("trim", explode(",", $selectedColumns));
if (($handle = fopen($csvFile, "r")) !== false) {
    $header = fgetcsv($handle);
    $indices = array_flip($header);
    $selectedColumns[] = "table_id";
    $selectedColumns[] = "table_name";
    $selectedIndices = array_intersect_key(
        $indices,
        array_flip($selectedColumns)
    );
    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Begin a transaction
        $pdo->beginTransaction();
        $transactionActive = true;
        // Prepend the primary key definition to the array
        $columnDefinitions = array_unshift($selectedColumns, "`primary_key` INT AUTO_INCREMENT PRIMARY KEY");
        $columnDefinitions = array_map(function ($string) {
            if (strpos($string, 'PRIMARY KEY') !== false) {
                return $string;
            } else {
                $stringWithoutSpace = addUnderScoreBetweenSpaceInString($string);
                $col = strtolower($stringWithoutSpace);
                return "`$col` TEXT";
            }
        }, $selectedColumns);
        $columnDefinitions[] = "`original_table_name` TEXT";
        $originalTableName = $tableName;
        $tableName = $projectName."_".$tableName;
        $tableName = (strlen($tableName) > 20) ? substr($tableName, 0, 20) : $tableName;
        $createTableSQL = "CREATE TABLE `$tableName` (" . implode(", ", $columnDefinitions) . ")";
        $pdo->exec($createTableSQL);

        // Insert data into dynamically created table
        $sql = "INSERT INTO tables_list (name, project_id, original_table_name, table_type) VALUES (:name, :project_id, :original_table_name, :table_type)";
        $tableName = strtolower($tableName);
        $tableType = 'main';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $tableName);
        $stmt->bindParam(":project_id", $projectId);
        $stmt->bindParam(":original_table_name", $originalTableName);
        $stmt->bindValue(":table_type", $tableType);
        $stmt->execute();
        $tableId = $pdo->lastInsertId();

        unset($selectedColumns[0]);
        $selectedColumns[] = 'original_table_name';
        $insertColumns = implode(", ", array_map(function ($string) {
            $stringWithoutSpace = addUnderScoreBetweenSpaceInString($string);
            $col = strtolower($stringWithoutSpace);
            return "`$col`";
        }, $selectedColumns));
        $insertValues = implode(", ", array_fill(0, count($selectedColumns), "?"));
        $insertSQL = "INSERT INTO `$tableName` ($insertColumns) VALUES ($insertValues)";
        $insertStmt = $pdo->prepare($insertSQL);

        while (($row = fgetcsv($handle)) !== false) {
            $insertData = [];
            foreach ($selectedIndices as $col => $index) {
                if (isset($row[$index]) && $row[$index] !== '') {
                    $insertData[] = $row[$index];
                } else {
                    $insertData[] = null; // Set null for empty values
                }
            }
            $insertData[] = $tableId;
            $insertData[] = $tableName;
            $insertData[] = $originalTableName;
            $insertStmt->execute($insertData);
        }

        // Create dynamic table for data verification
        $isDataVerificationTableCreated = createDynamicTableForDataVerification($tableName, $pdo);

        $tableDatatypeinfo = [];
        foreach ($columns as $index => $itemValue) {
            // Check if the checkbox was checked and a corresponding option was selected
            if (isset($dataTypes[$index]) && !empty($dataTypes[$index])) {
                $stmt = $pdo->prepare("SELECT name FROM datatypes WHERE id = :dataTypeId");
                $stmt->bindParam(":dataTypeId", $dataTypes[$index]);
                $stmt->execute();
                $dataType = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $dataTypeName = $dataType[0]["name"] ?? null;

                $columnNameWithoutSpace = addUnderScoreBetweenSpaceInString($columns[$index]);
                $columnName = strtolower($columnNameWithoutSpace);

                $tableDatatypeinfo[] = [
                    "table_id" => $tableId,
                    "table_name" => $tableName,
                    "datatype_id" => $dataTypes[$index],
                    "column_name" => $columnName,
                    "datatype" => $dataTypeName,
                ];
            }
        }

        $isDataTypeTableCreated = createDynamicTableTypes($tableName . '_datatype', $pdo);

        $dashBoardTableName = $tableName . "_dashboard";
        $isDataInsertedIntoDatyTypeTable = insertIntoDynamicDatatypeTable($tableName, $originalTableName, $tableDatatypeinfo, $pdo);

        // Create dynamic table to store dashboard data
        $isDashboardTableCreated = createDynamicTableForDashboard($dashBoardTableName, $pdo);
        $isDataInsertedIntoDashboardTable = insertIntoDynamicDashboardTable($dashBoardTableName, $pdo);

        fclose($handle);

        // Commit the transaction if all operations are successful
        $pdo->commit();
        $transactionActive = false;
    } catch (Exception $e) {
        // Rollback the transaction if any operation fails

        if ($transactionActive && $pdo->inTransaction()) {
            $pdo->rollBack();
            $transactionActive = false; // Reset the transaction active flag
            $isTableCreated = false;
            header("Location:home.php?msg=error");
            exit;
        }
    }
} else {
    echo "No file or columns selected.";
}

header("Location:dashboard.php?table_name=$tableName&project=$projectName");
?>
