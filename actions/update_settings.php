<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reminder_days_before = isset($_POST['reminder_days_before']) && is_numeric($_POST['reminder_days_before'])
        ? max(1, min(30, intval($_POST['reminder_days_before'])))
        : 1;

    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'light';

    // Check if the user already has settings
    $check_query = "SELECT user_id FROM User_Settings WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing settings
        $update_query = "UPDATE User_Settings SET reminder_days_before = ?, theme = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("isi", $reminder_days_before, $theme, $user_id);
    } else {
        // Insert new settings
        $insert_query = "INSERT INTO User_Settings (user_id, reminder_days_before, theme) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $user_id, $reminder_days_before, $theme);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Settings updated successfully.";
        $_SESSION['theme'] = $theme;
    } else {
        $_SESSION['error_message'] = "Failed to update settings. Please try again.";
    }

    header("Location: ../public/settings.php");
    exit();
}
?>