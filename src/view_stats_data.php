<?php
include_once "database.php";
include_once "sidebar.php";

$tableName = $_REQUEST['table'] ?? null;
$dataQualityType = $_REQUEST['type'] ?? null;
$projectName = $_REQUEST['project'] ?? '';
$columnName = $_REQUEST['column'] ?? '';
$columnValue = $_REQUEST['col_value'] ?? '';

// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columnQuery = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name')");
$columnQuery->execute();
$columns = $columnQuery->fetchAll(PDO::FETCH_COLUMN);

$sqlQuery = $pdo->prepare("SELECT * FROM `$tableName` WHERE  `$columnName` = '$columnValue'");
$sqlQuery->execute();
$data = $sqlQuery->fetchAll(PDO::FETCH_ASSOC);

include_once "header.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
            <!-- Table Below Cards -->
            <div style="padding:10px;">
             <h3>Stats for <?=$columnName?> column</h3>
                <?php if(!empty($data)) { ?>
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($columns as $col): ?>
                                        <td><?= htmlspecialchars($row[$col]) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php } else {?>
                    <div style="margin-top:60px; text-align:center; color:red; font-size:25px;">No data found in <?=$tableName?> table</div>
                <?php } ?>
                </div>
                <a href="data_quality_dimensions_stats.php?column=<?=$columnName?>&table=<?=$tableName?>&project=<?=$projectName?>" class="btn btn-primary" style="margin-right:5px;">Back</a>
             </div>
        </div>
    </div>
</div>
</body>
</html>
