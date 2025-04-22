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
    $group_id = $_POST['group_id']; 
    $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID


    $group_id = empty($_POST['group_id']) ? null : $_POST['group_id']; // Set to NULL if not provided

    // Prepare SQL query to update project details
    $query = "UPDATE Projects SET title = ?, description = ?, deadline = ?, category_id = ?, group_id = ? WHERE project_id = ? AND created_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssiiii", $title, $description, $deadline, $category_id, $group_id, $project_id, $user_id);
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
