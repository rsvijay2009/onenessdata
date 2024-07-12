
<?php
require_once "auth.php";
check_authentication();
include_once "database.php";
include_once "constants/common_constants.php";

$userNotificationMsg = '';
$notificationClassName = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formName']) && $_POST['formName'] == 'sidebarForm') {
    include_once("utilities/common_utils.php");
    $projectId = $_POST['deleteProjectId'] ?? 0;
    $tableId = $_POST['deleteTableId'] ?? 0;

    if(isset($_POST['deleteTableByName']) && !empty($_POST['deleteTableByName'])) {
        try {
            $tableName = $_POST['deleteTableByName'];
            $sql = "DROP TABLE IF EXISTS $tableName";
            $pdo->exec($sql);

            $pdo->exec("DELETE FROM tables_list WHERE name = '$tableName'");

            $response['notificationClassName'] = 'notification-success-banner';
            $response['userNotificationMsg'] = "Table deleted successfully";
            $actionType = 'table';
        } catch (\PDOException $e) {
            $response['notificationClassName'] = 'notification-error-banner';
            $response['userNotificationMsg'] =  "Something went wrong";
            $actionType = 'table';
        }
    }
    if($projectId > 0) {
        $response = deleteAllProjectRelatedData($pdo, $projectId);
        $actionType = 'project';
    } else if($tableId > 0) {
        $response = deleteAllTableRelatedData($pdo, $tableId);
        $actionType = 'table';
    }

    $notificationClassName = $response['notificationClassName'] ?? '';
    $userNotificationMsg = $response['userNotificationMsg'] ?? '';

    header("Location:home.php?msg=$actionType");
}
$stmt = $pdo->prepare("SELECT id, name FROM projects");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
?>