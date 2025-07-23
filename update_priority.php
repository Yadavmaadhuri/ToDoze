<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taskid'], $_POST['taskpriority'])) {
    $taskid = $_POST['taskid'];
    $priority = $_POST['taskpriority'];

    // Update priority
    $sql = "UPDATE tasks SET taskpriority = ? WHERE taskid = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $priority, $taskid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Check if the task belongs to a team
    $teamQuery = "SELECT teamid FROM tasks WHERE taskid = ?";
    $teamStmt = mysqli_prepare($conn, $teamQuery);
    mysqli_stmt_bind_param($teamStmt, "i", $taskid);
    mysqli_stmt_execute($teamStmt);
    mysqli_stmt_bind_result($teamStmt, $teamid);
    mysqli_stmt_fetch($teamStmt);
    mysqli_stmt_close($teamStmt);

    if (!empty($teamid)) {
        header("Location: team/team_view.php?teamid=$teamid");
        exit();
    }
}

header("Location: task/task.php");
exit();
?>
