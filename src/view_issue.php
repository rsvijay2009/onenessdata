<?php
include_once "database.php";
include_once "sidebar.php";

try {
   $tableName = $_REQUEST['table'];
    // PDO connection setup
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare(
        "SELECT  column_name, SUM(CASE WHEN ignore_flag = 0 THEN 1 ELSE 0 END) AS count FROM data_verification  where table_name = '$tableName' GROUP BY column_name ORDER BY column_name"
    );
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

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
                    <h2 style="margin-bottom:25px;">Issues from the <?=$tableName?> table</h2>
                    <div class="">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Column Name</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(count($issues) > 0) {
                                foreach ($issues as $issue) { ?>
                                    <tr>
                                        <td>
                                            <?php if($issue['count'] > 0) {?>
                                                <a href="ignore_issue.php?column=<?=$issue['column_name']?>&table=<?=$tableName?>" style="text-decoration:none; cursor:pointer;"><?=$issue['column_name']?>
                                            <?php } else { ?>
                                                <?=$issue['column_name']?>
                                            <?php } ?>
                                            </td>
                                        <td><?=$issue['count']?></td>
                                    </tr>
                                <?php }
                            } else { ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center;font-weight:bold; color:red;">No issues found</td>
                                    </tr>
                            <?php }?>
                            </tbody>
                        </table>
                        <a href="dashboard.php?table_name=<?=$tableName?>" class="btn btn-primary" style="margin-right:5px;">Back</a>
                    </div>
                </div>
        </div>
    </div>
</div>
</body>
</html>
