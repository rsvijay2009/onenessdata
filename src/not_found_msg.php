<?php
include_once("header.php");
include_once("constants/common_constants.php");
?>
<style>
.centered-content {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    text-align: center;
}
</style>
<div class="centered-content">
    <div>
        <h4><?=$tableName?> table is recently deleted and there is no data to display</h4>
        <p>You will be redirected to the home page in <span id="countdown">5</span> seconds.</p>
        <p>If you are not redirected, <a href="<?=WEBSITE_ROOT_PATH?>">click here</a> to go to the home page.</p>
    </div>
</div>

<script>
    var seconds = 5; // Number of seconds before redirection
    var countdownElement = document.getElementById('countdown');
    var intervalId = setInterval(function() {
        seconds--;
        countdownElement.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(intervalId);
            window.location.href = '<?=WEBSITE_ROOT_PATH?>';
        }
    }, 1000);
</script>
</body>