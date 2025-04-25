<?php
session_start();
include('../config/db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;

    // Check if the user already has settings
    $check_query = "SELECT user_id FROM User_Settings WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing settings
        $update_query = "UPDATE User_Settings SET dark_mode = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $dark_mode, $user_id);
    } else {
        // Insert new settings
        $insert_query = "INSERT INTO User_Settings (user_id, dark_mode) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $user_id, $dark_mode);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Appearance preferences updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update appearance preferences. Please try again.";
    }

    header("Location: ../public/settings.php");
    exit();
}
?>