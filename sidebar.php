<?php
//sidebar.php
?>
<nav>
  <ul>
    <li><a href="dashboard.php">Home</a></li>
    <li><a href="add_contact.php">New Contact</a></li>
    <?php if (($_SESSION["user_role"] ?? "") === "Admin"): ?>
        <li><a href="user.php">Users</a></li>
    <?php endif; ?>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>
