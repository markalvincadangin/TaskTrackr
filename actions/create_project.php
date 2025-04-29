<?php
// Include necessary files
include('../config/db.php');
include_once('../includes/email_sender.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from the form
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $category_id = $_POST['category'];
    $group_id = $_POST['group_id'];  // Assuming group_id is also passed from the form
    $user_id = $_SESSION['user_id'];  // Get the logged-in user ID

    // Validate input (make sure all required fields are filled)
    if (empty($title) || empty($description) || empty($deadline) || empty($category_id)) {
        $_SESSION['error_message'] = 'All fields are required.';
        header("Location: /TaskTrackr/public/projects.php");  // Redirect back to the projects page
        exit();
    }

    // Insert the new project into the database
    $query = "INSERT INTO Projects (title, description, deadline, category_id, created_by, group_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        // If there's an error with the query, output an error message
        $_SESSION['error_message'] = 'Error preparing the query: ' . $conn->error;
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    }

    // Check if the group_id is set, if not set it to NULL
    if (empty($group_id)) {
        $group_id = null;  // Set to NULL if not provided
    }
    
    // Bind parameters and execute the statement
    $stmt->bind_param("sssiii", $title, $description, $deadline, $category_id, $user_id, $group_id);
    $result = $stmt->execute();

    if ($result) {
        // If successful, redirect to the projects page
        $_SESSION['success_message'] = 'Project created successfully.';
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    } else {
        // If there was an issue executing the statement
        $_SESSION['error_message'] = 'Failed to create project: ' . $stmt->error;
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    }

    if ($result && !empty($group_id)) {
        // Notify all group members about the new project
        $members_query = "SELECT user_id FROM User_Groups WHERE group_id = ?";
        $members_stmt = $conn->prepare($members_query);
        $members_stmt->bind_param("i", $group_id);
        $members_stmt->execute();
        $members_result = $members_stmt->get_result();
        while ($member = $members_result->fetch_assoc()) {
            $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
            $notify_stmt = $conn->prepare($notify_query);
            $message = "A new project '{$title}' has been created for your group.";
            $notify_stmt->bind_param("is", $member['user_id'], $message);
            $notify_stmt->execute();

            // For each $member['user_id']
            $email_query = "SELECT email FROM Users WHERE user_id = ?";
            $email_stmt = $conn->prepare($email_query);
            $email_stmt->bind_param("i", $member['user_id']);
            $email_stmt->execute();
            $email_result = $email_stmt->get_result();
            $user_email = $email_result->fetch_assoc()['email'] ?? null;

            if ($user_email) {
                $subject = "New Project Assigned: $title";
                $body = "A new project '$title' has been created for your group.";
                sendUserEmail($user_email, $subject, $body);
            }
        }
    }
}
?>