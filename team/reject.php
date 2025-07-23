<?php
session_start();
include '../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];

// Check if team ID is provided
if (isset($_GET['teamid'])) {
    $teamId = intval($_GET['teamid']);

    // Check if the user is already a member of the team or has an invitation
    $sql = "SELECT * FROM team_members 
            WHERE userid = ? AND teamid = ? AND status = 'Pending'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $userid, $teamId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // If the user has a pending invitation, update status to 'Rejected'
    if (mysqli_num_rows($result) > 0) {
        $sql = "UPDATE team_members 
                SET status = 'Rejected' 
                WHERE userid = ? AND teamid = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $userid, $teamId);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Invitation rejected. You can be re-invited later.";
        } else {
            $_SESSION['error_message'] = "Failed to reject invitation.";
        }
    } else {
        // If no pending invitation exists, inform the user
        $_SESSION['error_message'] = "No invitation found for this team.";
    }

    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_message'] = "Project ID is missing.";
}

header("Location: invitation.php");
exit();
?>
