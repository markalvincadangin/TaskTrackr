<?php
// Include necessary files
include('../config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect if not logged in
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $category_id = $_POST['category'];
    $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

    // Validate input (ensure all fields are filled)
    if (empty($title) || empty($description) || empty($deadline) || empty($category_id)) {
        $_SESSION['error_message'] = 'All fields are required.';
        header("Location: /TaskTrackr/actions/edit_project.php?project_id=" . $project_id);
        exit();
    }

    // Prepare SQL query to update project details
    $query = "UPDATE Projects SET title = ?, description = ?, deadline = ?, category_id = ? WHERE project_id = ? AND created_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssiii", $title, $description, $deadline, $category_id, $project_id, $user_id);
    $result = $stmt->execute();

    if ($result) {
        // If update is successful
        $_SESSION['success_message'] = 'Project updated successfully.';
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    } else {
        // If there is an error updating
        $_SESSION['error_message'] = 'Error updating project: ' . $stmt->error;
        header("Location: /TaskTrackr/actions/edit_project.php?project_id=" . $project_id);
        exit();
    }
}
?>
