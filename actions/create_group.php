<?php
include('../config/db.php');
include_once('../includes/email_sender.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    $member_emails = isset($_POST['member_emails']) ? $_POST['member_emails'] : [];

    // Validate the group name if duplicate
    $check_query = "SELECT * FROM Groups WHERE group_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $group_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['error_message'] = 'Group name already exists. Please choose a different name.';
        header("Location: ../public/groups.php");
        exit();
    }
    $check_stmt->close();

    // Validate emails and check if users exist (skip empty fields, skip creator)
    $valid_user_ids = [];
    $invalid_emails = [];
    $emails_seen = [];

    foreach ($member_emails as $email) {
        $email = trim($email);
        if ($email === '' || strtolower($email) === strtolower($_SESSION['email'])) continue; // skip blank or creator

        if (in_array($email, $emails_seen)) continue; // skip duplicates
        $emails_seen[] = $email;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_query = "SELECT user_id FROM Users WHERE email = ?";
            $email_stmt = $conn->prepare($email_query);
            $email_stmt->bind_param("s", $email);
            $email_stmt->execute();
            $email_result = $email_stmt->get_result();

            if ($email_result->num_rows > 0) {
                $user = $email_result->fetch_assoc();
                if ($user['user_id'] != $user_id) { // don't add creator twice
                    $valid_user_ids[] = $user['user_id'];
                }
            } else {
                $invalid_emails[] = $email;
            }
        } else {
            $invalid_emails[] = $email;
        }
    }

    // If there are invalid emails, do not create group
    if (count($invalid_emails) > 0) {
        $_SESSION['error_message'] = "The following email addresses are invalid or do not exist: " . implode(", ", $invalid_emails);
        header("Location: ../public/groups.php");
        exit();
    }

    // Require at least one valid member (not the creator)
    if (count($valid_user_ids) < 1) {
        $_SESSION['error_message'] = "A group must have at least one member in addition to the creator.";
        header("Location: ../public/groups.php");
        exit();
    }

    // Insert the group into the Groups table
    $insert_group_query = "INSERT INTO Groups (group_name, created_by) VALUES (?, ?)";
    $insert_group_stmt = $conn->prepare($insert_group_query);
    $insert_group_stmt->bind_param("si", $group_name, $user_id);
    $insert_group_stmt->execute();
    $group_id = $insert_group_stmt->insert_id;
    $insert_group_stmt->close();

    // Add the creator to the group
    $user_group_query = "INSERT INTO User_Groups (user_id, group_id) VALUES (?, ?)";
    $user_group_stmt = $conn->prepare($user_group_query);
    $user_group_stmt->bind_param("ii", $user_id, $group_id);
    $user_group_stmt->execute();

    // In-app notification for the creator
    $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
    $notify_stmt = $conn->prepare($notify_query);
    $creator_message = "You have created the group: " . htmlspecialchars($group_name);
    $notify_stmt->bind_param("is", $user_id, $creator_message);
    $notify_stmt->execute();

    // Add the valid members to the group
    foreach ($valid_user_ids as $member_id) {
        $user_group_stmt->bind_param("ii", $member_id, $group_id);
        $user_group_stmt->execute();

        // In-app notification for new group member
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $group_message = "You have been added to the group: " . htmlspecialchars($group_name);
        $notify_stmt->bind_param("is", $member_id, $group_message);
        $notify_stmt->execute();

        // For each $member_id added
        $email_query = "SELECT email FROM Users WHERE user_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $member_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = $email_result->fetch_assoc()['email'] ?? null;

        if ($user_email) {
            $subject = "Added to Group: $group_name";
            $body = "You have been added to the group: $group_name.";
            sendUserEmail($user_email, $subject, $body);
        }
    }
    $user_group_stmt->close();

    $_SESSION['success_message'] = "Group created successfully.";
    header("Location: ../public/groups.php");
    exit();
}
?>
