<?php
session_start();
include '../config/database.php';
include '../include/load_username.php';


if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}

$userid = $_SESSION['userid'];
$usernameMessage = "";

// Update username
if (isset($_POST['update_username'])) {
    $newUsername = trim($_POST['username']);

    // Validation: only letters and one space between words
    if (empty($newUsername)) {
        $usernameMessage = "Username cannot be empty.";
    } elseif (!preg_match("/^[A-Za-z]+( [A-Za-z]+)*$/", $newUsername)) {
        $usernameMessage = "Name can only contain letters and one space between words.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE userid = ?");
        $stmt->bind_param("si", $newUsername, $userid);

        if ($stmt->execute()) {
            $_SESSION['username'] = $newUsername;
            $usernameMessage = "Username updated successfully.";
              header("Location: change_name.php"); // Redirect to avoid form resubmission
            exit();
        } else {
            $usernameMessage = "Error updating username: " . $conn->error;
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>change name</title>
    <link rel="stylesheet" href="css/dash.css">
         <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php include '../include/navbar.php'; ?>
<?php include '..include/toolbar.php'; ?>
<body id="body-pd">
 

<div class="container">
    <!-- Username Update Form -->
    <h3>Change Username</h3>
    <?php if ($usernameMessage): ?>
        <p style="color: <?= strpos($usernameMessage, 'success') !== false ? 'green' : 'red'; ?>;">
            <?= $usernameMessage; ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label for="username">New Username:</label><br>
        <input type="text" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required><br><br>
        <button type="submit" name="update_username" class="btn">Update Name</button>
    </form>
    <hr><br>
</div>

 <!-- Icons and Charts -->
<script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>
  <script src="..js/dash.js"></script>
</body>
</html>