<?php
// Include necessary files
include('../config/db.php');
include_once('../includes/email_sender.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['deadline'];  // Ensure this matches your form field name
    $assign_to = $_POST['assign_to'];
    $priority = $_POST['priority'];  
    $project_id = $_POST['project_id'] ?? null;
    $creator_id = $_SESSION['user_id']; // Get the logged-in user's ID

    // Check if the user is the creator of the project
    $project_query = "SELECT created_by FROM Projects WHERE project_id = ?";
    $project_stmt = $conn->prepare($project_query);
    $project_stmt->bind_param("i", $project_id);
    $project_stmt->execute();
    $project_result = $project_stmt->get_result();
    $project = $project_result->fetch_assoc();

    if (!$project || $project['created_by'] != $creator_id) {
        $_SESSION['error_message'] = "You are not authorized to add tasks to this project.";
        header("Location: /TaskTrackr/actions/view_tasks.php?project_id=" . $project_id);
        exit();
    }

    // Insert the new task into the database
    $query = "INSERT INTO Tasks (title, description, due_date, priority, project_id, assigned_to) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssii", $title, $description, $due_date, $priority, $project_id, $assign_to);

    if ($stmt->execute()) {
        $task_id = $stmt->insert_id; // Get the ID of the newly created task
        
        // In-app notification for assigned user
        $notify_query = "INSERT INTO Notifications (
            user_id, 
            message, 
            related_task_id, 
            related_project_id, 
            related_user_id,
            notification_type
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $notify_stmt = $conn->prepare($notify_query);
        $message = "You've been assigned a new task: \"" . htmlspecialchars($title) . "\" due on " . date('M j, Y', strtotime($due_date));
        $notification_type = "task_assignment";
        
        $notify_stmt->bind_param("isiisi", 
            $assign_to,
            $message,
            $task_id,
            $project_id, 
            $creator_id,
            $notification_type
        );
        $notify_stmt->execute();

        // Send email notification
        $email_query = "SELECT email FROM Users WHERE user_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $assign_to);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = $email_result->fetch_assoc()['email'] ?? null;

        if ($user_email) {
            $subject = "Task Assignment: $title";
            $body = "You've been assigned a new task: \"$title\"\n\n"
                  . "Priority: $priority\n"
                  . "Due Date: " . date('F j, Y', strtotime($due_date)) . "\n\n"
                  . "Description: $description";
            sendUserEmail($user_email, $subject, $body);
        }

        $_SESSION['success_message'] = 'Task added successfully!';
        // Redirect back to view tasks page for the project
        header("Location: /TaskTrackr/actions/view_tasks.php?project_id=" . $project_id);
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to add task!';
        header("Location: /TaskTrackr/actions/add_task.php?project_id=" . $project_id);
        exit();
    }
} else {
    $_SESSION['error_message'] = 'Invalid request.';
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}
