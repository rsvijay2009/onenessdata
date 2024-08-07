<?php
include_once "database.php";
include_once "sidebar.php";

$tableName = $_REQUEST['table'] ?? null;
$columnName = $_REQUEST['column'] ?? null;
$projectName = $_REQUEST['project'] ?? null;
$selectedColumns = [];
$data = [];
$columnToHighlight = (is_array($columnName) ? $columnName[0] : $columnName);
$dataVerificationTableName = $tableName.'_data_verification';

if(isset($_POST['issueId']) && $_POST['issueId'] != '') {
    $issueId = $_POST['issueId'];
    $query = "UPDATE $dataVerificationTableName SET ignore_flag = 1 WHERE id = $issueId";
    $updateQuery = $pdo->prepare($query);
    $updateQuery->execute();
    $notificationMsg = 'Selected issue ignored successfully';
}

$notificationMsg = '';
if(isset($_POST['ignoreAllIssueFlag']) && $_POST['ignoreAllIssueFlag'] != '') {
    $dvtQuery = $pdo->prepare("SELECT $columnName, $dataVerificationTableName.id as dvId  FROM `$tableName` INNER JOIN $dataVerificationTableName ON `$tableName`.primary_key =  $dataVerificationTableName.`master_primary_key` WHERE $dataVerificationTableName.table_name = '$tableName' AND column_name = '$columnName' AND ignore_flag = 0");
    $dvtQuery->execute();
    $dvtQueryResults = $dvtQuery->fetchAll(PDO::FETCH_ASSOC);
    $idsToIgnore = [];
    foreach ($dvtQueryResults as $dvtQueryResult) {
        $idsToIgnore[] = $dvtQueryResult['dvId'];
    }
    if(!empty($idsToIgnore)) {
        $idsToIgnore = implode(",", $idsToIgnore);
        $updateQuery = $pdo->prepare("UPDATE $dataVerificationTableName SET ignore_flag = 1 WHERE id in ($idsToIgnore)");
        $updateQuery->execute();
        $notificationMsg = 'All the issues are ignored successfully';
    }
}
try {
    // PDO connection setup
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);

    if ($stmt->fetch()) {
        $columnQuery = $pdo->prepare("SHOW COLUMNS FROM `$tableName` WHERE Field NOT IN('table_id')");
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
            $selectedColumns[] = 'primary_key';
            // Fetch data for selected columns
            if(!empty($selectedColumns)) {
                $queryColumns = implode(', ', array_map(function($col) use($tableName) { return "`$tableName`.`$col`"; }, $selectedColumns));
            }
            $queryColumns = $queryColumns.', '.$dataVerificationTableName.'.id as dvId';
            $columnName = is_array($selectedColumns) ? $selectedColumns[0] : $selectedColumns;
            $query = "SELECT $queryColumns  FROM `$tableName` INNER JOIN $dataVerificationTableName
            ON `$tableName`.primary_key =  $dataVerificationTableName.`master_primary_key` WHERE $dataVerificationTableName.table_name = '$tableName' AND column_name = '$columnName' AND ignore_flag = 0";

            $dataQuery = $pdo->prepare($query);
            $dataQuery->execute();
            $data = $dataQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $data = [];
    }
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
            <h2 style="margin-bottom:25px;">Data from the <?=$tableName?> table</h2>
            <span style="color:green;font-weight:bold;" id="ignoreAllIssueNotificationId"><?=$notificationMsg?></span>
            <?php if(!empty($selectedColumns) && !empty($data)) { ?>
                <div class="dropdown" style="display: flex; justify-content: flex-end;">
                <button class="btn" style="background-color: #5C6ABC;color:white;margin-right:15px;" onclick="ignoreAllIssues()">Ignore all</button>
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #5C6ABC;">
                        Select Columns
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <li>
                            <a class="dropdown-item">
                                <input class="form-check-input" type="checkbox" name="column[]" value="all" id="selectAll">
                                <label class="form-check-label" for="selectAll"> Select all</label>
                            </a>
                        </li>
                        <?php foreach ($columns as $col): ?>
                            <?php if($col != 'primary_key' && $col != 'table_name') {?>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="<?= htmlspecialchars($col) ?>" id="checkbox-<?= htmlspecialchars($col) ?>" <?= in_array($col, $selectedColumns) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="checkbox-<?= htmlspecialchars($col) ?>"> <?= htmlspecialchars($col) ?></label>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="">
                    <table class="table">
                        <thead>
                            <tr>
                                <?php foreach ($selectedColumns as $col): ?>
                                    <?php if($col != 'primary_key' && $col != 'table_name') {?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                    <?php } ?>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($selectedColumns as $col): ?>
                                        <?php if($col != 'primary_key' && $col != 'table_name') {?>
                                            <td style="max-width:100%">
                                                <?php if($col == $columnToHighlight) {?>
                                                    <span class="non-editable" style="color:red;"><?= htmlspecialchars($row[$col]) ?></span>
                                                <?php } else {?>
                                                    <span class="non-editable"><?= htmlspecialchars($row[$col]) ?></span>
                                                <?php } ?>
                                                <input type="text" class="form-control editable" value="<?= htmlspecialchars($row[$col]) ?>" style="display: none;">
                                                <input type="hidden" id="columnName" class="form-control editable" value="<?= htmlspecialchars($col) ?>" style="display: none;">
                                                <input type="hidden" id="rowId" class="form-control editable" value="<?= htmlspecialchars($row['primary_key']) ?>" style="display: none;">
                                            </td>
                                        <?php } ?>
                                    <?php endforeach; ?>
                                    <td style="max-width:5%">
                                        <button class="btn btn-primary edit-btn">Edit</button>
                                        <button class="btn btn-success save-btn" style="display: none;">Save</button>
                                        <form name="ignoreIssueForm" method="post" style="display:inline-block;">
                                            <input type="hidden" name="issueId" id="issueId" value="">
                                            <input type="hidden" name="ignoreAllIssueFlag" id="ignoreAllIssueFlag">
                                            <button class="btn btn-primary" onclick="ignoreIssue('<?= htmlspecialchars($row['dvId']) ?>');">Ignore</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="dashboard.php?table_name=<?=$tableName?>&project=<?=$projectName?>" class="btn btn-primary" style="margin-right:5px;">Back</a>
                    <?php } else {?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>column name</th>
                                    <th>actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="2" style="margin-top:30px;text-align:center; color:red; font-weight:bold;font-size:20px;">No incorrect data found for the <?=$columnToHighlight?>
                    column  <a href="dashboard.php?table_name=<?=$tableName?>&project=<?=$projectName?>">click here </a> to go back</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
             </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
         //Rearrange the array to put the selected column in first position
        const selectedColumnName = '<?=$columnToHighlight?>';
        const selectedColumnIndex = selectedColumns.indexOf(selectedColumnName);
        selectedColumns.splice(selectedColumnIndex, 1);
        selectedColumns.unshift(selectedColumnName);
        let queryString = selectedColumns.map(col => `column[]=${encodeURIComponent(col)}`).join('&');
        queryString = queryString + '&table=<?=$_REQUEST['table']?>&project=<?=$_REQUEST['project']?>';

        window.location.search = queryString;
    });
    $('.column-checkbox').on('change', function(e) {
        e.preventDefault();
        const selectedColumns = [];
        $('.column-checkbox:checked').each(function() {
            selectedColumns.push($(this).val());
        });
        //Rearrange the array to put the selected column in first position
        const selectedColumnName = '<?=$columnToHighlight?>';
        const selectedColumnIndex = selectedColumns.indexOf(selectedColumnName);
        selectedColumns.splice(selectedColumnIndex, 1);
        selectedColumns.unshift(selectedColumnName);
        let queryString = selectedColumns.map(col => `column[]=${encodeURIComponent(col)}`).join('&');
        queryString = queryString + '&table=<?=$_REQUEST['table']?>&project=<?=$_REQUEST['project']?>';
        window.location.search = queryString;
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
</body>
</html>
