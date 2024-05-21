<?php
include_once "database.php";
include_once "sidebar.php";


$tableName = $_REQUEST['table'] ?? null;
$columnName = $_REQUEST['column'] ?? null;
$selectedColumns = [];
$data = [];

// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columnQuery = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('primary_key', 'table_id')");
$columnQuery->execute();
$columns = $columnQuery->fetchAll(PDO::FETCH_COLUMN);

if($tableName && $columnName) {
    // Get selected columns from the query parameters or default to a specific column
    $initialSelectedColumn = '';
    if(!is_array($_REQUEST['column']) && $_REQUEST['column'] != '') {
        $selectedColumns[] = $_REQUEST['column'];
        $initialSelectedColumn = $_REQUEST['column'];
    } else {
        $selectedColumns = $_REQUEST['column'];
    }

    $selectedColumns = array_intersect($selectedColumns, $columns);  // Ensure only valid columns are processed

    // Fetch data for selected columns
    if(!empty($selectedColumns)) {
        $queryColumns = implode(', ', array_map(function($col) { return "`$col`"; }, $selectedColumns));
    }
    $dataQuery = $pdo->prepare("SELECT $queryColumns FROM `$tableName`");
    $dataQuery->execute();
    $data = $dataQuery->fetchAll(PDO::FETCH_ASSOC);
}
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
                <div>
                <div class="dropdown" style="display: flex; justify-content: flex-end;">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #5C6ABC;">
                        Select Columns
                    </button>
        
                    <form id="columnsForm" action="" method="get">
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                <li>
                                    <a class="dropdown-item">
                                        <input class="form-check-input" type="checkbox" name="column[]" value="all" id="selectAll">
                                        <label class="form-check-label" for="selectAll"> Select all</label>
                                    </a>
                                </li>
                            <?php foreach ($columns as $col): ?>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="<?= htmlspecialchars($col) ?>" id="checkbox-<?= htmlspecialchars($col) ?>" <?= in_array($col, $selectedColumns) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="checkbox-<?= htmlspecialchars($col) ?>"> <?= htmlspecialchars($col) ?></label>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </form>
                </div>
                <?php if(!empty($selectedColumns) && !empty($data)) { ?>
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <?php foreach ($selectedColumns as $col): ?>
                                    <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($selectedColumns as $col): ?>
                                        <td><?= htmlspecialchars($row[$col]) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php } else {?>
                    <div style="margin-top:-30px; text-align:center; color:red;">Please select atleast one column to show data</div>
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
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
