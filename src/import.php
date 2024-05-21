<?php

include_once "database.php";
include_once "utilities/common_utils.php";

$selectedColumns = [];
$columns   = $_POST["columns"] ?? [];
$dataTypes = $_POST["datatype"] ?? [];
$csvFile   = $_POST["file"];
$projectId = $_POST["project_id"];
$tableName = $_POST["table_name"];
$tableId   = 0;

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

    // Create table dynamically
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

        $tableName = (strlen($tableName) > 20) ? substr($tableName, 0, 20) : $tableName;
        $createTableSQL = "CREATE TABLE `$tableName` (" . implode(", ", $columnDefinitions) . ")";
        $pdo->exec($createTableSQL);
        $isTableCreated = true;
    } catch (PDOException $e) {
        echo $e->getMessage();
        $isTableCreated = false;
    }

    if ($isTableCreated) {
        $sql = "INSERT INTO tables_list (name, project_id) VALUES (:name, :project_id)";

        $tableName = strtolower($tableName);
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $tableName);
        $stmt->bindParam(":project_id", $projectId);
        $stmt->execute();
        $tableId = $pdo->lastInsertId();

        unset($selectedColumns[0]);
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
            $insertStmt->execute($insertData);
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tableName");
        $stmt->execute();
        $recordsCount = $stmt->fetchColumn();

        if($recordsCount == 0) {
            include_once("utilities/common_utils.php");
            deleteAllTableRelatedData($tableId, $pdo);
            $tableId = null;
        }
    }
    //Insert the table datatype details
    if ($tableId) {
        try {
            $tableDatatypeinfo = [];
            foreach ($columns as $index => $itemValue) {
                // Check if the checkbox was checked and a corresponding option was selected
                if (isset($dataTypes[$index]) && !empty($dataTypes[$index])) {
                    $stmt = $pdo->prepare(
                        "SELECT name FROM datatypes WHERE id = :dataTypeId"
                    );
                    $stmt->bindParam(":dataTypeId", $dataTypes[$index]);
                    $stmt->execute();
                    $dataType = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $dataTypeName = $dataType[0]["name"] ?? null;

                    $columnNameWithoutSpace = addUnderScoreBetweenSpaceInString($columns[$index]);
                    $columnName = strtolower($columnNameWithoutSpace);

                    $tableDatatypeinfo[] = [
                        "tableId" => $tableId,
                        "tableName" => $tableName,
                        "dataTypeId" => $dataTypes[$index],
                        "column_name" => $columnName,
                        "datatype" => $dataTypeName,
                    ];
                }
            }
            $sql = "INSERT INTO table_datatypes (table_id, table_name, column_name, datatype_id, datatype, data_quality, uniqueness) VALUES (:tableId, :tableName, :column_name, :dataTypeId, :datatype, :data_quality, :uniqueness)";
            $stmt = $pdo->prepare($sql);

            foreach ($tableDatatypeinfo as $tableInfo) {
                $dataQuality = rand(50, 100);
                $dataUniqueness = rand(50, 100);
                $stmt->bindParam(":tableId", $tableInfo["tableId"]);
                $stmt->bindParam(":tableName", $tableInfo["tableName"]);
                $stmt->bindParam(":column_name", $tableInfo["column_name"]);
                $stmt->bindParam(":dataTypeId", $tableInfo["dataTypeId"]);
                $stmt->bindParam(":datatype", $tableInfo["datatype"]);
                $stmt->bindParam(":data_quality", $dataQuality);
                $stmt->bindParam(":uniqueness", $dataUniqueness);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            die($e->getMessage());
        }
    }
    fclose($handle);
} else {
    echo "No file or columns selected.";
}

header("Location:dashboard.php?table_name=$tableName");
?>
