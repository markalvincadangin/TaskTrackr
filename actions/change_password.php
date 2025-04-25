<?php
session_start();
include('../config/db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: ../public/profile.php");
        exit();
    }

    if (strlen($new_password) < 8) {
        $_SESSION['error_message'] = "New password must be at least 8 characters long.";
        header("Location: ../public/profile.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New password and confirm password do not match.";
        header("Location: ../public/profile.php");
        exit();
    }

    // Verify current password
    $query = "SELECT password FROM Users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result || !password_verify($current_password, $result['password'])) {
        $_SESSION['error_message'] = "Current password is incorrect.";
        header("Location: ../public/profile.php");
        exit();
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE Users SET password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Password changed successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to change password. Please try again.";
    }

    header("Location: ../public/profile.php");
    exit();
}
?>