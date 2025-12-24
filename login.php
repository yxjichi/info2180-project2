<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dolphin CRM - Login</title>
  <link rel ="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <img src="dolphin.png" alt="Dolphin CRM Logo">
    <h1>Dolphin CRM</h1>
  </header>
  <main>
  <h2>Login</h2>

  <form method="POST" action="login_user.php">
    <label>Email address</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button id="loginButton" type="submit">Login</button>
  </form>
</main>
  <?php if (isset($_GET["error"])): ?>
    <p style="color:red;">Invalid email or password</p>
  <?php endif; ?>

</body>
</html>
