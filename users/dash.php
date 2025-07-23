<?php
session_start();
date_default_timezone_set('Asia/Kathmandu');
include '../config/database.php';
include '../include/load_username.php';



// Safe session check
if (!isset($_SESSION['userid'])) {
  header("Location: ../users/signin.php");
  exit();
}

$userid = $_SESSION['userid'];


// Initialize variables with default values
$totalTasks = 0;
$pendingTasks = 0;
$completedTasks = 0;
$overdueTasks = 0;

try {
  // Fetch task statistics for the logged-in user
  $sqlTotalTasks = "SELECT COUNT(*) as total FROM tasks WHERE userid = ? AND is_deleted = 0";
  $sqlPendingTasks = "SELECT COUNT(*) as pending FROM tasks WHERE userid = ? AND taskstatus = 'Pending'AND is_deleted = 0";
  $sqlCompletedTasks = "SELECT COUNT(*) as completed FROM tasks WHERE userid = ? AND taskstatus = 'Completed' AND is_deleted = 0";
  $sqlOverdueTasks = "SELECT COUNT(*) as overdue FROM tasks WHERE userid = ? AND is_overdue = 1 AND is_deleted = 0";

  // Total Tasks
  $stmtTotal = $conn->prepare($sqlTotalTasks);
  $stmtTotal->bind_param("i", $userid);
  $stmtTotal->execute();
  $resultTotal = $stmtTotal->get_result();
  if ($row = $resultTotal->fetch_assoc()) {
    $totalTasks = $row['total'];
  }
  $stmtTotal->close();

  // Pending Tasks
  $stmtPending = $conn->prepare($sqlPendingTasks);
  $stmtPending->bind_param("i", $userid);
  $stmtPending->execute();
  $resultPending = $stmtPending->get_result();
  if ($row = $resultPending->fetch_assoc()) {
    $pendingTasks = $row['pending'];
  }
  $stmtPending->close();

  // Completed Tasks
  $stmtCompleted = $conn->prepare($sqlCompletedTasks);
  $stmtCompleted->bind_param("i", $userid);
  $stmtCompleted->execute();
  $resultCompleted = $stmtCompleted->get_result();
  if ($row = $resultCompleted->fetch_assoc()) {
    $completedTasks = $row['completed'];
  }
  $stmtCompleted->close();
  $stmtOverdue = $conn->prepare($sqlOverdueTasks);
  $stmtOverdue->bind_param("i", $userid);
  $stmtOverdue->execute();
  $resultOverdue = $stmtOverdue->get_result();
  if ($row = $resultOverdue->fetch_assoc()) {
    $overdueTasks = $row['overdue'];
  }
  $stmtOverdue->close();





} catch (Exception $e) {
  // Log error but don't show to user
  error_log("Database error: " . $e->getMessage());
  // You might want to set default values here if the queries fail
}




// For current month daily stats
$currentYear = date('Y');
$currentMonth = date('m');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

$dailyLabels = [];
$dailyTotal = [];
$dailyCompleted = [];
$dailyPending = [];

for ($day = 1; $day <= $daysInMonth; $day++) {
  $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
  $dailyLabels[] = date('M j', strtotime($date)); // e.g., "May 7"

  // Total tasks
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE  is_deleted = 0 AND userid = ?  AND DATE(taskcreated_at) = ?");
  $stmt->bind_param("is", $userid, $date);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $dailyTotal[] = $res['count'];
  $stmt->close();

  // Completed tasks
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE userid = ? AND taskstatus = 'Completed'AND is_deleted = 0 AND DATE(taskcreated_at) = ?");
  $stmt->bind_param("is", $userid, $date);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $dailyCompleted[] = $res['count'];
  $stmt->close();

  // Pending tasks
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE userid = ? AND taskstatus = 'Pending'AND is_deleted = 0 AND DATE(taskcreated_at) = ?");
  $stmt->bind_param("is", $userid, $date);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $dailyPending[] = $res['count'];
  $stmt->close();
  //  tasks
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE userid = ? AND is_overdue = 1 AND is_deleted = 0 AND DATE(taskcreated_at) = ?");
  $stmt->bind_param("is", $userid, $date);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $dailyoverdue[] = $res['count'];
  $stmt->close();
}

?>
<!DOCTYPE html>
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/dash.css" />
    <link rel="stylesheet" href="../css/extra.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico" />
    <title>Dashboard</title>
    <style>
      /* Makes cards sit side-by-side properly */
.top-row {
  display: flex;
  gap: 20px;
}

/* Contains both cards equally */
.chart-card,
.task-card {
  flex: 1;
  min-width: 0;
}

/* Fixes the chart height */
.chart-wrapper {
  height: 300px; /* Set fixed height */
  position: relative;
}

/* Contains the task list */
#task-details {
  max-height: 250px;
  overflow-y: auto;
}
    </style>
  </head>

  <?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>


    <!-- Task Summary Section -->
    <div class="bottom-container-wrapper">
      <!-- Task Statistics at the top -->
      <div class="bottom-container">
        <h2>Task Statistics</h2>
        <div class="stats-grid">
          <div class="stat-item"style="background-color: lightblue">
            <div class="stat-label">Total</div>
            <div class="stat-value" ><?php echo $totalTasks; ?></div>
          </div>
          <div class="stat-item" style="background-color: #2ecc71;">
            <div class="stat-label">Completed</div>
            <div class="stat-value" ><?php echo $completedTasks; ?></div>
          </div>
          <div class="stat-item"style="background-color: #f39c12;">
            <div class="stat-label">Pending</div>
            <div class="stat-value" ><?php echo $pendingTasks; ?></div>
          </div>
          <div class="stat-item"style="background-color:rgb(216, 39, 57);">
            <div class="stat-label">Overdue</div>
            <div class="stat-value" ><?php echo $overdueTasks; ?></div>
          </div>
        </div> 
      </div>

        <!-- Bottom row with Task Distribution and Member sections -->
        <div class="top-row">
          <!-- Task Distribution Section -->
          <div class="chart-card">
            <div class="chart-header">
              <div class="chart-title">Task Distribution</div>
            </div>
            <div class="chart-wrapper">
              <canvas id="combinedTasksChart"></canvas>
            </div>
          </div>
          <!-- task monthly start -->
</div>
<div class="task-card">
  <div class="task-header">
    <div class="task-catchup">
      <?php
      $todayDate = date('Y-m-d');
      $currentTime = date('H:i:s');

      // Count today's tasks
      $sql = "SELECT COUNT(*) FROM tasks 
              WHERE userid=? AND taskdate = ? 
              AND (tasktime <= ? OR tasktime IS NULL) 
              AND taskstatus != 'Completed' 
              AND is_deleted = 0";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iss", $userid,$todayDate, $currentTime);
      $stmt->execute();
      $stmt->bind_result($taskCount);
      $stmt->fetch();
      $stmt->close();

      // Count overdue tasks
      $sql = "SELECT COUNT(*) FROM tasks 
              WHERE userid =? AND taskdate < ? 
              AND taskstatus != 'Completed' 
              AND is_deleted = 0
              And is_overdue=1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $userid,$todayDate);
      $stmt->execute();
      $stmt->bind_result($overdueCount);
      $stmt->fetch();
      $stmt->close();

      if ($taskCount > 0 || $overdueCount > 0) {
          echo '<div class="task-status">';
          echo '<img src="img/tasklist.png" alt="Tasks" class="task-icon">';
          echo '<div class="task-counts">';
          echo '<p><strong>'.$taskCount.'</strong> task(s) due today</p>';
          if ($overdueCount > 0) {
              echo '<p class="overdue"><strong>'.$overdueCount.'</strong> overdue task(s)</p>';
          }
          echo '</div>';
          echo '</div>';
          echo '<button onclick="toggleTasks()" class="view-tasks-btn">View tasks</button>';
      } else {
    echo '<div style="text-align: center; width: 100%;">';
    echo '<img src="img/notask.svg" width="450px" alt="No tasks" style="display: block; margin: 0 auto;">';
    echo '<p style="margin: 10px 0; font-size: 18px;">No tasks due today!</p>';
    echo '</div>';
}
      ?>
    </div>
  </div>
<style>
  /* Center align task status container */
.task-status {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 10px;
    margin-bottom: 20px;
}

/* Center align task counts */
.task-counts {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
}

/* Center align the button */
.view-tasks-btn {
    display: block;
    margin: 0 auto;
    width: 80%; /* Adjust width as needed */
    max-width: 200px; /* Optional: prevents button from getting too wide */
}
</style>
  <!-- Hidden task list initially -->
  <div id="task-details" style="display:none; padding: 10px;">
    <?php
    // Fetch today's tasks
    $sql = "SELECT taskid, taskname, taskdate, tasktime FROM tasks 
            WHERE userid=? AND taskdate = ? 
            AND (tasktime <= ? OR tasktime IS NULL) 
            AND taskstatus != 'Completed' 
            AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userid,$todayDate, $currentTime);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userid, $taskname, $taskdate, $tasktime);

    echo "<h4>Tasks Due Today:</h4>";
    if ($stmt->num_rows > 0) {
        echo "<ul>";
        while ($stmt->fetch()) {
            echo "<li><strong>$taskname</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tasks due today 🎉</p>";
    }
    $stmt->close();

    // Fetch overdue tasks
    $sql = "SELECT taskid, taskname, taskdate, tasktime FROM tasks 
            WHERE userid=? AND taskdate < ? 
            AND taskstatus != 'Completed' 
            AND is_deleted = 0 ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userid,$todayDate);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userid, $taskname, $taskdate, $tasktime);

    echo "<h4>Overdue Tasks:</h4>";
    if ($stmt->num_rows > 0) {
        echo "<ul class='overdue-list'>";
        while ($stmt->fetch()) {
            echo "<li ><strong style='color: red;'>$taskname</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No overdue tasks 🥳</p>";
    }
    $stmt->close();
    ?>
  </div>
</div>

<script>
function toggleTasks() {
    const taskDetails = document.getElementById('task-details');
    if (taskDetails.style.display === 'none') {
        taskDetails.style.display = 'block';
    } else {
        taskDetails.style.display = 'none';
    }
}
</script>
<!-- JavaScript to toggle task visibility -->
<script>
function toggleTasks() {
  const details = document.getElementById("task-details");
  details.style.display = (details.style.display === "none") ? "block" : "none";
}
</script>



          <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
          <script>
            const ctx = document.getElementById('combinedTasksChart').getContext('2d');
            new Chart(ctx, {
              type: 'line',
              data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [
                  {
                    label: 'Total Tasks',
                    data: <?php echo json_encode($dailyTotal); ?>,
                    borderColor: '#2980b9',
                    backgroundColor: 'rgba(7, 48, 76, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#2980b9',
                    tension: 0.3,
                    fill: false
                  },
                  {
                    label: 'Completed Tasks',
                    data: <?php echo json_encode($dailyCompleted); ?>,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#2ecc71',
                    tension: 0.3,
                    fill: false
                  },
                  {
                    label: 'Pending Tasks',
                    data: <?php echo json_encode($dailyPending); ?>,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#f39c12',
                    tension: 0.3,
                    fill: false
                  },
                  {
                    label: 'Overdue Tasks',
                    data: <?php echo json_encode($dailyoverdue); ?>,
                    borderColor: 'red',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'red',
                    tension: 0.3,
                    fill: false
                  }
                ]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: {
                    beginAtZero: true,
                    title: {
                      display: true,
                      text: 'Number of Tasks'
                    }
                  },
                  x: {
                    title: {
                      display: true,
                      text: 'Date (Current Month)'
                    },
                    ticks: {
                      maxRotation: 90,
                      minRotation: 45
                    }
                  }
                },
                plugins: {
                  tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                      label: function (context) {
                        return `${context.dataset.label}: ${context.raw}`;
                      }
                    }
                  },
                  legend: {
                    position: 'top'
                  }
                },
                interaction: {
                  mode: 'index',
                  intersect: false
                }
              }
            });
          </script>
          <script>
            function toggleTasks() {
  const taskDetails = document.getElementById('task-details');
  taskDetails.style.display = taskDetails.style.display === 'none' ? 'block' : 'none';
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
          <!-- ===== MAIN JS ===== -->
          <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>
          <script src="../js/dash.js"></script>
          </body>

          </html>