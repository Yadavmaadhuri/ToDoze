<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$show_red_bell = isset($_SESSION['has_pending_invites']) && $_SESSION['has_pending_invites'];
?>

<div class="top-bar">
  <div class="top-left"></div>
  <div class="top-right-icons">
    <a href="../team/invitation.php" class="top-icon">
      <ion-icon name="notifications-outline"></ion-icon>
      <?php if ($show_red_bell): ?>
        <span class="notification-dot"></span>
      <?php endif; ?>
    </a>
    <div class="profile-info">
      <div class="profile-circle" title="<?= htmlspecialchars($username) ?>">
        <ion-icon name="person-outline"></ion-icon>
      </div>
      <span class="username-text"><?= htmlspecialchars($username) ?></span>
    </div>
  </div>
</div>

<style>
.top-icon {
  position: relative;
  display: inline-block;
}

.notification-dot {
  position: absolute;
  top: 5px;
  right: 5px;
  height: 10px;
  width: 10px;
  background-color: red;
  border-radius: 50%;
  animation: pulse 1s infinite alternate;
}

@keyframes pulse {
  from {
    transform: scale(1);
  }
  to {
    transform: scale(1.3);
  }
}
</style>
