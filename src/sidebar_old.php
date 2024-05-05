
<?php
include_once "database.php";
include_once "constants/common_constants.php";

$stmt = $pdo->prepare("SELECT id, name FROM projects");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="styles/sidebar.css">
<div class="sidebar">
    <h5 style="margin:10px;" class="logo-data">Onness Data</h5>
            <ul class="nav flex-column p-1">
                <li class="nav-item">
                    <a class="nav-link" href="<?=WEBSITE_ROOT_PATH?>"><i class="fas fa-upload"></i> Upload</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?=WEBSITE_ROOT_PATH?>"><i class="fas fa-folder"></i> Projects</a>
                </li>
                
            <?php foreach ($projects as $project) { ?>
                <li class="nav-item" style="margin-left:25px;">
                    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#project_<?= $project["id"] ?>"><i class="fas fa-project-diagram"></i>  <?= ucfirst($project["name"]) ?></a>
                    <?php
                    $sql ="SELECT id, name FROM tables_list where  project_id = :projectId";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":projectId", $project["id"],  PDO::PARAM_STR );
                    $stmt->execute();
                    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="submenu collapse" id="project_<?= $project["id"] ?>">
                        <ul class="flex-column pl-4 nav">
                        <?php foreach ($tables as $table) { ?>
                            <li class="nav-item draggable" draggable="true" ondragstart="drag(event)"><a class="nav-link" style="margin-left:10px;" href="<?=WEBSITE_ROOT_PATH?>/dashboard.php?table_name=<?= $table[
                                "name"
                            ] ?>"><i class="fas fa-table"></i> <?= ucfirst($table["name"]) ?></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
                <?php } ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?=WEBSITE_ROOT_PATH?>/merge.php"><i class="fa fa-object-group"></i> Merge</a>
                </li>
            </ul>
        </div>
<div class="col-md-2 bg-light">
    <div class="d-flex flex-column flex-shrink-0 p-3" style="height: 100vh;">
            <h4 class="mb-4">oneness data</h4>
            <div class="menu-item">
                <h6 class="d-flex justify-content-between align-items-center">
                <img src="images/upload_icon.png" style="width:15px; height:15px"><a href="<?=WEBSITE_ROOT_PATH?>" style="text-decoration:none; color:black; margin-right:100px;"> Upload</a>
                </h6>
            </div>
        <?php foreach ($projects as $project) { ?>
            <div class="menu-item">
                <h6 class="d-flex justify-content-between align-items-center">
                    <span style=""><img src="images/project_icon.png" style="width:30px; height:40px;margin-left:-8px;"></span>
                    <span style="margin-right:78px;"><?= $project["name"] ?></span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </h6>
                <?php
                $sql = "SELECT id, name FROM tables_list where  project_id = :projectId";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam( ":projectId",$project["id"], PDO::PARAM_STR);
                $stmt->execute();
                $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="sub-menu">
                    <?php foreach ($tables as $table) { ?>
                        <a href="<?=WEBSITE_ROOT_PATH?>/dashboard.php?table_name=<?= $table["name"] ?>" style="text-decoration:none; color:black;"><p><?= $table["name"] ?></p>
                    </a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<script src="scripts/sidebar.js"></script>