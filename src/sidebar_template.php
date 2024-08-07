<?php
include_once "utilities/common_utils.php";
$currentFileName = basename($_SERVER['PHP_SELF']);
$sideBarWithDesign = ($currentFileName == 'merge.php') ? 'col-md-2' : 'col-md-2';
?>
<link rel="stylesheet" href="styles/sidebar.css">
<input type="hidden" id="notification-content" value="<?=$userNotificationMsg?>">

<div id="notification" class="<?=$notificationClassName?>">
    <!-- Message will be inserted here dynamically -->
</div>
<?php if($currentFileName == 'view_reconcile_table_data.php' || $currentFileName == 'rename_table.php') { ?>  <div class="<?=$sideBarWithDesign?> bg-light" style="width:17%;">
<?php } else {?>  <div class="<?=$sideBarWithDesign?> bg-light"> <?php }?>
    <div class="d-flex flex-column flex-shrink-0 p-3" style="height: 100vh;">
    <a href="<?= WEBSITE_ROOT_PATH ?>home.php" style="cursor:pointer; text-decoration:none;"><h5 class="logo-data">Onness Data</h5></a>
            <div class="menu-item">
                <a class="nav-link" href="<?= WEBSITE_ROOT_PATH ?>home.php" style="color:#71B6FA; padding-left:13px;">Upload</a>
                <a class="nav-link" href="<?= WEBSITE_ROOT_PATH ?>rename_table.php" style="color:#71B6FA; padding-left:13px;">Rename table</a>
            </div>
        <form name="sidebarForm" method="post">
            <input type="hidden" name="formName" value="sidebarForm">
            <input type="hidden" name="deleteProjectId" id="deleteProjectId" value="0">
            <input type="hidden" name="deleteTableId" id="deleteTableId" value="0">
            <input type="hidden" name="deleteTableByName" id="deleteTableByName" value="">

            <?php foreach ($projects as $project) { ?>
                <div class="menu-item">
                    <span class="d-flex justify-content-between align-items-center">
                        <a class="btn btn-toggle align-items-center rounded collapsed" data-bs-toggle="collapse" href="#<?= $project["name"
                        ] ?>" role="button" aria-expanded="false" style="color:#71B6FA; text-wrap:wrap;">
                        <?= ucfirst($project["name"] )?> <span class="badge" style="font-weight:bold;font-size:20px;color:black;"><?=(isset($_REQUEST['project']) && $_REQUEST['project'] == $project["name"]) ? "-" : "+"?></span></a>
                    </span>
                    <?php
                    $sql ="SELECT id, name, original_table_name FROM tables_list where  project_id = :projectId";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":projectId", $project["id"], PDO::PARAM_STR);
                    $stmt->execute();
                    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="menu collapse <?=(isset($_REQUEST['project']) && $_REQUEST['project'] == $project["name"]) ? "show" : ""?>" id="<?=$project["name"]?>">
                        <?php foreach ($tables as $table) {
                            $tblName = trimTableLength($table["original_table_name"]);
                            ?>
                            <ul class="nav flex-column p-1" style="width:220px;">
                                    <li class="nav-item"  draggable="true" ondragstart="drag(event, this)" style="max-width:215px; word-wrap:break-word;">
                                        <a href="dashboard.php?table_name=<?=$table["name"]?>&project=<?=$project["name"]?>" style="text-decoration:none;padding-left:14px;<?php if(isset($_REQUEST['table_name']) && strtolower($_REQUEST['table_name']) == strtolower($table["name"]) || isset($_REQUEST['table']) && strtolower($_REQUEST['table']) == strtolower($table["name"])) {?> color:#D828DA; font-weight:bold;<?php } else {?>color:black<?php } ?>" role="button"><?=ucfirst($tblName)?> <a onclick="confirmTableDeletion('<?= $table['id'] ?>', '<?= $table['name'] ?>')" style="cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        <span id="orgTableName" style="display:none;"><?=$table['name']?></span>
                                    </a>
                                    </li>
                                </ul>
                        <?php } ?>
                    </div>
                </div>
                <div class="collapse <?=(isset($_REQUEST['project']) && $_REQUEST['project'] == $project["name"]) ? "show" : ""?>" id="<?=$project["name"]?>">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link project-delete" onclick="confirmProjectDeletion(<?= $project['id'] ?>, '<?= $project['name'] ?>')"> Delete project</a>
                        </li>
                    </ul>
                </div>
            <?php } ?>
            <a class="nav-link" style="color:#71B6FA;margin-left:-3px;" href="merge.php">Merge</a>
            <?php
                $sql ="SELECT id, name, original_table_name FROM tables_list where  table_type = :table_type";
                $stmt = $pdo->prepare($sql);
                $table_type = 'join';
                $stmt->bindParam(":table_type", $table_type, PDO::PARAM_STR);
                $stmt->execute();
                $savedJoinTables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="menu-item">
                <span class="d-flex justify-content-between align-items-center">
                    <a class="btn btn-toggle align-items-center rounded collapsed" data-bs-toggle="collapse" href="#join" role="button" aria-expanded="false" style="color:#71B6FA; text-wrap:wrap;margin-top:-5px;"> Join
                            <span class="badge" style="font-weight:bold;font-size:20px;color:black;">+</span>
                    </a>
                </span>
                <div class="menu collapse " id="join">
                    <ul class="nav flex-column p-1">
                        <li class="nav-item" draggable="true" ondragstart="drag(event, this)" style="max-width:215px; word-wrap:break-word;"><a href="join.php" style="text-decoration:none;padding-left:14px;color:#71B6FA;" role="button">New join
                        </a>
                        </li>
                    </ul>
                    <?php foreach ($savedJoinTables as $savedJoinTable) {?>
                        <ul class="nav flex-column p-1">
                            <li class="nav-item" draggable="true" ondragstart="drag(event, this)" style="max-width:215px; word-wrap:break-word;">
                                <a href="view_join_table_data.php?table=<?=$savedJoinTable['name']?>" style="text-decoration:none;padding-left:14px;color:black" role="button"><?=trimTableLength(ucfirst($savedJoinTable['name']))?>
                                </a>
                                <a onclick="confirmTableDeletionByName('<?=$savedJoinTable['name']?>')" style="cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></a>
                            </li>
                        </ul>
                    <?php } ?>
                </div>
            </div>
            <?php
                $sql ="SELECT id, name, original_table_name FROM tables_list where  table_type = :table_type";
                $stmt = $pdo->prepare($sql);
                $table_type = 'reconcile';
                $stmt->bindParam(":table_type", $table_type, PDO::PARAM_STR);
                $stmt->execute();
                $reconcileTables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="menu-item">
                <span class="d-flex justify-content-between align-items-center">
                    <a class="btn btn-toggle align-items-center rounded collapsed" data-bs-toggle="collapse" href="#reconcile" role="button" aria-expanded="false" style="color:#71B6FA; text-wrap:wrap;margin-top:-5px;"> Reconcile
                            <span class="badge" style="font-weight:bold;font-size:20px;color:black;">+</span>
                    </a>
                </span>
                <div class="menu collapse " id="reconcile">
                    <ul class="nav flex-column p-1">
                        <li class="nav-item" draggable="true" ondragstart="drag(event, this)" style="max-width:215px; word-wrap:break-word;"><a href="reconcile.php" style="text-decoration:none;padding-left:14px;color:#71B6FA;" role="button">New project
                        </a>
                        </li>
                    </ul>
                    <?php foreach ($reconcileTables as $reconcileTable) {?>
                        <ul class="nav flex-column p-1">
                            <li class="nav-item" draggable="true" ondragstart="drag(event, this)" style="max-width:215px; word-wrap:break-word;">
                                <a href="view_reconcile_table_data.php?table=<?=$reconcileTable['name']?>" style="text-decoration:none;padding-left:14px;color:black" role="button"><?=trimTableLength(ucfirst($reconcileTable['name']))?>
                                </a>
                                <a onclick="confirmTableDeletionByName('<?=$reconcileTable['name']?>')" style="cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></a>
                            </li>
                        </ul>
                    <?php } ?>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="scripts/sidebar.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">