<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // If user exists
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        // Note: Assuming password is stored as a hash in the database
        // If you are using plain text passwords, use $user['password'] === $password instead
        // if (password_verify($password, $user['password'])) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['firstname'] = $user['first_name'];
            $_SESSION['lastname'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];

            // Fetch user settings
            $settings_query = "SELECT theme FROM User_Settings WHERE user_id = ?";
            $settings_stmt = $conn->prepare($settings_query);
            $settings_stmt->bind_param("i", $user['user_id']);
            $settings_stmt->execute();
            $settings_result = $settings_stmt->get_result();
            if ($settings = $settings_result->fetch_assoc()) {
                $_SESSION['theme'] = $settings['theme'];
            } else {
                $_SESSION['theme'] = 'light';
            }

            header("Location: ../public/dashboard.php");
            exit();
        } else {
            // If password is incorrect
            $_SESSION['error_message'] = 'Incorrect password.';
            header("Location: ../public/login.php");
            exit();
        }
    } else {
        // If no user found with this email
        $_SESSION['error_message'] = 'No user found with this email.';
        header("Location: ../public/login.php");
        exit();
    }

    $stmt->close();
} else {
    // If the request method is not POST, redirect to login page
    $_SESSION['error_message'] = 'Invalid request method.';
    header("Location: ../public/login.php");
    exit();
}
?>
