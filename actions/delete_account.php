<?php
session_start();
include('../config/db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete user account
$query = "DELETE FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    session_destroy(); // Log the user out
    header("Location: ../public/register.php");
    exit();
} else {
    $_SESSION['error_message'] = "Failed to delete account. Please try again.";
    header("Location: ../public/profile.php");
    exit();
}
?>