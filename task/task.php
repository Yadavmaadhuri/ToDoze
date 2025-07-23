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
// Show errors during development (remove in production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Check session properly
$userid = $_SESSION['userid'] ?? null;
if (!$userid) {
    echo "User not logged in.";
    exit;
}

$filter = $_GET['filter'] ?? 'all';
// Initialize $sort_by with a default value if not set
$sort_by = $_GET['sort_by'] ?? '';
$taskname = $taskdescription = $taskdate = $tasktime = $reminder_percentage = "";

// Handle Task Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $taskname = trim($_POST['taskname']);
  $taskdescription = isset($_POST['taskdescription']) ? trim($_POST['taskdescription']) : null;
  $taskdate = (!empty($_POST['taskdate'])) ? ($_POST['taskdate']) : null;
  $tasktime = (!empty($_POST['tasktime'])) ? ($_POST['tasktime']) : null;
  $reminder_percentage = (!empty($_POST['reminder_percentage'])) ? $_POST['reminder_percentage'] : null;

  // echo $tasktime;
  // exit();
  $sql = "INSERT INTO tasks (userid, taskname, taskdescription, taskdate, tasktime, reminder_percentage, taskstatus) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
  $stmt = mysqli_prepare($conn, $sql);

  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "isssss", $userid, $taskname, $taskdescription, $taskdate, $tasktime, $reminder_percentage);
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success_message'] = "Task added successfully!";
      header("Location: task.php"); // Redirect to avoid form resubmission
      exit();
    } else {
      echo "Error executing query: " . mysqli_error($conn);
    }
  } else {
    echo "Error preparing statement: " . mysqli_error($conn);
  }
}



// for overdue part
$now = date('Y-m-d H:i:s');
$update_sql = "UPDATE tasks 
    SET is_overdue = 
        CASE 
            WHEN CONCAT(taskdate, ' ', IFNULL(tasktime, '00:00:00')) < ? 
            THEN 1 
            ELSE 0 
        END 
    WHERE userid = ? AND taskstatus != 'Completed' AND is_deleted = 0 AND teamid IS NULL
";
$update_stmt = mysqli_prepare($conn, $update_sql);
if ($update_stmt) {
  mysqli_stmt_bind_param($update_stmt, "si", $now, $userid);
  mysqli_stmt_execute($update_stmt);
}

// Base SQL query
$sql = "SELECT * FROM tasks WHERE userid = ? AND is_deleted = 0 AND teamid IS NULL";

// Add search functionality
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $search = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (taskname LIKE ? OR taskdescription LIKE ?)";
}

// --- Apply the filter first ---
if ($filter === 'completed') {
  $sql .= " AND taskstatus = 'completed'"; // filter completed tasks
} elseif ($filter === 'overdue') {
  $sql .= " AND is_overdue = 1 AND taskstatus != 'completed'"; // filter overdue tasks
} else {
  // Default filter is pending tasks
  $sql .= " AND taskstatus != 'completed'";
}

// --- Now apply the sorting options ---
switch ($sort_by) {
  case 'priority_high':
    $sql .= " ORDER BY 
                    CASE 
                        WHEN taskpriority = 'High' THEN 1
                        WHEN taskpriority = 'Medium' THEN 2
                        WHEN taskpriority = 'Low' THEN 3
                        ELSE 4
                    END ASC";
    break;
  case 'priority_low':
    $sql .= " ORDER BY 
                    CASE 
                        WHEN taskpriority = 'Low' THEN 1
                        WHEN taskpriority = 'Medium' THEN 2
                        WHEN taskpriority = 'High' THEN 3
                        ELSE 4
                    END ASC";
    break;
  case 'duedate_asc':
    $sql .= " ORDER BY 
        CASE 
            WHEN taskdate IS NOT NULL AND tasktime IS NOT NULL THEN 1
            WHEN taskdate IS NOT NULL AND tasktime IS NULL THEN 2
            WHEN taskdate IS NULL AND tasktime IS NOT NULL THEN 3
            ELSE 4
        END ASC,
        taskdate ASC,
        tasktime ASC";
    break;


case 'duedate_desc':
    $sql .= " ORDER BY 
        CASE 
            WHEN taskdate IS NOT NULL AND taskdate != '0000-00-00' THEN 0
            WHEN tasktime IS NOT NULL AND tasktime != '00:00:00' THEN 1
            ELSE 2
        END ASC,
        taskdate DESC,
        tasktime DESC";
    break;



  case 'created_new':
    $sql .= " ORDER BY taskcreated_at DESC";
    break;
  case 'created_old':
    $sql .= " ORDER BY taskcreated_at ASC";
    break;
  default:
    $sql .= " ORDER BY taskid DESC"; // default order
    break;
}

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);
if (!empty($search)) {
    mysqli_stmt_bind_param($stmt, "iss", $userid, $search, $search);
} else {
    mysqli_stmt_bind_param($stmt, "i", $userid);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task</title>
  <link rel="stylesheet" href="../css/dash.css">
  <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>

<body id="body-pd">
  
  <div class="filter-container">
    <div style="display: flex; justify-content: center;">
      <button id="AddTaskBtn" class="create-btn"> + Add task</button>
    </div>

    <a href="task.php?filter=all&sort_by=<?= urlencode($sort_by) ?>"
      class="task-filter <?= $filter == 'all' ? 'active' : '' ?>">🕒 Pending</a>
    <a href="task.php?filter=completed&sort_by=<?= urlencode($sort_by) ?>"
      class="task-filter <?= $filter == 'completed' ? 'active' : '' ?>">✅ Completed</a>
    <a href="task.php?filter=overdue&sort_by=<?= urlencode($sort_by) ?>"
      class="task-filter <?= $filter == 'overdue' ? 'active' : '' ?>">⏰ Overdue</a>
  </div>

  <!-- sorting -->

<div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 0px; margin-bottom: 5px;">
  <form id="searchForm" method="GET" action="task.php" style="margin: 0; display: flex; align-items: center;">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
    <input type="text" name="search" placeholder="Search tasks..." 
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
           style="padding: 3px 8px; border-radius: 4px; border: 1px solid #ccc; font-size: 12px; min-width: 150px;">
    <button type="submit" style="padding: 2px 4px; margin-left: 5px; border-radius: 4px; border: 1px solid #ccc; background: #fff; cursor: pointer; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
      🔍
    </button>
  </form>

  <form id="sortForm" method="GET" action="task.php" style="margin: 0;">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <!-- <label for="sort_by" style="font-size: 14px; margin-right: 5px;">Sort By:</label> -->
    <select name="sort_by" id="sort_by" onchange="document.getElementById('sortForm').submit();"
      style="padding: 3px 4px; border-radius: 2px; font-size: 12px; min-width: 50px;">
      <option value=""> sort_by </option>
      <option value="priority_high" <?= $sort_by == 'priority_high' ? 'selected' : '' ?>>Priority (High → Low)</option>
      <option value="priority_low" <?= $sort_by == 'priority_low' ? 'selected' : '' ?>>Priority (Low → High)</option>
      <option value="duedate_asc" <?= $sort_by == 'duedate_asc' ? 'selected' : '' ?>>Due Date (Soonest First)</option>
      <option value="duedate_desc" <?= $sort_by == 'duedate_desc' ? 'selected' : '' ?>>Due Date (Latest First)</option>
      <option value="created_new" <?= $sort_by == 'created_new' ? 'selected' : '' ?>>Task Added (Newest First)</option>
      <option value="created_old" <?= $sort_by == 'created_old' ? 'selected' : '' ?>>Task Added (Oldest First)</option>
    </select>
  </form>
</div>


</div>


  <?php
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      $isOverdue = $row['is_overdue'] == 1;
      $isCompleted = strtolower($row['taskstatus']) === 'completed';

      echo "<div class='task' id='task-" . $row['taskid'] . "'>";
      echo "<div class='task-content'>";

      // Tick box (only if not completed)
      if (!$isCompleted) {
        echo "<form action='task_completion.php' method='POST' class='complete-form'>";
        echo "<input type='hidden' name='taskid' value='" . $row['taskid'] . "'>";
        echo "<button type='submit' name='complete-box' class='complete-box' title='Tick to complete'></button>";
        echo "</form>";
      }

      echo "<div class='task-details'>";
      echo "<h4 style='display: inline; margin-right: 10px;";
      if ($isOverdue && !$isCompleted) {
        echo "color: red;";
      }
      echo "'>" . htmlspecialchars($row['taskname']) . "</h4>";

      if ($isOverdue && !$isCompleted) {
        echo "<span style='color: red; font-weight: light;'>(Overdue)</span>";
      }
      echo "<div class='task-info-line'>";
      echo "<div class='task-details-left'>";

      if (!empty($row['taskdescription'])) {
        echo "<div class='task-description'><span class='info'>Description: " . htmlspecialchars($row['taskdescription']) . "</span></div>";
      }
      echo (!empty($row['taskdate']) ? "<span class='info'>DueDate: " . htmlspecialchars(date('Y-m-d', strtotime($row['taskdate']))) . "</span>" : "");
      echo (!empty($row['tasktime']) ? "<span class='info'>DueTime: " . htmlspecialchars(date('H:i', strtotime($row['tasktime']))) . "</span>" : "");
      echo "<span class='info'>Reminder: " . (isset($row['reminder_percentage']) && $row['reminder_percentage'] !== null ? htmlspecialchars($row['reminder_percentage']) . "%" : "Not set") . "</span>";
      echo "</div>";

      // Task Actions (Edit/Delete or Completed Date)
      echo "<div class='task-actions'>";
      if (!$isCompleted) {

        // priority section 
        $currentPriority = $row['taskpriority'] ?? 'none';
        $taskId = $row['taskid'];

        // Map priorities to circle icons
        $priorityIcons = [
          'High' => '🔴',
          'Medium' => '🟡',
          'Low' => '🟢',
          'none' => '⚫'
        ];

        echo "<div class='priority-wrapper' style='display: inline-block; vertical-align: middle; margin-right: 8px;'>";


        // Toggle icon
        echo "<span style='margin-right: 2px;'>Priority:</span>";
        echo "<button type='button' class='priority-toggle' onclick=\"toggleDropdown('dropdown-$taskId')\" title='Priority' style='background: none; border: none; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: auto; height: auto; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;'>";
        echo $priorityIcons[$currentPriority];
        echo "</button>";


        // Hidden dropdown form
        echo "<form method='POST' action='update_priority.php' class='priority-dropdown' id='dropdown-$taskId' style='background: none; border: none; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: auto; height: auto; cursor: pointer; display: none; align-items: center; justify-content: center;'>";
        echo "<input type='hidden' name='taskid' value='" . $taskId . "'>";
        echo "<select name='taskpriority' onchange='this.form.submit();'>";
        foreach ($priorityIcons as $key => $icon) {
          $selected = ($currentPriority === $key) ? 'selected' : '';
          echo "<option value='$key' $selected>$icon $key</option>";
        }
        echo "</select>";
        echo "</form>";

        echo "</div>";
        // priority section ends
  

        echo "<a href='edit_task.php?taskid=" . $row['taskid'] . "' class='edit-btn' title='Edit'><ion-icon name='create-outline'></ion-icon>Edit</a>";
        echo "<a href='#' class='delete-btn'   title='Delete'  data-taskid='" . $row['taskid'] . "'><ion-icon name='trash-outline'></ion-icon>Delete</a>";
      } else {
        if (!empty($row['completed_at'])) {
          echo "<span class='info' style='color: green;'><ion-icon name='checkmark-done-outline'></ion-icon> Completed on: " . date('Y-m-d H:i', strtotime($row['completed_at'])) . "</span>";
        }
        echo "<a href='#' title='Delete' class='delete-btn' data-taskid='" . $row['taskid'] . "' ><ion-icon  name='trash-outline'></ion-icon>Delete</a>";
      }

      echo "</div>"; // task-actions
  
      echo "</div></div></div></div>"; // task-info-line, task-details, task-content, task
    }
  } else {
    echo '
<div class="centered-content">
  <div class="content-wrapper">
    <img src="img/notask.png" alt="No tasks yet" />
    <h3><p>No tasks yet 🚀</p></h3>
  </div>
</div>';
  }
  ?>

  <div class="container">

    <!-- Modal -->
    <div id="TaskModal" class="modal-overlay" style="display: none;">
      <div class="modal-content">
        <span class="close-modal" id="closeModalBtn">&times;</span>
        <h2 style="text-align: center;">Add Task Here </h2>
        <form class="add-task-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
          <!-- <label for="taskname">Task Name:</label> -->
          <input type="text" id="taskname" name="taskname" placeholder="Add task here" maxlength="50" required>

          <!-- <label for="taskDescription">Task Description:</label> -->
          <input type="text" id="taskDescription" name="taskdescription" placeholder="Task Description" maxlength="140"
            style="height: 80px;">
          <div>
            <!-- Date Section -->
            <div style="display: inline-block; vertical-align: top; margin-right: 20px;">
              <label for="taskdate" style="display: block;">Select Due Date 📅</label>
              <input type="date" id="taskdate" name="taskdate" style="width: 170px;">
            </div>

            <!-- Time Section -->
            <div style="display: inline-block; vertical-align: top;">
              <label for="tasktime" style="display: block;">Select Time 🕰️</label>
              <input type="time" id="tasktime" name="tasktime" style="width: 170px;">
            </div>
          </div>
          <!-- <label for="reminder">Set Reminder:</label> -->
          <select id="reminder" name="reminder_percentage">
            <option value="" disabled selected>Set Reminder Here 🔔</option>
            <option value="50">50% (Halfway to Due Date)</option>
            <option value="75">75% (Closer to Due Date)</option>
            <option value="90">90% (Near Due Date)</option>
            <option value="100">100% (On Time)</option>
          </select>
          <button type="submit" style="margin-top: 20px; border-radius: 20px;">Done</button>
        </form>
      </div>
    </div>
  </div>
  </div>




  <?php if (isset($_SESSION['success_message']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
          title: "<?php echo $_SESSION['success_message']; ?>",
          text: "", // Empty text since you only want the success message
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

    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>


  <script>
    // Get references to the button and container
    const addTaskButton = document.getElementById('AddTaskButton');
    const container = document.querySelector('.container');

    // Add click event listener to the button
    addTaskButton.addEventListener('click', function () {
      // Toggle the 'active' class on the container
      container.classList.toggle('actives');
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
      const taskDate = document.getElementById('taskdate');
      const taskTime = document.getElementById('tasktime');
      const reminderSelect = document.getElementById('reminder');
      const form = document.querySelector('.add-task-form');

      // Disable reminder initially
      function checkDateAndTime() {
        const disableReminder = !(taskDate.value && taskTime.value);
        reminderSelect.disabled = disableReminder;
        if (disableReminder) reminderSelect.value = "";
      }

      // Run check on load and on input
      checkDateAndTime();
      taskDate.addEventListener('input', checkDateAndTime);
      taskTime.addEventListener('input', checkDateAndTime);

      // Prevent setting reminder without date & time (extra safety)
      reminderSelect.addEventListener('change', function () {
        if (!taskDate.value || !taskTime.value) {
          alert("Please set both date and time before choosing a reminder.");
          this.value = "";
        }
      });

      // Prevent form submit if reminder is set but date/time is missing
      form.addEventListener('submit', function (event) {
        if (reminderSelect.value && (!taskDate.value || !taskTime.value)) {
          alert("You must select both date and time if you want to set a reminder.");
          event.preventDefault();
        }
      });
    });
  </script>
  <!-- for filters -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Optional: If "showFiltersBtn" exists
      const showFiltersBtn = document.getElementById("showFiltersBtn");
      const taskCategories = document.getElementById("taskCategories");
      if (showFiltersBtn && taskCategories) {
        showFiltersBtn.addEventListener("click", function () {
          if (taskCategories.style.display === "none" || taskCategories.style.display === "") {
            taskCategories.style.display = "flex";
            this.textContent = "Hide Filters";
          } else {
            taskCategories.style.display = "none";
            this.textContent = "Show Filters";
          }
        });
      }

      document.querySelectorAll('.delete-btn').forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          var taskid = this.getAttribute('data-taskid');
          Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = 'delete_task.php?taskid=' + taskid;
            }
          });
        });
      });

      document.querySelectorAll(".complete-form").forEach(function (form) {
        form.addEventListener("submit", function (e) {
          e.preventDefault();
          Swal.fire({
            text: "Task completed?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes"
          }).then((result) => {
            if (result.isConfirmed) {
              form.submit();
            }
          });
        });
      });
    });
  </script>

  <!-- for floooting  -->
  <script>
    const modal = document.getElementById("TaskModal");
    const openBtn = document.getElementById("AddTaskBtn");
    const closeBtn = document.getElementById("closeModalBtn");

    // Open modal
    openBtn.addEventListener("click", () => {
      modal.style.display = "flex";
      openBtn.style.display = "none";
    });

    // Close modal
    closeBtn.addEventListener("click", closeModal);

    // Close modal when clicking outside the content
    window.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });

    function closeModal() {
      modal.style.display = "none";
      openBtn.style.display = "inline-block"; // or "block" based on styling
    }
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const descriptions = document.querySelectorAll('.task-details-left .info');

      descriptions.forEach(desc => {
        if (desc.textContent.startsWith("Description:")) {
          const fullText = desc.textContent.trim().replace("Description:", "").trim();
          if (fullText.length > 20) {
            const shortText = fullText.substring(0, 20) + "..........";

            let toggled = false;
            desc.textContent = "Description: " + shortText;
            desc.classList.add("truncated");

            desc.addEventListener("click", function () {
              toggled = !toggled;
              desc.textContent = "Description: " + (toggled ? fullText : shortText);
            });
          }
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

      // Toggle the selected dropdown
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';

      // Prevent multiple listeners
      document.removeEventListener('click', handleOutsideClick);
      setTimeout(() => {
        document.addEventListener('click', handleOutsideClick);
      }, 0);

      function handleOutsideClick(e) {
        // If the click is outside any .priority-dropdown and .priority-toggle
        if (!dropdown.contains(e.target) && !e.target.closest('.priority-toggle')) {
          dropdown.style.display = 'none';
          document.removeEventListener('click', handleOutsideClick);
        }
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
  



</body>

</html>