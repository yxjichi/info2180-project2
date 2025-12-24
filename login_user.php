<?php
session_start();
require_once "db.php";

$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"] : "";

if ($email === "" || $password === "") {
    header("Location: login.php?error=1");
    exit;
}

$stmt = $pdo->prepare("SELECT id, password, role, firstname, lastname FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user["password"])) {
    header("Location: login.php?error=1");
    exit;
}

// success
$_SESSION["user_id"] = $user["id"];
$_SESSION["user_role"] = $user["role"];
$_SESSION["user_name"] = $user["firstname"] . " " . $user["lastname"];

header("Location: dashboard.php");
exit;
