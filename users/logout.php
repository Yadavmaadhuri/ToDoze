<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: ../index.php"); // or login page
    exit();
}
?>
