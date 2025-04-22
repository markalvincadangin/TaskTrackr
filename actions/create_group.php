<?php
// Include the database connection
include('../config/db.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    $member_emails = $_POST['member_emails'];

    // Validate the group name if duplicate
    $check_query = "SELECT * FROM Groups WHERE group_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $group_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Group name already exists, display an error
        $_SESSION['error_message'] = 'Group name already exists. Please choose a different name.';
        header("Location: ../public/groups.php");
        exit();
    }
    $check_stmt->close();

    // Insert the group into the Groups table
    $insert_group_query = "INSERT INTO Groups (group_name, created_by) VALUES (?, ?)";
    $insert_group_stmt = $conn->prepare($insert_group_query);
    $insert_group_stmt->bind_param("si", $group_name, $user_id);
    $insert_group_stmt->execute();

    // Get the newly created group's ID
    $group_id = $insert_group_stmt->insert_id;
    $insert_group_stmt->close();

    // Add the creator to the group
    $user_group_query = "INSERT INTO User_Groups (user_id, group_id) VALUES (?, ?)";
    $user_group_stmt = $conn->prepare($user_group_query);
    $user_group_stmt->bind_param("ii", $user_id, $group_id);
    $user_group_stmt->execute();
    $user_group_stmt->close();

    // Validate emails and check if users exist
    $valid_emails = [];
    $invalid_emails = [];
    $user_ids = [];

    foreach ($member_emails as $email) {
        // Trim and sanitize email input
        $email = trim($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check if user exists with this email
            $email_query = "SELECT user_id FROM Users WHERE email = ?";
            $email_stmt = $conn->prepare($email_query);
            $email_stmt->bind_param("s", $email);
            $email_stmt->execute();
            $email_result = $email_stmt->get_result();

            if ($email_result->num_rows > 0) {
                // User exists, add the user_id to the valid list
                $user = $email_result->fetch_assoc();
                $valid_emails[] = $email;
                $user_ids[] = $user['user_id'];
            } else {
                // User does not exist
                $invalid_emails[] = $email;
            }
        } else {
            // Invalid email format
            $invalid_emails[] = $email;
        }
    }

    // If there are invalid emails, display them
    if (count($invalid_emails) > 0) {
        $_SESSION['error_message'] = "The following email addresses are invalid or do not exist: " . implode(", ", $invalid_emails);
        header("Location: add_group_members.php?group_id=$group_id");
        exit();
    }

    // Add the valid members to the group
    $user_group_query = "INSERT INTO User_Groups (user_id, group_id) VALUES (?, ?)";
    $user_group_stmt = $conn->prepare($user_group_query);

    foreach ($user_ids as $member_id) {
        $user_group_stmt->bind_param("ii", $member_id, $group_id);
        $user_group_stmt->execute();
    }

    $_SESSION['success_message'] = "Members added successfully.";
    header("Location: ../public/groups.php");
    exit();
}
?>
