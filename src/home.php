<?php
include_once "sidebar.php";
$error = "";
$userNotificationMsg = "";
$notificationClassName = "";
$requestErr = $_REQUEST["error"] ?? "";
$requestSuccessMsg = $_REQUEST["msg"] ?? "";

if ($requestErr == "table") {
    $error = "Table name already exist. please try some other name";
} elseif ($requestErr == "project") {
    $error = "Project name already exist. please try some other name";
} elseif ($requestErr == "error") {
    $error = "Something went wrong. Please try again";
} elseif($requestErr == 'invalid_table') {
    $error = "Invalid table name. Please use only letters, numbers, and underscores, and start with a letter or underscore.";
}
if($requestSuccessMsg == "table") {
    $userNotificationMsg = 'Table deleted successfully';
    $notificationClassName = 'notification-success-banner';
} else if($requestSuccessMsg == "project") {
    $userNotificationMsg = 'Project deleted successfully';
    $notificationClassName = 'notification-success-banner';
} else if($requestSuccessMsg == "error") {
    $userNotificationMsg = 'Something went wrong. Please try again';
    $notificationClassName = 'notification-error-banner';
}
include_once "header.php";
?>
<link rel="stylesheet" href="styles/index.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        
        <div class="col-md-4" style="margin:10px; padding:30px">
        <?php if (!empty($error)) { ?>
            <p class="errorMsg" id="errorMsg" style="color:red;padding-bottom:10px;"><?=$error?></p>
        <?php } ?>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="projectName" class="form-label">Project Name</label>
                <input type="text" class="form-control" id="projectName" name="projectName" placeholder="Enter project name">
            </div>
            <?php try {
                $stmt = $pdo->prepare("SELECT id, name FROM projects");
                $stmt->execute();
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $projects = [];
            } ?>
            <div class="mb-3">
                <label for="projectList" class="form-label">Choose Project</label>
                <select class="form-select" id="projectList" name="projectList">
                    <option selected value="">Choose project</option>
                    <?php foreach ($projects as $project) { ?>
                        <option value=<?= $project["id"] ?>><?= ucfirst($project["name"]) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="tableName" class="form-label">Table Name</label>
                <input type="text" class="form-control" id="tableName" name="tableName" placeholder="Enter table name" required>
            </div>
            <div class="mb-3">
                <label for="chooseFile" class="form-label">Choose File</label>
                <input type="file" class="form-control" id="fileToUpload" name="fileToUpload" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    </div>
</div>
<script src="scripts/index.js"></script>
</body>
</html>