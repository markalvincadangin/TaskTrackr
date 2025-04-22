<?php
session_start(); // required for using $_SESSION
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
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
    $query = "INSERT INTO Users (name, email, password) VALUES (?, ?, ?)";
    $stmt  = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $hashedPassword);

        if ($stmt->execute()) {
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
