<?php
session_start();
include '../config/database.php';
include '../users/load_username.php';

$invite_message = $_SESSION['invite_message'] ?? null;
$invite_message_type = $_SESSION['invite_message_type'] ?? null;
unset($_SESSION['invite_message'], $_SESSION['invite_message_type']);

if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];
$teamId = $_GET['teamid'] ?? null;

if (!$teamId) {
    die("Project not found!");
}

// fetching role 
$role_sql = "SELECT role FROM team_members WHERE userid = ? AND teamid = ?";
$role_stmt = mysqli_prepare($conn, $role_sql);
mysqli_stmt_bind_param($role_stmt, "ii", $userid, $teamId);
mysqli_stmt_execute($role_stmt);
$role_result = mysqli_stmt_get_result($role_stmt);
$user_role_data = mysqli_fetch_assoc($role_result);
$user_role = $user_role_data['role'] ?? 'Member'; // default to Member if role not found



// Get admin (creator) of the team
$sql = "SELECT userid FROM team_members WHERE teamid = ? AND role = 'admin' LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Query preparation failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
$admin_userid = $admin['userid'];
mysqli_stmt_close($stmt);

// Fetch accepted members
$sql = "SELECT users.userid, users.useremail, team_members.role 
        FROM team_members 
        JOIN users ON team_members.userid = users.userid 
        WHERE team_members.teamid = ? AND team_members.status = 'accepted'AND team_members.has_exited = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$accepted_members = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);


// Fetch ex members with exit dates
$sql = "SELECT u.userid, u.useremail, team_members.role, team_members.exited_at 
        FROM team_members 
        JOIN users u ON team_members.userid = u.userid 
        WHERE team_members.teamid = ? AND team_members.has_exited = 1
        ORDER BY team_members.exited_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ex_members = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Fetch pending members
$sql = "SELECT users.userid, users.useremail 
        FROM team_members 
        JOIN users ON team_members.userid = users.userid 
        WHERE team_members.teamid = ? 
        AND team_members.status = 'pending'
        AND users.userid != ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $teamId, $admin_userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pending_members = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$sql = "
SELECT userid, useremail 
FROM users 
WHERE userid != ? 
AND userid NOT IN (
    SELECT userid 
    FROM team_members 
    WHERE teamid = ? 
      AND status IN ('Accepted', 'Pending') 
      AND has_exited = 0
)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $userid, $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$available_users = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title> Members</title>
  <link rel="stylesheet" href="../css/dash.css" />
  <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
  
</head>

<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>

<body id="body-pd">
 


  <div class="container">
  <div class="filter-container">
       <a href="team_view.php?teamid=<?php echo $teamId; ?>" class="back-link">View Project</a>
    <button class="member-task-filter active" onclick="showSection('accepted', this)">Members</button>
    <!-- <button class="member-task-filter" onclick="showSection('ex', this)">Ex Members</button>     -->
    <?php if ($user_role === 'Admin'): ?>
    <button class="member-task-filter" onclick="showSection('pending', this)">Pending Invitations</button>
    <button class="member-task-filter" onclick="showSection('invite', this)">Send Invitation</button>
    <!-- <button class="member-task-filter" onclick="showSection('ex', this)">Ex Members</button> -->
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['status'])): ?>
    <div class="message <?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>">
      <?= $_GET['status'] === 'success' ? 'Invitation sent successfully.' : htmlspecialchars($_GET['message'] ?? "An error occurred."); ?>
    </div>
  <?php endif; ?>

  <!-- Accepted Members -->
  <div id="accepted" class="members-list section" style="display: block;">
    <h3>Members</h3>
    <?php if (!empty($accepted_members)) { ?>
      <ul class="member-cards">
        <?php foreach ($accepted_members as $member) { ?>
          <li class="member-card">
            <div class="member-info">
              <span class="member-email"><?= htmlspecialchars($member['useremail']) ?></span>
              <span class="role-badge"><?= htmlspecialchars($member['role']) ?></span>
            </div>
      

            <?php if ($user_role === 'Admin'): ?>
            <?php if ($member['userid'] != $admin_userid) { ?>
              <a href="remove_member.php?userid=<?= $member['userid'] ?>&teamid=<?= $teamId ?>" class="remove-btn" onclick="return confirm('Are you sure you want to remove this member?');">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                  <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                </svg>
                Remove
              </a>
              
            <?php }  else { ?>
              <span class="admin-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                </svg>
                Admin
              </span>
            <?php } ?>

            <?php endif; ?>
          </li>
        <?php } ?>
      </ul>
    <?php } else { ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <p>No accepted members yet.</p>
      </div>
    <?php } ?>
  </div>
  <!-- only admin can see this -->
  <?php if ($user_role === 'Admin'): ?>
  <!-- Pending Invitations -->
  <div id="pending" class="pending-list section" style="display: none;">
    <h3>Pending Invitations</h3>
    <?php if (!empty($pending_members)) { ?>
      <ul class="pending-cards">
        <?php foreach ($pending_members as $pending) { ?>
          <li class="pending-card">
            <span class="pending-email"><?= htmlspecialchars($pending['useremail']) ?></span>
            <span class="pending-badge">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.17l-1 .025zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.16.36-.345.706-.555 1.038l-.845-.535zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z"/>
                <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z"/>
                <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/>
              </svg>
              Pending
            </span>
          </li>
        <?php } ?>
      </ul>
    <?php } else { ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>No pending invitations.</p>
      </div>
    <?php } ?>
  </div>

  <!-- Send Invitation -->
  <div id="invite" class="add-task-form section" style="display: none;">
    <h3>Send Invitation</h3>
    
    <form action="send_invitation.php" method="POST" class="invite-form">
        <input type="hidden" name="teamid" value="<?= $teamId ?>">
        <div class="form-group">
            <label for="useremail">Enter User Email:</label>
            <div class="search-container">
                <input type="email" 
                       name="useremail" 
                       id="useremail" 
                       class="form-control" 
                       placeholder="Enter email address to invite..." 
                       required
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"            
    ></div>
            <?php if (isset($_SESSION['invite_alert'])): ?>
                <div class="error-message">
                    <?php 
                    echo $_SESSION['invite_alert'];
                    unset($_SESSION['invite_alert']); // Clear the message after showing
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="submit-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555zM0 4.697v7.104l5.803-3.558L0 4.697zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757zm3.436-.586L16 11.801V4.697l-5.803 3.546z"/>
                </svg>
                Send Invite
            </button>
            <button type="button" onclick="window.history.back();" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Go Back
            </button>
        </div>
    </form>
  </div>
 

</div>
<?php endif; ?>

<style>
.member-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background-color: #f6f8fa;
    border-radius: 6px;
    transition: background-color 0.2s ease;
    margin-bottom: 8px;
}

.member-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.exit-date {
    font-size: 12px;
    color: #666;
}

.reinvite-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    border-radius: 4px;
    background-color: #0366d6;
    color: white;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.reinvite-btn:hover {
    background-color: #0256b4;
}

.reinvite-btn ion-icon {
    font-size: 16px;
}

.search-container {
    margin-bottom: 4px; /* Reduced margin since error message will be below */
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #0366d6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(3, 102, 214, 0.2);
}

.error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 8px;
    padding: 4px 0;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.submit-btn, .back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.submit-btn {
    background-color: #0366d6;
    color: white;
}

.submit-btn:hover {
    background-color: #0256b4;
}

.back-btn {
    background-color: #6c757d;
    color: white;
}

.back-btn:hover {
    background-color: #5a6268;
}

.submit-btn svg, .back-btn svg {
    width: 16px;
    height: 16px;
}

/* Add these styles for the alert message */
.alert-message {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 4px;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    font-size: 14px;
}

.alert-message:empty {
    display: none;
}

/* Style for success message */
.alert-message.success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

/* Style for error message */
.alert-message.error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
</style>

<script>
function showSection(id, btn) {
    const sections = ['accepted', 'pending', 'invite', 'ex'];
    sections.forEach(sec => {
        document.getElementById(sec).style.display = (sec === id) ? 'block' : 'none';
    });
    
    // Update active state of buttons
    document.querySelectorAll('.member-task-filter').forEach(b => {
        b.classList.remove('active');
    });
    btn.classList.add('active');
    
    // Clear any existing alert when switching sections
    const alertMessage = document.querySelector('.alert-message');
    if (alertMessage) {
        alertMessage.textContent = '';
    }
}
</script>
<script>
// Dropdown functionality
document.querySelectorAll('.nav__dropdown-btn').forEach(button => {
  button.addEventListener('click', () => {
    const dropdown = button.closest('.nav__dropdown');
    dropdown.classList.toggle('active');
  });
});
</script>
<!-- Icons and Charts -->
<script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>
<script src="../js/dash.js"></script>

<?php if ($invite_message): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    Swal.fire({
        title: "<?= addslashes($invite_message) ?>",
        icon: "<?= $invite_message_type === 'success' ? 'success' : 'error' ?>",
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'colored-toast',
            title: 'swal2-title-custom'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
</script>
<?php endif; ?>

</body>
</html>