<?php
include_once "database.php";
include_once "sidebar.php";
include_once "utilities/common_utils.php";

$columnName = $_REQUEST['column'] ?? null;
$tableName = $_REQUEST['table'] ?? null;
$projectName = $_REQUEST['project'] ?? '';
// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//Get row count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM $tableName");
$stmt->execute();
$rowCount = $stmt->fetchColumn();

$columns = getColumnNames($pdo, $tableName);
$minMaxKey = 'primary_key';

if (in_array('id', $columns)) {
    $minMaxKey = 'id';
}

$sql = $pdo->prepare("SELECT  $columnName FROM `$tableName`");
$sql->execute();
$data = $sql->fetchAll(PDO::FETCH_ASSOC);

$sqlForTop5Stat = $pdo->prepare("select $columnName, count(*) as count from $tableName group by $columnName order by count desc limit 10");
$sqlForTop5Stat->execute();
$top5Data = $sqlForTop5Stat->fetchAll(PDO::FETCH_ASSOC);

$sqlForBottom5Stat = $pdo->prepare("select $columnName, count(*) as count from $tableName group by $columnName order by count asc limit 10");
$sqlForBottom5Stat->execute();
$bottom5Data = $sqlForBottom5Stat->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once "header.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <?php if(count($data) > 0) { ?>
            <div class="col-md-10">
                <!-- Table Below Cards -->
                <div style="padding:10px;">
                    <h3>Stats of dimensions</h3>
                    <a href="dashboard.php?table_name=<?=$tableName?>&project=<?=$projectName?>">&lt;&lt;Back</a>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <table class="table mt-3 table-bordered">
                                <thead>
                                    <tr>
                                        <th style="text-align:center;background:#D3C5E5"><?=htmlspecialchars($columnName)?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row[$columnName]?? 'NULL') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table mt-3 table-bordered">
                                <thead>
                                    <tr>
                                        <th style="text-align:center;background:#D3C5E5">Top 10</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <thead>
                                        <tr>
                                            <td>
                                                <table class="table table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Value</th>
                                                            <th>Count</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($top5Data as $top5) {?>
                                                            <tr>
                                                                <td><a href="view_stats_data.php?table=<?=$tableName?>&project=<?=$projectName?>&column=<?=$columnName?>&col_value=<?=$top5[$columnName]?>" style="text-decoration:none;"><?=$top5[$columnName] ?? 'NULL'?></a></td>
                                                                <td><?=$top5['count']?></td>
                                                                <td><?=calculateDataQualityStatPercentage($rowCount, $top5['count'])?>%</td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </thead>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table mt-3 table-bordered">
                                <thead>
                                    <tr>
                                        <th style="text-align:center;background:#D3C5E5">Bottom 10</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <thead>
                                        <tr>
                                            <td>
                                                <table class="table table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Value</th>
                                                            <th>Count</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($bottom5Data as $bottom5) {?>
                                                            <tr>
                                                                <td><a href="view_stats_data.php?table=<?=$tableName?>&project=<?=$projectName?>&column=<?=$columnName?>&col_value=<?=$bottom5[$columnName]?>" style="text-decoration:none;"><?=$bottom5[$columnName] ?? 'NULL'?></a></td>
                                                                <td><?=$bottom5['count']?></td>
                                                                <td><?=calculateDataQualityStatPercentage($rowCount, $bottom5['count'])?>%</td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </thead>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else {?>
            <div class="col-md-10">
                <div style="margin-top:30px;text-align:center; color:red; font-weight:bold;font-size:20px;">Sorry! Stats not available <a href="dashboard.php?table_name=<?=$tableName?>&project=<?=$projectName?>">click here </a> to go back</div>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
