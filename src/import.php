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
        $isTableCreated = true;
    } catch (Exception $e) {
        $isTableCreated = false;
        dropAlltheTablesIfAnyIssue($pdo, $tableName);
    }
    if ($isTableCreated) {
        try {
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

            $batchData = [];
            $batchSize = 1000; // Batch size for inserting
            $rowCount = 0;

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

                $batchData[] = $insertData;
                $rowCount++;

                // Perform batch insert when batch size is reached
                if ($rowCount % $batchSize === 0) {
                    batchInsert($pdo, $insertSQL, $batchData);
                    $batchData = []; // Reset batch data
                }
            }

            // Insert remaining data if any
            if (!empty($batchData)) {
                batchInsert($pdo, $insertSQL, $batchData);
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tableName");
            $stmt->execute();
            $recordsCount = $stmt->fetchColumn();

            if($recordsCount == 0) {
                deleteAllTableRelatedData($pdo, $tableId);
                $tableId = null;
            } else {
                //create dynamic table for data verification
                createDynamicTableForDataVerification($tableName, $pdo);
            }
        } catch(Exception $e) {
            dropAlltheTablesIfAnyIssue($pdo, $tableName);
        }
    }
    // Insert the table datatype details
    if ($tableId) {
        try {
            $tableDatatypeinfo = [];
            foreach ($columns as $index => $itemValue) {
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
            $isDataTypeTableCreated = createDynamicTableTypes($tableName.'_datatype', $pdo);

            if($isDataTypeTableCreated) {
                $dashBoardTableName = $tableName."_dashboard";
                insertIntoDynamicDatatypeTable($tableName, $originalTableName, $tableDatatypeinfo, $pdo);

                createDynamicTableForDashboard($dashBoardTableName, $pdo);
                insertIntoDynamicDashboardTable($dashBoardTableName, $pdo);
            }
        } catch(Exception $e) {
            dropAlltheTablesIfAnyIssue($pdo, $tableName);
        }
    }

    fclose($handle);
} else {
    echo "No file or columns selected.";
}

header("Location:dashboard.php?table_name=$tableName&project=$projectName");

// Helper function for batch insert
function batchInsert($pdo, $insertSQL, $batchData) {
    $insertStmt = $pdo->prepare($insertSQL);
    foreach ($batchData as $data) {
        $insertStmt->execute($data);
    }
}
?>