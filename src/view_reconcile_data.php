<?php
include_once "database.php";
include_once "sidebar.php";

try {
    $table = $_REQUEST['table'];
    $selectedColumnsArr = explode(",", $_REQUEST['selectedColumns']);
    $selectedColumnsToSumArr = explode(",", $_REQUEST['selectedColumnsToSum']);
    $selectedColumnsToGroupByArr = explode(",", $_REQUEST['selectedColumnsToGroupBy']);
    $selectedColumns = array_diff($selectedColumnsArr, $selectedColumnsToSumArr);

    $columnsToSelectFromTable = implode(', ', $selectedColumns);
    $sumColumn = implode(',', $selectedColumnsToSumArr);
    $groupByColumns = implode(', ', $selectedColumnsToGroupByArr);

    // PDO connection setup
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "SELECT $columnsToSelectFromTable, SUM($sumColumn) as $sumColumn FROM $table GROUP BY $groupByColumns";
    $stmt = $pdo->prepare(
    "SELECT $columnsToSelectFromTable, SUM($sumColumn) as $sumColumn FROM $table GROUP BY $groupByColumns"
    );
    $stmt->execute();
    $reconcileData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error :" . $e->getMessage());
}
$columns = array_keys($reconcileData[0]);
include_once "header.php";
?>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
                <div style="padding:10px;">
                    <h2 style="margin-bottom:25px;">Prepared data</h2>
                    <div class="dropdown" style="display: flex; justify-content: flex-end; margin-top:25px;">
                        <a href="reconcile.php" class="btn btn-primary" style="margin-right: 5px;height: 40px;">Back</a>
                        <input type="submit" value="Save table" style="line-height: 20px;">
                    </div>
                    <div class="">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php foreach ($columns as $column) {?>
                                        <th><?=$column?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(count($reconcileData) > 0) {
                                foreach ($reconcileData as $data) { ?>
                                    <tr>
                                        <?php foreach ($columns as $column) { ?>
                                            <td><?=$data[$column]?></td>
                                        <?php } ?>
                                    </tr>
                                <?php }
                            }?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>
</div>
</body>
</html>
