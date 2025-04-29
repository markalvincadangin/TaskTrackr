<?php
session_start();
include('../config/db.php');
include_once('../includes/email_sender.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to continue.";
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Check if task ID and project ID are provided
$task_id = $_GET['task_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;

if (!$task_id || !$project_id) {
    $_SESSION['error_message'] = "Invalid task or project ID.";
    header("Location: /TaskTrackr/actions/view_tasks.php");
    exit();
}

// Before deleting the task
$assigned_user_query = "SELECT u.user_id, u.email FROM Users u WHERE u.user_id = ?";
$assigned_user_stmt = $conn->prepare($assigned_user_query);
$assigned_user_stmt->bind_param("i", $assigned_to);
$assigned_user_stmt->execute();
$assigned_user_result = $assigned_user_stmt->get_result();
$assigned_user = $assigned_user_result->fetch_assoc();

if ($assigned_user) {
    $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
    $notify_stmt = $conn->prepare($notify_query);
    $message = "Task '{$title}' has been deleted.";
    $notify_stmt->bind_param("is", $assigned_user['user_id'], $message);
    $notify_stmt->execute();

    if ($assigned_user['email']) {
        $subject = "Task Deleted";
        $body = $message;
        sendUserEmail($assigned_user['email'], $subject, $body);
    }
}

// Perform delete operation
$query = "DELETE FROM Tasks WHERE task_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $task_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Task deleted successfully.";
} else {
    $_SESSION['error_message'] = "Failed to delete task.";
}

// Redirect back to view_tasks with the same project ID
header("Location: /TaskTrackr/actions/view_tasks.php?project_id=" . $project_id);
exit();