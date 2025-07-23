<?php
session_start();
include '../config/database.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../phpmailer/PHPMailer.php';
require '../phpmailer/Exception.php';
require '../phpmailer/SMTP.php';

$username = $useremail = "";
$username_err = $useremail_err = $otp_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $useremail = trim($_POST["useremail"]);

    if (empty($username)) $username_err = "Please enter your username.";
    if (empty($useremail) || !filter_var($useremail, FILTER_VALIDATE_EMAIL)) {
        $useremail_err = "Please enter a valid email.";
    }

    if (empty($username_err) && empty($useremail_err)) {
        $stmt = $conn->prepare("SELECT userid FROM users WHERE username=? AND useremail=?");
        $stmt->bind_param("ss", $username, $useremail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $otp = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", time() + 180); // expires in 3 minutes

            // Update OTP in database
            $update = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE username=? AND useremail=?");
            $update->bind_param("ssss", $otp, $expiry, $username, $useremail);
            $update->execute();

            // Store OTP and expiry in session
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = time() + 180;
            $_SESSION['reset_email'] = $useremail;
            $_SESSION['otp_type'] = 'reset';

            // Send OTP via email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'todoze3@gmail.com';
                $mail->Password = 'bwwz veye ktcd mfxb';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('todoze3@gmail.com', 'ToDoze');
                $mail->addAddress($useremail, $username);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP to Reset Password';
                $mail->Body = "<p>Hello <b>$username</b>,<br>Your OTP is: <b>$otp</b> (valid for 3 minutes).</p>";

                $mail->send();
                header("Location: verifyotp.php");
                exit();
            } catch (Exception $e) {
                $otp_msg = "Error sending OTP: {$mail->ErrorInfo}";
            }
        } else {
            $otp_msg = "Username and email combination not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/form.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <style>.error { color: red; font-size: 12px; }</style>
</head>
<body>
    <div class="form-wrapper">
        <div class="form-container">
            <h1>Forgot Password</h1>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="Enter your username" required>
                <span class="error"><?php echo $username_err ?? ''; ?></span>

                <input type="email" name="useremail" placeholder="Enter your registered email" required>
                <span class="error"><?php echo $useremail_err ?? ''; ?></span>

                <span class="error"><?php echo $otp_msg ?? ''; ?></span>
                <button type="submit">Send OTP</button>
            </form>
            <h4>Back to <a href="signin.php"><b>Sign In</b></a></h4>
        </div>
    </div>
</body>
</html>
