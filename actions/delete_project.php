<?php
// Include necessary files
include('../config/db.php');
include_once('../includes/email_sender.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect if not logged in
    exit();
}

// Check if project_id is provided
if (isset($_GET['project_id'])) {
    $project_id = $_GET['project_id'];
    $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

    // Prepare SQL query to check if the project belongs to the logged-in user
    $query = "SELECT * FROM Projects WHERE project_id = ? AND created_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Before deleting the project
        if (!empty($project['group_id'])) {
            $members_query = "SELECT u.user_id, u.email FROM Users u
                              JOIN User_Groups ug ON u.user_id = ug.user_id
                              WHERE ug.group_id = ?";
            $members_stmt = $conn->prepare($members_query);
            $members_stmt->bind_param("i", $project['group_id']);
            $members_stmt->execute();
            $members_result = $members_stmt->get_result();

            while ($member = $members_result->fetch_assoc()) {
                $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
                $notify_stmt = $conn->prepare($notify_query);
                $message = "Project '{$project['title']}' has been deleted.";
                $notify_stmt->bind_param("is", $member['user_id'], $message);
                $notify_stmt->execute();

                if ($member['email']) {
                    $subject = "Project Deleted";
                    $body = $message;
                    sendUserEmail($member['email'], $subject, $body);
                }
            }
        }

        // 1. Delete all tasks in this project
        $delete_tasks_query = "DELETE FROM Tasks WHERE project_id = ?";
        $delete_tasks_stmt = $conn->prepare($delete_tasks_query);
        $delete_tasks_stmt->bind_param("i", $project_id);
        $delete_tasks_stmt->execute();

        // 2. Delete the project
        $delete_query = "DELETE FROM Projects WHERE project_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $project_id);
        $delete_stmt->execute();

        if ($delete_stmt->affected_rows > 0) {
            // If successful, redirect back to projects page
            $_SESSION['success_message'] = 'Project deleted successfully.';
            header("Location: /TaskTrackr/public/projects.php");
            exit();
        } else {
            // If delete fails
            $_SESSION['error_message'] = 'Error deleting project.';
            header("Location: /TaskTrackr/public/projects.php");
            exit();
        }
    } else {
        // If project doesn't belong to the user
        $_SESSION['error_message'] = 'You are not authorized to delete this project.';
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    }
} else {
    // If no project_id is provided
    $_SESSION['error_message'] = 'Invalid project ID.';
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}
?>
