<?php
session_start();
include('../config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Get POST data
$task_id = $_POST['task_id'] ?? null;
$project_id = $_POST['project_id'] ?? null;
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;
$deadline = $_POST['deadline'] ?? null;
$status = $_POST['status'] ?? null;
$priority = $_POST['priority'] ?? null;  
$assign_to = $_POST['assign_to'] ?? null;

// Check if task ID is provided
if (!$task_id || !$project_id || !$title || !$description || !$deadline || !$status || !$assign_to) {
    $_SESSION['error_message'] = "All fields are required.";
    header("Location: /TaskTrackr/actions/edit_task.php?task_id=$task_id&project_id=$project_id");
    exit();
}

// Update the task in the database
$query = "UPDATE Tasks SET title = ?, description = ?, due_date = ?, status = ?, priority = ?, assigned_to = ? WHERE task_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssii", $title, $description, $deadline, $status, $priority, $assign_to, $task_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Task updated successfully.";
} else {
    $_SESSION['error_message'] = "Failed to update task.";
}

// Redirect back to view_tasks with the same project ID
header("Location: /TaskTrackr/actions/view_tasks.php?project_id=" . $project_id);
exit();
?>
