
<?php
include_once "database.php";
include_once "constants/common_constants.php";

$userNotificationMsg = '';
$notificationClassName = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include_once("utilities/common_utils.php");
    $projectId = $_POST['deleteProjectId'] ?? 0;
    $tableId = $_POST['deleteTableId'] ?? 0;

    if($projectId > 0) {
        $response = deleteAllProjectRelatedData($projectId, $pdo);
    } else if($tableId > 0) {
        $response = deleteAllTableRelatedData($tableId, $pdo);
    }

    $notificationClassName = $response['notificationClassName'] ?? '';
    $userNotificationMsg = $response['userNotificationMsg'] ?? '';
}
$stmt = $pdo->prepare("SELECT id, name FROM projects");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
?>