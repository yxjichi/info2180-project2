<?php
session_start();
require_once "db.php";

// must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// admin-only
if (($_SESSION["user_role"] ?? "") !== "Admin") {
    http_response_code(403);
    echo "Forbidden: Admins only.";
    exit;
}

// Fetch users (name, email, role, created_at)
$stmt = $pdo->query("
    SELECT id, firstname, lastname, email, role, created_at
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

/**
 * AJAX-ready:
 * If later you do fetch('/users.php?format=json'), this returns JSON.
 */
if (isset($_GET["format"]) && $_GET["format"] === "json") {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($users);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dolphin CRM - Users</title>
  <link rel ="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <img src="dolphin.png" alt="Dolphin CRM Logo">
    <h1>Dolphin CRM</h1>
  </header>
  <div class="container">
  <main>
  <h2>Users</h2>
  <p>
    <form action = "add_user.php">
      <button id="add-user" type="submit">+ Add User</button>
    </form>
  </p>
  <p>Logged in as: <?php echo htmlspecialchars($_SESSION["user_name"] ?? ""); ?></p>

  <?php if (count($users) === 0): ?>
    <p>No users found.</p>
  <?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?php echo htmlspecialchars(trim($u["firstname"] . " " . $u["lastname"])); ?></td>
            <td><?php echo htmlspecialchars($u["email"]); ?></td>
            <td><?php echo htmlspecialchars($u["role"]); ?></td>
            <td><?php echo htmlspecialchars($u["created_at"]); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>
<aside>
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
</aside>
    </div>
</body>
</html>
