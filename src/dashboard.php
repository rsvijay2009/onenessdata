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
                            <canvas id="pieChart"></canvas>
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
                                $storedProcedureVariableName = strtolower($datatype['name']).'_issue';
                                $issueCount = $spDashboardData[$storedProcedureVariableName] ?? 0;

                                if($issueCount == 0) {
                                    echo '<div class="sticky-bar" style="background-color:#E9EDF0;width: 80%;color:black;">'.$datatype['name'].' -  '.$issueCount.'</div>';
                                } else {
                                    echo '<a href="view_issue.php?table='.$tableName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color: '.$backGroundColor.'; width: 100%;">'.$datatype['name'].' -  '.$issueCount.'</div></a>';
                                }
                             }
                                $duplicateEntriesIssueCount = $spDashboardData["duplicate_entries_issue"] ?? 0;
                                $otherIssueCount = $spDashboardData["others_issue"] ?? 0;
                                $nullIssueCount = $spDashboardData["null_issue"] ?? 0;

                             if($duplicateEntriesIssueCount == 0) {
                                echo '<div class="sticky-bar" style="color:black;background-color:#E9EDF0; width: 80%;">Duplicate entries - '.$duplicateEntriesIssueCount.'</div>';
                             } else {
                                echo '<a href="view_issue.php?table='.$tableName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color:#5C6ABD"; width: 100%;">Duplicate entries - '.$duplicateEntriesIssueCount.'</div></a>';
                             }
                             if($otherIssueCount == 0) {
                                echo '<div class="sticky-bar" style="color:black;background-color:#E9EDF0;width: 80%;">Others - '.$otherIssueCount.'</div>';
                             } else {
                                echo '<a href="view_issue.php?table='.$tableName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color:#71B6FA"; width: 100%;">Others - '.$otherIssueCount.'</div></a>';
                             }
                             if($nullIssueCount == 0) {
                                echo '<div class="sticky-bar" style="color:black;background-color:#E9EDF0;width: 80%;">NULL - '.$nullIssueCount.'</div>';
                             } else {
                                echo '<a href="view_issue.php?table='.$tableName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color:#5C6ABD"; width: 100%;">NULL - '.$nullIssueCount.'</div></a>';
                             }
                             ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                    <h5 class="card-title">Total Records</h5>
                        <div class="card-body">
                            <canvas id="barChart" style="margin-top:53px;"></canvas>
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
                                    <td><a href="view_table.php?column=<?=$column["column_name"]?>&table=<?=$tableName?>" class="table-name-link"><?=$column["column_name"]?></a></td>
                                    <td>
                                        <div class="sticky-bar-container">
                                            <div class="gradient-sticky-bar" style="background-color: green; width: 80%; --percentage: <?= $column["data_quality"]?>%;"></div>
                                            <div class="sticky-bar-1" style="background-color: green; width: 10%; border-radius:3px;font-size:10px;text-align:center;"><?= $column["data_quality"]?>%</div>
                                        </div>
                                    </td>
                                    <td>
                                    <div class="sticky-bar-container">
                                            <div class="gradient-sticky-bar2" style="background-color: #CC313D; width: 50%; --percentage: <?= $column["uniqueness"]?>%; text-align:center;"><span style="margin-left:39px;">Uniqueness - <?= $column["uniqueness"]?>%</span></div>

                                            <?php if($column["uniqueness"] < 100) { ?>
                                                <div class="sticky-bar-1" style="background-color: #A7BEAE; width: 10%; font-size:10px; text-align:center;border-top-right-radius:3px;border-bottom-right-radius:3px;margin-left:-12px;"><a href="data_quality_dimensions_stats.php?column=<?=$column["column_name"]?>&table=<?=$tableName?>" style="text-decoration:none; cursor:pointer;color:white;">View stats</a></div>
                                            <?php } else { ?>
                                                <div class="sticky-bar-1" style="background-color: #CC313D; width: 10%; font-size:10px; text-align:center;border-top-right-radius:3px;border-bottom-right-radius:3px;margin-left:-12px;"></div>
                                            <?php } ?>
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
const data = {
    labels: ['Incorrect data', 'Correct data'],
    datasets: [{
        data: [<?= $spDashboardData["data_quality_correct_data"] ?? 0 ?>, <?= $spDashboardData["data_quality_incorrect_data"] ?? 0 ?>],
        backgroundColor: ['#E92C18', '#4DB24F']
    }]
};
// Config for the chart
const pieChartconfig = {
  type: 'pie',
  data: data,
  options: {
    responsive: true,
    onClick: (evt, elements) => {
        if (elements.length > 0) {
            const chartElement = elements[0];
            const index = chartElement.index;
            const label = data.labels[index];
            const urlMap = {
                'Incorrect data': '<?=WEBSITE_ROOT_PATH?>view_dataquality.php?table=<?=$tableName?>&type=incorrect',
                'Correct data': '<?=WEBSITE_ROOT_PATH?>view_dataquality.php?table=<?=$tableName?>&type=correct',
            };
            const url = urlMap[label];
            if (url) {
                window.open(url, '_blank');
            }
        }
    },
    onHover: (evt, elements) => {
      const canvas = document.getElementById('pieChart');
      if (elements.length > 0) {
        canvas.style.cursor = 'pointer';
      } else {
        canvas.style.cursor = 'default';
      }
    },
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
};
// Render the pie chart
const pieChart = new Chart(
  document.getElementById('pieChart'),
  pieChartconfig
);


var ctx2 = document.getElementById('barChart').getContext('2d');
var barChart = new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: ['Correct data', 'Wrong data'],
        datasets: [{
            data: [<?= $spDashboardData["overall_correct_data"] ?? 0 ?>, <?= $spDashboardData["overall_incorrect_data"] ?? 0?>],
            backgroundColor: ['#4DB24F', '#833771'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
            }
        },
        plugins: {
            legend: {
                display: false
            },
            datalabels: {
                color: '#fff',
                font: {
                    size:10,
                    weight: 'bold'
                },
                formatter: (value, ctx) => {
                    let sum = 0;
                    let dataArr = ctx.chart.data.datasets[0].data;
                    dataArr.map(data => sum += data);
                    return (value / sum * 100).toFixed(2);
                }
            },
            tooltip: {
                enabled: false
            },
            title: {
                display: false // Disable the title plugin
            }
        }
    }
});
//To prevent the charts not to disappear while switch to another tabs in browser and come back
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        pieChart.update();
        barChart.update();
    }
});
</script>
</body>
</html>
