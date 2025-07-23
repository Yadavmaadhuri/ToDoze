<?php
session_start();
include '../config/database.php';

$otp_err = "";
$timeMessage = "";
$remainingTime = 0;

// OTP countdown logic
if (isset($_SESSION['otp_expiry'])) {
    $remainingTime = $_SESSION['otp_expiry'] - time();
    if ($remainingTime <= 0) {
        $timeMessage = "OTP has expired!";
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
    } else {
        $minutes = floor($remainingTime / 60);
        $seconds = $remainingTime % 60;
        $timeMessage = "Time remaining: {$minutes}m {$seconds}s";
    }
} else {
    $timeMessage = "No OTP generated.";
}

// Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['resend'])) {
        // Redirect based on context
        if ($_SESSION['otp_type'] === 'reset') {
            header("Location: forgot_password.php");
        } else {
            header("Location: signup.php");
        }
        exit();
    }

    $enteredOtp = trim($_POST['otp']);

    if (empty($enteredOtp)) {
        $otp_err = "Please enter your verification code.";
    } elseif (!isset($_SESSION['otp'])) {
        $otp_err = "OTP has expired. Please request a new one.";
    } elseif ($enteredOtp == $_SESSION['otp'] && $remainingTime > 0) {

        if ($_SESSION['otp_type'] === 'signup') {
            // Register new user
            $username = $_SESSION['username'];
            $useremail = $_SESSION['useremail'];
            $userpassword = $_SESSION['userpassword'];

            $sql = "INSERT INTO users (username, useremail, userpassword, is_verified) VALUES (?, ?, ?, '1')";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sss", $username, $useremail, $userpassword);
                if (mysqli_stmt_execute($stmt)) {
                    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_type']);
                    header("Location: signin.php");
                    exit();
                } else {
                    $otp_err = "Something went wrong. Try again.";
                }
                mysqli_stmt_close($stmt);
            }

        } elseif ($_SESSION['otp_type'] === 'reset') {
            // Verified OTP for password reset
            $_SESSION['verified_email'] = $_SESSION['useremail'];
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_type']);
            header("Location: reset_password.php");
            exit();
        }

    } else {
        $otp_err = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="../css/form.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <style>
        .error { color: red; font-size: 12px; }
        .countdown { color: green; font-size: 14px; font-weight: bold; }
        .countdown.expiring { color: orange; }
        .countdown.expired { color: red; }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <div class="logo-container">
            <img src="../img/logo.png" alt="Logo">
        </div>
        <div class="form-container">
            <h3>OTP Verification</h3>
            <form method="POST" action="">
                <label for="otp">Enter OTP sent to your email:</label>
                <p id="countdown" class="countdown"><?php echo $timeMessage; ?></p>
                
                <input type="number" name="otp" id="otp" required <?php echo ($remainingTime <= 0) ? 'disabled' : ''; ?>>
                <span class="error"><?php echo $otp_err; ?></span>
                
                <button type="submit" id="verifyButton" <?php echo ($remainingTime <= 0) ? 'style="display:none;"' : ''; ?>>Verify</button>
                
                <button type="submit" name="resend" id="resendButton" <?php echo ($remainingTime > 0) ? 'style="display:none;"' : ''; ?>>Resend OTP</button>
            </form>
        </div>
    </div>

    <script>
        var otpExpiryTime = <?php echo isset($_SESSION['otp_expiry']) ? $_SESSION['otp_expiry'] : 0; ?>;

        function updateCountdown() {
            var currentTime = Math.floor(Date.now() / 1000);
            var remainingTime = otpExpiryTime - currentTime;

            if (remainingTime <= 0) {
                document.getElementById('countdown').innerHTML = "OTP has expired!";
                document.getElementById('countdown').classList.add("expired");
                document.getElementById('otp').disabled = true;
                document.getElementById('verifyButton').style.display = "none";
                document.getElementById('resendButton').style.display = "block";
            } else {
                var minutes = Math.floor(remainingTime / 60);
                var seconds = remainingTime % 60;
                document.getElementById('countdown').innerHTML = "Time remaining: " + minutes + "m " + seconds + "s";
                if (remainingTime <= 30) {
                    document.getElementById('countdown').classList.add("expiring");
                } else {
                    document.getElementById('countdown').classList.remove("expiring");
                }
            }
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
</body>
</html>
