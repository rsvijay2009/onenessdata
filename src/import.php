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
        $tableName = $projectName."_".$tableName;
        $tableName = (strlen($tableName) > 20) ? substr($tableName, 0, 20) : $tableName;
        $createTableSQL = "CREATE TABLE `$tableName` (" . implode(", ", $columnDefinitions) . ")";
        $pdo->exec($createTableSQL);
        $isTableCreated = true;
    } catch (PDOException $e) {
        echo $e->getMessage();
        echo $e->getLine();
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
            deleteAllTableRelatedData($tableId, $pdo);
            $tableId = null;
        }

        //create dynamic table for data verification
        createDynamicTableForDataVerification($tableName, $pdo);
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
            $tbl = str_replace($projectName, "", $tableName);
            $isDataTypeTableCreated = createDynamicTableTypes($projectName, $tbl, $pdo);

            if($isDataTypeTableCreated) {
                $tableDataTypeName = $projectName.$tbl."_datatype";
                $sql = "INSERT INTO $tableDataTypeName (table_id, table_name, column_name, datatype_id, datatype, data_quality, uniqueness) VALUES (:tableId, :tableName, :column_name, :dataTypeId, :datatype, :data_quality, :uniqueness)";
                $stmt = $pdo->prepare($sql);

                foreach ($tableDatatypeinfo as $tableInfo) {
                    $dataQuality = mt_rand(50, 100);
                    $dataUniqueness = mt_rand(50, 100);
                    $stmt->bindParam(":tableId", $tableInfo["tableId"]);
                    $stmt->bindParam(":tableName", $tableInfo["tableName"]);
                    $stmt->bindParam(":column_name", $tableInfo["column_name"]);
                    $stmt->bindParam(":dataTypeId", $tableInfo["dataTypeId"]);
                    $stmt->bindParam(":datatype", $tableInfo["datatype"]);
                    $stmt->bindParam(":data_quality", $dataQuality);
                    $stmt->bindParam(":uniqueness", $dataUniqueness);
                    $stmt->execute();
                }

                //create dynamic table to store dashboard data
                createDynamicTableForDashboard($projectName, $tbl, $pdo);
                $dashBoardTableName = $projectName.$tbl."_dashboard";

                $sql = "INSERT INTO $dashBoardTableName (data_quality_correct_data, data_quality_incorrect_data, text_issue, number_issue, date_issue, alphanumeric_issue, email_issue, duplicate_entries_issue, others_issue, null_issue, overall_correct_data, overall_incorrect_data) VALUES (:data_quality_correct_data, :data_quality_incorrect_data, :text_issue, :number_issue, :date_issue, :alphanumeric_issue, :email_issue, :duplicate_entries_issue, :others_issue, :null_issue, :overall_correct_data, :overall_incorrect_data)";
                $stmt = $pdo->prepare($sql);

                // Bind the parameters
                $data_quality_correct_data = mt_rand(1, 100);
                $data_quality_incorrect_data = mt_rand(1, 100);
                $text_issue = mt_rand(1, 100);
                $number_issue = mt_rand(1, 100);
                $date_issue = mt_rand(1, 100);
                $alphanumeric_issue = mt_rand(1, 100);
                $email_issue = mt_rand(1, 100);
                $duplicate_entries_issue = mt_rand(1, 100);
                $others_issue = mt_rand(1, 100);
                $null_issue = mt_rand(1, 100);
                $overall_correct_data = mt_rand(1, 100);
                $overall_incorrect_data = mt_rand(1, 100);

                $stmt->bindParam(':data_quality_correct_data', $data_quality_correct_data);
                $stmt->bindParam(':data_quality_incorrect_data', $data_quality_incorrect_data);
                $stmt->bindParam(':text_issue', $text_issue);
                $stmt->bindParam(':number_issue', $number_issue);
                $stmt->bindParam(':date_issue', $date_issue);
                $stmt->bindParam(':alphanumeric_issue', $alphanumeric_issue);
                $stmt->bindParam(':email_issue', $email_issue);
                $stmt->bindParam(':duplicate_entries_issue', $duplicate_entries_issue);
                $stmt->bindParam(':others_issue', $others_issue);
                $stmt->bindParam(':null_issue', $null_issue);
                $stmt->bindParam(':overall_correct_data', $overall_correct_data);
                $stmt->bindParam(':overall_incorrect_data', $overall_incorrect_data);

                // Execute the statement
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

header("Location:dashboard.php?table_name=$tableName&project=$projectName");
?>
