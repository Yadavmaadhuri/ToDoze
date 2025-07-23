<?php
session_start();
include '../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];

// Ensure team ID and user email are provided
$teamid = $_POST['teamid'] ?? $_GET['teamid'] ?? null;
$user_email = $_POST['useremail'] ?? null;
$message = "";  // To hold feedback message

$now = date('Y-m-d H:i:s');

// Validate inputs
if (!$teamid || !$user_email) {
    $message = "Missing team ID or user email.";
} else {
    // Check if the user is the admin of the team
    $sql = "SELECT pm.userid
            FROM team_members pm
            WHERE pm.teamid = ? AND pm.userid = ? AND pm.role = 'Admin'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $teamid, $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // If the user is not the admin
    if (mysqli_num_rows($result) == 0) {
        $message = "You must be the admin of the team to send invitations.";
    } else {
        // Fetch the user ID for the provided email
        $sql = "SELECT userid FROM users WHERE useremail = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $user_email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $message = "User not found.";
        } else {
            $invitee_id = $user['userid'];

            // Prevent self-invitation
            if ($userid == $invitee_id) {
                $message = "You cannot invite yourself to the team.";
            } else {
                // Check if the user is already invited or a member of the team
                $sql = "SELECT * FROM team_members WHERE teamid = ? AND userid = ? AND status IN ('Pending', 'Accepted') AND has_exited = 0";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $teamid, $invitee_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $existing = mysqli_fetch_assoc($result);

                if ($existing) {
                    $message = "This user is already a member or has a pending invitation.";
                } else {
                    // Check if user was previously a member (has_exited = 1)
                    $sql = "SELECT * FROM team_members WHERE teamid = ? AND userid = ? AND has_exited = 1";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $teamid, $invitee_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $was_member = mysqli_fetch_assoc($result);

                    if ($was_member) {
                        // Update existing record for ex-member
                        $sql = "UPDATE team_members 
                                SET status = 'Pending', 
                                    has_exited = 0, 
                                    exited_at = NULL,
                                    removed_at = NULL,
                                    invited_at = NOW() 
                                WHERE teamid = ? AND userid = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ii", $teamid, $invitee_id);
                    } else {
                        // Insert new invitation
                        $sql = "INSERT INTO team_members (teamid, userid, role, status, invited_at) 
                                VALUES (?, ?, 'Member', 'Pending', NOW())";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ii", $teamid, $invitee_id);
                    }

                    if (mysqli_stmt_execute($stmt)) {
                        $message = "Invitation sent successfully.";
                    } else {
                        $message = "Failed to send invitation: " . mysqli_error($conn);
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Close the connection
mysqli_close($conn);
?>

<!-- HTML to show SweetAlert -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            <?php if ($message): ?>
                Swal.fire({
                    title: "<?php echo addslashes($message); ?>",
                    icon: "<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>",
                    timer: 1500,
                    showConfirmButton: false
                }).then(function () {
                    window.location.href = "member.php?teamid=<?php echo $teamid; ?>"; // Redirect to members page after showing the message
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>