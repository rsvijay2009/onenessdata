<?php
include_once "database.php";
include_once "sidebar.php";

$columnName = $_REQUEST['column'] ?? null;
$tableName = $_REQUEST['table'] ?? null;
// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = $pdo->prepare("SELECT  $columnName FROM `$tableName`");
$sql->execute();
$data = $sql->fetchAll(PDO::FETCH_ASSOC);

$sqlForTop5Stat = $pdo->prepare("
SELECT  $columnName, count
FROM (
    SELECT $columnName, COUNT(*) AS count, MIN(primary_key) AS max_primary_key
    FROM $tableName
    GROUP BY $columnName
) AS subquery
LIMIT 5
");
$sqlForTop5Stat->execute();
$top5Data = $sqlForTop5Stat->fetchAll(PDO::FETCH_ASSOC);

$sqlForBottom5Stat = $pdo->prepare("
SELECT  $columnName, count
FROM (
    SELECT $columnName, COUNT(*) AS count, MIN(primary_key) AS max_primary_key
    FROM $tableName
    GROUP BY $columnName
) AS subquery
ORDER BY max_primary_key DESC
LIMIt 5
");
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
                    <a href="dashboard.php?table_name=<?=$tableName?>">&lt;&lt;Back</a>
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
                                        <th style="text-align:center;background:#D3C5E5">Top 5</th>
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
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($top5Data as $top5) {?>
                                                            <tr>
                                                                <td><?=$top5[$columnName] ?? 'NULL'?></td>
                                                                <td><?=$top5['count']?></td>
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
                                        <th style="text-align:center;background:#D3C5E5">Bottom 5</th>
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
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($bottom5Data as $bottom5) {?>
                                                            <tr>
                                                                <td><?=$bottom5[$columnName] ?? 'NULL'?></td>
                                                                <td><?=$bottom5['count']?></td>
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
                <div style="margin-top:30px;text-align:center; color:red; font-weight:bold;font-size:20px;">Sorry! Stats not available <a href="dashboard.php?table_name=<?=$tableName?>">click here </a> to go back</div>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
