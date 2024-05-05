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
            if($col['name'] != 'table_id') {
                $columns[] = $col['name'];
            }
        }
    
        // Fetch data with limit and offset
        $dataQuery = $pdo->prepare("SELECT * FROM $tableName LIMIT :perPage OFFSET :offset");
        $dataQuery->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $dataQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
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
                <table class="table table-bordered">
                    <thead style="background:#D8E3F4">
                        <tr>
                            <?php foreach ($columns as $column): ?>
                            <th><?= htmlspecialchars($column) ?></th>
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
</body>
</html>
