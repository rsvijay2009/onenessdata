<?php
include_once "database.php";

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = "";
$tableName = $_REQUEST["table_name"];
$projectId = $_REQUEST["project_id"];
$projectName = $_REQUEST["projectName"];

if (!empty($tableName) && !empty($projectName)) {
    $tableWithProjectName = strtolower($projectName."_".$tableName);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tablename");
    $stmt->bindParam(':dbname', $dbname);
    $stmt->bindParam(':tablename', $tableWithProjectName);
    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
        header("Location: home.php?error=table");
        exit();
    }
}
$projectSql = $pdo->prepare("select name FROM projects where id = $projectId");
$projectSql->execute();
$project = $projectSql->fetch(PDO::FETCH_ASSOC);
$projectName = $project['name'];

try {
    $stmt = $pdo->prepare("select id, name, description FROM datatypes");
    $stmt->execute();
    $dataTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

include_once "sidebar.php";
include_once "header.php";
?>
<link rel="stylesheet" href="styles/csv_columns.css">
</head>
<body>
<?php if (!empty($error)) {
    include_once "error_msg.php";
} else {?>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
            <div class="col-md-10" style="margin-top:30px;">
                <form action="import.php" method="post" class="csv_columns_form">
                    <input type="hidden" name="table_name" value="<?= $tableName ?>">
                    <input type="hidden" name="project_id" value="<?= $projectId ?>">
                    <input type="hidden" name="project_name" value="<?= $projectName ?>">
                    <div class="table-responsive" style="margin:20px;">
                        <table class="table table-bordered">
                            <thead>
                                <tr style="background:#E9EDF0">
                                    <th><input type="checkbox" class="highlightCheck" id="selectAll" onclick="toggleCheckboxes(this)">Column Name</th>
                                    <th>Datatype</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (!isset($_GET["file"]) || empty($_GET["file"])) {
                                die("File is not specified.");
                            }
                            $file = $_GET["file"];
                            if (($handle = fopen($file, "r")) !== false) {
                                if (($data = fgetcsv($handle, 1000, ",")) !== false) {
                                    $datatypeHtml = "";
                                    foreach ($dataTypes as $dataType) {
                                        $id = $dataType["id"];
                                        $datatypeName = $dataType["name"];
                                        $dataTypeDescription = $dataType["description"];
                                        $datatypeHtml .="<option value='" .$id ."'>$datatypeName ( $dataTypeDescription )</option>";
                                    }
                                    foreach ($data as $index => $column) {
                                        echo "<tr>
                                                <td style='padding:5px;'><input class='highlightCheck' type='checkbox' id='{$column}' name='columns[]' value='{$column}'>
                                                <label for='{$column}'>" .
                                                    htmlspecialchars($column) ."
                                                </td>
                                                <td>
                                                    <div class='form-group'>
                                                        <select class='form-select' id='datatype_".htmlspecialchars($column)."' name='datatype[]'>
                                                            <option selected value=''>Choose datatype</option>
                                                            $datatypeHtml
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>";
                                    }
                                }
                                fclose($handle);
                            }
                            ?>
                            </tbody>
                        </table>
                        <input type="submit" value="Import">
                        <a href="home.php" class="btn btn-primary link-button">Back</a>
                    </div>
                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($file); ?>">
                </form>
            </div>
    </div>
</div>

<script src="scripts/csv_columns.js"></script>
</body>
<?php } ?>
</html>
