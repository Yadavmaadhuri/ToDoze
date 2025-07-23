<?php
session_start();
date_default_timezone_set('Asia/Kathmandu');
include '../config/database.php';
include '../iclude/load_username.php';


// Check if user is logged in
if (!isset($_SESSION['userid'])) {
  header("Location: ../users/signin.php");
  exit();
}

$userid = $_SESSION['userid'];
$teamId = $_POST['teamid'] ?? $_GET['teamid'] ?? null;
$taskid = $_GET['taskid'] ?? null;

if (!$teamId || !is_numeric($teamId)) {
    die("Project ID is missing or invalid.");
}
if (!$taskid || !is_numeric($taskid)) {
    die("Task ID is missing or invalid.");
}

// Check if user is admin
$check_admin_sql = "SELECT role FROM team_members WHERE teamid = ? AND userid = ? AND status = 'Accepted'";
$check_admin_stmt = mysqli_prepare($conn, $check_admin_sql);
mysqli_stmt_bind_param($check_admin_stmt, "ii", $teamId, $userid);
mysqli_stmt_execute($check_admin_stmt);
$admin_result = mysqli_stmt_get_result($check_admin_stmt);
$is_admin = false;
if ($admin_row = mysqli_fetch_assoc($admin_result)) {
    $is_admin = ($admin_row['role'] === 'Admin');
}
mysqli_stmt_close($check_admin_stmt);

// Get team members for assignment dropdown
$members_sql = "SELECT u.userid, u.username 
                FROM users u 
                JOIN team_members tm ON u.userid = tm.userid 
                WHERE tm.teamid = ? AND tm.status = 'Accepted'";
$members_stmt = mysqli_prepare($conn, $members_sql);
mysqli_stmt_bind_param($members_stmt, "i", $teamId);
mysqli_stmt_execute($members_stmt);
$members_result = mysqli_stmt_get_result($members_stmt);
$team_members = [];
while ($member = mysqli_fetch_assoc($members_result)) {
    $team_members[] = $member;
}
mysqli_stmt_close($members_stmt);

// Fetch task details (only if not deleted)
$sql = "SELECT t.*, u.username as assigned_username 
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.userid 
        WHERE t.taskid = ? AND t.teamid = ? AND t.is_deleted = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $taskid, $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo "Task not found.";
    exit();
}

$task = mysqli_fetch_assoc($result);
$taskname = $task['taskname'];
$taskdescription = $task['taskdescription'];
$reminder_percentage = $task['reminder_percentage'];
$assigned_to = $task['assigned_to'];
$taskpriority = $task['taskpriority'] ?? 'none';

$taskdate = isset($task['taskdate']) ? $task['taskdate'] : '';  
$tasktime = isset($task['tasktime']) ? $task['tasktime'] : ''; 

// Handle Task Update Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $taskname = trim($_POST['taskname']);
    $taskdescription = isset($_POST['taskdescription']) ? trim($_POST['taskdescription']) : null;
    $taskdate = isset($_POST['taskdate']) ? trim($_POST['taskdate']) : null;
    $tasktime = isset($_POST['tasktime']) ? trim($_POST['tasktime']) : null;
    $reminder_percentage = (!empty($_POST['reminder_percentage'])) ? $_POST['reminder_percentage'] : null;
    $new_assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $new_taskpriority = isset($_POST['taskpriority']) ? $_POST['taskpriority'] : 'none';

    // Initialize update values with the current data
    $update_values = [
        'taskname' => $taskname,
        'taskdescription' => $taskdescription,
        'taskdate' => $taskdate,
        'tasktime' => $tasktime,
        'reminder_percentage' => $reminder_percentage,
        'taskpriority' => $new_taskpriority
    ];

    // Check if any field is empty, and if so, don't update it in the database
    $sql = "UPDATE tasks SET ";

    $fields = [];
    $params = [];
    $types = "";

    if (!empty($taskname) && $taskname !== $task['taskname']) {
        $fields[] = "taskname = ?";
        $params[] = $taskname;
        $types .= "s";
    }
    if (!empty($taskdescription) && $taskdescription !== $task['taskdescription']) {
        $fields[] = "taskdescription = ?";
        $params[] = $taskdescription;
        $types .= "s";
    }
    if (!empty($taskdate) && $taskdate !== $task['taskdate']) {
        $fields[] = "taskdate = ?";
        $params[] = $taskdate;
        $types .= "s";
    }
    if (!empty($tasktime) && $tasktime !== $task['tasktime']) {
        $fields[] = "tasktime = ?";
        $params[] = $tasktime;
        $types .= "s";
    }
    if (isset($reminder_percentage) && $reminder_percentage !== $task['reminder_percentage']) {
        $fields[] = "reminder_percentage = ?";
        $params[] = $reminder_percentage;
        $types .= "s";
    }
    if ($is_admin) {
        $fields[] = "assigned_to = ?";
        $params[] = $new_assigned_to;  // This will be NULL if no user is selected
        $types .= "i";
    }
    if (!empty($new_taskpriority) && $new_taskpriority !== $task['taskpriority']) {
        $fields[] = "taskpriority = ?";
        $params[] = $new_taskpriority;
        $types .= "s";
    }

    // Only execute the update if there are any fields to update
    if (count($fields) > 0) {
        $sql .= implode(", ", $fields) . " WHERE taskid = ? AND teamid = ?";
        $params[] = $taskid;
        $params[] = $teamId;
        $types .= "ii";

        $update_stmt = mysqli_prepare($conn, $sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // If task was assigned to someone, create a notification
                if ($is_admin && isset($new_assigned_to) && $new_assigned_to !== $assigned_to) {
                    // Get task name for the notification message
                    $task_name = $taskname;
                    
                    // Create notification for the assigned member
                    $notif_sql = "INSERT INTO notifications (userid, teamid, taskid, message, type) 
                                 VALUES (?, ?, ?, ?, 'task_assignment')";
                    $notif_stmt = mysqli_prepare($conn, $notif_sql);
                    
                    if ($notif_stmt) {
                        $message = "You have been assigned to task: " . $task_name;
                        mysqli_stmt_bind_param($notif_stmt, "iiis", $new_assigned_to, $teamId, $taskid, $message);
                        mysqli_stmt_execute($notif_stmt);
                        mysqli_stmt_close($notif_stmt);
                    }
                }
                
                $_SESSION['success_message'] = "Task updated successfully!";
                header("Location: team_view.php?teamid=" . $teamId);
                exit();
            } else {
                echo "Error updating task: " . mysqli_error($conn);
            }
            mysqli_stmt_close($update_stmt);
        }
    } else {
        // No fields to update
        header("Location: team_view.php?teamid=" . $teamId);
        exit();
    }
}
?>

<!DOCTYPE html>
<head>
<html lang="en">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Task</title>
<link rel="stylesheet" href="css/dash.css">
<link rel="icon" type="image/x-icon" href="../img/favicon.ico">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>

<body id="body-pd">



    <div class="box">
      <h2 style="text-align: center; ">Edit Task</h2>
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?teamid=' . urlencode($teamId) . '&taskid=' . urlencode($taskid); ?>" class="add-task-form">


      <input type="hidden" name="teamid" value="<?php echo htmlspecialchars($teamId); ?>">

        <input type="text" name="taskname" id="taskname" placeholder="Add task here" value="<?php echo htmlspecialchars($taskname); ?>" maxlength="50" required>

        <input type="text" name="taskdescription" id="taskdescription" placeholder="Task Description" style="height: 80px;" value="<?php echo htmlspecialchars($taskdescription); ?>"  maxlength="150">

        <div>
            <div style="display: inline-block; vertical-align: top; margin-right: 20px;">
                <label for="taskdate" style="display: block;">Select Due Date 📅</label>
                <input type="date" id="taskdate" name="taskdate" value="<?php echo htmlspecialchars($taskdate); ?>" style="width: 170px;">
            </div>

            <div style="display: inline-block; vertical-align: top;">
                <label for="tasktime" style="display: block;">Select Time 🕰️</label>
                <input type="time" id="tasktime" name="tasktime" value="<?php echo htmlspecialchars($tasktime); ?>" style="width: 170px;">
            </div>
        </div>

        <select id="reminder" name="reminder_percentage">
            <option value="" disabled selected>Set Reminder Here 🔔</option>
            <option value="50" <?php echo ($reminder_percentage == 50) ? 'selected' : ''; ?>>50% (Halfway to Due Date)</option>
            <option value="75" <?php echo ($reminder_percentage == 75) ? 'selected' : ''; ?>>75% (Closer to Due Date)</option>
            <option value="90" <?php echo ($reminder_percentage == 90) ? 'selected' : ''; ?>>90% (Near Due Date)</option>
            <option value="100" <?php echo ($reminder_percentage == 100) ? 'selected' : ''; ?>>100% (On Time)</option>
        </select>

        <!-- Priority Section -->
        <div style="margin-top: 10px;">
            <label for="taskpriority">Priority:</label>
            <select id="taskpriority" name="taskpriority">
                <option value="none" <?php echo ($taskpriority == 'none') ? 'selected' : ''; ?>>⚫ None</option>
                <option value="High" <?php echo ($taskpriority == 'High') ? 'selected' : ''; ?>>🔴 High</option>
                <option value="Medium" <?php echo ($taskpriority == 'Medium') ? 'selected' : ''; ?>>🟡 Medium</option>
                <option value="Low" <?php echo ($taskpriority == 'Low') ? 'selected' : ''; ?>>🟢 Low</option>
            </select>
        </div>

        <?php if ($is_admin): ?>
        <div style="margin-top: 10px;">
            <label for="assigned_to">Assign To:</label>
            <select id="assigned_to" name="assigned_to">
                <option value="">Unassigned</option>
                <?php foreach ($team_members as $member): ?>
                    <option value="<?php echo htmlspecialchars($member['userid']); ?>" 
                            <?php echo ($assigned_to == $member['userid']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($member['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" style="margin-top: 20px;">Update Task</button>
      </form>
      <br>
      <a href="team_view.php?teamid=<?php echo $teamId; ?>">Back</a>
    </div>
  </div>
  </script>
  <script>
document.addEventListener("DOMContentLoaded", function() {
    const toggle = document.getElementById('nav-toggle');
    const body = document.getElementById('body-pd');

    toggle.addEventListener('click', () => {
        body.classList.toggle('nav-collapsed');
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById('taskdate');
    const timeInput = document.getElementById('tasktime');

    function setMinDate() {
        const today = new Date();
        // Format YYYY-MM-DD for min attribute
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const minDate = `${yyyy}-${mm}-${dd}`;
        dateInput.min = minDate;

        // If current date selected is today, restrict time to now or later
        if (dateInput.value === minDate) {
            // Format time as HH:MM in 24-hour format
            const hh = String(today.getHours()).padStart(2, '0');
            const min = String(today.getMinutes()).padStart(2, '0');
            const minTime = `${hh}:${min}`;
            timeInput.min = minTime;

            // If current time value is before minTime, reset time value
            if (timeInput.value && timeInput.value < minTime) {
                timeInput.value = minTime;
            }
        } else {
            // For future dates, no min time restriction
            timeInput.min = "";
        }
    }

    // Run on page load
    setMinDate();

    // Whenever the date changes, update min time accordingly
    dateInput.addEventListener('change', setMinDate);
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById('taskdate');
    const timeInput = document.getElementById('tasktime');
    const reminderSelect = document.getElementById('reminder');

    function toggleReminderAvailability() {
        if (dateInput.value && timeInput.value) {
            reminderSelect.disabled = false;
        } else {
            reminderSelect.disabled = true;
            reminderSelect.value = ""; // Reset the reminder if disabling
        }
    }

    // Run on page load
    toggleReminderAvailability();

    // Attach events to inputs
    dateInput.addEventListener('input', toggleReminderAvailability);
    timeInput.addEventListener('input', toggleReminderAvailability);
});
</script>



<!-- ===== IONICONS ===== -->
<script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>

<!-- ===== MAIN JS ===== -->
<script src="../js/dash.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>

</html>