<?php
session_start();
include('../config/db.php');
include_once('../includes/email_sender.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $profile_img = $_FILES['profile_img'];

    // Validate inputs
    if (empty($name) || empty($email)) {
        $_SESSION['error_message'] = "Name and email cannot be empty.";
        header("Location: ../public/profile.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: ../public/profile.php");
        exit();
    }

    // Handle profile picture upload 
    $profile_img_path = null;
    if (!empty($profile_img['name'])) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($profile_img['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            header("Location: ../public/profile.php");
            exit();
        }

        if ($profile_img['size'] > 2 * 1024 * 1024) { // 2MB limit
            $_SESSION['error_message'] = "File size exceeds the 2MB limit.";
            header("Location: ../public/profile.php");
            exit();
        }

        $upload_dir = '../uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        $profile_img_path = $upload_dir . uniqid() . '.' . $file_extension;

        if (!move_uploaded_file($profile_img['tmp_name'], $profile_img_path)) {
            $_SESSION['error_message'] = "Failed to upload profile picture.";
            header("Location: ../public/profile.php");
            exit();
        }

        // Delete the old profile picture if it exists
        $old_picture_query = "SELECT profile_picture FROM Users WHERE user_id = ?";
        $stmt = $conn->prepare($old_picture_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
            unlink($user['profile_picture']); // Delete the old file
        }
    }

    // Update user information
    $query = "UPDATE Users SET name = ?, email = ?" . ($profile_img_path ? ", profile_picture = ?" : "") . " WHERE user_id = ?";
    $stmt = $conn->prepare($query);

    if ($profile_img_path) {
        $stmt->bind_param("sssi", $name, $email, $profile_img_path, $user_id);
    } else {
        $stmt->bind_param("ssi", $name, $email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully.";
        $_SESSION['name'] = $name; // to update the name in the session

        // Notify user (in-app)
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $message = "Your profile information was updated.";
        $notify_stmt->bind_param("is", $user_id, $message);
        $notify_stmt->execute();

        // Notify user (email)
        if ($email) {
            $subject = "Profile Updated";
            $body = $message;
            sendUserEmail($email, $subject, $body);
        }
    } else {
        $_SESSION['error_message'] = "Failed to update profile. Please try again.";
    }

    header("Location: ../public/profile.php");
    exit();
}
?>