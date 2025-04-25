<?php
// Include necessary files
include('../config/db.php');
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
    $project_id = $_POST['project_id'];

    // Insert the new task into the database
    $query = "INSERT INTO Tasks (title, description, due_date, priority, project_id, assigned_to) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssii", $title, $description, $due_date, $priority, $project_id, $assign_to);

    if ($stmt->execute()) {
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
