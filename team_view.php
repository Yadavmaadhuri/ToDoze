<?php
session_start();
include 'config/database.php';
include 'load_username.php';

if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
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
$sql = "SELECT * FROM tasks WHERE teamid = ? AND is_deleted = 0 AND taskstatus='pending' ORDER BY taskid DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// mysqli_close($conn);

$now = date('Y-m-d H:i:s');
$update_sql = "UPDATE tasks
    SET is_overdue = 
        CASE 
            WHEN CONCAT(taskdate, ' ', IFNULL(tasktime, '00:00:00')) < ? 
            THEN 1 
            ELSE 0 
        END 
    WHERE userid = ? AND taskstatus != 'Completed' AND is_deleted = 0 AND teamid = ?
";
$update_stmt = mysqli_prepare($conn, $update_sql);
if ($update_stmt) {
    mysqli_stmt_bind_param($update_stmt, "sii", $now, $userid, $teamid);
    mysqli_stmt_execute($update_stmt);
}


// --- Task query based on filter ---
// Get the filter from URL if set (default to pending)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';

switch ($filter) {
    case 'completed':
        $sql = "SELECT * FROM tasks 
                WHERE teamid = ? 
                AND taskstatus = 'completed' AND is_deleted = 0 
                ORDER BY completed_at DESC";
        break;
    
    case 'overdue':
        $sql = "SELECT * FROM tasks 
                WHERE teamid = ? 
                AND is_overdue = 1 AND is_deleted = 0 AND taskstatus != 'completed' 
                ORDER BY taskid DESC";
        break;

    case 'pending':
    default:
        $sql = "SELECT * FROM tasks 
                WHERE teamid = ? 
                AND taskstatus != 'completed' AND is_deleted = 0 
                ORDER BY taskid DESC";
        break;
}

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    // Adjusted to bind only the teamId, not the userId
    mysqli_stmt_bind_param($stmt, "i", $teamId);  // Bind only the teamId
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    echo "Error preparing filtered task statement: " . mysqli_error($conn);
    exit();
}


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
    <link rel="stylesheet" href="css/dash.css">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>
    
<body id="body-pd">
<div class="top-bar">

    <div class="top-right-icons">
      <!-- Notification Icon -->
      <a href="invitation.php" class="top-icon">
        <ion-icon name="notifications-outline"></ion-icon>
      </a>
      
         <!-- Profile Icon -->
         <div class="profile-info">
  <a href="#" class="profile-circle" title="<?= htmlspecialchars($username) ?>">
    <ion-icon name="person-outline"></ion-icon>
  </a>
  <span class="username-text"><?= htmlspecialchars($username) ?></span>
</div>
    </div>
  </div>

  <!-- Logo Above Sidebar -->
  <div class="logo-container">
    <img src="img/logo.png" alt="Logo" class="logo">
  </div>

  <!-- Sidebar Navigation -->
  <div class="l-navbar" id="navbar">
    <nav class="nav">
      <div class="nav__list">
        <a href="dash.php" class="nav__link ">
          <ion-icon name="home-outline" class="nav__icon"></ion-icon>
          <span class="nav__name">Home</span>
        </a>
        <a href="task.php" class="nav__link">
          <ion-icon name="add-outline" class="nav__icon"></ion-icon>
          <span class="nav__name">Task</span>
        </a>
        <a href="team.php" class="nav__link active">
          <ion-icon name="folder-outline" class="nav__icon"></ion-icon>
          <span class="nav__name">Team </span>
        </a>
        <a href="review.php" class="nav__link">
          <ion-icon name="chatbox-ellipses-outline" class="nav__icon"></ion-icon>
          <span class="nav__name">Review</span>
        </a>
      </div>
      <a href="logout.php" class="nav__link logout">
        <ion-icon name="log-out-outline" class="nav__icon"></ion-icon>
        <span class="nav__name" style="color: #d96c4f;"><b>Log Out</b></span>
      </a>
    </nav>
  </div>

  <div class="team-box">
  <div class="team-header">
    <h2 class="team-name"><?php echo htmlspecialchars($team['teamname']); ?></h2>
  </div>
  <p style="font-size: small;"><strong>Description:</strong> <?php echo htmlspecialchars($team['teamdescription']); ?></p>
  <p><strong>Due Date:</strong> <?php echo htmlspecialchars($team['teamduedate']); ?></p>

  <div class="icons" style="display: flex; gap: 20px; margin-top: 15px;">
    <div class="team-actions">
      <?php if ($user_role === 'Admin'): ?>
        <a href="team_task.php?teamid=<?php echo $teamId; ?>" class="edit-btn" title="Edit">
          <ion-icon name="add-circle-outline"></ion-icon> Task
        </a>
        <a href="member.php?teamid=<?php echo $teamId; ?>" class="edit-btn" title="Edit">
          <ion-icon name="people-outline"></ion-icon> Member
        </a>
      <?php else: ?>
        <span class="view-only-msg">🔒 View Only</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="filter-container">
  <div style="display: flex; justify-content: center;">
</div>
<a href="team_view.php?teamid=<?= $teamId ?>&filter=pending" class="task-filter <?= $filter == 'pending' ? 'active' : '' ?>">🕒 Pending Tasks</a>
<a href="team_view.php?teamid=<?= $teamId ?>&filter=completed" class="task-filter <?= $filter == 'completed' ? 'active' : '' ?>">✅ Completed Tasks</a>
<a href="team_view.php?teamid=<?= $teamId ?>&filter=overdue" class="task-filter <?= $filter == 'overdue' ? 'active' : '' ?>">⏰ Overdue Tasks</a>
</div>
</div>
<style>
  .team-box {
  border: 1px solid #ccc;
  /* border-radius: 10px; */
  padding: 10px;
  background: #fff;
  margin-bottom: 10px;
  margin-left: 50px;
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

.view-only-msg {
  color: #888;
  font-style: italic;
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
        if (!$isCompleted && $assignedTo == $currentUserId) {
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
            // Admin can edit and delete
            if (!$isCompleted) {
                echo "<a href='editteam_task.php?teamid=" . $teamId . "&taskid=" . $row['taskid'] . "' class='edit-btn'><ion-icon name='create-outline'></ion-icon> Edit</a>";
            }
            echo "<a href='#' class='delete-btn' data-taskid='" . $row['taskid'] . "'><ion-icon name='trash-outline'></ion-icon> Delete</a>";
        } elseif ($isCompleted && $assignedTo == $currentUserId) {
            // Assigned user sees completed date
            if (!empty($row['completed_at'])) {
                echo "<span class='info' style='color: green;'><ion-icon name='checkmark-done-outline'></ion-icon> Completed on: " . date('Y-m-d H:i', strtotime($row['completed_at'])) . "</span>";
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
 <!-- IONICONS -->
 <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>

<!-- MAIN JS -->
<script src="js/dash.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>