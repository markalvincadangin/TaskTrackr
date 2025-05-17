<?php
session_start(); // required for using $_SESSION
include '../config/db.php';
include_once('../includes/email_sender.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['first_name']);
    $lastname = trim($_POST['last_name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $_SESSION['error_message'] = 'Passwords do not match.';
        header("Location: ../public/register.php");
        exit();
    }

    // Check if email already exists
    $query = "SELECT * FROM Users WHERE email = ?";
    $stmt  = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = 'Email already registered.';
        header("Location: ../public/register.php");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user
    $query = "INSERT INTO Users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
    $stmt  = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $firstname, $lastname, $email, $hashedPassword);

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;

            // After successful registration and before redirecting
            $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
            $notify_stmt = $conn->prepare($notify_query);
            $message = "Welcome to TaskTrackr! Your account has been created.";
            $notify_stmt->bind_param("is", $new_user_id, $message);
            $notify_stmt->execute();

            if ($email) {
                $subject = "Welcome to TaskTrackr!";
                $body = $message;
                sendUserEmail($email, $subject, $body);
            }

            $_SESSION['success_message'] = 'Registration successful. Please log in.';
            header("Location: ../public/login.php");
            exit();
        } else {
            $_SESSION['error_message'] = 'Registration failed. Please try again.';
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = 'Error preparing SQL statement.';
    }

    $conn->close();
    header("Location: ../public/register.php");
    exit();
}
?>
