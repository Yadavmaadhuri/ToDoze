<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$reset_email = $_SESSION['reset_email'];
$new_password = $confirm_password = "";
$new_err = $confirm_err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (strlen($new_password) < 8 || strlen($new_password) > 15) {
        $new_err = "Password must be 8 to 15 characters.";
    }

    if ($new_password !== $confirm_password) {
        $confirm_err = "Passwords do not match.";
    }

    if (empty($new_err) && empty($confirm_err)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET userpassword=?, otp=NULL, otp_expiry=NULL WHERE useremail=?");
        $stmt->bind_param("ss", $hashed, $reset_email);
        $stmt->execute();

        session_destroy(); // End session after reset
        header("Location: signin.php");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/form.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <style>.error { color: red; font-size: 12px; }</style>
</head>
<body>
    <div class="form-wrapper">
        <div class="form-container">
            <h1>Set New Password</h1>
            <form method="POST" action="">
                <input type="password" name="new_password" placeholder="New password" required>
                <span class="error"><?php echo $new_err ?? ''; ?></span>

                <input type="password" name="confirm_password" placeholder="Confirm password" required>
                <span class="error"><?php echo $confirm_err ?? ''; ?></span>

                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
