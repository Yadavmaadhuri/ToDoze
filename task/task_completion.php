<?php
session_start();
include '../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

// Validate request method and POST data
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['taskid'])) {
    echo "Invalid request.";
    exit();
}

$userid = $_SESSION['userid'];
$taskid = intval($_POST['taskid']); // Sanitize input

// Fetch task info including assigned user
$sql = "SELECT t.userid, t.teamid, t.assigned_to, t.taskstatus 
        FROM tasks t 
        WHERE t.taskid = ? AND t.is_deleted = 0";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo "Database error: " . mysqli_error($conn);
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $taskid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($result);

if (!$task) {
    echo "Task not found.";
    exit();
}

$taskOwnerId = $task['userid'];
$teamid = $task['teamid'];
$assignedTo = $task['assigned_to'];
$allowed = false;

// Check if task is already completed
if ($task['taskstatus'] === 'completed') {
    echo "Task is already completed.";
    exit();
}

// Check permissions
if ($taskOwnerId == $userid) {
    // Task owner can always complete their own tasks
    $allowed = true;
} elseif (!is_null($teamid)) {
    // For team tasks, only the assigned member can complete it
    if ($assignedTo == $userid) {
        $allowed = true;
    } else {
        // Check if user is admin of the team
        $checkAdminSql = "SELECT role FROM team_members WHERE teamid = ? AND userid = ? AND status = 'Accepted'";
        $checkAdminStmt = mysqli_prepare($conn, $checkAdminSql);
        if ($checkAdminStmt) {
            mysqli_stmt_bind_param($checkAdminStmt, "ii", $teamid, $userid);
            mysqli_stmt_execute($checkAdminStmt);
            $adminResult = mysqli_stmt_get_result($checkAdminStmt);
            if ($adminRow = mysqli_fetch_assoc($adminResult)) {
                // Team admins can also complete tasks
                $allowed = ($adminRow['role'] === 'Admin');
            }
            mysqli_stmt_close($checkAdminStmt);
        }
    }
}

if (!$allowed) {
    echo "You are not allowed to complete this task. Only the assigned member or team admin can complete it.";
    exit();
}

// Mark the task as completed
$updateSql = "UPDATE tasks SET taskstatus = 'completed', completed_at = NOW() WHERE taskid = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, "i", $taskid);
    if (mysqli_stmt_execute($updateStmt)) {
        // Redirect based on task type
        if (!is_null($teamid)) {
            header("Location: team_view.php?teamid=" . $teamid);
        } else {
            header("Location: task.php");
        }
        exit();
    } else {
        echo "Error updating task: " . mysqli_stmt_error($updateStmt);
    }
} else {
    echo "Database error: " . mysqli_error($conn);
}
?>