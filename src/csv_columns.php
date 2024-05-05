<?php
include_once "database.php";

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = "";
$tableName = $_REQUEST["table_name"];
$projectId = $_REQUEST["project_id"];

try {
    $stmt = $pdo->prepare("select id, name, description FROM datatypes");
    $stmt->execute();
    $dataTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

include_once "header.php";
include_once "sidebar.php"
?>
<link rel="stylesheet" href="styles/csv_columns.css">
</head>
<body>
<?php if (!empty($error)) {
    include_once "error_msg.php";
} else {?>
<form action="import.php" method="post" class="csv_columns_form">
<input type="hidden" name="table_name" value="<?= $tableName ?>">
<input type="hidden" name="project_id" value="<?= $projectId ?>">
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <div class="col-md-9">
            <div class="table-responsive" style="margin:20px;">
            <table class="table table-bordered">
            <thead>
                <tr style="background:#E9EDF0"> 
                    <th><input type="checkbox" id="selectAll" onclick="toggleCheckboxes(this)">Column Name</th>
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
                                        <td style='padding:5px;'><input type='checkbox' id='{$column}' name='columns[]' value='{$column}'>
                                        <label for='{$column}'>" .
                                            htmlspecialchars($column) ."
                                        </td>
                                        <td>
                                            <div class='form-group'>
                                                <select class='form-select' id='datatype' name='datatype[]'>
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
            </div>
        </div>
        <input type="hidden" name="file" value="<?php echo htmlspecialchars($file); ?>">
    </div>
</div>
</form>
<script src="scripts/csv_columns.js"></script>
</body>
<?php } ?>
</html>
