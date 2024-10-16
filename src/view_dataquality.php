<?php
require_once "../vendor/autoload.php";
include_once "database.php";
include_once "sidebar.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


$tableName = $_REQUEST['table'] ?? null;
$dataQualityType = $_REQUEST['type'] ?? null;
$projectName = $_REQUEST['project'] ?? '';
$notificationMsg = '';

// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dataVerificationTableName = $tableName . '_data_verification';

// Handle "Ignore All" functionality
if (isset($_POST['ignoreAllIssueFlag']) && $_POST['ignoreAllIssueFlag'] != '') {
    $dvtQuery = $pdo->prepare("SELECT ec.*, ecdv.column_name, ecdv.master_primary_key FROM `$tableName` ec LEFT JOIN `$dataVerificationTableName` ecdv ON ec.primary_key = ecdv.master_primary_key WHERE ecdv.ignore_flag = 0");
    $dvtQuery->execute();
    $dvtQueryResults = $dvtQuery->fetchAll(PDO::FETCH_ASSOC);
    $idsToIgnore = array_column($dvtQueryResults, 'master_primary_key');

    if (!empty($idsToIgnore)) {
        $idsToIgnore = implode(",", $idsToIgnore);
        $updateQuery = $pdo->prepare("UPDATE $dataVerificationTableName SET ignore_flag = 1 WHERE master_primary_key IN ($idsToIgnore)");
        $updateQuery->execute();
        $notificationMsg = 'All the issues are ignored successfully';
    }
}

// Handle ignoring individual issues
if (isset($_POST['issueId']) && $_POST['issueId'] != '') {
    $issueId = $_POST['issueId'];
    $query = "UPDATE $dataVerificationTableName SET ignore_flag = 1 WHERE master_primary_key = ?";
    $updateQuery = $pdo->prepare($query);
    $updateQuery->execute([$issueId]);
    $notificationMsg = 'Selected issue ignored successfully';
}
$columns = [];
$stmt = $pdo->query("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id', 'table_name', 'original_table_name')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}
$data = [];
if ($dataQualityType == 'correct') {
    $dataVerificationTableSql = $pdo->prepare("SELECT count(*) from $dataVerificationTableName");
    $dataVerificationTableSql->execute();
    $totalDataVerificationDataCount = $dataVerificationTableSql->fetchColumn();

    if($totalDataVerificationDataCount == 0) {
        $sqlQuery = $pdo->prepare("SELECT * FROM `$tableName`");
    } else {
        $sqlQuery = $pdo->prepare("SELECT * FROM `$tableName` where primary_key not in (SELECT distinct master_primary_key FROM `$dataVerificationTableName` where ignore_flag=0);");
    }
    $sqlQuery->execute();
    $data = $sqlQuery->fetchAll(PDO::FETCH_ASSOC);
} elseif ($dataQualityType == 'incorrect') {
    // Fetch unique records from $tableName and aggregate columns with issues
    $sqlQuery = $pdo->prepare("SELECT ec.*, GROUP_CONCAT(ecdv.column_name) AS problematic_columns FROM `$tableName` ec LEFT JOIN `$dataVerificationTableName` ecdv ON ec.primary_key = ecdv.master_primary_key WHERE ecdv.ignore_flag = 0 GROUP BY ec.primary_key");
    $sqlQuery->execute();
    $data = $sqlQuery->fetchAll(PDO::FETCH_ASSOC);
}
// Handle data download
if (isset($_POST['downloadType'])) {
    $stmt = $pdo->query("SELECT * FROM $tableName");

    if ($_POST['downloadType'] == 'csv') {
        // Set headers for CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $tableName . '.csv');

        $firstRow = true;
        $sNo = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            unset($row['table_id'], $row['primary_key']);
            $row = array_merge(['S.No' => $sNo], $row);
            if ($firstRow) {
                echo implode(",", array_keys($row)) . "\r\n";
                $firstRow = false;
            }
            echo implode(",", array_values($row)) . "\r\n";
            $sNo++;
        }
        exit;
    } elseif ($_POST['downloadType'] == 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Fetch and write the data
        $rowIndex = 1;
        $firstRow = true;
        $sNo = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            unset($row['table_id'], $row['primary_key']);
            $row = array_merge(['S.No' => $sNo], $row);
            if ($firstRow) {
                $sheet->fromArray(array_keys($row), NULL, 'A' . $rowIndex);
                $firstRow = false;
                $rowIndex++;
            }
            $sheet->fromArray(array_values($row), NULL, 'A' . $rowIndex);
            $rowIndex++;
            $sNo++;
        }
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $tableName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } elseif ($_POST['downloadType'] == 'sql') {
        ob_start();
        // Output header for SQL file
        echo "-- SQL dump of table your_table\n";
        echo "DROP TABLE IF EXISTS your_table;\n";
        echo "CREATE TABLE your_table (...);\n"; // Add your table creation SQL here

        // Output data as INSERT statements
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_keys($row);
            $values = array_map([$pdo, 'quote'], array_values($row));
            echo "INSERT INTO $tableName (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
        }

        // Capture the output buffer into a variable
        $sqlDump = ob_get_clean();
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment;filename="' . $tableName . '.sql"');
        echo $sqlDump;
        exit;
    }
}
include_once "header.php";
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
            <!-- Table Below Cards -->
            <div style="padding:10px;">
                <h2 style="margin-bottom:25px;"><?=ucfirst($dataQualityType)?> data from the <?=$tableName?> table</h2>
                <span style="color:green;font-weight:bold;" id="ignoreAllIssueNotificationId"><?=$notificationMsg?></span>
                <?php if(!empty($data)) { ?>
                    <div class="dropdown" style="display: flex; justify-content: flex-end; margin-top:40px;">
                        <a href="dashboard.php?table_name=<?=$tableName?>&project=<?=$projectName?>" class="btn btn-primary" style="margin-right:5px;">Back</a>
                        <?php if($dataQualityType == 'incorrect') { ?>
                            <button class="btn" style="background-color: #5C6ABC;color:white;margin-right:4px;" onclick="ignoreAllIssues()">Ignore all</button>
                        <?php } ?>
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #5C6ABC;">
                           Download as
                        </button>

                        <form name="downloadForm" action="view_dataquality.php?table=<?=$tableName?>&<?=$dataQualityType?>" method="post">
                            <input type="hidden" name="downloadType" id="downloadType" value="">
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                <li>
                                    <a class="dropdown-item" data-name="csv">CSV</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" data-name="excel">Excel</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" data-name="sql">Sql</a>
                                </li>
                            </ul>
                        </form>
                    </div>
                <?php } ?>
                <?php if(!empty($data)) { ?>
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($dataQualityType == 'correct') { ?>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php foreach ($columns as $col): ?>
                                            <td><?= htmlspecialchars($row[$col] ?? '') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php } else { ?>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php foreach ($columns as $col): ?>
                                        <?php
                                        $problematicColumns = $row['problematic_columns'] ?? '';
                                        $highlight = (strpos($problematicColumns, $col) !== false) ? ' style="color: red;"' : '';
                                        ?>
                                        <td<?= $highlight ?>><?= htmlspecialchars($row[$col] ?? '') ?></td>
                                        <?php endforeach; ?>
                                        <td style="width:13%;">
                                            <button class="btn btn-primary edit-btn">Edit</button>
                                            <button class="btn btn-success save-btn" style="display: none;">Save</button>
                                            <form name="ignoreIssueForm" method="post" style="display:inline-block;">
                                                <input type="hidden" name="issueId" id="issueId">
                                                <input type="hidden" name="ignoreAllIssueFlag" id="ignoreAllIssueFlag">
                                                <button class="btn btn-primary" type="button" onclick="ignoreIssue('<?= htmlspecialchars($row['primary_key']) ?>');">Ignore</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else {?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>data</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2" style="margin-top:30px;text-align:center; color:red; font-weight:bold;">No incorrect data found <a href="dashboard.php?table_name=<?=$tableName?>&project=<?=$projectName?>">click here </a> to go back</td>
                            </tr>
                        </tbody>
                    </table>
                <?php } ?>
                </div>
             </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.edit-btn').click(function() {
        var row = $(this).closest('tr');
        row.find('.editable').show();
        row.find('.non-editable').hide();
        $(this).hide();
        row.find('.save-btn').show();
    });

    $('.save-btn').click(function() {
        var row = $(this).closest('tr');
        var idToUpdate = row.find('input#rowId').val();
        var updatedData = {};
        row.find('input.editable#columnName').each(function() {
            var input = $(this);
            var key = input.val();
            var value = input.prev().val().trim();
            updatedData[key] = value;
        });

        // AJAX call to save data
        $.post('ajax_save_table_data.php', {data: updatedData, table: '<?=$tableName?>', id: idToUpdate}, function(response) {
            row.find('.editable').hide();
            row.find('.non-editable').each(function() {
                $(this).text($(this).next().val());
            }).show();
            row.find('.edit-btn').show();
            row.find('.save-btn').hide();
        });
    });
    // Toggle all checkboxes when 'Select All' is clicked
    var allChecked = $('.column-checkbox').length === $('.column-checkbox:checked').length;
    $('#selectAll').prop('checked', allChecked);
    $('#selectAll').change(function() {
        $('.column-checkbox').prop('checked', this.checked);

        var selectedColumns = [];
        $('.column-checkbox:checked').each(function() {
            selectedColumns.push($(this).val());
        });

        var queryString = selectedColumns.map(col => `column[]=${encodeURIComponent(col)}`).join('&');
        queryString = queryString + '&table=<?=$_REQUEST['table']?>';

        window.location.search = queryString;
    });
    $('.column-checkbox').on('change', function(e) {
        e.preventDefault();
        var selectedColumns = [];
        $('.column-checkbox:checked').each(function() {
            selectedColumns.push($(this).val());
        });
        var queryString = selectedColumns.map(col => `column[]=${encodeURIComponent(col)}`).join('&');
        queryString = queryString + '&table=<?=$_REQUEST['table']?>';
        window.location.search = queryString;
    });
    $('.dropdown-menu li a').on('click', function(e) {
        document.getElementById('downloadType').value = $(this).attr('data-name');
        window.downloadForm.submit();
    });
});
function ignoreIssue(issueId) {
    let result = confirm("Are you sure to ignore this issue?");

    if (result) {
        document.getElementById('issueId').value= issueId;
        document.forms["ignoreIssueForm"].submit();
    } else {
        event.preventDefault();
        return false;
    }
}
function ignoreAllIssues() {
    let result = confirm("Are you sure to ignore all the issues?");

    if (result) {
        document.getElementById('ignoreAllIssueFlag').value= true;
        document.forms["ignoreIssueForm"].submit();
    } else {
        event.preventDefault();
        return false;
    }
}
setTimeout(function() {
    var p = document.getElementById('ignoreAllIssueNotificationId');
    p.style.opacity = '0';

    setTimeout(function() {
        p.style.display = 'none';
    }, 1000);
}, 3000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
