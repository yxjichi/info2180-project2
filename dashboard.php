<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$filter = $_GET["filter"] ?? "all";
$userId = $_SESSION["user_id"];

$sql = "SELECT id, title, firstname, lastname, email, company, type
        FROM contacts ";
$params = [];

// Apply filter conditions
if ($filter === "sales") {
    $sql .= "WHERE type = :type ";
    $params[":type"] = "Sales Lead";
} elseif ($filter === "support") {
    $sql .= "WHERE type = :type ";
    $params[":type"] = "Support";
} elseif ($filter === "mine") {
    $sql .= "WHERE assigned_to = :uid ";
    $params[":uid"] = $userId;
} else {
    $filter = "all"; // normalize unknown values
}

$sql .= "ORDER BY updated_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dolphin CRM - Dashboard</title>
</head>
<body>

  <?php require_once "sidebar.php"; ?>

  <h2>Dashboard</h2>
  <p>Logged in as: <?php echo htmlspecialchars($_SESSION["user_name"] ?? ""); ?></p>

  <p>
    <a href="new_contact.php">+ Add Contact</a>
  </p>

  <p>
    Filter by:
    <a href="dashboard.php?filter=all">All</a> |
    <a href="dashboard.php?filter=sales">Sales Leads</a> |
    <a href="dashboard.php?filter=support">Support</a> |
    <a href="dashboard.php?filter=mine">Assigned to me</a>
  </p>

  <h3>Contacts (<?php echo htmlspecialchars($filter); ?>)</h3>

  <?php if (count($contacts) === 0): ?>
    <p>No contacts found.</p>
  <?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Company</th>
          <th>Type</th>
          <th>View</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars(trim(($c["title"] ?? "") . " " . $c["firstname"] . " " . $c["lastname"])); ?></td>
            <td><?php echo htmlspecialchars($c["email"]); ?></td>
            <td><?php echo htmlspecialchars($c["company"] ?? ""); ?></td>
            <td><?php echo htmlspecialchars($c["type"]); ?></td>
            <td>
              <a href="contact.php?id=<?php echo urlencode($c["id"]); ?>">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</body>
</html>