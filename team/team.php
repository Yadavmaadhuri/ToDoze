<?php

session_start();
include '../config/database.php';
include '../include/load_username.php';


// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
  header("Location: ../users/signin.php");
  exit();
}


$alertMessage = "";
if (isset($_SESSION['alert_message'])) {
    $alertMessage = $_SESSION['alert_message'];
    unset($_SESSION['alert_message']); // Clear after showing
}


$userid = $_SESSION['userid'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'admin';

$params = []; // Initialize an empty array for additional parameters
$types = "i"; // Initialize types string with 'i' for the $userid parameter

// Handle team creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $teamName = trim($_POST['teamname']);
  $teamDescription = trim($_POST['teamdescription']);
  // $teamDueDate = trim($_POST['teamduedate']);
  // $teamDueDate = $teamDueDate === '' ? null : $teamDueDate;

  // Insert into 'teams' table
  $sql = "INSERT INTO teams (teamname, teamdescription) VALUES (?, ?)";
  $stmt = mysqli_prepare($conn, $sql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $teamName, $teamDescription);
    if (mysqli_stmt_execute($stmt)) {
      $teamId = mysqli_insert_id($conn);

      // Assign the creator as "Admin" in team_members
      $sql = "INSERT INTO team_members (userid, teamid, status ,role) VALUES (?, ?, 'Accepted','Admin')";
      $stmt2 = mysqli_prepare($conn, $sql);
      if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, "ii", $userid, $teamId);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
      }

      $_SESSION['success_message'] = "Team created successfully!";
      header("Location: team.php");
      exit();
    }
    mysqli_stmt_close($stmt);
  }
}

// Base query for fetching teams
$baseQuery = "
SELECT t.*, tm.role, tm.status, tm.has_exited, tm.removed_at, tm.exited_at 
FROM teams t
JOIN team_members tm ON t.teamid = tm.teamid
WHERE tm.userid = ? AND t.is_teamdeleted = 0";

// Apply filter based on role and status
if ($filter === 'admin') {
  $baseQuery .= " AND tm.role = 'Admin' AND tm.status = 'Accepted' AND tm.has_exited = 0";
} elseif ($filter === 'member') {
  $baseQuery .= " AND tm.role != 'Admin' AND tm.status = 'Accepted' AND tm.has_exited = 0";
} elseif ($filter === 'ex_members') {
  $baseQuery .= " AND (tm.status = 'Removed' OR tm.has_exited = 1)";
}

// Add search condition
if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $baseQuery .= " AND t.teamname LIKE ?";
    $params[] = $search_term; // Add search term to parameters
    $types .= "s"; // Add 's' for the search term string
}

// Add grouping and ordering
$baseQuery .= " GROUP BY t.teamid ORDER BY t.teamcreated_at DESC";

$stmt = mysqli_prepare($conn, $baseQuery);
if ($stmt === false) {
    die("Error preparing statement: " . mysqli_error($conn));
}

// Bind parameters
mysqli_stmt_bind_param($stmt, $types, $userid, ...$params);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Team</title>
  <link rel="stylesheet" href="../css/dash.css">
  <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<?php include '../include/navbar.php'; ?>
<?php include '../include/toolbar.php'; ?>

<body id="body-pd">
  

  <!-- filters -->
  <div class="filter-container">
    <div style="display: flex; justify-content: center;">

      <button id="createProjectBtn" class="create-btn"> + Create New Team</button>
    </div>
    <a href="team.php?filter=admin" class="task-filter <?= $filter == 'admin' ? 'active' : '' ?>"
      title="Teams where you are the administrator">👑 Managed Teams</a>
    <a href="team.php?filter=member" class="task-filter <?= $filter == 'member' ? 'active' : '' ?>"
      title="Teams you have joined as a member">👥 Joined Teams</a>
    <a href="team.php?filter=ex_members" class="task-filter <?= $filter == 'ex_members' ? 'active' : '' ?>"
      title="Teams you have exited or been removed from">👻 Ex Teams</a>

      <!-- Search Form -->
      <form method="GET" action="team.php" class="search-form" style="display: flex; align-items: center; gap: 5px;">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="text" name="search" placeholder="Search teams..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width: 180px;">
            <button type="submit" style="padding: 2px 4px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f9fa; cursor: pointer;">🔍</button>
      </form>
  </div>


  

  <div class="box" style="margin-right: 300px;">
    <!-- <h2>Your Teams</h2> -->
    <?php if (mysqli_num_rows($result) > 0): ?>
      <div class="team-list">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <?php $role = $row['role']; ?>

          <div class="team-box" style="padding: 8px;">
            <!-- Project Name on its own line -->
            <div class="team-title" style="margin-bottom: 3px;">
              <?php if ($filter === 'ex_members'): ?>
                <h3 style="margin-top: 0; margin-bottom: 0; font-size: 16px;"><?php echo htmlspecialchars($row['teamname']); ?></h3>
              <?php else: ?>
                <a href="team_view.php?teamid=<?php echo $row['teamid']; ?>">
                  <h3 style="margin-top: 0; margin-bottom: 0; font-size: 16px;"><?php echo htmlspecialchars($row['teamname']); ?></h3>
                </a>
              <?php endif; ?>
            </div>

            <!-- All other info in a single line -->
            <div class="team-info-line" style="margin-top: 0;">
              <?php if (!empty($row['teamdescription'])): ?>
                <div class="team-description">
                  <span class="info">Description: <?= htmlspecialchars($row['teamdescription']) ?></span>
                </div>
              <?php endif; ?>

              <div class="team-actions" style="margin-right: 50px;">
                <?php if ($role === 'Admin'): ?>
                  <a href="team_task.php?teamid=<?php echo $row['teamid']; ?>" class="edit-btn" title="Edit">
                    <ion-icon name="add-circle-outline"></ion-icon>Task
                  </a>
                  <a href="member.php?teamid=<?php echo $row['teamid']; ?>" class="edit-btn" title="Edit">
                    <ion-icon name="people-outline"></ion-icon> Member
                  </a>
                  <a href="edit_team.php?teamid=<?php echo $row['teamid']; ?>" class="edit-btn" title="Edit">
                    <ion-icon name="create-outline"></ion-icon> Edit
                  </a>
                  <a href="#" class="delete-btn" title="Delete" onclick="confirmDelete(<?php echo $row['teamid']; ?>)">
                    <ion-icon name="trash-outline"></ion-icon> Delete
                  </a>
                <?php else: ?>
                  <?php 
                  // Display status for non-admin members, including exited/removed
                  $has_exited = isset($row['has_exited']) ? $row['has_exited'] : 0;
                  $status = isset($row['status']) ? $row['status'] : 'Accepted';

                  if ($role === 'Member') {
                      echo '<div style="display: flex; gap: 45px; align-items: center;">'; // Use flex to align content
                      
                      if ($status === 'Accepted' && $has_exited == 0) {
                          // Display Exit button only for active members in 'Joined Teams' view
                          if ($filter !== 'ex_members') {
                              echo '<span class="view-only-msg">🔒 View Only</span>'; // Keep view only for active members
                              echo '<a href="#" class="edit-btn exit-team" data-teamid="'. $row['teamid'] .'">';
                              echo '<ion-icon name="log-out-outline"></ion-icon>Exit';
                              echo '</a>';
                          }
                      } elseif ($filter === 'ex_members') {
                          // This block is now moved outside the .team-actions div
                          // The display logic is handled below the .team-info-line div
                      }
                      echo '</div>';
                  }
                  ?>
                <?php endif; ?>

              </div>

            </div>
            <?php 
            // Display status and date for ex-members below team info if filter is ex_members
            $has_exited = isset($row['has_exited']) ? $row['has_exited'] : 0;
            $status = isset($row['status']) ? $row['status'] : 'Accepted';

            if ($filter === 'ex_members' && $role === 'Member') {
                echo '<div style="color: red; font-size: 12px; margin-top: 3px;">';
                
                $status_date = null;
                $status_text = '';

                if ($has_exited == 1 && $status == 'Removed') {
                    $status_text = 'Left on:';
                    $status_date = $row['exited_at'];
                } elseif ($has_exited == 0 && $status == 'Removed') {
                    $status_text = 'Removed on:';
                    $status_date = $row['removed_at'];
                }
                
                if ($status_date) {
                    // Format the date
                    $formatted_date = date('Y-m-d', strtotime($status_date));
                    echo $status_text . ' ' . htmlspecialchars($formatted_date);
                }
                echo '</div>';
            }
            ?>

          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="centered-content">
        <div class="content-wrapper">
          <img src="../img/notask.svg" alt="No teams yet" />
          <h3>
            <p>Nothing yet! 🚀</p>
          </h3>
        </div>
      </div>
    <?php endif; ?>
  </div>


  <div class="container">
    <!-- Modal -->
    <div id="teamModal" class="modal-overlay" style="display: none;">
      <div class="modal-content">
        <span class="close-modal" id="closeModalBtn">&times;</span>
        <h2>Create New Team</h2>
        <form method="POST" >
          <label for="teamname">Team Name:</label>
          <input type="text" id="teamname" name="teamname" required>

          <label for="teamdescription">Team Description:</label>
          <input type="text" id="teamdescription" name="teamdescription" style="height: 80px;">

          <button type="submit">Create Team </button>

        </form>
      </div>
    </div>
  </div>

  <!-- for delete msg -->
  <script>
    function confirmDelete(teamId) {
      Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = "delete_team.php?teamid=" + teamId;
        }
      });
    }
  </script>



  <script>
    const modal = document.getElementById("teamModal");
    const openBtn = document.getElementById("createProjectBtn");
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



<!-- for exit  -->
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if (!empty($alertMessage)): ?>
    Swal.fire({
        icon: '<?= (strpos($alertMessage, 'success:') === 0) ? 'success' : 'warning' ?>',
        title: '<?= (strpos($alertMessage, 'success:') === 0) ? 'Success' : 'Notice' ?>',
        text: "<?= str_replace('success: ', '', $alertMessage) ?>"
    });
<?php endif; ?>
</script>

<!-- too toggll full test  -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const descriptions = document.querySelectorAll('.team-description .info');

  descriptions.forEach(desc => {
    if (desc.textContent.startsWith("Description:")) {
      const fullText = desc.textContent.trim().replace("Description:", "").trim();
      
      // Set initial state to show 2 lines
      desc.classList.add("truncated");
      
      // Add click handler to toggle between full and truncated view
      desc.addEventListener("click", function () {
        const isExpanded = desc.classList.contains("expanded");
        if (isExpanded) {
          desc.classList.remove("expanded");
          desc.classList.add("truncated");
        } else {
          desc.classList.add("expanded");
          desc.classList.remove("truncated");
        }
      });
    }
  });
});
</script>


  <!-- IONICONS -->
  <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>

  <!-- MAIN JS -->
  <script src="../js/dash.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>

</html>

<?php mysqli_close($conn); ?>

<style>
/* Add these styles to your existing CSS */
.team-description {
    display: inline-block;
    margin-right: 15px;
    max-width: 600px; /* Adjust this value based on your layout */
}

.team-description .info {
    cursor: pointer;
    color: black;
    font-size: 1 em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.5;
    max-height: 3em; /* 2 lines × 1.5 line-height */
}

.team-description .info.expanded {
    -webkit-line-clamp: unset;
    max-height: none;
}

.team-info-line {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 20px;
}
</style>