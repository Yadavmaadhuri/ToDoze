<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_GET['userid'] ?? null;
$teamId = $_GET['teamid'] ?? null;

if (!$userid || !$teamId) {
    die("Invalid request!");
}

// Ensure the user attempting removal is authorized (e.g., team owner)
$loggedInUser = $_SESSION['userid'];
$checkRoleSql = "SELECT role FROM team_members WHERE userid = ? AND teamid = ?";
$stmt = mysqli_prepare($conn, $checkRoleSql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $loggedInUser, $teamId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userRole = mysqli_fetch_assoc($result)['role'] ?? null;
    mysqli_stmt_close($stmt);

    // Only allow removal if the user is an "admin" or "owner" of the team
    if ($userRole !== 'Admin' && $userRole !== 'Owner') {
        {
        die("You do not have permission to remove members.");
    }}
}

// Soft-remove member by updating their status and setting removed_at timestamp
$sql = "UPDATE team_members 
        SET status = 'Removed', removed_at = NOW() 
        WHERE userid = ? AND teamid = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $userid, $teamId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
header("Location: member.php?teamid=$teamId");
exit();
