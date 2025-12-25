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
  <link rel ="stylesheet" href="styles.css">
</head>
<div class="container">
<body>
  <header>
    <img src="dolphin.png" alt="Dolphin CRM Logo">
    <h1>Dolphin CRM</h1>
  </header>
  <main>
  <h2>Dashboard</h2>
  <p>Logged in as: <?php echo htmlspecialchars($_SESSION["user_name"] ?? ""); ?></p>

  <div id = "filters">
  <p> 
    Filter by:
    <a href="dashboard.php?filter=all">All</a> |
    <a href="dashboard.php?filter=sales">Sales Leads</a> |
    <a href="dashboard.php?filter=support">Support</a> |
    <a href="dashboard.php?filter=mine">Assigned to me</a>
  </p>
  <p>           </p>
  <p>
    <form action = "add_contact.php">
      <button id="add-contact" type="submit">+ Add Contact</button>
    </form>
  </p>
</div>
  

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