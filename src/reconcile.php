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
            <input type="hidden" id="selectedTable" name="selectedTable" value="">
            <input type="hidden" id="selectedColumns" name="selectedColumns" value="">
            <input type="hidden" id="selectedColumnsToSum" name="selectedColumnsToSum" value="">
            <input type="hidden" id="selectedColumnsToGroupBy" name="selectedColumnsToGroupBy" value="">


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
            <div id="uniqueKeyGenerationTitleId" style="display:none; margin-left:20px;font-weight:bold;margin-top:15px;">Select columns for unique key generation <input class="form-check-input column-checkbox" type="checkbox" name="mandateUniqueKeyGen" id="mandateUniqueKeyGen" style="margin-right: 10px;">
            </div>
            <div style="display:flex; display:none;" id="uniquKeyGenOptionsDiv">
                <div id="uniquKeyGenColumns" name="uniquKeyGenColumns" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%">
                </div>
            </div>
            <div id="selectColumnsToSumTitleId" style="display:none; margin-left:20px;font-weight:bold;margin-top:15px;">Select columns to sum
            </div>
            <div style="display:flex; display:none;" id="reconcileTableColumnsToSumDiv">
                <div id="reconcileTableColumnsToSum" name="reconcileTableColumnsToSum" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%">
                </div>
            </div>
            <div id="groupByTitleId" style="display:none; margin-left:20px;font-weight:bold;margin-top:15px;">Select columns to group
            </div>
            <div style="display:flex; display:none;" id="reconcileGroupByColumnsDiv">
                <div id="reconcileGroupByColumns" name="reconcileGroupByColumns" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%">
                </div>
            </div>
                <p>
                <a href="#"  id="prepareBtnId" class="btn btn-success" style="width:10%; height:45px; padding:10px;margin-top:20px;margin-left:20px;display:none;">Prepare</a>
                <!--<a href="home.php" class="btn btn-primary" style="width:10%; height:45px; padding:10px;margin-bottom:4px;margin-left:-15px;">Back</a>
                <span id="successMsg" class="successMsg"><?= $successMsg ?></span>
                <span id="errorMsg" class="errorMsg"><?= $errorMsg ?></span>
            </p> -->
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="scripts/reconcile.js"></script>
<script>
$(document).ready(function() {
    $('#tablesList').change(function(e) {
        const tableName = e.target.value;
        $('#selectedTable').val(tableName);
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
                $("#uniquKeyGenColumns").html(response);
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
    $('#reconcileGroupByColumnsDiv').on('change', '.reconcileGroupByColumns', function() {
        console.log("fjshjfhsjghs");
        //Dynamically generate the link for prepare
        const tableName = document.getElementById('selectedTable').value;
        let selectedColumns = document.getElementById('selectedColumns').value;
        selectedColumns = selectedColumns.replace(/,\s*/g, ',');
        const selectedColumnsToSum = document.getElementById('selectedColumnsToSum').value;
        const link = document.getElementById('prepareBtnId');
        const checkboxes4 = document.querySelectorAll('.reconcileGroupByColumns');
        const selectedTableColumnsToGroupBy = [];
        checkboxes4.forEach(checkbox => {
            if (checkbox.checked) {
                console.log(checkbox.value);
                selectedTableColumnsToGroupBy.push(checkbox.value);
            }
            const selectedTableColumnsToGroupByStr = selectedTableColumnsToGroupBy.join(", ");
            const newUrl = 'view_reconcile_data.php?table='+tableName+'&selectedColumns='+selectedColumns+'&selectedColumnsToSum='+selectedColumnsToSum+'&selectedColumnsToGroupBy='+selectedTableColumnsToGroupByStr
                console.log(newUrl);
                link.setAttribute('href', newUrl);
        });
    });
    $('#reconcileTableColumnsToSum').on('change', '.columnsToSumChkBox', function() {
        const checkboxes1 = document.querySelectorAll('.tableColsChkBox');
        const checkboxes2 = document.querySelectorAll('.uniqueKeyGenChkBox');
        const checkboxes3 = document.querySelectorAll('.columnsToSumChkBox');
        // Collect selected checkboxes
        const selectedTableColumns = [];
        const selectedTableColumnsForUniquKeyGen = [];
        const selectedTableColumnsToSum = [];

        checkboxes1.forEach(checkbox => {
            if (checkbox.checked) {
                selectedTableColumns.push(checkbox.value);
            }
        });

        checkboxes3.forEach(checkbox => {
            if (checkbox.checked) {
                selectedTableColumnsToSum.push(checkbox.value);
                // Filter array1 to get elements not in array2
                const groupBycolumns = selectedTableColumns.filter(item => !selectedTableColumnsToSum.includes(item));
                reconcileGroupByColumns = '<button class="btn dropdown-toggle" type="button" id="reconcileGroupByColumns" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:2px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 310px;">Select columns</span></button><ul class="dropdown-menu" aria-labelledby="reconcileGroupByColumns" style="width: 95%; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start">';

                groupBycolumns.forEach(option => {
                    reconcileGroupByColumns+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox reconcileGroupByColumns" type="checkbox" name="reconcileGroupByColumns[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';
                });
                $('#groupByTitleId').show();
                $('#reconcileGroupByColumnsDiv').show();
                $('#reconcileGroupByColumns').html(reconcileGroupByColumns);
                $('#prepareBtnId').show();
                document.getElementById('selectedColumnsToSum').value = selectedTableColumnsToSum.join(", ");
            }
        });
    });

    function updateDropdown() {
        reconcileTableColumnsToSum = '<button class="btn dropdown-toggle" type="button" id="reconcileTableColumnsToSum" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:2px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 310px;">Select columns</span></button><ul class="dropdown-menu" aria-labelledby="reconcileTableColumnsToSum" style="width: 95%; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start">';

        uniquKeyGenColumns = '<button class="btn dropdown-toggle" type="button" id="uniquKeyGenColumns" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:2px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 310px;">Select columns</span></button><ul class="dropdown-menu" aria-labelledby="uniquKeyGenColumns" style="width: 95%; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start">';

        const checkboxes1 = document.querySelectorAll('.tableColsChkBox');
        const checkboxes2 = document.querySelectorAll('.uniqueKeyGenChkBox');
        const checkboxes3 = document.querySelectorAll('.columnsToSumChkBox');
        // Collect selected checkboxes
        const selectedTableColumns = [];
        const selectedTableColumnsForUniquKeyGen = [];
        const selectedTableColumnsToSum = [];
        checkboxes1.forEach(checkbox => {
            if (checkbox.checked) {
                selectedTableColumns.push(checkbox.value);
            }
            document.getElementById('selectedColumns').value = selectedTableColumns.join(", ");
        });

        checkboxes3.forEach(checkbox => {
            if (checkbox.checked) {
                selectedTableColumnsToSum.push(checkbox.value);
                console.log(selectedTableColumnsToSum);
                // Filter array1 to get elements not in array2
                const groupBycolumns = selectedTableColumns.filter(item => !selectedTableColumnsToSum.includes(item));
                reconcileGroupByColumns = '<button class="btn dropdown-toggle" type="button" id="reconcileGroupByColumns" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:2px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 310px;">Select columns</span></button><ul class="dropdown-menu" aria-labelledby="reconcileGroupByColumns" style="width: 95%; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start">';

                groupBycolumns.forEach(option => {
                    reconcileGroupByColumns+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox reconcileGroupByColumns" type="checkbox" name="reconcileGroupByColumns[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';
                });
                $('#groupByTitleId').show();
                $('#reconcileGroupByColumnsDiv').show();
                $('#reconcileGroupByColumns').html(reconcileGroupByColumns);
            }
        });
        // Populate dropdown with selected options
        selectedTableColumns.forEach(option => {
            reconcileTableColumnsToSum+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox columnsToSumChkBox" type="checkbox" name="reconcileTableColumnsToSum[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';

            uniquKeyGenColumns+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox uniqueKeyGenChkBox" type="checkbox" name="uniquKeyGenColumns[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';
        });
        $('#uniqueKeyGenerationTitleId').show();
        $('#selectColumnsToSumTitleId').show();
        $('#reconcileTableColumnsToSumDiv').show();
        $('#uniquKeyGenColumns').html(uniquKeyGenColumns);
        $('#reconcileTableColumnsToSum').html(reconcileTableColumnsToSum);
    }

    //Display the unique key generation dropdown conditionally
    const checkbox = document.getElementById('mandateUniqueKeyGen');
    const uniquKeyGenOptionsDiv = document.getElementById('uniquKeyGenOptionsDiv');

    checkbox.addEventListener('change', function() {
        if (checkbox.checked) {
            uniquKeyGenOptionsDiv.style.display = 'block';
        } else {
            uniquKeyGenOptionsDiv.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
