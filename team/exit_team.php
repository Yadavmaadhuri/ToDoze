<?php
session_start();
include '../config/database.php';

if (isset($_SESSION['userid']) && isset($_GET['teamid'])) {
    $userid = $_SESSION['userid'];
    $teamid = intval($_GET['teamid']);

    // Check pending tasks
    $checkQuery = "SELECT COUNT(*) as task_count FROM tasks WHERE assigned_to = ? AND teamid = ? AND is_deleted = 0 AND taskstatus != 'Completed'";
    $checkStmt = mysqli_prepare($conn, $checkQuery);

    if (!$checkStmt) {
        $_SESSION['alert_message'] = "Error: Failed to prepare statement.";
        header("Location: team.php");
        exit();
    }

    mysqli_stmt_bind_param($checkStmt, "ii", $userid, $teamid);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);
    $row = mysqli_fetch_assoc($result);

    if ($row['task_count'] > 0) {
        $_SESSION['alert_message'] = "You cannot exit the team. You have pending tasks.";
        header("Location: team.php");
        exit();
    }

    // Update team_members
    $sql = "UPDATE team_members SET has_exited = 1, exited_at = NOW(), status = 'Removed' WHERE userid = ? AND teamid = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $_SESSION['alert_message'] = "Error: Failed to prepare update.";
        header("Location: team.php");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "ii", $userid, $teamid);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['alert_message'] = "success: You have exited the team.";
    } else {
        $_SESSION['alert_message'] = "Failed to exit team.";
    }

    header("Location: team.php");
    exit();
} else {
    $_SESSION['alert_message'] = "Invalid request. Missing team or user info.";
    header("Location: team.php");
    exit();
}
