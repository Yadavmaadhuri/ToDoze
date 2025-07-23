<?php
session_start();
date_default_timezone_set('Asia/Kathmandu');
include '../config/database.php';
include '../include/load_username.php';


// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];
$taskname = $taskdescription = $taskdate = $tasktime = $reminder_percentage = "";
// $teamid = isset($_GET['teamid']) ? $_GET['teamid'] : null; // Get team ID from URL
$teamid = $_GET['teamid'] ?? $_POST['teamid'] ?? null;

if (!$teamid || !is_numeric($teamid)) {
    echo "Project ID is missing or invalid.";
    exit();
}

// Update overdue status for tasks
$now = date('Y-m-d H:i:s');
$update_sql = "UPDATE tasks 
    SET is_overdue = 
        CASE 
            WHEN taskdate IS NOT NULL 
                AND CONCAT(taskdate, ' ', IFNULL(tasktime, '23:59:59')) < ? 
                AND taskstatus != 'Completed'
            THEN 1 
            ELSE 0 
        END 
    WHERE teamid = ? AND is_deleted = 0";

$update_stmt = mysqli_prepare($conn, $update_sql);
if ($update_stmt) {
    mysqli_stmt_bind_param($update_stmt, "si", $now, $teamid);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
}

// Check if user is admin of the team
$check_admin_sql = "SELECT role FROM team_members WHERE teamid = ? AND userid = ? AND status = 'Accepted'";
$check_admin_stmt = mysqli_prepare($conn, $check_admin_sql);
mysqli_stmt_bind_param($check_admin_stmt, "ii", $teamid, $userid);
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
mysqli_stmt_bind_param($members_stmt, "i", $teamid);
mysqli_stmt_execute($members_stmt);
$members_result = mysqli_stmt_get_result($members_stmt);
$team_members = [];
while ($member = mysqli_fetch_assoc($members_result)) {
    $team_members[] = $member;
}
mysqli_stmt_close($members_stmt);

// Handle Task Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $taskname = trim($_POST['taskname']);
    $taskdescription = isset($_POST['taskdescription']) ? trim($_POST['taskdescription']) : null;
    $taskdate = !empty($_POST['taskdate']) ? $_POST['taskdate'] : null;
    $tasktime = !empty($_POST['tasktime']) ? $_POST['tasktime'] : null;
    $reminder_percentage = isset($_POST['reminder_percentage']) ? trim($_POST['reminder_percentage']) : null;
    $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $taskpriority = isset($_POST['taskpriority']) ? $_POST['taskpriority'] : 'none';

    // Check if user is admin before allowing assignment
    if (!$is_admin) {
        $assigned_to = null;
    }

    // Insert Task
    $sql = "INSERT INTO tasks (userid, teamid, taskname, taskdescription, taskdate, tasktime, reminder_percentage, taskstatus, assigned_to, taskpriority) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // If assigned_to is empty string or not set, make it NULL
        if (empty($assigned_to)) {
            $assigned_to = null;
        }

        // Handle null values for optional fields
        $taskdescription = $taskdescription ?? null;
        $taskdate = $taskdate ?? null;
        $tasktime = $tasktime ?? null;
        $reminder_percentage = $reminder_percentage ?? null;

        mysqli_stmt_bind_param($stmt, "iisssssis", 
            $userid, 
            $teamid, 
            $taskname, 
            $taskdescription, 
            $taskdate, 
            $tasktime, 
            $reminder_percentage,
            $assigned_to,
            $taskpriority
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['task_added'] = true; // Set a flag instead of the message directly
            mysqli_stmt_close($stmt);
            header("Location: team_task.php?teamid=" . $teamid);
            exit();
        } else {
            echo "Error executing query: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Tasks</title>
    <link rel="stylesheet" href="../css/dash.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>

<body>



    <div class="container">
        <h2>Add New Tasks</h2>
        <a href="team_view.php?teamid=<?php echo $teamid; ?>" class="back-link">View Project</a>

        <div class="box">

            <form class="add-task-form"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?teamid=' . $teamid; ?>" method="POST">
                <input type="hidden" name="teamid" value="<?php echo $teamid; ?>">

                <!-- <label for="taskname">Task Name:</label> -->
                <input type="text" id="taskname" name="taskname" placeholder="Add task here" maxlength="50" required>

                <!-- <label for="taskDescription">Task Description:</label> -->
                <input type="text" id="taskDescription" name="taskdescription" placeholder="Task Description"
                    maxlength="140" style="height: 80px;">
                <div>
                    <!-- Date Section -->
                    <div style="display: inline-block; vertical-align: top; margin-right: 20px;">
                        <label for="taskdate" style="display: block;">Select Due Date 📅</label>
                        <input type="date" id="taskdate" name="taskdate" style="width: 170px;"
                            min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Time Section -->
                    <div style="display: inline-block; vertical-align: top;">
                        <label for="tasktime" style="display: block;">Select Time 🕰️</label>
                        <input type="time" id="tasktime" name="tasktime" style="width: 170px;">
                    </div>

                    <!-- Priority Section -->
                    <div style="margin-top: 10px;">
                        <label for="taskpriority">Priority:</label>
                        <select id="taskpriority" name="taskpriority">
                            <option value="none">⚫ None</option>
                            <option value="High">🔴 High</option>
                            <option value="Medium">🟡 Medium</option>
                            <option value="Low">🟢 Low</option>
                        </select>
                    </div>

                    <!-- <label for="reminder">Set Reminder:</label> -->
                    <select id="reminder" name="reminder_percentage">
                        <option value="" disabled selected>Set Reminder Here 🔔</option>
                        <option value="50">50% (Halfway to Due Date)</option>
                        <option value="75">75% (Closer to Due Date)</option>
                        <option value="90">90% (Near Due Date)</option>
                        <option value="100">100% (On Time)</option>
                    </select>

                    <?php if ($is_admin): ?>
                        <div style="margin-top: 10px;">
                            <label for="assigned_to">Assign To:</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($team_members as $member): ?>
                                    <option value="<?php echo htmlspecialchars($member['userid']); ?>">
                                        <?php echo htmlspecialchars($member['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" style="margin-top: 20px;">Done</button>


            </form>
            </br>

        </div>
    </div>

    <?php if (isset($_SESSION['task_added'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    title: "Task added successfully!",
                    text: "", 
                    timer: 1000,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'small-swal',
                        title: 'small-swal-title',
                        content: 'small-swal-content'
                    }
                });
            });
        </script>

        <style>
            .small-swal {
                width: 200px;
                padding: 20px;
            }

            .small-swal-title {
                font-size: 16px;
                font-weight: bold;
            }

            .small-swal-content {
                font-size: 14px;
            }
        </style>

        <?php unset($_SESSION['task_added']); // Clear the flag after showing the message ?>
    <?php endif; ?>

    <script>
        // Get references to the button and container
        const addTaskButton = document.getElementById('addTaskButton');
        const container = document.querySelector('.container');

        // Add click event listener to the button
        addTaskButton.addEventListener('click', function () {
            // Toggle the 'active' class on the container
            container.classList.toggle('actives');
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const taskDate = document.getElementById('taskdate');
            const taskTime = document.getElementById('tasktime');
            const reminderSelect = document.getElementById('reminder');
            const form = document.querySelector('.add-task-form');

            // Disable reminder initially
            reminderSelect.disabled = true;

            // Get current date and time
            function getCurrentDateTime() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                return {
                    date: `${year}-${month}-${day}`,
                    time: `${hours}:${minutes}`
                };
            }

            function validateDateTime() {
                const current = getCurrentDateTime();
                const selectedDate = taskDate.value;
                const selectedTime = taskTime.value;

                if (selectedDate === current.date) {
                    // If same day, time must be future
                    if (selectedTime <= current.time) {
                        taskTime.value = '';
                        alert("Please select a future time for today's tasks.");
                        return false;
                    }

                }

                if (taskDate.value === current.date) {
                    taskTime.min = current.time; // Set minimum time to current time
                } else {
                    taskTime.removeAttribute('min'); // Allow full range for future dates
                }

                return true;
            }

            function checkDateAndTime() {
                // Enable reminder only if both date and time are set and valid
                const isValid = taskDate.value && taskTime.value && validateDateTime();
                reminderSelect.disabled = !isValid;
                if (reminderSelect.disabled) {
                    reminderSelect.value = ""; // Reset reminder if disabled
                }
            }

            // Event listeners for date and time changes
            taskDate.addEventListener('input', checkDateAndTime);
            taskTime.addEventListener('input', checkDateAndTime);

            // Ensure user selects both valid date and time before setting a reminder
            reminderSelect.addEventListener('change', function () {
                if (!taskDate.value || !taskTime.value || !validateDateTime()) {
                    alert("Please set valid future date and time before selecting a reminder.");
                    this.value = "";  // Clear the reminder selection
                }
            });

            // Prevent form submission if date/time is invalid
            form.addEventListener('submit', function (event) {
                if (!validateDateTime()) {
                    event.preventDefault();  // Prevent form submission
                    alert("Please select a valid future date and time.");
                    return;
                }

                if (reminderSelect.value && (!taskDate.value || !taskTime.value)) {
                    alert("Set both date and time before setting a reminder.");
                    event.preventDefault();  // Prevent form submission
                }
            });
        });

    </script>


    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const allDropdowns = document.querySelectorAll('.priority-dropdown');
            allDropdowns.forEach(el => {
                if (el.id !== id) el.style.display = 'none';
            });
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    </script>
    <!-- IONICONS -->
    <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>

    <!-- MAIN JS -->
    <script src="../js/dash.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>

</html>