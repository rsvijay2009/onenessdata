<?php

function deleteAllProjectRelatedData($pdo, $projectId = 0)
{
    if($projectId) {
        //Call the stored procedure to delete all the project related data
        try {
            $stmt = $pdo->prepare("CALL DeleteProjectData(:projectId)");
            $stmt->bindParam(':projectId', $projectId, PDO::PARAM_INT);
            $stmt->execute();

            $notificationClassName = 'notification-success-banner';
            $userNotificationMsg = "Project deleted successfully";
        } catch (PDOException $e) {
            $notificationClassName = 'notification-error-banner';
            $userNotificationMsg =  "No project found with the ID: $projectId";
        }
        return [
            'notificationClassName' => $notificationClassName ?? '',
            'userNotificationMsg' => $userNotificationMsg ?? ''
        ];
        /*try {
            //Find the tables list and delete them
            $selectTablesList = $pdo->prepare("SELECT id, name FROM tables_list  WHERE project_id = $projectId");
            $selectTablesList->execute();
            $tables = $selectTablesList->fetchAll(PDO::FETCH_ASSOC);
            $selectTablesList->closeCursor();
            $tableIds = [];
            foreach($tables as $table) {
                $tableName = $table['name'] ?? '';
                if(!empty($tableName)) {
                    $sql = "DROP TABLE  $tableName";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $stmt->closeCursor();
                    $tableIds[] = $table['id'] ?? 0;
                }
            }

            $sql = "DELETE FROM tables_list WHERE project_id = :id";
            $deleteTablesList = $pdo->prepare($sql);
            $deleteTablesList->bindParam(':id', $projectId, PDO::PARAM_INT);
            $deleteTablesList->execute();
            $deleteTablesList->closeCursor();

            if(count($tableIds) > 0) {
                $tableIds = implode(",", $tableIds);
                $sql = "DELETE FROM table_datatypes WHERE table_id  IN($tableIds)";
                $tableDataTypes = $pdo->prepare($sql);
                $tableDataTypes->execute();
                $tableDataTypes->closeCursor();
            }
          
            $sql = "DELETE FROM projects WHERE id = :id";
            $projects = $pdo->prepare($sql);
            $projects->bindParam(':id', $projectId, PDO::PARAM_INT);
            $projects->execute();
            $projects->closeCursor();

            if ($projects->rowCount() > 0) {
                $notificationClassName = 'notification-success-banner';
                $userNotificationMsg = "Project deleted successfully";
            } else {
                $notificationClassName = 'notification-error-banner';
                $userNotificationMsg =  "No project found with the ID: $projectId";
            }

            return [
                'notificationClassName' => $notificationClassName ?? '',
                'userNotificationMsg' => $userNotificationMsg ?? ''
            ];
        } catch (Exception  $e) {
            die("Could not delete record: " . $e);
        }*/
    }
}

function deleteAllTableRelatedData(PDO $pdo, $tableId = 0)
{
    if($tableId) {
        //Call the stored procedure to delete all the table related data
        try {
            $stmt = $pdo->prepare("CALL DropAndCleanUpTable(:tableId)");
            $stmt->bindParam(':tableId', $tableId, PDO::PARAM_INT);
            $stmt->execute();

            $notificationClassName = 'notification-success-banner';
            $userNotificationMsg = "Table deleted successfully";
        } catch (PDOException $e) {
            $notificationClassName = 'notification-error-banner';
            $userNotificationMsg =  "No table found with the ID: $tableId";
        }
        return [
            'notificationClassName' => $notificationClassName ?? '',
            'userNotificationMsg' => $userNotificationMsg ?? ''
        ];
        /*try
        {
            $stmt = $pdo->prepare("SELECT id, name FROM tables_list  WHERE id = $tableId");
            $stmt->execute();
            $table = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $tableName = $table[0]['name'] ?? '';
            if(!empty($tableName)) {
                $sql = "DROP TABLE  $tableName";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
            $sql = "DELETE FROM tables_list WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $tableId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $sql = "DELETE FROM table_datatypes WHERE table_id  = $tableId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            if ($stmt->rowCount() > 0) {
                $notificationClassName = 'notification-success-banner';
                $userNotificationMsg = "Table deleted successfully";
            } else {
                $notificationClassName = 'notification-error-banner';
                $userNotificationMsg =  "No table found with the ID: $tableId";
            }

            return [
                'notificationClassName' => $notificationClassName ?? '',
                'userNotificationMsg' => $userNotificationMsg ?? ''
            ];
        } catch (Exception  $e) {
            die("Could not delete record: " . $e);
        }*/
    }
}

function addUnderScoreBetweenSpaceInString($string)
{
    $string = preg_replace('/\s+/', '_', $string);
    return preg_replace('/_+/', '_', $string);
}

function convertMultipleUnderscoresIntoSingle($string)
{
    return preg_replace('/_+/', '_', $string);
}

function createDynamicTableTypes($tableName, $pdo)
{
    try {
        $tableName = convertMultipleUnderscoresIntoSingle($tableName);

        $sql = "CREATE TABLE IF NOT EXISTS $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_id INT NOT NULL,
            table_name VARCHAR(255) NOT NULL,
            original_table_name VARCHAR(255) NOT NULL,
            column_name  VARCHAR(255) NOT NULL,
            datatype_id INT NOT NULL,
            datatype VARCHAR(255) NOT NULL,
            data_quality INT NOT NULL DEFAULT 0,
            uniqueness INT NOT NULL DEFAULT 0,
            status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        return true;
    } catch(Exception $e) {
        return false;
    }
}

function createDynamicTableForDashboard($tableName, $pdo)
{
    try {
        $tableName = convertMultipleUnderscoresIntoSingle($tableName);
        $sql = "CREATE TABLE IF NOT EXISTS $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_quality_correct_data INT NOT NULL DEFAULT 0,
            data_quality_incorrect_data INT NOT NULL DEFAULT 0,
            text_issue INT NOT NULL DEFAULT 0,
            number_issue INT NOT NULL DEFAULT 0,
            date_issue INT NOT NULL DEFAULT 0,
            alphanumeric_issue INT NOT NULL DEFAULT 0,
            email_issue INT NOT NULL DEFAULT 0,
            duplicate_entries_issue INT NOT NULL DEFAULT 0,
            others_issue INT NOT NULL DEFAULT 0,
            null_issue INT NOT NULL DEFAULT 0,
            overall_correct_data INT NOT NULL DEFAULT 0,
            overall_incorrect_data INT NOT NULL DEFAULT 0,
            status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        return true;
    } catch(Exception $e) {
        return false;
    }
}

function createDynamicTableForDataVerification($tableName, $pdo)
{
    try {
        $tableName = $tableName."_data_verification";
        $tableName = convertMultipleUnderscoresIntoSingle($tableName);

        $sql = "CREATE TABLE $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            table_id INT NOT NULL,
            table_name VARCHAR(255) NOT NULL,
            original_table_name VARCHAR(255) NOT NULL,
            column_name  VARCHAR(255) NOT NULL,
            master_primary_key INT NOT NULL,
            ignore_flag INT NOT NULL DEFAULT 0,
            status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        return true;
    } catch(Exception $e) {
        return false;
    }
}

function calculateDataQualityStatPercentage($oveallCount, $value)
{
    return ($value / 100 ) * 100;
}

function insertIntoDynamicDatatypeTable($tableName, $originalTableName, $dataToInsert, $pdo)
{
    try {
        $dataTypeTableName = $tableName.'_datatype';
        $dataTypeTableName = convertMultipleUnderscoresIntoSingle($dataTypeTableName);
        $insertSql = "INSERT INTO $dataTypeTableName (table_id, table_name, original_table_name, column_name, datatype_id, datatype, data_quality, uniqueness) VALUES (:table_id, :table_name, :original_table_name, :column_name, :datatype_id, :datatype, :data_quality, :uniqueness)";
        $stmt = $pdo->prepare($insertSql);

        foreach ($dataToInsert as $row) {
            $dataQuality = mt_rand(50, 100);
            $dataUniqueness = mt_rand(50, 100);
            $stmt->bindParam(':table_id', $row['table_id']);
            $stmt->bindParam(':table_name', $tableName);
            $stmt->bindParam(':original_table_name', $originalTableName);
            $stmt->bindParam(':column_name', $row['column_name']);
            $stmt->bindParam(':datatype_id', $row['datatype_id']);
            $stmt->bindParam(':datatype', $row['datatype']);
            $stmt->bindParam(':data_quality', $dataQuality);
            $stmt->bindParam(':uniqueness', $dataUniqueness);
            $stmt->execute();
        }
        return true;
    } catch(Exception $e) {
        return false;
    }
}

function insertIntoDynamicDashboardTable($tableName, $pdo)
{
    try {
        $sql = "INSERT INTO $tableName (data_quality_correct_data, data_quality_incorrect_data, text_issue, number_issue, date_issue, alphanumeric_issue, email_issue, duplicate_entries_issue, others_issue, null_issue, overall_correct_data, overall_incorrect_data) VALUES (:data_quality_correct_data, :data_quality_incorrect_data, :text_issue, :number_issue, :date_issue, :alphanumeric_issue, :email_issue, :duplicate_entries_issue, :others_issue, :null_issue, :overall_correct_data, :overall_incorrect_data)";
        $stmt = $pdo->prepare($sql);

        // Bind the parameters
        $data_quality_correct_data = mt_rand(30, 100);
        $data_quality_incorrect_data = mt_rand(30, 100);
        $text_issue = mt_rand(1, 100);
        $number_issue = mt_rand(1, 100);
        $date_issue = mt_rand(1, 100);
        $alphanumeric_issue = mt_rand(1, 100);
        $email_issue = mt_rand(1, 100);
        $duplicate_entries_issue = mt_rand(1, 100);
        $others_issue = mt_rand(1, 100);
        $null_issue = mt_rand(1, 100);
        $overall_correct_data = mt_rand(30, 100);
        $overall_incorrect_data = mt_rand(30, 100);

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
        $stmt->execute();

        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getColumnNames($pdo, $table)
{
    $stmt = $pdo->query("DESCRIBE $table");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function trimTableLength($tableName)
{
    return (strlen($tableName) > 19) ? substr($tableName, 0, 19) : $tableName;
}

function updateOriginalTableNameColumnInRequiredTables($pdo, $oldTableName, $newTableName, $dbName)
{
    try {
        $newTableName = strtolower($newTableName);
        $dataTypeTableName = $newTableName.'_datatype';
        $dataVerificationTableName = $newTableName.'_data_verification';

        $pdo->exec("UPDATE tables_list SET name = '$newTableName', original_table_name = '$newTableName' WHERE name = '$newTableName'");

        $tables = [
            $newTableName.'_datatype'
        ];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table");
            $stmt->execute(['db' => $dbName, 'table' => $table]);

            // Fetch the result
            $tableExists = $stmt->fetchColumn();
            if ($tableExists) {
                $pdo->exec("UPDATE $table SET table_name = '$newTableName', original_table_name = '$newTableName' WHERE table_name = '$newTableName'");
            }
        }
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

function calculateDataQualityPercentage($pdo, $tableName, $columName)
{
    try {
        $dataVerificationTableName = $tableName.'_data_verification';

        //Get the total data
        $stmt1 = $pdo->query("SELECT count(*) as total_data FROM $tableName");
        $totalData = $stmt1->fetch(PDO::FETCH_ASSOC)['total_data'];


        //Get the total incorrect data
        $stmt2 = $pdo->query("SELECT count(*) as total_incorrect_data FROM $dataVerificationTableName WHERE column_name = '$columName' AND ignore_flag = 0");
        $totalIncorrectData = $stmt2->fetch(PDO::FETCH_ASSOC)['total_incorrect_data'];

        //Calculate the correct & incorrect data percentage
        $incorrectDataPercentage = round(($totalIncorrectData / $totalData) * 100);
        $correctDataPercentage = 100 - $incorrectDataPercentage;

        return [
            'correct_data_percentage' => $correctDataPercentage,
            'incorrect_data_percentage' => $incorrectDataPercentage
        ];
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

function calculateDataQualityNumbers($total, $correctDataPercentage, $inCorrectDataPercentage)
{
    // Step 1: Round both counts
    $correctDataCount = $total * ($correctDataPercentage / 100);
    $inCorrectDataCount = $total * ($inCorrectDataPercentage / 100);
    $roundedCorrectCount = round($correctDataCount);
    $roundedIncorrectCount = round($inCorrectDataCount);

    // Step 2: Calculate the difference to ensure the total is accurate
    $roundedTotal = $roundedCorrectCount + $roundedIncorrectCount;
    $difference = $total - $roundedTotal;

    // Step 3: Adjust the count based on the difference
    if ($difference != 0) {
        if ($roundedCorrectCount > $roundedIncorrectCount) {
            $roundedCorrectCount += $difference;
        } else {
            $roundedIncorrectCount += $difference;
        }
    }

    return [
        'correct_data_count' => $roundedCorrectCount,
        'incorrect_data_count' => $roundedIncorrectCount
    ];
}