<?php
include_once "database.php";
include_once "sidebar.php";
include_once "utilities/common_utils.php";
// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableName = $_REQUEST["table_name"] ?? "";
$projectName = $_REQUEST["project"] ?? "";

try {
    $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$tableName'";
    $result = $pdo->query($sql);
    $isTableExists = $result->fetchColumn() > 0 ? true : false;
} catch (PDOException $e) {
    $isTableExists =  false;
}
$columns = [];
if (!empty($tableName) && $isTableExists) {
    try {
        $tableDataTypes = $tableName."_datatype";
        $stmt = $pdo->prepare("SELECT column_name, data_quality, uniqueness FROM $tableDataTypes WHERE table_name = '".$tableName."'");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close cursor to release the connection

        $dashboardDataTableName = $tableName.'_dashboard';
        $stmt = $pdo->prepare("CALL GetDashboardData('$dashboardDataTableName')");
        $stmt->execute();
        $spDashboardData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close cursor to release the connection

        $dataVerificationTable = $tableName.'_data_verification';

        //Get the ignored issue to update the status, otherwise FindIncorrectData SP will make the ignore_flag as 0 for all the records
        $dvtQuery = $pdo->prepare("SELECT id FROM `$dataVerificationTable` where ignore_flag = 1");
        $dvtQuery->execute();
        $dvtQueryResults = $dvtQuery->fetchAll(PDO::FETCH_ASSOC);
        $idsToIgnore = [];
        foreach ($dvtQueryResults as $dvtQueryResult) {
            $idsToIgnore[] = $dvtQueryResult['id'];
        }

        //Truncate the existing wrong data from data_verification and populate again
        $stmt = $pdo->prepare("TRUNCATE TABLE `$dataVerificationTable`");
        $stmt->execute();
        $stmt->closeCursor(); // Close cursor to release the connection

        // Prepare and execute the stored procedure call
        $stmt = $pdo->prepare("CALL FindIncorrectData('$tableName', '$tableDataTypes')");
        $stmt->execute();
        $stmt->closeCursor(); // Close cursor to release the connection

        $idsToIgnore = implode(",", $idsToIgnore);
        $updateIgnoreFlagQuery = $pdo->prepare("UPDATE $dataVerificationTable SET ignore_flag = 1 WHERE id in ($idsToIgnore)");
        $updateIgnoreFlagQuery->execute();

        //Get the count of incorrect datas to displayed it in issues card
        $stmt = $pdo->prepare("SELECT dt.name, COUNT(dvt.master_primary_key) AS count FROM datatypes dt LEFT JOIN `$tableDataTypes` td ON dt.name = td.datatype LEFT JOIN `$dataVerificationTable` dvt ON td.column_name = dvt.column_name AND dvt.ignore_flag = 0 GROUP BY dt.name;");
        $stmt->execute();
        $issuesCountData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch (PDOException $e) {
        die("Something went wrong" . $e->getMessage().$e->getLine());
    }
}
$dataQualityPercentage = [];
include_once "header.php";
?>
<link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
<?php if (count($columns) == 0) {
    header("Location:home.php?msg=table");
    exit;
} else {?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10" style="margin-top:50px;">
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
                            foreach ($issuesCountData as $key => $issueCountRow) {
                                $backGroundColor = ($key % 2 == 0) ? '#71B6FA' : '#5C6ABD';

                                if($issueCountRow['count'] == 0) {
                                    echo '<div class="sticky-bar" style="background-color:#E9EDF0;width: 80%;color:black;">'.$issueCountRow['name'].' -  '.$issueCountRow['count'].'</div>';
                                } else {
                                    echo '<a href="view_issue.php?table='.$tableName.'&project='.$projectName.'&type='.$issueCountRow['name'].'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color: '.$backGroundColor.'; width: 100%;">'.$issueCountRow['name'].' -  '.$issueCountRow['count'].'</div></a>';
                                }
                             }

                            $duplicateEntriesIssueCount = $spDashboardData["duplicate_entries_issue"] ?? 0;
                            $otherIssueCount = $spDashboardData["others_issue"] ?? 0;
                            $nullIssueCount = $spDashboardData["null_issue"] ?? 0;

                             if($duplicateEntriesIssueCount == 0) {
                                echo '<div class="sticky-bar" style="color:black;background-color:#E9EDF0; width: 80%;">Duplicate entries - '.$duplicateEntriesIssueCount.'</div>';
                             } else {
                                echo '<a href="view_issue.php?table='.$tableName.'&project='.$projectName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color:#5C6ABD"; width: 100%;">Duplicate entries - '.$duplicateEntriesIssueCount.'</div></a>';
                             }
                             if($otherIssueCount == 0) {
                                echo '<div class="sticky-bar" style="color:black;background-color:#E9EDF0;width: 80%;">Others - '.$otherIssueCount.'</div>';
                             } else {
                                echo '<a href="view_issue.php?table='.$tableName.'&project='.$projectName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color:#71B6FA"; width: 100%;">Others - '.$otherIssueCount.'</div></a>';
                             }
                             if($nullIssueCount == 0) {
                                echo '<div class="sticky-bar" style="color:black;background-color:#E9EDF0;width: 80%;">NULL - '.$nullIssueCount.'</div>';
                             } else {
                                echo '<a href="view_issue.php?table='.$tableName.'&project='.$projectName.'" style="cursor:pointer;text-decoration:none;width:80%"><div class="sticky-bar" style="background-color:#5C6ABD"; width: 100%;">NULL - '.$nullIssueCount.'</div></a>';
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
                        <?php
                         $correctDataPercentage = 0;
                         $inCorrectDataPercentage = 0;
                        foreach ($columns as $column) {
                            $dataQuality = calculateDataQualityPercentage($pdo, $tableName, $column["column_name"]);
                            $dataQualityPercentage[] = [
                                'correct_data_percentage' => $correctDataPercentage + $dataQuality['correct_data_percentage'],
                                'incorrect_data_percentage' => $inCorrectDataPercentage + $dataQuality['incorrect_data_percentage']
                            ];
                            ?>
                                    <tr>
                                    <td><a href="view_table.php?column=<?=$column["column_name"]?>&table=<?=$tableName?>&project=<?=$projectName?>" class="table-name-link"><?=$column["column_name"]?></a></td>
                                    <td>
                                        <div class="sticky-bar-container">
                                            <div class="gradient-sticky-bar" style="background-color: green; width: 80%; --percentage: <?= $dataQuality['correct_data_percentage'] ?? 0?>%;"></div>
                                            <div class="sticky-bar-1" style="background-color: green; width: 10%; border-radius:3px;font-size:10px;text-align:center;"><?=$dataQuality['correct_data_percentage'] ?? 0?>%</div>
                                        </div>
                                    </td>
                                    <td>
                                    <div class="sticky-bar-container">
                                            <div class="gradient-sticky-bar2" style="background-color: #CC313D; width: 30%; --percentage: <?= $column["uniqueness"] ?? 0;?>%; text-align:center;"><span style="margin-left:39px;">Uniqueness - <?= $column["uniqueness"] ?? 0;?>%</span></div>

                                            <?php if($column["uniqueness"] ?? 0 < 100) { ?>
                                                <div class="sticky-bar-1" style="background-color: #F5613C; width: 10%; font-size:10px; text-align:center;border-top-right-radius:3px;border-bottom-right-radius:3px;margin-left:-12px;"><a href="data_quality_dimensions_stats.php?column=<?=$column["column_name"]?>&table=<?=$tableName?>&project=<?=$projectName?>" style="text-decoration:none; cursor:pointer;color:white;">View stats</a></div>
                                            <?php } else { ?>
                                                <div class="sticky-bar-1" style="background-color: #CC313D; width: 10%; font-size:10px; text-align:center;border-top-right-radius:3px;border-bottom-right-radius:3px;margin-left:-12px;"></div>
                                            <?php } ?>
                                            <div class="sticky-bar-1" style="background-color: #5C6ABD; width: 40%; border-radius:3px;font-size:10px;text-align:center;">Completeness - 97%</div>
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
}

//Calculate overall data quality percentage
$overallCorrectData = 0;

foreach($dataQualityPercentage as  $dqp) {
    $overallCorrectData+= $dqp['correct_data_percentage'] ?? 0;
}
$overallCorrectDataPercentage = round($overallCorrectData / count($columns));
$overallInCorrectDataPercentage = 100 - $overallCorrectDataPercentage;
?>
<script>
// Register the Datalabels plugin with Chart.js
Chart.register(ChartDataLabels);
const data = {
    labels: ['Incorrect data', 'Correct data'],
    datasets: [{
        data: [<?=$overallCorrectDataPercentage?>, <?=$overallInCorrectDataPercentage?>],
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
                'Incorrect data': '<?=WEBSITE_ROOT_PATH?>view_dataquality.php?table=<?=$tableName?>&project=<?=$projectName?>&type=incorrect',
                'Correct data': '<?=WEBSITE_ROOT_PATH?>view_dataquality.php?table=<?=$tableName?>&project=<?=$projectName?>&type=correct',
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
                return (value)+ '%';
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
            data: [<?=$overallInCorrectDataPercentage?>, <?=$overallCorrectDataPercentage?>],
            backgroundColor: ['#4DB24F', '#E92C18'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                grid: {
                    display: false // Disable grid lines
                }
            },
            x: {
                grid: {
                    display: false // Disable grid lines
                }
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
                    return (value);
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
