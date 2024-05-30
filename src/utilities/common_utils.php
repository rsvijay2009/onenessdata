<?php

function deleteAllProjectRelatedData($projectId = 0, $pdo)
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

function deleteAllTableRelatedData($tableId = 0, PDO $pdo)
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

function createDynamicTableTypes($projectName, $tableName, $pdo)
{
    try {
        $tableDataTypeName = $projectName.$tableName."_datatype";

        $sql = "CREATE TABLE IF NOT EXISTS $tableDataTypeName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_id INT NOT NULL,
            table_name VARCHAR(255) NOT NULL,
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

function createDynamicTableForDashboard($projectName, $tableName, $pdo)
{
    try {
        $tableDashboardData = $projectName.$tableName."_dashboard";

        $sql = "CREATE TABLE IF NOT EXISTS $tableDashboardData (
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