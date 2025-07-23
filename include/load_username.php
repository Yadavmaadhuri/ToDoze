<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../config/database.php';

if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

// If username is not in session, fetch from database
if (!isset($_SESSION['username'])) {
    $userid = $_SESSION['userid'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->bind_result($fetched_username);
    if ($stmt->fetch()) {
        $_SESSION['username'] = $fetched_username;
    } else {
        $_SESSION['username'] = 'User';
    }
    $stmt->close();
}

// Now you can use $username
$username = $_SESSION['username'];
?>
