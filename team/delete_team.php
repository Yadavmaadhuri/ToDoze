<?php
session_start();
include '../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];

// Ensure team ID is provided in the URL
if (!isset($_GET['teamid'])) {
    $_SESSION['error_message'] = "Project ID is missing.";
    header("Location: team.php");
    exit();
}

$teamid = $_GET['teamid'];

// Check if the user is part of the team and has 'Admin' or 'Super Admin' role
$sql = "SELECT pm.userid 
        FROM team_members pm 
        WHERE pm.teamid = ? AND pm.userid = ? AND (pm.role = 'Admin' OR pm.role = 'Super Admin')";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    $_SESSION['error_message'] = "Database error: " . mysqli_error($conn);
    header("Location: team.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "ii", $teamid, $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "You do not have permission to delete this team.";
    header("Location: team.php");
    exit();
}

// Soft delete: Mark team as deleted and store deletion timestamp
$sql = "UPDATE teams 
        SET is_teamdeleted = 1, teamdeleted_at = NOW() 
        WHERE teamid = ? AND is_teamdeleted = 0";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    $_SESSION['error_message'] = "Database error: " . mysqli_error($conn);
    header("Location: team.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $teamid);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Project deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting team: " . mysqli_error($conn);
}

mysqli_close($conn);
header("Location: team.php");
exit();
?>
