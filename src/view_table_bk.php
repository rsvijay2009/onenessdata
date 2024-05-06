<?php
include_once "database.php";
include_once "sidebar.php";

$tableName = $_REQUEST['table_name'] ?? '';

if (empty($tableName)) {
    $isTableAvailable = false;
} else {
    try {
        $isTableAvailable = true;
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $perPage = 10; // Number of records per page
    
        // Get current page number
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max($page, 1); // Ensure $page is at least 1
    
        // Calculate offset
        $offset = ($page - 1) * $perPage;
    
        // Count total rows
        $countQuery = $pdo->query("SELECT COUNT(*) FROM $tableName");
        $totalRows = $countQuery->fetchColumn();
        $totalPages = ceil($totalRows / $perPage);
    
        // Fetch column names
        $stmt = $pdo->prepare("SELECT * FROM $tableName LIMIT 1");
        $stmt->execute();
        $columnCount = $stmt->columnCount();
        $columns = [];
        for ($i = 0; $i < $columnCount; $i++) {
            $col = $stmt->getColumnMeta($i);
            if($col['name'] !== 'primary_key' && trim($col['name']) != 'table_id') {
                $columns[] = $col['name'];
            }
        }

        // Fetch data with limit and offset
        $dataQuery = $pdo->prepare("SELECT * FROM $tableName LIMIT :perPage OFFSET :offset");
        $dataQuery->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $dataQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
        //$dataQuery = $pdo->prepare("SELECT * FROM $tableName");
        $dataQuery->execute();
        $data = $dataQuery->fetchAll(PDO::FETCH_ASSOC);
    
    } catch (PDOException $e) {
        die("Could not connect to the database $dbname :" . $e->getMessage());
    }
}
include_once "header.php";
?>
</head>
<body>
<?php if (!$isTableAvailable || count($columns) == 0) {
    include "not_found_msg.php";
} else {?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <div class="col-md-10">
            <div style="padding:10px;">
            <h2 style="margin-bottom:25px;">Data from the <?=$tableName?> table</h2>
                <div class="table-responsive">
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
                                        <input type="hidden" id="columnName" class="form-control editable" value="<?= htmlspecialchars($column) ?>" style="display: none;">
                                        <input type="hidden" id="tableId" class="form-control editable" value="<?= htmlspecialchars($row['primary_key']) ?>" style="display: none;">
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
                    <nav aria-label="Page navigation example">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?table_name=<?=$tableName?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
             </div>
        </div>
    </div>
</div>
<?php
} ?>
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
        var idToUpdate = row.find('input#tableId').val();
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
});
</script>
</body>
</html>
