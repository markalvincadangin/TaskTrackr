<?php
session_start();
include('../config/db.php');
include_once('../includes/email_sender.php');

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
    // Notify assigned user about status update
    $assigned_user_query = "SELECT u.user_id, u.email FROM Users u WHERE u.user_id = ?";
    $assigned_user_stmt = $conn->prepare($assigned_user_query);
    $assigned_user_stmt->bind_param("i", $assign_to);
    $assigned_user_stmt->execute();
    $assigned_user_result = $assigned_user_stmt->get_result();
    $assigned_user = $assigned_user_result->fetch_assoc();

    if ($assigned_user) {
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $message = "Task '{$title}' has been updated.";
        $notify_stmt->bind_param("is", $assigned_user['user_id'], $message);
        $notify_stmt->execute();

        if ($assigned_user['email']) {
            $subject = "Task Updated";
            $body = $message;
            sendUserEmail($assigned_user['email'], $subject, $body);
        }
    }

    // Notify the user (self)
    $email_query = "SELECT email FROM Users WHERE user_id = ?";
    $email_stmt = $conn->prepare($email_query);
    $email_stmt->bind_param("i", $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $user_email = $email_result->fetch_assoc()['email'] ?? null;

    if ($user_email) {
        $subject = "Task Status Updated: {$task_info['title']}";
        $body = "You updated the status of your task '{$task_info['title']}' to '{$new_status}'.";
        sendUserEmail($user_email, $subject, $body);
    }

    // Notify project creator if not the same as user
    if ($creator && $creator['created_by'] && $creator['created_by'] != $user_id) {
        $creator_email_query = "SELECT email FROM Users WHERE user_id = ?";
        $creator_email_stmt = $conn->prepare($creator_email_query);
        $creator_email_stmt->bind_param("i", $creator['created_by']);
        $creator_email_stmt->execute();
        $creator_email_result = $creator_email_stmt->get_result();
        $creator_email = $creator_email_result->fetch_assoc()['email'] ?? null;

        if ($creator_email) {
            $subject = "Task Status Updated in Your Project";
            $body = "A task in your project ('{$task_info['title']}') was updated to '{$new_status}'.";
            sendUserEmail($creator_email, $subject, $body);
        }
    }
} else {
    $_SESSION['error_message'] = "Failed to update task.";
}

// Redirect back to view_tasks with the same project ID
header("Location: /TaskTrackr/actions/view_tasks.php?project_id=" . $project_id);
exit();
?>
