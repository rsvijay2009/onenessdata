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
                    <p>
                        <input type="submit" value="Join" id="joinBtn" name="submit" style="margin:20px; width:120px;">
                        <span id="successMsg" class="successMsg"><?= $successMsg ?></span>
                        <span id="errorMsg" class="errorMsg"><?= $errorMsg ?></span>
                    </p>
            </form>
        </div>
    </div>
</div>
<script src="scripts/join.js"></script>
</body>
</html>
