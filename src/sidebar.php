
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

    if($projectId > 0) {
        $response = deleteAllProjectRelatedData($projectId, $pdo);
        $actionType = 'project';
    } else if($tableId > 0) {
        $response = deleteAllTableRelatedData($tableId, $pdo);
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