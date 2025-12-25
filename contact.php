<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

function wants_json(): bool {
    if (isset($_GET["format"]) && $_GET["format"] === "json") return true;
    $accept = $_SERVER["HTTP_ACCEPT"] ?? "";
    return stripos($accept, "application/json") !== false;
}

$errors = [];
$success = false;

$contactId = $_GET["id"] ?? "";
if (!ctype_digit((string)$contactId)) {
    http_response_code(400);
    echo "Invalid contact id.";
    exit;
}
$contactId = (int)$contactId;

// ---- Handle POST actions (assign to me, switch type, add note) ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // 1) Assign to me
    if ($action === "assign_to_me") {
        $stmt = $pdo->prepare("UPDATE contacts SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION["user_id"], $contactId]);
        $success = true;

    // 2) Switch type
    } elseif ($action === "switch_type") {
        // get current type
        $t = $pdo->prepare("SELECT type FROM contacts WHERE id = ?");
        $t->execute([$contactId]);
        $row = $t->fetch();

        if (!$row) {
            $errors[] = "Contact not found.";
        } else {
            $current = $row["type"];
            $newType = ($current === "Sales Lead") ? "Support" : "Sales Lead";
            $u = $pdo->prepare("UPDATE contacts SET type = ?, updated_at = NOW() WHERE id = ?");
            $u->execute([$newType, $contactId]);
            $success = true;
        }

    // 3) Add note
    } elseif ($action === "add_note") {
        $comment = trim($_POST["comment"] ?? "");
        if ($comment === "") {
            $errors[] = "Note cannot be empty.";
        } else {
            // insert note
            $ins = $pdo->prepare("
                INSERT INTO notes (contact_id, comment, created_by, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $ins->execute([$contactId, $comment, $_SESSION["user_id"]]);

            // update contact updated_at
            $upd = $pdo->prepare("UPDATE contacts SET updated_at = NOW() WHERE id = ?");
            $upd->execute([$contactId]);

            $success = true;
        }

    } else {
        $errors[] = "Unknown action.";
    }

    // AJAX-ready response
    if (wants_json()) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => $success,
            "errors" => $errors
        ]);
        exit;
    }

    // normal redirect to avoid resubmitting form on refresh
    header("Location: contact.php?id=" . urlencode((string)$contactId));
    exit;
}

// ---- Load contact details ----
// Need: title+name, email, company, telephone, created_at and who created, updated_at, assigned_to name :contentReference[oaicite:1]{index=1}
$contactStmt = $pdo->prepare("
    SELECT
        c.*,
        CONCAT(cb.firstname, ' ', cb.lastname) AS created_by_name,
        CONCAT(at.firstname, ' ', at.lastname) AS assigned_to_name
    FROM contacts c
    LEFT JOIN users cb ON c.created_by = cb.id
    LEFT JOIN users at ON c.assigned_to = at.id
    WHERE c.id = ?
");
$contactStmt->execute([$contactId]);
$contact = $contactStmt->fetch();

if (!$contact) {
    http_response_code(404);
    echo "Contact not found.";
    exit;
}

// ---- Load notes list ----
// Each note should show user name, comment, date added :contentReference[oaicite:2]{index=2}
$notesStmt = $pdo->prepare("
    SELECT
        n.id,
        n.comment,
        n.created_at,
        CONCAT(u.firstname, ' ', u.lastname) AS author_name
    FROM notes n
    JOIN users u ON n.created_by = u.id
    WHERE n.contact_id = ?
    ORDER BY n.created_at DESC
");
$notesStmt->execute([$contactId]);
$notes = $notesStmt->fetchAll();

// AJAX: get whole page data in JSON
if (wants_json() && ($_SERVER["REQUEST_METHOD"] === "GET")) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "contact" => $contact,
        "notes" => $notes
    ]);
    exit;
}

$fullName = trim(($contact["title"] ?? "") . " " . $contact["firstname"] . " " . $contact["lastname"]);
$currentType = $contact["type"] ?? "";
$toggleLabel = ($currentType === "Sales Lead") ? "Switch to Support" : "Switch to Sales Lead";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dolphin CRM - Contact Details</title>
  <link rel ="stylesheet" href="styles.css">
</head>
<body>
<header>
    <img src="dolphin.png" alt="Dolphin CRM Logo">
    <h1>Dolphin CRM</h1>
  </header>
    <div class="container">
  <main>

<h2><?php echo htmlspecialchars($fullName); ?></h2>

<p>
  Created: <?php echo htmlspecialchars($contact["created_at"] ?? ""); ?>
  by <?php echo htmlspecialchars($contact["created_by_name"] ?? ""); ?>
</p>
<p>
  Updated: <?php echo htmlspecialchars($contact["updated_at"] ?? ""); ?>
</p>

<div id = "actions">
<!-- Action buttons: assign to me + switch type :contentReference[oaicite:3]{index=3} -->
<form method="POST" style="display:inline;">
  <input type="hidden" name="action" value="assign_to_me">
  <button id = "assign" type="submit">Assign to me</button>
</form>

<form method="POST" style="display:inline;">
  <input type="hidden" name="action" value="switch_type">
  <button id = "switch" type="submit"><?php echo htmlspecialchars($toggleLabel); ?></button>
</form>
</div>
<hr>

<h3>Contact Info</h3>
<ul>
  <li><strong>Email:</strong> <?php echo htmlspecialchars($contact["email"] ?? ""); ?></li>
  <li><strong>Telephone:</strong> <?php echo htmlspecialchars($contact["telephone"] ?? ""); ?></li>
  <li><strong>Company:</strong> <?php echo htmlspecialchars($contact["company"] ?? ""); ?></li>
  <li><strong>Type:</strong> <?php echo htmlspecialchars($contact["type"] ?? ""); ?></li>
  <li><strong>Assigned To:</strong> <?php echo htmlspecialchars($contact["assigned_to_name"] ?? ""); ?></li>
</ul>

<hr>

<h3>Notes</h3>

<?php if (count($notes) === 0): ?>
  <p>No notes yet.</p>
<?php else: ?>
  <?php foreach ($notes as $n): ?>
    <div style="margin-bottom: 16px;">
      <strong><?php echo htmlspecialchars($n["author_name"]); ?></strong><br>
      <p><?php echo nl2br(htmlspecialchars($n["comment"])); ?></p>
      <small><?php echo htmlspecialchars($n["created_at"]); ?></small>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<h4>Add a note about <?php echo htmlspecialchars($contact["firstname"] ?? ""); ?></h4>

<!-- Add note form :contentReference[oaicite:4]{index=4} -->
<form method="POST">
  <input type="hidden" name="action" value="add_note">
  <textarea name="comment" rows="5" cols="60" placeholder="Enter details here" required></textarea>
  <br><br>
  <button type="submit">Add Note</button>
</form>
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
