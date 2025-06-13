<?php
session_start();
include '../config/db.php';         

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['register_error'] = "Database connection failed!";
    header("Location: register.php");
    exit;
}

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$role = $_POST['role'];

if (empty($name) || empty($email) || empty($password) || empty($role)) {
    $_SESSION['register_error'] = "All fields are required!";
    header("Location: register.php");
    exit;
}

// email already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['register_error'] = "Email already exists!";
    header("Location: register.php");
    exit;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// nsert user
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
try {
    $stmt->execute([$name, $email, $hashedPassword, $role]);
    $_SESSION['register_success'] = "Registration successful!";
} catch (PDOException $e) {
    $_SESSION['register_error'] = "Registration failed: " . $e->getMessage();
}

header("Location: register.php");
exit;
