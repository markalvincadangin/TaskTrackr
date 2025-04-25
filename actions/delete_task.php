<?php
session_start();
include('../config/db.php');

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