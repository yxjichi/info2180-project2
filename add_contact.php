<?php
session_start();
require_once "db.php";

// must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = false;

function wants_json(): bool {
    if (isset($_GET["format"]) && $_GET["format"] === "json") return true;
    $accept = $_SERVER["HTTP_ACCEPT"] ?? "";
    return stripos($accept, "application/json") !== false;
}

// Load users for "Assigned To" dropdown (must list all users) :contentReference[oaicite:1]{index=1}
$usersStmt = $pdo->query("SELECT id, firstname, lastname FROM users ORDER BY firstname, lastname");
$users = $usersStmt->fetchAll();

// If you ever want AJAX to fetch dropdown data:
if ($_SERVER["REQUEST_METHOD"] === "GET" && wants_json() && (($_GET["resource"] ?? "") === "users")) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($users);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title      = trim($_POST["title"] ?? "");
    $firstname  = trim($_POST["firstname"] ?? "");
    $lastname   = trim($_POST["lastname"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $telephone  = trim($_POST["telephone"] ?? "");
    $company    = trim($_POST["company"] ?? "");
    $type       = trim($_POST["type"] ?? "");
    $assignedTo = $_POST["assigned_to"] ?? "";

    // basic required validation
    if ($title === "")     $errors[] = "Title is required.";
    if ($firstname === "") $errors[] = "First name is required.";
    if ($lastname === "")  $errors[] = "Last name is required.";
    if ($email === "")     $errors[] = "Email is required.";
    if ($telephone === "") $errors[] = "Telephone is required.";
    if ($company === "")   $errors[] = "Company is required.";
    if ($type === "")      $errors[] = "Type is required.";
    if ($assignedTo === "") $errors[] = "Assigned To is required.";

    // validate email
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid.";
    }

    // validate type allowed by spec: Sales Lead or Support :contentReference[oaicite:2]{index=2}
    $allowedTypes = ["Sales Lead", "Support"];
    if ($type !== "" && !in_array($type, $allowedTypes, true)) {
        $errors[] = "Type must be Sales Lead or Support.";
    }

    // validate assignedTo is a valid user id
    if ($assignedTo !== "" && !ctype_digit((string)$assignedTo)) {
        $errors[] = "Assigned To must be a valid user.";
    } else if ($assignedTo !== "") {
        $chk = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $chk->execute([$assignedTo]);
        if (!$chk->fetch()) $errors[] = "Assigned To user does not exist.";
    }

    // if valid, insert contact
    if (count($errors) === 0) {
        $createdBy = $_SESSION["user_id"];

        // Optional: prevent duplicate contact email (not required, but sane)
        $dup = $pdo->prepare("SELECT id FROM contacts WHERE email = ?");
        $dup->execute([$email]);
        if ($dup->fetch()) {
            $errors[] = "A contact with that email already exists.";
        } else {
            $ins = $pdo->prepare("
                INSERT INTO contacts
                    (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $ins->execute([
                $title, $firstname, $lastname, $email, $telephone, $company, $type,
                (int)$assignedTo, (int)$createdBy
            ]);

            $success = true;
        }
    }

    // AJAX-ready JSON response
    if (wants_json()) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => $success,
            "errors"  => $errors
        ]);
        exit;
    }

    // non-AJAX success behavior
    if ($success) {
        header("Location: dashboard.php?added_contact=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dolphin CRM - New Contact</title>
</head>
<body>

<?php require_once "sidebar.php"; ?>

<h2>New Contact</h2>

<?php if (!empty($errors)): ?>
  <div style="color: red;">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="add_contact.php">
  <label>Title</label><br>
  <select name="title" required>
    <?php
      $titles = ["Mr", "Mrs", "Ms", "Dr", "Prof"];
      $curTitle = $_POST["title"] ?? "";
      foreach ($titles as $t) {
        $sel = ($curTitle === $t) ? "selected" : "";
        echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
      }
    ?>
  </select>
  <br><br>

  <label>First Name</label><br>
  <input type="text" name="firstname" value="<?php echo htmlspecialchars($_POST["firstname"] ?? ""); ?>" required>
  <br><br>

  <label>Last Name</label><br>
  <input type="text" name="lastname" value="<?php echo htmlspecialchars($_POST["lastname"] ?? ""); ?>" required>
  <br><br>

  <label>Email</label><br>
  <input type="email" name="email" value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" required>
  <br><br>

  <label>Telephone</label><br>
  <input type="text" name="telephone" value="<?php echo htmlspecialchars($_POST["telephone"] ?? ""); ?>" required>
  <br><br>

  <label>Company</label><br>
  <input type="text" name="company" value="<?php echo htmlspecialchars($_POST["company"] ?? ""); ?>" required>
  <br><br>

  <label>Type</label><br>
  <select name="type" required>
    <?php
      $curType = $_POST["type"] ?? "";
      foreach (["Sales Lead", "Support"] as $t) {
        $sel = ($curType === $t) ? "selected" : "";
        echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
      }
    ?>
  </select>
  <br><br>

  <label>Assigned To</label><br>
  <select name="assigned_to" required>
    <option value="">-- Select a user --</option>
    <?php
      $curAssigned = $_POST["assigned_to"] ?? "";
      foreach ($users as $u) {
        $id = (string)$u["id"];
        $name = trim($u["firstname"] . " " . $u["lastname"]);
        $sel = ($curAssigned === $id) ? "selected" : "";
        echo "<option value=\"" . htmlspecialchars($id) . "\" $sel>" . htmlspecialchars($name) . "</option>";
      }
    ?>
  </select>
  <br><br>

  <button type="submit">Save</button>
</form>

</body>
</html>
