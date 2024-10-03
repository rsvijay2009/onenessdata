<?php

include "database.php";

$error = "";
$tableName = $_POST["tableName"] ?? "";
$projectName = $_POST["projectName"] ?? "";
$projectId = $_POST["projectList"] ?? 0;

if (!empty($projectName)) {
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE name = :projectName");
    $stmt->bindParam(":projectName", $projectName);
    $stmt->execute();
    $projectData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projectId = $projectData[0]["id"] ?? null;
    $error = $projectId ? "project" : "";
}
if (!empty($error)) {
    echo $error;
    header("Location: home.php?error=$error");
    exit();
} else {
    if(isset($_POST["projectName"]) && !empty($_POST["projectName"])) {
        $sql = "INSERT INTO projects (name) VALUES (:name)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', strtolower($projectName));
        $stmt->execute();
        $projectId = $pdo->lastInsertId();
    }
    $target_dir = "uploads/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $fileSize = $_FILES['fileToUpload']['size'];
    $fileSizeMB = ($fileSize / 1024) / 1024;

    if ($fileType != "csv") {
        header("Location: home.php?error=file_type");
        exit();
    }
    if ($fileSizeMB > 10) {
        header("Location: home.php?error=file_size&size=$fileSizeMB");
        exit();
    }
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $projectName = ($projectId > 0) ? $_POST['projectNameFromList'] :  $_POST["projectName"];
        header("Location: csv_columns.php?file=" .urlencode($target_file) ."&table_name=" .$tableName."&project_id=".$projectId."&projectName=".$projectName);
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>
