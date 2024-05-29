<?php
session_start();

function check_authentication() {
    if (!isset($_SESSION['authenticated'])) {
        header('Location: index.php');
        exit;
    }
}