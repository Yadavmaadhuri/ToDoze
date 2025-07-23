080<?php
session_start();
include '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];

// Check if teamid is passed
if (isset($_GET['teamid'])) {
    $teamId = intval($_GET['teamid']);

    // Update invitation to accepted and set joinedteam_at timestamp
    $sql = "UPDATE team_members 
            SET status = 'Accepted', joinedteam_at = NOW() 
            WHERE userid = ? AND teamid = ? AND status = 'Pending'";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $userid, $teamId);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Invitation accepted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to accept the invitation.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_message'] = "Database error.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

mysqli_close($conn);
header("Location: invitation.php");
exit();
?>
