<?php
include_once "database.php";
include_once "sidebar.php";
// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableName = $_REQUEST["table_name"] ?? "";
if (!empty($tableName)) {
    try {
        $stmt = $pdo->prepare("SELECT column_name, data_quality, uniqueness  FROM table_datatypes WHERE table_name = '".$tableName."'");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close cursor to release the connection

        $stmt = $pdo->prepare("CALL GetDashboardData()");
        $stmt->execute();
        $spDashboardData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close cursor to release the connection
    } catch (PDOException $e) {
        die("Something went wrong" . $e->getMessage());
    }
}

include_once "header.php";
?>
<link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
<?php if (count($columns) == 0) {
    include "not_found_msg.php";
} else {?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-9">
            <div class="row g-4">
                <!-- Cards with Charts -->
                <div class="col-md-4">
                    <div class="card">
                    <h5 class="card-title">Data Quality</h5>
                        <div class="card-body">
                            <canvas id="pieChart1"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                    <h5 class="card-title">Issues</h5>
                    <div class="card-body">
                            <?php
                            $stmt = $pdo->prepare("SELECT name from datatypes WHERE status = 'ACTIVE'");
                            $stmt->execute();
                            $datatypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($datatypes as $key => $datatype) {
                                $backGroundColor = ($key % 2 == 0) ? '#71B6FA' : '#5C6ABD';
                                $storedProcedureVaraibleName = strtolower($datatype['name']).'_issue';
                            ?>
                            <a href="view_issue.php?table=<?=$tableName?>" style="cursor:pointer;color:white;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color: <?=$backGroundColor?>; width: 100%;"><?=$datatype['name']?> -  <?= $spDashboardData[$storedProcedureVaraibleName] ?? 0 ?></div></a>
                            <?php } ?>
                            <a href="view_issue.php?table=<?=$tableName?>" style="cursor:pointer;color:white;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color: #5C6ABD; width: 100%;">Duplicate entries - <?= $spDashboardData["duplicate_entries_issue"] ?? 0 ?></div>
                            <a href="view_issue.php?table=<?=$tableName?>" style="cursor:pointer;color:white;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color: #71B6FA; width: 100%;">Others - <?= $spDashboardData["others_issue"] ?? 0 ?></div></a>
                            <a href="view_issue.php?table=<?=$tableName?>" style="cursor:pointer;color:white;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color: #5C6ABD; width: 100%;">NULL - <?= $spDashboardData["null_issue"] ?? 0 ?></div></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                    <h5 class="card-title">Total Records</h5>
                        <div class="card-body">
                            <canvas id="pieChart2"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <a href="view_table.php?table=<?=$tableName?>"><input type="button" value="View Table" class="view-table" style="padding:5px; margin-left:10px;"></a> -->
            <!-- Table Below Cards -->
            <div style="padding:10px;">
                <div class="table-responsive">
                <table class="table table-bordered">
                <thead class="thead-light">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th>Data Quality</th>
                                    <th>Data Quality Dimensions</th>
                                </tr>
                            </thead>
                        <tbody>
                        <?php foreach ($columns as $column) { ?>
                                    <tr>
                                    <td><a href="view_table.php?column=<?=$column["column_name"]?>&table=<?=$tableName?>" class="table-name-link"><?= $column["column_name"]?></a></td>
                                    <td>
                                        <div class="sticky-bar-container">
                                            <div class="gradient-sticky-bar" style="background-color: green; width: 80%; --percentage: <?= $column["data_quality"]?>%;"></div>
                                            <div class="sticky-bar-1" style="background-color: green; width: 10%; border-radius:3px;font-size:10px;text-align:center;"><?= $column["data_quality"]?>%</div>
                                        </div>
                                    </td>
                                    <td>
                                    <div class="sticky-bar-container">
                                            <div class="gradient-sticky-bar2" style="background-color: #C17CAB; width: 80%; --percentage: <?= $column["uniqueness"]?>%; text-align:center;">Uniqueness - <?= $column["uniqueness"]?>%</div>
                                            <!-- <div class="sticky-bar-1" style="background-color: #71B6FA; width: 50%; border-radius:3px;font-size:10px; text-align:center;">Uniqueness - <?= $column["uniqueness"]?>%</div> -->
                                            <div class="sticky-bar-1" style="background-color: #5C6ABD; width: 50%; border-radius:3px;font-size:10px;text-align:center;">Completeness - 97%</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                        </tbody>
                    </table>
                </div>
             </div>
        </div>
    </div>
</div>
<?php
} ?>
<script>
// Register the Datalabels plugin with Chart.js
Chart.register(ChartDataLabels);
var ctx1 = document.getElementById('pieChart1').getContext('2d');
        var pieChart1 = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['Incorrect data', 'Correct data'],
                datasets: [{
                    data: [<?= $spDashboardData["data_quality_correct_data"] ?? 0 ?>, <?= $spDashboardData["data_quality_incorrect_data"] ?? 0 ?>],
                    backgroundColor: ['#E92C18', '#4DB24F']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Overall Score',
                        position: 'bottom',
                        font: {
                            size: 13
                        }
                    },
                    legend: {
                        display: true,
                        position: "right",
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 13,
                                family: 'Arial',
                            },
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold'
                        },
                        formatter: (value, ctx) => {
                            let sum = 0;
                            let dataArr = ctx.chart.data.datasets[0].data;
                            dataArr.map(data => sum += data);
                            return (value / sum * 100).toFixed(2) + '%';
                        }
                    },
                    tooltip: {
                        enabled: false
                    },
                }
            }
        });

        var ctx2 = document.getElementById('pieChart2').getContext('2d');
        var pieChart2 = new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: ['Correct data', 'Wrong data'],
                datasets: [{
                    data: [<?= $spDashboardData["overall_correct_data"] ?? 0 ?>, <?= $spDashboardData["overall_incorrect_data"] ?? 0?>],
                    backgroundColor: ['#4DB24F', '#833771']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: "right",
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 13,
                                family: 'Arial',
                            },
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold'
                        },
                        formatter: (value, ctx) => {
                            let sum = 0;
                            let dataArr = ctx.chart.data.datasets[0].data;
                            dataArr.map(data => sum += data);
                            return (value / sum * 100).toFixed(2) + '%';
                        }
                    },
                    tooltip: {
                        enabled: false
                    },
                }
            }
        });
</script>
</body>
</html>
