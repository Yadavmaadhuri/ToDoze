<?php
session_start();
date_default_timezone_set('Asia/Kathmandu');
include '../config/database.php';
include '../iclude/load_username.php';


if (!isset($_SESSION['userid'])) {
    header("Location: ../users/signin.php");
    exit();
}

$userid = $_SESSION['userid'];
$teamId = isset($_GET['teamid']) ? (int) $_GET['teamid'] : null;

if (!$teamId) {
    echo "Project not found!";
    exit();
}

// Fetch team details
$sql = "SELECT * FROM teams WHERE teamid = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$team = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$team) {
    echo "Project not found!";
    exit();
}

// Fetch tasks
$sql = "SELECT * FROM tasks WHERE teamid = ? AND is_deleted = 0 AND taskstatus='Pending' ORDER BY taskid DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// mysqli_close($conn);

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
    mysqli_stmt_bind_param($update_stmt, "si", $now, $teamId);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
}

// --- Task query based on filter ---
// Get the filter from URL if set (default to pending)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';

// Function to get user initials
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

// Fetch tasks with assigned user information
switch ($filter) {
    case 'completed':
        $sql = "SELECT t.*, u.username as assigned_username 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.userid 
                WHERE t.teamid = ? 
                AND t.taskstatus = 'Completed' AND t.is_deleted = 0";
        break;
    
    case 'overdue':
        $sql = "SELECT t.*, u.username as assigned_username 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.userid 
                WHERE t.teamid = ? 
                AND t.is_overdue = 1 AND t.is_deleted = 0 AND t.taskstatus != 'Completed'";
        break;

    case 'pending':
    default:
        $sql = "SELECT t.*, u.username as assigned_username 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.userid 
                WHERE t.teamid = ? 
                AND t.taskstatus != 'Completed' AND t.is_deleted = 0";
        break;
}

// Add search functionality
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $search = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (t.taskname LIKE ? OR t.taskdescription LIKE ?)";
}

// Add sorting to the query
$sort_by = $_GET['sort_by'] ?? '';
switch ($sort_by) {
    case 'priority_high':
        $sql .= " ORDER BY 
                CASE 
                    WHEN t.taskpriority = 'High' THEN 1
                    WHEN t.taskpriority = 'Medium' THEN 2
                    WHEN t.taskpriority = 'Low' THEN 3
                    ELSE 4
                END ASC";
        break;
    case 'priority_low':
        $sql .= " ORDER BY 
                CASE 
                    WHEN t.taskpriority = 'Low' THEN 1
                    WHEN t.taskpriority = 'Medium' THEN 2
                    WHEN t.taskpriority = 'High' THEN 3
                    ELSE 4
                END ASC";
        break;
    case 'duedate_asc':
        $sql .= " ORDER BY 
            CASE 
                WHEN t.taskdate IS NOT NULL AND t.tasktime IS NOT NULL THEN 1
                WHEN t.taskdate IS NOT NULL AND t.tasktime IS NULL THEN 2
                WHEN t.taskdate IS NULL AND t.tasktime IS NOT NULL THEN 3
                ELSE 4
            END ASC,
            t.taskdate ASC,
            t.tasktime ASC";
        break;
    case 'duedate_desc':
        $sql .= " ORDER BY 
            CASE 
                WHEN t.taskdate IS NOT NULL AND t.taskdate != '0000-00-00' THEN 0
                WHEN t.tasktime IS NOT NULL AND t.tasktime != '00:00:00' THEN 1
                ELSE 2
            END ASC,
            t.taskdate DESC,
            t.tasktime DESC";
        break;
    case 'created_new':
        $sql .= " ORDER BY t.taskcreated_at DESC";
        break;
    case 'created_old':
        $sql .= " ORDER BY t.taskcreated_at ASC";
        break;
    default:
        $sql .= " ORDER BY t.taskid DESC"; // default order
        break;
}

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);
if (!empty($search)) {
    mysqli_stmt_bind_param($stmt, "iss", $teamId, $search, $search);
} else {
    mysqli_stmt_bind_param($stmt, "i", $teamId);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


// fetching role 
$role_sql = "SELECT role FROM team_members WHERE userid = ? AND teamid = ?";
$role_stmt = mysqli_prepare($conn, $role_sql);
mysqli_stmt_bind_param($role_stmt, "ii", $userid, $teamId);
mysqli_stmt_execute($role_stmt);
$role_result = mysqli_stmt_get_result($role_stmt);
$user_role_data = mysqli_fetch_assoc($role_result);
$user_role = $user_role_data['role'] ?? 'Member'; // default to Member if role not found





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['teamname']); ?></title>
    <link rel="stylesheet" href="../css/dash.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>
<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>
    
<body id="body-pd">


 

  <div class="team-box">
  <div class="team-header">
    <h2 class="team-name"><?php echo htmlspecialchars($team['teamname']); ?></h2>
  </div>
  <p style="font-size: small;"><strong>Description:</strong> <?php echo htmlspecialchars($team['teamdescription']); ?></p>


    <!-- Team actions section -->
  <!-- <div class="icons" > -->
    <div class="tea-actions" style="justify-content: flex-start;">
<?php if ($user_role === 'Admin'): ?>
  <!-- Admin sees the Add Task button -->
  <a href="team_task.php?teamid=<?= $teamId; ?>" class="edit-btn" title="Add Task">
    <ion-icon name="add-circle-outline"></ion-icon> Task
  </a>
<?php endif; ?>


      <a href="member.php?teamid=<?php echo $teamId; ?>" class="edit-btn" title="Add Member">
        <ion-icon name="people-outline"></ion-icon> Member
      </a>

      <a href="teamreport.php?teamid=<?php echo $teamId; ?>" class="edit-btn" title="Team Report">
        <ion-icon name="stats-chart-outline"></ion-icon> Report
      </a>

    </div>
  <!-- </div> -->
  <div class="filter-container">
  <div style="display: flex; justify-content: center; margin-bottom: 10px;">
  </div>
  <a href="team_view.php?teamid=<?= $teamId ?>&filter=pending" class="task-filter <?= $filter == 'pending' ? 'active' : '' ?>">🕒 Pending Tasks</a>
  <a href="team_view.php?teamid=<?= $teamId ?>&filter=completed" class="task-filter <?= $filter == 'completed' ? 'active' : '' ?>">✅ Completed Tasks</a>
  <a href="team_view.php?teamid=<?= $teamId ?>&filter=overdue" class="task-filter <?= $filter == 'overdue' ? 'active' : '' ?>">⏰ Overdue Tasks</a>

  <!-- Add search and sort container -->
  <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin: 10px;">
    <form id="searchForm" method="GET" action="team_view.php" style="margin: 0; display: flex; align-items: center;">
      <input type="hidden" name="teamid" value="<?= $teamId ?>">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <input type="hidden" name="sort_by" value="<?= htmlspecialchars($_GET['sort_by'] ?? '') ?>">
      <input type="text" name="search" placeholder="Search tasks..." 
             value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
             style="padding: 5px 8px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px; min-width: 150px;">
      <button type="submit" style="padding: 2px 4px; margin-left: 5px; border-radius: 4px; border: 1px solid #ccc; background: #fff; cursor: pointer; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
        🔍
      </button>
    </form>

    <form id="sortForm" method="GET" action="team_view.php" style="margin: 0;">
      <input type="hidden" name="teamid" value="<?= $teamId ?>">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <select name="sort_by" id="sort_by" onchange="document.getElementById('sortForm').submit();"
        style="padding: 5px; border-radius: 4px; font-size: 14px;">
      <option value="" <?= empty($_GET['sort_by']) ? 'selected' : '' ?>>Sort By</option>
      <option value="priority_high" <?= ($_GET['sort_by'] ?? '') == 'priority_high' ? 'selected' : '' ?>>Priority (High → Low)</option>
      <option value="priority_low" <?= ($_GET['sort_by'] ?? '') == 'priority_low' ? 'selected' : '' ?>>Priority (Low → High)</option>
      <option value="duedate_asc" <?= ($_GET['sort_by'] ?? '') == 'duedate_asc' ? 'selected' : '' ?>>Due Date (Earliest)</option>
      <option value="duedate_desc" <?= ($_GET['sort_by'] ?? '') == 'duedate_desc' ? 'selected' : '' ?>>Due Date (Latest)</option>
      <option value="created_new" <?= ($_GET['sort_by'] ?? '') == 'created_new' ? 'selected' : '' ?>>Created (Newest)</option>
      <option value="created_old" <?= ($_GET['sort_by'] ?? '') == 'created_old' ? 'selected' : '' ?>>Created (Oldest)</option>
    </select>
    </form>
  </div>
</div>
</div>
<style>
  .team-box {
  border: 1px solid #ccc;
  padding: 5px;
  background: #fff;
  margin-bottom: 8px;
  margin-left: 10px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.team-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.team-name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 20px;
  margin: 0 0 10px 0;
  color: #333;
}
.tea-actions {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-start;
  margin-top: 15px;
   margin-bottom: 0px;
}

.view-only-msg {
  color: #888;
  font-style: italic;
}

.assigned-member {
    display: inline-block;
    background-color: #e0e0e0;
    color: #333;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    text-align: center;
    line-height: 32px;
    font-size: 14px;
    margin-right: 8px;
    cursor: help;
    position: relative;
    transition: all 0.2s ease;
}

.assigned-member:hover::after {
    content: attr(data-fullname);
    position: absolute;
    background-color: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    left: 50%;
    transform: translateX(-50%);
    top: -30px;
}

.assigned-member.unassigned {
    background-color: transparent;
    font-size: 20px;
    line-height: 32px;
}

</style>



<!-- Displaying the tasks -->

<?php
if ($result && mysqli_num_rows($result) > 0) {
    $currentUserId = $userid; // From session

    while ($row = mysqli_fetch_assoc($result)) {
        $isOverdue = $row['is_overdue'] == 1;
        $isCompleted = strtolower($row['taskstatus']) === 'completed';
        $assignedTo = $row['assigned_to']; // Get assigned user
    
        echo "<div class='task' id='task-" . $row['taskid'] . "'>";
        echo "<div class='task-content'>";
    
        // Show tick box only if the user is assigned to this task and not completed
        if (!$isCompleted && ($assignedTo == $currentUserId)) {
            echo "<form action='task_completion.php' method='POST' class='complete-form'>";
            echo "<input type='hidden' name='taskid' value='" . $row['taskid'] . "'>";
            echo "<button type='submit' name='complete-box' class='complete-box' title='Tick to complete'></button>";
            echo "</form>";
        }
    
        echo "<div class='task-details'>";
        echo "<h4 style='display: inline; margin-right: 10px;";
        if ($isOverdue && !$isCompleted) echo "color: red;";
        echo "'>" . htmlspecialchars($row['taskname']) . "</h4>";
    
        if ($isOverdue && !$isCompleted) {
            echo "<span style='color: red;'>(Overdue)</span>";
        }
    
        echo "<div class='task-info-line'><div class='task-details-left'>";
        if (!empty($row['taskdescription'])) {
            echo "<div class='task-description'><span class='info'>Description: " . htmlspecialchars($row['taskdescription']) . "</span></div>";
        }
        
        echo (!empty($row['taskdate']) ? "<span class='info'>DueDate: " . htmlspecialchars(date('Y-m-d', strtotime($row['taskdate']))) . "</span>" : "");
        echo (!empty($row['tasktime']) ? "<span class='info'>DueTime: " . htmlspecialchars(date('H:i', strtotime($row['tasktime']))) . "</span>" : "");
        echo "<span class='info'>Reminder: " . (isset($row['reminder_percentage']) ? htmlspecialchars($row['reminder_percentage']) . "%" : "Not set") . "</span>";
        echo "</div>"; // task-details-left

        echo "<div class='task-actions'>";
     
        if ($user_role === 'Admin') {
            if ($isCompleted) {
                // For completed tasks, show completion date, assigned user, and delete button
                if (!empty($row['completed_at'])) {
                    echo "<span class='info' style='color: green;'><ion-icon name='checkmark-done-outline'></ion-icon> Completed on: " . date('Y-m-d H:i', strtotime($row['completed_at'])) . "</span>";
                    if (!empty($row['assigned_username'])) {
                        echo "<span class='info' style='margin-left: 10px;'><ion-icon name='person-outline'></ion-icon> Completed by: " . htmlspecialchars($row['assigned_username']) . "</span>";
                    }
                }
                echo "<a href='#' class='delete-btn' title='Delete' data-taskid='" . $row['taskid'] . "'><ion-icon name='trash-outline'></ion-icon>Delete</a>";
            } else {
                // For non-completed tasks, show all controls
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
                echo "<span style='margin-right: 2px;'>Priority:</span>";
                echo "<button type='button' class='priority-toggle' onclick=\"toggleDropdown('dropdown-$taskId')\" title='Priority' style='background: none; border: none; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: auto; height: auto; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;'>"; 
                echo $priorityIcons[$currentPriority];
                echo "</button>";

                echo "<form method='POST' action='update_priority.php' class='priority-dropdown' id='dropdown-$taskId'  style='background: none; border: none; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: auto; height: auto; cursor: pointer; display: none; align-items: center; justify-content: center;'>";
                echo "<input type='hidden' name='taskid' value='" . $taskId . "'>";
                echo "<select name='taskpriority' onchange='this.form.submit();'>";
                foreach ($priorityIcons as $key => $icon) {
                    $selected = ($currentPriority === $key) ? 'selected' : '';
                    echo "<option value='$key' $selected>$icon $key</option>";
                }
                echo "</select>";
                echo "</form>";
                echo "</div>";

                // Assignment section for admin
                if (empty($row['assigned_username'])) {
                    echo "<span class='assigned-member unassigned'>👤</span>";
                } else {
                    $initials = getInitials($row['assigned_username']);
                    echo "<span class='assigned-member' data-fullname='Assigned to: " . htmlspecialchars($row['assigned_username']) . "'>" . htmlspecialchars($initials) . "</span>";
                }
                echo "<a href='editteam_task.php?teamid=" . $teamId . "&taskid=" . $row['taskid'] . "' class='edit-btn' title='Edit'><ion-icon name='create-outline'></ion-icon>Edit</a>";
                echo "<a href='#' class='delete-btn' title='Delete' data-taskid='" . $row['taskid'] . "'><ion-icon name='trash-outline'></ion-icon>Delete</a>";
            }
        } else {
            // Non-admin view - show priority and assigned member in read-only mode
            if ($isCompleted) {
                // Show completion date for completed tasks
                if (!empty($row['completed_at'])) {
                    echo "<span class='info' style='color: green;'><ion-icon name='checkmark-done-outline'></ion-icon> Completed on: " . date('Y-m-d H:i', strtotime($row['completed_at'])) . "</span>";
                    if (!empty($row['assigned_username'])) {
                        echo "<span class='info' style='margin-left: 10px;'><ion-icon name='person-outline'></ion-icon> Completed by: " . htmlspecialchars($row['assigned_username']) . "</span>";
                    }
                }
            } else {
                // Show priority in read-only mode
                $currentPriority = $row['taskpriority'] ?? 'none';
                $priorityIcons = [
                    'High' => '🔴',
                    'Medium' => '🟡',
                    'Low' => '🟢',
                    'none' => '⚫'
                ];
                
                echo "<div class='priority-wrapper' style='display: inline-block; vertical-align: middle; margin-right: 8px;'>"; 
                echo "<span style='margin-right: 2px;'>Priority: " . $priorityIcons[$currentPriority] . " " . $currentPriority . "</span>";
                echo "</div>";

                // Show assigned member in read-only mode
                if (!empty($row['assigned_username'])) {
                    $initials = getInitials($row['assigned_username']);
                    echo "<span class='assigned-member' data-fullname='Assigned to: " . htmlspecialchars($row['assigned_username']) . "'>" . htmlspecialchars($initials) . "</span>";
                } else {
                    echo "<span class='assigned-member unassigned'>👤</span>";
                }
            }
        }
        echo "</div>"; // task-actions
    
        echo "</div></div></div></div>"; // Close all divs
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


<!-- too toggll full test  -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const descriptions = document.querySelectorAll('.task-details-left .info');

  descriptions.forEach(desc => {
    if (desc.textContent.startsWith("Description:")) {
      const fullText = desc.textContent.trim().replace("Description:", "").trim();
      if (fullText.length > 8) {
        const shortText = fullText.substring(0, 8) + "..........";

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
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.exit-team').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault(); // Prevent default link behavior
      const teamId = this.getAttribute('data-teamid');
      
      Swal.fire({
        title: 'Are you sure?',
        text: "You will leave the team.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, leave it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Redirect to the PHP exit URL
          window.location.href = `exit_team.php?teamid=${teamId}`;
        }
      });
    });
  });
});
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

<style>
.priority-dropdown {
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 5px;
    z-index: 1000;
    margin-top: 5px;
}

.priority-dropdown select {
    width: 100%;
    padding: 5px;
    border: none;
    background: none;
    cursor: pointer;
}

.priority-dropdown select:focus {
    outline: none;
}

.priority-wrapper {
    position: relative;
}

.priority-toggle {
    cursor: pointer;
}
</style>

<!-- IONICONS -->
<script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>

<!-- MAIN JS -->
<script src="../js/dash.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>