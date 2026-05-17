<?php
// includes/auth.php - Authentication handler
session_start();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Demo authentication (in production, use database with hashed passwords)
    // Default credentials: admin / admin123
    $validUsers = [
        'admin' => 'admin123',
        'user' => 'user123'
    ];
    
    if (isset($validUsers[$username]) && $validUsers[$username] === $password) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        header('Location: ../index.php');
        exit;
    } else {
        $_SESSION['login_error'] = 'Invalid username or password';
        header('Location: ../login.php?error=1');
        exit;
    }
} else {
    header('Location: ../login.php');
    exit;
}
?>
