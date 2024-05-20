<?php
require_once "../vendor/autoload.php";
include_once "database.php";
include_once "sidebar.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


$tableName = $_REQUEST['table'] ?? null;
$dataQualityType = $_REQUEST['type'] ?? null;

// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columnQuery = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id')");
$columnQuery->execute();
$columns = $columnQuery->fetchAll(PDO::FETCH_COLUMN);

$sqlQuery = $pdo->prepare("SELECT * FROM `$tableName` LIMIT 10");
$sqlQuery->execute();
$data = $sqlQuery->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST) && !empty($_POST['downloadType'])) {
    // Query to fetch data
    $stmt = $pdo->query("SELECT * FROM $tableName");

    if($_POST['downloadType'] == 'csv') {
         // Set headers to download file rather than display
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename='.$tableName.'.csv');

        // Output header row (if necessary)
        $firstRow = true;
        $sNo = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            unset($row['table_id']);
            unset($row['primary_key']);
            $row = array_merge(array('S.No' => $sNo), $row);
            if ($firstRow) {
                echo implode(",", array_keys($row)) . "\r\n";
                $firstRow = false;
            }
            echo implode(",", array_values($row)) . "\r\n";
            $sNo++;
        }
        exit;
    } else if($_POST['downloadType'] == 'excel') {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Fetch and write the data
        $rowIndex = 1;
        $firstRow = true;
        $sNo = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            unset($row['table_id']);
            unset($row['primary_key']);
            $row = array_merge(array('S.No' => $sNo), $row);
            if ($firstRow) {
                // Write header
                $sheet->fromArray(array_keys($row), NULL, 'A' . $rowIndex);
                $firstRow = false;
                $rowIndex++;
            }
            // Write data
            $sheet->fromArray(array_values($row), NULL, 'A' . $rowIndex);
            $rowIndex++;
            $sNo++;
        }
        // Clear any output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
    
        // Set headers to download file rather than display
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$tableName.'.xlsx"');
        header('Cache-Control: max-age=0');
    
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } else if($_POST['downloadType'] == 'sql') {
        // Query to fetch data
        $stmt = $pdo->query("SELECT * FROM $tableName");

        // Start output buffering to capture the SQL dump
        ob_start();

        // Output header for SQL file
        echo "-- SQL dump of table your_table\n";
        echo "DROP TABLE IF EXISTS your_table;\n";
        echo "CREATE TABLE your_table (...);\n"; // Add your table creation SQL here

        // Output data as INSERT statements
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_keys($row);
            $values = array_map([$pdo, 'quote'], array_values($row));
            echo "INSERT INTO your_table (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
        }

        // Capture the output buffer into a variable
        $sqlDump = ob_get_clean();

        // Set headers to download file rather than display
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment;filename="'.$tableName.'.sql"');
        header('Cache-Control: max-age=0');

        echo $sqlDump;
        exit;
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-9">
            <!-- Table Below Cards -->
            <div style="padding:10px;">
                <?php if(!empty($data)) { ?>
                    <div class="dropdown" style="display: flex; justify-content: flex-end; margin-top:40px;">
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
             </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
