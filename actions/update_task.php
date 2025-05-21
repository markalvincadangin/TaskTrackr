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
$user_id = $_SESSION['user_id'];

// Check if task ID is provided
if (!$task_id || !$project_id || !$title || !$description || !$deadline || !$status || !$assign_to) {
    $_SESSION['error_message'] = "All fields are required.";
    header("Location: /TaskTrackr/actions/edit_task.php?task_id=$task_id&project_id=$project_id");
    exit();
}

// Check if project still exists before updating the task
$project_check_query = "SELECT * FROM Projects WHERE project_id = ?";
$project_check_stmt = $conn->prepare($project_check_query);
$project_check_stmt->bind_param("i", $project_id);
$project_check_stmt->execute();
$project_check_result = $project_check_stmt->get_result();

if ($project_check_result->num_rows === 0) {
    $_SESSION['error_message'] = "The project for this task no longer exists.";
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}

// Get original task data to check what's changed
$original_query = "SELECT * FROM Tasks WHERE task_id = ?";
$original_stmt = $conn->prepare($original_query);
$original_stmt->bind_param("i", $task_id);
$original_stmt->execute();
$original_task = $original_stmt->get_result()->fetch_assoc();

// Update task
$query = "UPDATE Tasks SET title = ?, description = ?, due_date = ?, status = ?, priority = ?, assigned_to = ? WHERE task_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssii", $title, $description, $deadline, $status, $priority, $assign_to, $task_id);

if ($stmt->execute()) {
    // Check if assignment changed
    $assignment_changed = $original_task['assigned_to'] != $assign_to;
    
    // Build notification message based on what changed
    $changes = [];
    if ($original_task['status'] != $status) $changes[] = "status updated to \"$status\"";
    if ($original_task['priority'] != $priority) $changes[] = "priority set to \"$priority\"";
    if ($assignment_changed) $changes[] = "assigned to you";
    if ($original_task['due_date'] != $deadline) $changes[] = "deadline changed to " . date('M j, Y', strtotime($deadline));
    
    $change_summary = !empty($changes) ? " (" . implode(", ", $changes) . ")" : "";
    
    // If task was assigned to a different user
    if ($assignment_changed) {
        // Notification for new assignee
        $notify_query = "INSERT INTO Notifications (
            user_id, 
            message, 
            related_task_id, 
            related_project_id, 
            related_user_id,
            notification_type
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $notify_stmt = $conn->prepare($notify_query);
        $message = "You've been assigned task \"" . htmlspecialchars($title) . "\"" . $change_summary;
        $notification_type = "task_assignment";
        
        $notify_stmt->bind_param("isiisi", 
            $assign_to, 
            $message, 
            $task_id, 
            $project_id, 
            $user_id,
            $notification_type
        );
        $notify_stmt->execute();

        // Send email notification to new assignee
        $email_query = "SELECT email FROM Users WHERE user_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $assign_to);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = $email_result->fetch_assoc()['email'] ?? null;

        if ($user_email) {
            $subject = "Task Assigned: $title";
            $body = "You've been assigned task \"$title\"$change_summary\n\n"
                  . "Priority: $priority\n"
                  . "Status: $status\n"
                  . "Due Date: " . date('F j, Y', strtotime($deadline)) . "\n\n"
                  . "Description: $description";
            sendUserEmail($user_email, $subject, $body);
        }
    } 
    // If task was updated but assignment didn't change, notify current assignee
    else if (!empty($changes)) {
        $notify_query = "INSERT INTO Notifications (
            user_id, 
            message, 
            related_task_id, 
            related_project_id, 
            related_user_id,
            notification_type
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $notify_stmt = $conn->prepare($notify_query);
        $message = "Task \"" . htmlspecialchars($title) . "\" has been updated" . $change_summary;
        $notification_type = "task_update";
        
        $notify_stmt->bind_param("isiisi", 
            $assign_to, 
            $message, 
            $task_id, 
            $project_id, 
            $user_id,
            $notification_type
        );
        $notify_stmt->execute();

        // Send email notification to current assignee
        $email_query = "SELECT email FROM Users WHERE user_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $assign_to);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = $email_result->fetch_assoc()['email'] ?? null;

        if ($user_email) {
            $subject = "Task Updated: $title";
            $body = "Task \"$title\" has been updated$change_summary\n\n"
                  . "Priority: $priority\n"
                  . "Status: $status\n"
                  . "Due Date: " . date('F j, Y', strtotime($deadline)) . "\n\n"
                  . "Description: $description";
            sendUserEmail($user_email, $subject, $body);
        }
    }

    $_SESSION['success_message'] = "Task updated successfully.";
    header("Location: /TaskTrackr/actions/view_tasks.php?project_id=$project_id");
    exit();
} else {
    $_SESSION['error_message'] = "Failed to update task.";
    header("Location: /TaskTrackr/actions/edit_task.php?task_id=$task_id&project_id=$project_id");
    exit();
}
?>
