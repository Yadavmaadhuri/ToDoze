<?php
session_start();
include '../config/database.php';
include '../include/load_username.php';


if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}

$userid = $_SESSION['userid'];
$passwordMessage = "";

// Update password
if (isset($_POST['update_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordMessage = "All fields are required.";
    } else {
        // Get current hashed password from database
        $stmt = $conn->prepare("SELECT userpassword FROM users WHERE userid = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($hashedCurrentPassword);
        $stmt->fetch();
        $stmt->close();

        // Verify old password
        if (!password_verify($oldPassword, $hashedCurrentPassword)) {
            $passwordMessage = "Old password is incorrect.";
        } elseif ($newPassword !== $confirmPassword) {
            $passwordMessage = "New passwords do not match.";
        } elseif (strlen(trim($newPassword)) < 8 || strlen(trim($newPassword)) > 15) {
            $passwordMessage = "New password must be 8 to 15 characters long.";
        } else {
            // Hash and update new password
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET userpassword = ? WHERE userid = ?");
            $stmt->bind_param("si", $hashedNewPassword, $userid);

            if ($stmt->execute()) {
                $passwordMessage = "Password updated successfully.";
                header("Location: change_password.php"); // Redirect to avoid form resubmission
                exit();
            } else {
                $passwordMessage = "Error updating password: " . $conn->error;
            }

            $stmt->close();
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="../css/dash.css">
      <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>
<body id="body-pd">

<div class="container">

    <!-- Password Update Form -->
    <h3>Change Password</h3>
  <?php if ($passwordMessage): ?>
    <p style="color: <?= strpos($passwordMessage, 'successfully') !== false ? 'green' : 'red'; ?>;">
        <?= $passwordMessage; ?>
    </p>
<?php endif; ?>

   <form method="POST">
    <label for="old_password">Old Password:</label><br>
    <input type="password" name="old_password" required><br><br>

    <label for="new_password">New Password:</label><br>
    <input type="password" name="new_password" required><br><br>

    <label for="confirm_password">Confirm New Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit" name="update_password" class="btn">Change Password</button>
</form>


    <!-- <br><a href=".php" class="btn">Back to Profile</a> -->
</div>

  <!-- Icons and Charts -->
  <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>
  <script src="../js/dash.js"></script>

</body>
</html>