<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $inputPassword = trim($_POST['password']);

    // Prepare and fetch user by email
    $stmt = $conn->prepare("SELECT id, name, role, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $storedPassword = $user['password'];

        // Check bcrypt password
        if (password_verify($inputPassword, $storedPassword)) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../employee/dashboard.php");
            }
            exit;
        }

        // Optional: Support old MD5 passwords (legacy users)
        if ($storedPassword === md5($inputPassword)) {
            // Optional: rehash and update to bcrypt for better security
            $newHash = password_hash($inputPassword, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $newHash, $user['id']);
            $update->execute();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../employee/dashboard.php");
            }
            exit;
        }

        // Password mismatch
        $_SESSION['error'] = "Invalid login credentials!";
    } else {
        $_SESSION['error'] = "Invalid login credentials!";
    }

    header("Location: login.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
