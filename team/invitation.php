<?php
session_start();
include '../config/database.php';
include '../include/load_username.php';

if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];

// Fetch all invitations
$sql = "SELECT 
    p.teamid,
    p.teamname,
    u_admin.username AS adminname,
    pm_user.status,
    pm_user.invited_at,
    'invitation' as type
FROM 
    team_members pm_user
JOIN 
    teams p ON pm_user.teamid = p.teamid
JOIN 
    team_members pm_admin ON pm_admin.teamid = p.teamid AND pm_admin.role = 'Admin'
JOIN 
    users u_admin ON pm_admin.userid = u_admin.userid
WHERE 
    pm_user.userid = ? AND
    pm_user.userid != pm_admin.userid
UNION ALL
SELECT 
    t.teamid,
    t.teamname,
    '' as adminname,
    '' as status,
    n.created_at as invited_at,
    'task_assignment' as type
FROM 
    notifications n
JOIN 
    teams t ON n.teamid = t.teamid
JOIN 
    tasks tk ON n.taskid = tk.taskid
WHERE 
    n.userid = ? AND
    n.type = 'task_assignment'
ORDER BY 
    invited_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $userid, $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// 🔴 Check for pending invitations
$_SESSION['has_pending_invites'] = false;
foreach ($notifications as $notif) {
    if ($notif['type'] === 'invitation' && $notif['status'] === 'Pending') {
        $_SESSION['has_pending_invites'] = true;
        break;
    }
}

// Mark notifications as read
$update_sql = "UPDATE notifications SET is_read = TRUE WHERE userid = ? AND is_read = FALSE";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "i", $userid);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

?>


<!DOCTYPE html>
<html lang="en">
<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/dash.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .notification-info {
            flex: 1;
            min-width: 200px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .action-btn,
        .accepted-btn,
        .rejected-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .action-btn.accept-btn {
            background-color: #007bff;
            color: white;
        }

        .action-btn.reject-btn {
            background-color: #ef552a;
            color: white;
        }

        .accepted-btn {
            background-color: #28a745;
            color: white;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .rejected-btn {
            background-color: red;
            color: white;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .notification-type {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 8px;
        }

        .type-invitation {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .type-task {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .notification-date {
            font-size: 0.9em;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>

<body>


    

    <div class="container">
        <div class="box">
            <h2>Notifications</h2>

            <?php if (!empty($notifications)) { ?>
                <ul>
                    <?php foreach ($notifications as $notif) { ?>
                        <li class="notification-item">
                            <div class="notification-info">
                                <?php if ($notif['type'] === 'invitation') { ?>
                                    <span class="notification-type type-invitation">Invitation</span>
                                    <div><strong>Hi! You have a request to join:</strong> <?= htmlspecialchars($notif['teamname']) ?></div>
                                    <div>From: <?= htmlspecialchars($notif['adminname']) ?></div>
                                <?php } else { ?>
                                    <span class="notification-type type-task">Task Assignment</span>
                                    <div><strong>New task assignment in:</strong> <?= htmlspecialchars($notif['teamname']) ?></div>
                                <?php } ?>
                                <div class="notification-date">Date: <?= htmlspecialchars($notif['invited_at']) ?></div>
                            </div>

                            <?php if ($notif['type'] === 'invitation') { ?>
                                <div class="notification-actions">
                                    <?php if ($notif['status'] === 'Pending') { ?>
                                        <a href="accept.php?teamid=<?= $notif['teamid'] ?>"
                                            class="action-btn accept-btn">Accept</a>
                                        <a href="reject.php?teamid=<?= $notif['teamid'] ?>"
                                            class="action-btn reject-btn">Reject</a>
                                    <?php } elseif ($notif['status'] === 'Accepted') { ?>
                                        <button class="accepted-btn" disabled>Accepted</button>
                                    <?php } elseif ($notif['status'] === 'Removed') { ?>
                                        <button class="rejected-btn" disabled>Removed</button>
                                    <?php } elseif ($notif['status'] === 'Rejected') { ?>
                                        <button class="rejected-btn" disabled>Rejected</button>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="notification-actions">
                                    <a href="team_view.php?teamid=<?= $notif['teamid'] ?>" 
                                       class="action-btn accept-btn">View Task</a>
                                </div>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <div class="centered-content">
                    <div class="content-wrapper">
                        <img src="img/notify.svg" alt="No notifications yet" />
                        <h3>
                            <p>No notifications yet 🚀</p>
                        </h3>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<script>
// Dropdown functionality
document.querySelectorAll('.nav__dropdown-btn').forEach(button => {
  button.addEventListener('click', () => {
    const dropdown = button.closest('.nav__dropdown');
    dropdown.classList.toggle('active');
  });
});
</script>
    <!-- IONICONS -->
    <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>
    <script src="../js/dash.js"></script>
    
</body>

</html>