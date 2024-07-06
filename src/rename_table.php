<?php
include_once "database.php";
include_once "sidebar.php";
include_once "utilities/common_utils.php";

$oldTableNameStr = $_POST['oldTableName'] ?? null;
$newTableName = $_POST['newTableName'] ?? null;
$message = '';
$errorMessageColor = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, name FROM projects");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $projects = [];
}
if($oldTableNameStr && $newTableName) {
    $tableTypeArr = explode('###', $oldTableNameStr);
    $oldTableName = trim($tableTypeArr[0]);
    $tableType = trim($tableTypeArr[1]);

    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$newTableName]);
    if ($stmt->rowCount() > 0) {
        $messageId =  0;
        $errorMessageColor = 'red';
        $message =  "Error: Table with name '$newTableName' already exists.";
    } else {
        $renameSql = "RENAME TABLE $oldTableName TO $newTableName";
        $pdo->exec($renameSql);
        
        if ($tableType == 'main_table') {
            //check and update the table name in table_list also
            $updateTable = "UPDATE tables_list SET name = '$newTableName' WHERE name = '$oldTableName'";
            $pdo->exec($updateTable);
            $newTableNametoLower = strtolower($newTableName);
            $oldTableNametoLower = strtolower($oldTableName);

            $pdo->exec("UPDATE $newTableName SET original_table_name = '$newTableNametoLower', table_name = '$newTableNametoLower' WHERE table_name = '$oldTableNametoLower'");

            $oldTableNameDatatype = $oldTableName.'_datatype';
            $oldTableNameDataVerification = $oldTableName.'_data_verification';

            $updateOldTableDatatype = "UPDATE $oldTableNameDatatype SET table_name = '$newTableName' WHERE table_name = '$oldTableName'";
            $pdo->exec($updateOldTableDatatype);

            $updateOldTableDataVerification = "UPDATE $oldTableNameDataVerification SET table_name = '$newTableName' WHERE table_name = '$oldTableName'";
            $pdo->exec($updateOldTableDataVerification);

            $oldTableNameList = [
                '_datatype' => $oldTableName.'_datatype',
                '_dashboard' =>  $oldTableName.'_dashboard',
                '_data_verification' => $oldTableName.'_data_verification'
            ];
            foreach ($oldTableNameList as $key => $table) {
                $checkTableQuery = "SHOW TABLES LIKE :table";
                $stmt = $pdo->prepare($checkTableQuery);
                $stmt->execute(['table' => $table]);
                $tableNameToUpdate = $newTableName.$key;

                if ($stmt->rowCount() > 0) {
                    $renameTableQuery = "ALTER TABLE `$table` RENAME TO `$tableNameToUpdate`";
                    $pdo->query($renameTableQuery);
                }
            }
        } else {
            $pdo->exec("UPDATE other_tables SET name = '$newTableName' WHERE name = '$oldTableName'");
        }

        //Update the original_table_name column in all the required tables
        updateOriginalTableNameColumnInRequiredTables($pdo, $oldTableName, $newTableName, $dbname);

        $messageId =  1;
        $errorMessageColor = '#258B27';
        $message =  "Table '$oldTableName' renamed to '$newTableName' successfully.";
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <!-- Content Area -->
        <?php if(empty($projects)) { ?>
        <div class="col-md-10">
        <?php } else {?>
            <div class="col-md-5">
        <?php } ?>
            <!-- Table Below Cards -->
            <div style="padding:10px;">
                <h2 style="margin-bottom:25px;">Rename table</h2>
                <p class="notificationMsg" id="notificationMsg" style="color:<?=$errorMessageColor?>;padding-bottom:10px;font-weight:bold;"><?=$message?></p>
                <?php if(!empty($projects)) { ?>
                    <form action="rename_table.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="projectName" class="form-label">Choose project</label>
                            <select class="form-select" id="projectName" name="projectName" required>
                                <option selected value="">Choose project</option>
                                <?php foreach ($projects as $project) {?>
                                    <option value=<?=$project['id']?>><?=$project['name']?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="oldTableName" class="form-label">Choose table</label>
                            <select class="form-select" id="oldTableName" name="oldTableName" required>
                                <option value=''>Choose table</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newTableName" class="form-label">Enter new table name</label>
                            <input type="text" class="form-control" id="newTableName" name="newTableName" placeholder="Enter new table name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                <?php } else {?>
                    <div class="container">
                        <div class="alert alert-warning text-center" role="alert">
                            <h4 class="alert-heading">No Data Found</h4>
                            <p>Sorry, there is no table available at the moment</p>
                        </div>
                    </div>
                <?php } ?>
                </div>
             </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>

$(document).ready(function() {
    if ($('#notificationMsg').html().trim() !== '') {
        setTimeout(function() {
            $('#notificationMsg').hide();
        }, 2000);
    }

    $("#projectName").change(function(e) {
        const projectId = e.target.value;
        console.log(projectId);
        $.ajax({
            type: "POST",
            url: "get_tables.php",
            data: {
                projectId: projectId.trim()
            },
            success: function(response) {
                console.log(response);
                let data = JSON.parse(response);
                var html = '<option value="">Choose table</option>';
                // Loop through the array and extract values in sets of three
                if (Array.isArray(data)) {
                    data.forEach(item => {
                        html+='<option value="'+item.name+'###'+item.table_type+'">'+item.original_table_name+'</option>';
                    });
                    $("#oldTableName").html(html);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Something went wrong");
            }
        });
    });
});
</script>
</body>
</html>
