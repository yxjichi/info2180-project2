<?php
session_start();
require_once "db.php";
require_once "sidebar.php";

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

$errors = [];
$success = false;

// helper: should we return JSON? (AJAX-ready)
function wants_json(): bool {
    if (isset($_GET["format"]) && $_GET["format"] === "json") return true;
    $accept = $_SERVER["HTTP_ACCEPT"] ?? "";
    return stripos($accept, "application/json") !== false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = trim($_POST["firstname"] ?? "");
    $lastname  = trim($_POST["lastname"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $password  = $_POST["password"] ?? "";
    $role      = trim($_POST["role"] ?? "Member");

    // validate required
    if ($firstname === "") $errors[] = "First name is required.";
    if ($lastname === "")  $errors[] = "Last name is required.";
    if ($email === "")     $errors[] = "Email is required.";
    if ($password === "")  $errors[] = "Password is required.";

    // validate email
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid.";
    }

    // validate role
    $allowed_roles = ["Admin", "Member"];
    if (!in_array($role, $allowed_roles, true)) {
        $errors[] = "Role must be Admin or Member.";
    }

    // password rules: >=8 chars, at least one letter, one number, one capital letter
    // (matches the project instruction)
    if ($password !== "" && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[A-Z]).{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters and include at least 1 letter, 1 number, and 1 capital letter.";
    }

    // if valid so far, check email uniqueness + insert
    if (count($errors) === 0) {
        // check if email exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = "A user with that email already exists.";
        } else {
            // hash password before storing
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $ins = $pdo->prepare("
                INSERT INTO users (firstname, lastname, email, password, role, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([$firstname, $lastname, $email, $hash, $role]);

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

    // normal (non-AJAX) behavior
    if ($success) {
        header("Location: user.php?added=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dolphin CRM - Add User</title>
</head>
<body>

<h2>New User</h2>

<?php if ($success): ?>
  <p style="color: green;">User added successfully.</p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div style="color: red;">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="add_user.php">
  <label>First Name</label><br>
  <input type="text" name="firstname" value="<?php echo htmlspecialchars($_POST["firstname"] ?? ""); ?>" required><br><br>

  <label>Last Name</label><br>
  <input type="text" name="lastname" value="<?php echo htmlspecialchars($_POST["lastname"] ?? ""); ?>" required><br><br>

  <label>Email</label><br>
  <input type="email" name="email" value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" required><br><br>

  <label>Password</label><br>
  <input type="password" name="password" required><br><br>

  <label>Role</label><br>
  <select name="role">
    <option value="Member" <?php echo (($_POST["role"] ?? "Member") === "Member") ? "selected" : ""; ?>>Member</option>
    <option value="Admin" <?php echo (($_POST["role"] ?? "") === "Admin") ? "selected" : ""; ?>>Admin</option>
  </select><br><br>

  <button type="submit">Save</button>
</form>

</body>
</html>
