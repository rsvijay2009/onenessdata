<?php
$currentFileName = basename($_SERVER['PHP_SELF']);
$sideBarWithDesign = ($currentFileName == 'merge.php') ? 'col-md-2' : 'col-md-2';
?>
<link rel="stylesheet" href="styles/sidebar.css">
<input type="hidden" id="notification-content" value="<?=$userNotificationMsg?>">

<div id="notification" class="<?=$notificationClassName?>">
    <!-- Message will be inserted here dynamically -->
</div>
<div class="<?=$sideBarWithDesign?> bg-light">
    <div class="d-flex flex-column flex-shrink-0 p-3" style="height: 100vh;">
    <a href="<?= WEBSITE_ROOT_PATH ?>" style="cursor:pointer; text-decoration:none;"><h5 class="logo-data">Onness Data</h5></a>
            <div class="menu-item">
                <a class="nav-link" href="<?= WEBSITE_ROOT_PATH ?>" style="color:#71B6FA; padding-left:13px;">Upload</a>
                </h6>
            </div>
        <form name="sidebarForm" method="post">
            <input type="hidden" name="deleteProjectId" id="deleteProjectId" value="0">
            <input type="hidden" name="deleteTableId" id="deleteTableId" value="0">
            <?php foreach ($projects as $project) { ?>
                <div class="menu-item">
                    <span class="d-flex justify-content-between align-items-center">
                        <a class="btn btn-toggle align-items-center rounded collapsed" data-bs-toggle="collapse" href="#<?= $project["name"
                        ] ?>" role="button" aria-expanded="false" style="color:#71B6FA; text-wrap:wrap;">
                        <?= ucfirst($project["name"] )?> <span class="badge" style="font-weight:bold;font-size:20px;color:black;">+</span></a>
                    </span>
                    <?php
                    $sql ="SELECT id, name FROM tables_list where  project_id = :projectId";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":projectId", $project["id"], PDO::PARAM_STR);
                    $stmt->execute();
                    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="menu collapse" id="<?= $project["name"] ?>">
                        <?php foreach ($tables as $table) { ?>
                            <ul class="nav flex-column p-1">
                                    <li class="nav-item"  draggable="true" ondragstart="drag(event)">
                                        <a href="dashboard.php?table_name=<?=$table["name"]?>" style="text-decoration:none; color:black;padding-left:15px;" role="button"><?= ucfirst(
                                            $table["name"]
                                        ) ?> <a onclick="confirmTableDeletion('<?= $table['id'] ?>', '<?= $table['name'] ?>')" style="cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                    </a>
                                    </li>
                                </ul>
                        <?php } ?>
                    </div>
                </div>
                <div class="collapse" id="<?= $project["name"] ?>">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link project-delete" onclick="confirmProjectDeletion(<?= $project['id'] ?>, '<?= $project['name'] ?>')"> Delete project</a>
                        </li>
                    </ul>
                </div>
            <?php } ?>
                    <a class="nav-link" style="color:#71B6FA;margin-left:-3px;" href="merge.php">Merge</a>
        </form>
    </div>
</div>
<script src="scripts/sidebar.js"></script>