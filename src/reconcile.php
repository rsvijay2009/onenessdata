<?php

include_once "database.php";
include_once "sidebar.php";

$errorMsg = "";
$successMsg = "";
// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = $pdo->prepare("SELECT name FROM tables_list");
$sql->execute();
$tables = $sql->fetchAll(PDO::FETCH_COLUMN);

include_once "header.php";
?>
<link rel="stylesheet" href="styles/merge.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <div class="col-md-10">
            <form action="merge.php" method="post">
                    <input type="hidden" id="selected_tables" name="selected_tables" value="">
                    <!-- <input type="hidden" id="errMsg" name="errMsg" value=<?= $errorMsg ?>>
                    <input type="hidden" id="successMsg" name="successMsg" value=<?= $successMsg ?>> -->

                    <div class="row" style="margin-top:50px;">
                        <div class="col-xl-5">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)">
                                <div class="plus-icon">Prepare</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-xl-5" style="margin-left:150px;">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)">
                                <div class="plus-icon">Compare</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;">
                        <button class="btn dropdown-toggle" type="button" id="reconcilePrepare" data-bs-toggle="dropdown" aria-expanded="false" style="margin-top:20px; margin-left:20px; width:37%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right:325px;"> Select tables</span></button>
                        <ul class="dropdown-menu" aria-labelledby="reconcilePrepare" style="width:30%;">
                            <?php foreach($tables as $table) {?>
                                <li><a class="dropdown-item"><input class="form-check-input column-checkbox" type="checkbox" name="reconcilePrepare[]" value="<?=$table['name']?>" id="" style="margin-right:5px;"><label class="form-check-label" for="checkbox"><?=$table['name']?></label></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <div style="display:flex;">
                        <div id="reconcileTableColumns1" class="dropdown" style="display: flex; justify-content: flex-start;">
                        </div>
                        <div id="reconcileTableColumns2" class="dropdown" style="display: flex; justify-content: flex-start;margin-left:12px;">
                        </div>
                    </div>
                    <!-- Relationship drop down -->
                    <div style="display:flex;">
                        <div id="reconciletable1RelationShip" class="dropdown" style="display: flex; justify-content: flex-start;display:none">
                        </div>
                        <div id="reconciletable2RelationShip" class="dropdown" style="display: flex; justify-content: flex-start;margin-left:12px;display:none">
                        </div>
                    </div>
                    <!-- <p>
                        <input type="submit" value="Merge data" name="submit" style="margin:20px; width:120px;">
                        <a href="home.php" class="btn btn-primary" style="width:10%; height:45px; padding:10px;margin-bottom:4px;margin-left:-15px;">Back</a>
                        <span id="successMsg" class="successMsg"><?= $successMsg ?></span>
                        <span id="errorMsg" class="errorMsg"><?= $errorMsg ?></span>
                    </p> -->
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="scripts/reconcile.js"></script>
<script>
$(document).ready(function(){
    $('#joinTable1').change(function(e) {
        const tableName = e.target.value;
        $.ajax({
            type: "POST",
            url: "get_tables_for_reconcile.php", // Replace with your PHP page URL
            data: {
                tableName: tableName,
                selectBoxId: 'joinDropdown1',
                selectBoxName: 'joinTable1Columns'
            },
            success: function(response) {
                const result = response.split("||");
                $("#joinTableColumns1").html(result[0]);
                $("#table1RelationShip").html(result[1]);
                $("#table1RelationShip").show();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Something went wrong");
            }
        });
    });

    $('#joinTable2').change(function(e) {
        const tableName = e.target.value;
        $.ajax({
            type: "POST",
            url: "load_join_tables.php", // Replace with your PHP page URL
            data: {
                tableName: tableName,
                selectBoxId: 'joinDropdown2',
                selectBoxName: 'joinTable2Columns'
            },
            success: function(response) {
                const result = response.split("||");
                $("#joinTableColumns2").html(result[0]);
                $("#table2RelationShip").html(result[1]);
                $("#table2RelationShip").show();
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
