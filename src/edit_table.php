<?php
include_once "database.php";

try {
    $tableName = $_REQUEST['table_name'] ?? '';
    $tableId = $_REQUEST['table_name'] ?? '';
    // PDO connection setup
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch column names dynamically
    $stmt = $pdo->prepare("SELECT * FROM `$tableName` LIMIT 1");
    $stmt->execute();

    $results = $stmt->fetch(PDO::FETCH_ASSOC);

    $tableId = $results[0]['table_id'] ?? 0;

   
    $columns = array_keys($results);

    // Fetch all data from the table
    $dataQuery = $pdo->prepare("SELECT * FROM `$tableName`");
    $dataQuery->execute();
    $data = $dataQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dynamic Table Display and Edit</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Editable Table for <?= htmlspecialchars($tableName) ?></h2>
    <table class="table">
        <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th><?= htmlspecialchars($column) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <td>
                            <span class="non-editable"><?= htmlspecialchars($row[$column]) ?></span>
                            <input type="text" class="form-control editable" value="<?= htmlspecialchars($row[$column]) ?>" style="display: none;">
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <button class="btn btn-primary edit-btn">Edit</button>
                        <button class="btn btn-success save-btn" style="display: none;">Save</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
        var updatedData = {};
        row.find('input.editable').each(function() {
            var input = $(this);
            var key = input.prev().text().trim(); // Assuming column name is in the span
            updatedData[key] = input.val();
        });

        // AJAX call to save data
        $.post('ajax_save_table_data.php', {data: updatedData, table: '<?=$tableName?>', tableId: '<?=$tableId?>'}, function(response) {
            row.find('.editable').hide();
            row.find('.non-editable').each(function() {
                $(this).text($(this).next().val());
            }).show();
            row.find('.edit-btn').show();
            row.find('.save-btn').hide();
        });
    });
});
</script>
</body>
</html>
