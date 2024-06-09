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
                        <div class="col-xl-5" id="prepareDiv">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)" style="cursor:pointer;">
                                <div class="plus-icon">Prepare</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-xl-5" style="margin-left:150px;" id="compareDiv">
                            <div class="card" ondrop="drop(event)" ondragover="allowDrop(event)">
                                <div class="plus-icon">Compare</div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                    </div>
                    <div id="tableListDropDown" style="display:none;">
                        <div style="display:flex;">
                            <div  style="width:30%;">
                                <select class='form-select' id='tablesList' name='tablesList' style="margin-left:20px;width:60%">
                                    <option value=''>Choose table</option>
                                    <?php foreach($tables as $table) {?>
                                        <option value='<?=$table['name']?>'><?=$table['name']?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <div style="">
                                <div id="reconcileTableColumns1" name="reconcileTableColumns1" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%"></div>
                        </div>
                    </div>
                    <div id="uniqueKeyGenerationTitleId" style="display:none; margin-left:20px;font-weight:bold;margin-top:50px;">Select columns for unique key generation
                    </div>
                    <div style="display:flex; display:none;" id="uniquKeyGenOptionsDiv">
                        <div id="reconcileTableColumns2" name="reconcileTableColumns2" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%">
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
$(document).ready(function() {
    $('#tablesList').change(function(e) {
        const tableName = e.target.value;
        $.ajax({
            type: "POST",
            url: "get_tables_for_reconcile.php", // Replace with your PHP page URL
            data: {
                tableName: tableName,
                selectBoxId: 'reconcileTableColumns1',
                selectBoxName: 'reconcileTableColumns1'
            },
            success: function(response) {
                $("#reconcileTableColumns1").html(response);
                $("#reconcileTableColumns2").html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Something went wrong");
            }
        });
    });

    $('#prepareDiv').click(function(){
        $('#tableListDropDown').show();
    });

    $('#reconcileTableColumns1').on('change', '.tableColsChkBox', function() {
        updateDropdown();
    });

    function updateDropdown() {
        dropDownList = '<button class="btn dropdown-toggle" type="button" id="reconcileTableColumns1" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 310px;"> Select Columns</span></button><ul class="dropdown-menu" aria-labelledby="reconcileTableColumns1" style="width: 95%; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start">';

        const checkboxes = document.querySelectorAll('.tableColsChkBox');
        // Collect selected checkboxes
        const selectedOptions = [];
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedOptions.push(checkbox.value);
            }
        });

        // Populate dropdown with selected options
        selectedOptions.forEach(option => {
            dropDownList+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox" type="checkbox" name="uniqueKeyGenOptions[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';
        });
        $('#uniqueKeyGenerationTitleId').show();
        $('#uniquKeyGenOptionsDiv').show();
        $('#reconcileTableColumns2').html(dropDownList);
    }
});
</script>
</body>
</html>
