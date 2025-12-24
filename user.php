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
</head>
<body>

    <?php require_once "sidebar.php"; ?>

  <h2>Users</h2>
    <p>
        <a href="add_user.php">+ Add User</a>
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
</body>
</html>
