<?php
session_start();
date_default_timezone_set('Asia/Kathmandu');
include '../config/database.php';
include '../include/load_username.php';


// Check if user is logged in
if (!isset($_SESSION['userid'])) {
  header("Location: ../users/ signin.php");
  exit();
}

$userid = $_SESSION['userid'];

if (!isset($_GET['taskid'])) {
  echo "Task ID is missing.";
  exit();
}

$taskid = $_GET['taskid'];

// Fetch task details (only if not deleted)
$sql = "SELECT * FROM tasks WHERE taskid = ? AND userid = ? AND is_deleted = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $taskid, $userid);
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

$taskdate = isset($task['taskdate']) ? $task['taskdate'] : '';  
$tasktime = isset($task['tasktime']) ? $task['tasktime'] : ''; 

// Handle Task Update Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $taskname = trim($_POST['taskname']);
    $taskdescription = isset($_POST['taskdescription']) ? trim($_POST['taskdescription']) : null;
    $taskdate = isset($_POST['taskdate']) ? trim($_POST['taskdate']) : null;
    $tasktime = isset($_POST['tasktime']) ? trim($_POST['tasktime']) : null;
    $reminder_percentage = (!empty($_POST['reminder_percentage'])) ? $_POST['reminder_percentage'] : null;


    // Initialize update values with the current data
    $update_values = [
        'taskname' => $taskname,
        'taskdescription' => $taskdescription,
        'taskdate' => $taskdate,
        'tasktime' => $tasktime,
        'reminder_percentage' => $reminder_percentage
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

    // Only execute the update if there are any fields to update
    if (count($fields) > 0) {
        $sql .= implode(", ", $fields) . " WHERE taskid = ? AND userid = ?";
        $params[] = $taskid;
        $params[] = $userid;
        $types .= "ii";

        // Prepare and execute the statement
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: task.php");
            exit();
        } else {
            echo "Error updating task: " . mysqli_error($conn);
        }
    } else {
        // No fields to update
        header("Location: task.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<head>
<html lang="en">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Task</title>
<link rel="stylesheet" href="../css/dash.css">
<link rel="icon" type="image/x-icon" href="../img/favicon.ico">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>

<body id="body-pd">


    <div class="box">
      <h2 style="text-align: center; ">Edit Task</h2>
      <form method="POST" action="" class="add-task-form">
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
    <option value="" <?php if ($reminder_percentage === null || $reminder_percentage === "") echo "selected"; ?>>
        No Reminder 🔕
    </option>
    <option value="50" <?php if ($reminder_percentage == 50) echo "selected"; ?>>50% (Halfway to Due Date)</option>
    <option value="75" <?php if ($reminder_percentage == 75) echo "selected"; ?>>75% (Closer to Due Date)</option>
    <option value="90" <?php if ($reminder_percentage == 90) echo "selected"; ?>>90% (Near Due Date)</option>
    <option value="100" <?php if ($reminder_percentage == 100) echo "selected"; ?>>100% (On Time)</option>
</select>


        <button type="submit">Update Task</button>
      </form>
      <br>
      <a href="task.php" class="back-link">← Back to Task List</a>
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