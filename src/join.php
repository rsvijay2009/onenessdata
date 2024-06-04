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
            <form action="merge.php" method="post">
                    <input type="hidden" id="selected_tables" name="selected_tables" value="">
                    <!-- <input type="hidden" id="errMsg" name="errMsg" value=<?= $errorMsg ?>>
                    <input type="hidden" id="successMsg" name="successMsg" value=<?= $successMsg ?>> -->

                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="card join-card" style="cursor:pointer;">
                                <div class="plus-icon"><img src="images/left-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card join-card">
                                <div class="plus-icon"><img src="images/right-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card join-card">
                                <div class="plus-icon"><img src="images/full-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card join-card">
                                <div class="plus-icon"><img src="images/inner-join.png" class="join-img-width"></div>
                                <div class="card-body"></div>
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

                        <div class="dropdown" style="display: flex; justify-content: flex-start; display:block;" id="joinRelation">
                            <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="margin-top:20px; margin-left:20px; width:100%; color:#000; border: 1px solid #c9c5c5;">
                            <span style="margin-right: 217px;"> Select Relationship</span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1" style="width:100%;">
                            <h6 class="dropdown-header">Dropdown header</h6>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="" id="">
                                    <label class="form-check-label" for="checkbox">Test 1</label>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="" id="">
                                    <label class="form-check-label" for="checkbox">Test 1</label>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="" id="">
                                    <label class="form-check-label" for="checkbox">Test 1</label>
                                    </a>
                                </li>

                                <h6 class="dropdown-header">Dropdown header</h6>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="" id="">
                                    <label class="form-check-label" for="checkbox">Test 1</label>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="" id="">
                                    <label class="form-check-label" for="checkbox">Test 1</label>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item">
                                    <input class="form-check-input column-checkbox" type="checkbox" name="column[]" value="" id="">
                                    <label class="form-check-label" for="checkbox">Test 1</label>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p>
                        <input type="submit" value="Join" id="joinBtn" name="submit" style="margin:20px; width:120px;">
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
    $('#joinTable1').change(function(e){
        const tableName = e.target.value;
        $.ajax({
            type: "POST",
            url: "load_join_tables.php", // Replace with your PHP page URL
            data: {
                tableName: tableName
            },
            success: function(response) {
                $("#joinTableColumns1").html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Something went wrong");
            }
        });
    });

    $('#joinTable2').change(function(e){
        const tableName = e.target.value;
        $.ajax({
            type: "POST",
            url: "load_join_tables.php", // Replace with your PHP page URL
            data: {
                tableName: tableName
            },
            success: function(response) {
                $("#joinTableColumns2").html(response);
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