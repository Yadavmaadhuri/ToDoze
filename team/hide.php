<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userid = $_SESSION['userid'];
$teamid = $_POST['teamid'] ?? null;

if (!$teamid) {
    echo json_encode(['success' => false, 'message' => 'Team ID missing']);
    exit();
}

// Update team_members to mark as hidden for this user only
$sql = "UPDATE team_members 
        SET is_hidden = 1 
        WHERE userid = ? AND teamid = ? AND (has_exited = 1 OR status = 'Removed')";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $userid, $teamid);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Team hidden successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?> 