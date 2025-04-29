<?php
session_start();
include('../config/db.php');
include_once('../includes/email_sender.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user email before deleting
$email_query = "SELECT email FROM Users WHERE user_id = ?";
$email_stmt = $conn->prepare($email_query);
$email_stmt->bind_param("i", $user_id);
$email_stmt->execute();
$email_result = $email_stmt->get_result();
$user_email = $email_result->fetch_assoc()['email'] ?? null;

// Delete user account
$query = "DELETE FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Send email notification after account deletion
    if ($user_email) {
        $subject = "Your TaskTrackr Account Has Been Deleted";
        $body = "Your TaskTrackr account has been successfully deleted. We're sorry to see you go.";
        sendUserEmail($user_email, $subject, $body);
    }
    session_destroy(); // Log the user out
    header("Location: ../public/register.php");
    exit();
} else {
    $_SESSION['error_message'] = "Failed to delete account. Please try again.";
    header("Location: ../public/profile.php");
    exit();
}
?>