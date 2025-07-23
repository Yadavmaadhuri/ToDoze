<?php
  $current_page = basename($_SERVER['PHP_SELF']);

  $is_home_active = $current_page === 'dash.php';
  $is_team_active = strpos($current_page, 'team') !== false;
  $is_task_active = strpos($current_page, 'task') !== false && strpos($current_page, 'team') === false;
  $is_review_active = strpos($current_page, 'review') !== false;
  $is_dropdown_page = $is_review_active || 
                      strpos($current_page, 'change_name') !== false || 
                      strpos($current_page, 'change_password') !== false;
?>

<div class="logo-container">
  <img src="../img/logo.png" alt="App Logo" class="logo">
</div>

<div class="l-navbar" id="navbar">
  <nav class="nav">
    <div class="nav__list">
      <a href="../users/dash.php" class="nav__link <?= $is_home_active ? 'active' : '' ?>">
        <ion-icon name="home-outline" class="nav__icon"></ion-icon>
        <span class="nav__name">Home</span>
      </a>

      <a href="../task/task.php" class="nav__link <?= $is_task_active ? 'active' : '' ?>">
        <ion-icon name="add-outline" class="nav__icon"></ion-icon>
        <span class="nav__name">Task</span>
      </a>

      <a href="../team/team.php" class="nav__link <?= $is_team_active ? 'active' : '' ?>">
        <ion-icon name="people-outline" class="nav__icon"></ion-icon>
        <span class="nav__name">Team</span>
      </a>

     <!-- Dropdown -->
<div class="nav__dropdown">
  <a href="javascript:void(0)" class="nav__dropdown-btn">
    <ion-icon name="ellipsis-horizontal-outline" class="nav__icon"></ion-icon>
    <span class="nav__name">Others</span>
    <i class="nav__dropdown-icon fa fa-caret-down <?= $is_dropdown_page ? 'rotate-caret' : '' ?>"></i>
  </a>

  <div class="nav__dropdown-content <?= $is_dropdown_page ? 'show-dropdown' : '' ?>">
    <a href="../review.php" class="nav__link <?= $is_review_active ? 'active' : '' ?>">
      <ion-icon name="chatbox-ellipses-outline" class="nav__icon"></ion-icon>
      <span class="nav__name">Review</span>
    </a>
    <a href="../users/change_name.php" class="nav__link <?= strpos($current_page, 'change_name') !== false ? 'active' : '' ?>">
      <ion-icon name="person-circle-outline" class="nav__icon"></ion-icon>
      <span class="nav__name">Name</span>
    </a>
    <a href="../users/change_password.php" class="nav__link <?= strpos($current_page, 'change_password') !== false ? 'active' : '' ?>">
      <ion-icon name="key-outline" class="nav__icon"></ion-icon>
      <span class="nav__name">Password</span>
    </a>
  </div>
</div>

    </div>

    <!-- Logout -->
    <div class="nav__logout-container">
      <a href="javascript:void(0)" onclick="confirmLogout(event)" class="nav__link logout">
        <ion-icon name="log-out-outline" class="nav__icon"></ion-icon>
        <span class="nav__name" style="color: #d96c4f;"><b>Log Out</b></span>
      </a>
    </div>
  </nav>
</div>




<style>
        
.nav__dropdown-content {
  display: none;
  flex-direction: column;
  padding-left: 1.5rem;
}

.nav__dropdown-content.show-dropdown {
  display: flex;
}

.rotate-caret {
  transform: rotate(180deg);
  transition: transform 0.3s ease;
}

.nav__dropdown {
  display: flex;
  flex-direction: column;
}






</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const dropdownBtns = document.querySelectorAll('.nav__dropdown-btn');

    dropdownBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const parent = btn.closest('.nav__dropdown');
        const dropdownContent = parent.querySelector('.nav__dropdown-content');
        const caretIcon = btn.querySelector('.nav__dropdown-icon');

        dropdownContent.classList.toggle('show-dropdown');
        caretIcon.classList.toggle('rotate-caret');
      });
    });
  });
</script>


