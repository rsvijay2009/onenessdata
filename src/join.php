<?php
include_once "database.php";
include_once "sidebar.php";
include_once "header.php";
$errorMsg = "";
$successMsg = "";

// PDO connection setup
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = $pdo->prepare("SELECT name FROM tables_list");
$sql->execute();
$tables = $sql->fetchAll(PDO::FETCH_COLUMN);
?>
<style>
.join-img-width {
    width: 200px;
}
.dropdown-checkboxes {
    max-height: 200px;
    overflow-y: auto;
}
.form-select {
    width:98%;
}
</style>
<link rel="stylesheet" href="styles/merge.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include_once "sidebar_template.php"; ?>
        <div class="col-md-10">
            <form action="viewjoin.php">
                <input type="hidden" name="joinType" id="joinType" value="">
                <input type="hidden" id="table1JoinColumns" value="">
                <input type="hidden" id="table2JoinColumns" value="">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="card join-card" style="cursor:pointer;">
                                <div class="plus-icon"><img src="images/left-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                                <span style="font-weight:bold;margin-bottom:35px;" class="joinTypeClass">LEFT JOIN</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card join-card">
                                <div class="plus-icon"><img src="images/right-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                                <span style="font-weight:bold;margin-bottom:35px;" class="joinTypeClass">RIGHT JOIN</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card join-card">
                                <div class="plus-icon"><img src="images/full-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                                <span style="font-weight:bold;margin-bottom:35px;" class="joinTypeClass">FULL JOIN</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card join-card">
                                <div class="plus-icon"><img src="images/inner-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                                <span style="font-weight:bold;margin-bottom:35px;" class="joinTypeClass">INNER JOIN</span>
                            </div>
                        </div>
                    </div>
                    <div id="joinSection" style="display:none;">
                        <p style="margin-left:20px; font-weight:bold; font-size:18px;">Choose tables to join</p>
                        <div style="display:flex;">
                            <div  style="width:30%;margin-left:20px;">
                                <select class='form-select' id='joinTable1' name='joinTable1' style="margin-right:20px;">
                                    <option value=''>Choose table to join</option>
                                    <?php foreach($tables as $table) {?>
                                        <option value='<?=$table['name']?>'><?=$table['name']?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <div style="width:30%;margin-left:20px;">
                                <select class='form-select' id='joinTable2' name='joinTable2' style="margin-right:20px;">
                                    <option value=''>Choose table to join</option>
                                    <?php foreach($tables as $table) {?>
                                        <option value='<?=$table['name']?>'><?=$table['name']?></option>
                                    <?php }?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;">
                        <div id="joinTableColumns1" class="dropdown" style="display: flex; justify-content: flex-start;">
                        </div>
                        <div id="joinTableColumns2" class="dropdown" style="display: flex; justify-content: flex-start;margin-left:12px;">
                        </div>
                    </div>
                    <!-- Relationship drop down -->
                    <div style="display:flex;">
                        <div id="table1RelationShip" class="dropdown" style="display: flex; justify-content: flex-start;display:none; margin-top:20px; width:30%; margin-left:20px;">
                        </div>
                        <div id="table2RelationShip" class="dropdown" style="display: flex; justify-content: flex-start;display:none; margin-top:20px; width:30%; margin-left:20px;">
                        </div>
                    </div>
                    <p>
                        <a id="joinBtn" class="btn btn-success" style="width:10%; height:45px; padding:10px;margin-top:20px;margin-left:20px;display:none;">Join</a>
                        <span id="successMsg" class="successMsg"><?= $successMsg ?></span>
                        <span id="errorMsg" class="errorMsg"><?= $errorMsg ?></span>
                    </p>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="scripts/join.js"></script>

<script>
$(document).ready(function(){
    $('#joinTable1').change(function(e) {
        const tableName = e.target.value;
        $.ajax({
            type: "POST",
            url: "load_join_tables.php", // Replace with your PHP page URL
            data: {
                tableName: tableName,
                selectBoxId: 'joinDropdown1',
                selectBoxName: 'joinTable1Columns',
                selectBoxClass : 'joinDropdownClass1'
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
                selectBoxName: 'joinTable2Columns',
                selectBoxClass : 'joinDropdownClass2'
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
    $(document).on('change', '#joinTable2', function() {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
          $('.uniqueKeyGenChkBox').prop('checked', true);
        } else {
          $('.uniqueKeyGenChkBox').prop('checked', false);
        }
    });
    $(document).on('change', '.joinTable1ColumnsAll', function() {
        var isChecked = $(this).prop('checked');
        const table1SelectedColumns = [];
        if (isChecked) {
          $('.joinTable1Columns').prop('checked', true);
            const checkboxes1 = document.querySelectorAll('.joinTable1Columns');

            checkboxes1.forEach(checkbox => {
                if (checkbox.checked) {
                    table1SelectedColumns.push(checkbox.value);
                    document.getElementById('table1JoinColumns').value = table1SelectedColumns.join(",");
                }
            });
        } else {
          $('.joinTable1Columns').prop('checked', false);
        }
    });
    $(document).on('change', '.joinTable1Columns', function() {
        var allChecked = $('.joinTable1Columns').length === $('.joinTable1Columns:checked').length;
        $('.joinTable1ColumnsAll').prop('checked', allChecked);
        const table1SelectedColumns = [];
        const checkboxes1 = document.querySelectorAll('.joinTable1Columns');

        checkboxes1.forEach(checkbox => {
            if (checkbox.checked) {
                table1SelectedColumns.push(checkbox.value);
                document.getElementById('table1JoinColumns').value = table1SelectedColumns.join(",");
            }
        });
    });
    $(document).on('change', '.joinTable2ColumnsAll', function() {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
            $('.joinTable2Columns').prop('checked', true);
                const table2SelectedColumns = [];
                const checkboxes2 = document.querySelectorAll('.joinTable2Columns');

                checkboxes2.forEach(checkbox => {
                    if (checkbox.checked) {
                        table2SelectedColumns.push(checkbox.value);
                        document.getElementById('table2JoinColumns').value = table2SelectedColumns.join(",");
                    }
                });
        } else {
          $('.joinTable2Columns').prop('checked', false);
        }
    });


    $(document).on('change', '.joinTable2Columns', function() {
        var allChecked = $('.joinTable2Columns').length === $('.joinTable2Columns:checked').length;
        $('.joinTable2ColumnsAll').prop('checked', allChecked);
        const table2SelectedColumns = [];
        const checkboxes2 = document.querySelectorAll('.joinTable2Columns');
        checkboxes2.forEach(checkbox => {
            if (checkbox.checked) {
                table2SelectedColumns.push(checkbox.value);
                document.getElementById('table2JoinColumns').value = table2SelectedColumns.join(",");
            }
        });
    });
    $(document).on('change', '.joinTable2Columns_relationship', function() {
        $("#joinBtn").show();
    });
    $("#joinBtn").click(function(){
        event.preventDefault();
        let joinTable1 = $('#joinTable1').val();
        let joinTable2 = $('#joinTable2').val();
        let joinTable1Columns = $('#table1JoinColumns').val();
        let joinTable2Columns = $('#table2JoinColumns').val();
        let joinTable1Columns_relationship = $('#joinTable1Columns_relationship').val();
        let joinTable2Columns_relationship = $('#joinTable2Columns_relationship').val();
        let joinType = $('#joinType').val();

        if(joinTable1 == '' || joinTable2 == '') {
            alert('Choose tables to join');
            return;
        } else if(joinTable1Columns == '' || joinTable2Columns == '') {
            alert('Please select the columns to join');
            return;
        } else if(joinTable1Columns_relationship == '' || joinTable2Columns_relationship == '') {
            alert('Please select the relationship to join');
            return;
        } else {
            let url = "viewjoin.php?joinType="+joinType+"&table1="+joinTable1+"&table2="+joinTable2+"&table1Columns="+joinTable1Columns+"&table2Columns="+joinTable2Columns+"&table1Relationship="+joinTable1Columns_relationship+"&table2Relationship="+joinTable2Columns_relationship
            window.location.href = url;
        }
    });
});
</script>
</body>
</html>