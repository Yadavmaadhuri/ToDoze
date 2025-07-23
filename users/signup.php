<?php
session_start();
include '../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// use PHPMailer\PHPMailer\SMTP;

require '../phpmailer/PHPMailer.php';
require '../phpmailer/Exception.php';
require '../phpmailer/SMTP.php';

$username = $useremail = $userpassword = $confirmpassword = "";
$username_err = $useremail_err = $userpassword_err = $confirmpassword_err = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Validate Username (Only alphabets, one space between words allowed)
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your name.";
    } elseif (!preg_match("/^[A-Za-z]+( [A-Za-z]+)*$/", trim($_POST["username"]))) {
        $username_err = "Name can only contain alphabets and one space between words.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validate Email
    if (empty(trim($_POST["useremail"]))) {
        $useremail_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["useremail"]), FILTER_VALIDATE_EMAIL)) {
        $useremail_err = "Please enter a valid email address.";
    } else {
        $useremail = trim($_POST["useremail"]);
        // Check if email already exists
        $sql = "SELECT userid FROM users WHERE useremail = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $useremail);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $useremail_err = "This email is already registered.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate Password
    if (empty(trim($_POST["userpassword"]))) {
        $userpassword_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["userpassword"])) < 8 || strlen(trim($_POST["userpassword"])) > 15) {
        $userpassword_err = "Password must be 8 to 15 characters long.";
    } else {
        $userpassword = trim($_POST["userpassword"]);
    }

    // Confirm Password
    if (empty(trim($_POST["confirmpassword"]))) {
        $confirmpassword_err = "Please confirm your password.";
    } else {
        $confirmpassword = trim($_POST["confirmpassword"]);
        if (empty($userpassword_err) && ($userpassword != $confirmpassword)) {
            $confirmpassword_err = "Passwords do not match.";
        }
    }

    // If no errors, proceed with OTP generation
    if (empty($username_err) && empty($useremail_err) && empty($userpassword_err) && empty($confirmpassword_err)) {
        // Store user details in session
        $_SESSION['username'] = $username;
        $_SESSION['useremail'] = $useremail;
        $_SESSION['userpassword'] = password_hash($userpassword, PASSWORD_DEFAULT); // Hash the password

        // Generate a 6-digit OTP
        if (!isset($_SESSION['otp']) || time() >= $_SESSION['otp_expiry']) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 180; // OTP expires in 3 minutes
        $_SESSION['otp_type'] = 'signup';


        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'todoze3@gmail.com';
            $mail->Password   = 'bwwz veye ktcd mfxb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('todoze3@gmail.com', 'ToDoze');
            $mail->addAddress($useremail, $username);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for ToDoze Registration';
            $mail->Body    = "<h3>Hello $username,</h3><p>Your OTP is: <b>$otp</b></p><p>This OTP is valid for 3 minutes.</p>";

            $mail->send();
        } catch (Exception $e) {
            die("Error sending OTP: {$mail->ErrorInfo}");
        }
    }
        header("Location: verifyotp.php");
        exit();
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="../css/form.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        
.header {
position: fixed;
top: 0;
left: 0;
width: 100%;
z-index: 1000;
backdrop-filter: blur(10px);
background-color: rgba(255, 255, 255, 0.9);
box-shadow: var(--shadow-sm);
transition: var(--transition);
}

.header-container {
display: flex;
justify-content: space-between;
align-items: center;
padding: 1.5rem 2rem;
margin-top: -23rem;
}

.logo {
height: 4.5rem;
transition: var(--transition);
}

* {
margin: 0;
padding: 0;
box-sizing: border-box;
}

html {
scroll-behavior: smooth;
}
        .error { color: red; font-size: 12px; }
    </style>
</head>
<body>
     <header id="header">
<div class="container header-container">
<img src="../img/logo.png" alt="ToDoze" class="logo">
</header>
    <div class="form-wrapper">
    <div class="logo-container">
                <img src="../img/signup.svg" alt="Logo">
            </div> 
        <div class="form-container">
            <h1>Sign Up</h1>
           
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="text" placeholder="username" name="username" required>
                <span class="error"><?php echo $username_err; ?></span>
                <input type="email" placeholder="Enter your email" name="useremail" required>
                <span class="error"><?php echo $useremail_err; ?></span>
                <div class="password-wrapper">
                <input type="password" name="userpassword" id="signup-password" placeholder="Create a password" required>
                <div class="password-wrapper">
        <!-- Initial hide image -->
        <img src="../img/hide.png" id="toggle-icon" alt="Hide Icon" onclick="togglePassword('signup-password', 'toggle-icon')">
    </div>
                </div>
                <span class="error"><?php echo $userpassword_err; ?></span>
                <div class="password-wrapper">
                <input type="password" name="confirmpassword" id="confirm-password" placeholder="Confirm your password" required>
                <img src="../img/hide.png" id="toggle-password" alt="Hide Icon" onclick="togglePassword('confirm-password', 'toggle-password')"
                 style="width: 20px; cursor: pointer; position: absolute; right: 10px; top: 50%; transform: translateY(-80%)">   
            </div>
                <span class="error"><?php echo $confirmpassword_err; ?></span>
                <button type="submit" name="send">Sign Up</button>
            </form>
            <h4>Already a member? <a href="signin.php" class="sign-in-link switch"
            onclick="smoothRedirect('signin.php')"><b>Sign In</b></a></a></h4>
            </div>
    </div>
    <script>
    function togglePassword(passwordFieldId, iconId) {
    var passwordField = document.getElementById(passwordFieldId);
    var icon = document.getElementById(iconId);

    if (!passwordField || !icon) {
        console.error("Element not found: ", passwordFieldId, iconId);
        return;
    }

    if (passwordField.type === "password") {
        passwordField.type = "text";
        icon.src = "../img/show.png"; // Change icon to show image
    } else {
        passwordField.type = "password";
        icon.src = "../img/hide.png"; // Change icon to hide image
    }
}
function smoothRedirect(url) {
    document.body.classList.add("fade-out");
    setTimeout(() => {
        window.location.href = url;
    }, 100); // Adjusted timeout to match animation duration
}


document.addEventListener("DOMContentLoaded", function () {
    document.body.style.transition = "opacity 0.5s"; // Apply smooth transition
});
</script>
</body>
</html>
