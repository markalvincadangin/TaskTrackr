<?php
// Include necessary files
include('../config/db.php');
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
        // If project belongs to the user, proceed to delete
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
